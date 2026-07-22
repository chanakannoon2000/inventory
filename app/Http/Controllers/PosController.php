<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Support\LineNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $soldQty = SaleItem::query()
            ->select('sale_items.product_id', DB::raw('SUM(sale_items.qty) as sold_qty'))
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.cancelled_at')
            ->whereNotNull('sale_items.product_id')
            ->groupBy('sale_items.product_id');

        $query = Product::with(['category', 'unit', 'productGroup'])
            ->leftJoinSub($soldQty, 'sales_rank', function ($join) {
                $join->on('products.id', '=', 'sales_rank.product_id');
            })
            ->select('products.*', DB::raw('COALESCE(sales_rank.sold_qty, 0) as sold_qty'))
            ->orderByDesc('sold_qty')
            ->orderBy('products.name');

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('products.name', 'like', "%{$keyword}%")
                    ->orWhere('products.barcode', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }

        $products = $query->get();
        $categories = Category::orderBy('name')->get();
        $settings = Setting::current();
        $paymentAccounts = PaymentAccount::enabled()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (PaymentAccount $a) => [
                'id' => $a->id,
                'label' => $a->displayTitle(),
                'type' => $a->type,
                'promptpay_id' => $a->promptpay_id,
                'bank_name' => $a->bank_name,
                'bank_account_name' => $a->bank_account_name,
                'bank_account_no' => $a->bank_account_no,
                'is_default' => (bool) $a->is_default,
            ])
            ->values();
        $defaultPayment = PaymentAccount::defaultAccount();

        // จัดกลุ่มสินค้าตาม Master กลุ่มสินค้า (เช่น ท่อหลายไซส์)
        $posCards = collect();
        $grouped = $products->filter(fn (Product $p) => (bool) $p->product_group_id)->groupBy('product_group_id');
        $ungrouped = $products->reject(fn (Product $p) => (bool) $p->product_group_id);

        foreach ($grouped as $groupId => $items) {
            $sorted = $items->sortBy(fn (Product $p) => $p->sizeSortValue())->values();
            $inStock = $sorted->filter(fn (Product $p) => $p->isService() || (float) $p->stock > 0);
            $first = $sorted->first(fn (Product $p) => $p->imageSrc()) ?: $sorted->first();
            $groupModel = $first?->productGroup;
            $groupName = $groupModel?->name ?: ('กลุ่ม #'.$groupId);
            $minPrice = (float) $sorted->min('sell_price');
            $maxPrice = (float) $sorted->max('sell_price');
            $totalStock = (float) $sorted->sum('stock');
            $low = $sorted->contains(fn (Product $p) => $p->isLowStock());
            $groupImage = $groupModel?->imageSrc() ?: ($first?->imageSrc());

            $posCards->push([
                'type' => 'group',
                'group_key' => 'g'.(string) $groupId,
                'group_name' => $groupName,
                'category_id' => $first?->category_id,
                'image' => $groupImage,
                'icon' => $first?->placeholderIcon(),
                'color' => $first?->placeholderColor(),
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'stock' => $totalStock,
                'size_count' => $sorted->count(),
                'available_count' => $inStock->count(),
                'low' => $low,
                'soldout' => $inStock->isEmpty(),
                'sold_qty' => (float) $sorted->max('sold_qty'),
                'variants' => $sorted->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->displayName(),
                    'barcode' => $p->barcode,
                    'price' => (float) $p->sell_price,
                    'stock' => (float) $p->stock,
                    'unit' => $p->unit?->name,
                    'size' => $p->size_label ?: $p->name,
                    'image' => $p->imageSrc(),
                    'icon' => $p->placeholderIcon(),
                    'color' => $p->placeholderColor(),
                    'low' => $p->isLowStock(),
                    'item_type' => $p->type ?: Product::TYPE_PRODUCT,
                    'is_service' => $p->isService(),
                ])->values()->all(),
            ]);
        }

        foreach ($ungrouped as $p) {
            $posCards->push([
                'type' => 'single',
                'product' => $p,
                'sold_qty' => (float) ($p->sold_qty ?? 0),
            ]);
        }

        $posCards = $posCards->sortByDesc('sold_qty')->values();

        return view('pos.index', compact(
            'products',
            'posCards',
            'categories',
            'settings',
            'paymentAccounts',
            'defaultPayment'
        ));
    }

    public function findByBarcode(Request $request)
    {
        $barcode = trim((string) $request->input('barcode'));
        $product = Product::with(['category', 'unit'])->where('barcode', $barcode)->first();

        if (! $product) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบสินค้าบาร์โค้ด: '.$barcode], 404);
        }

        return response()->json([
            'ok' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->displayName(),
                'barcode' => $product->barcode,
                'sell_price' => (float) $product->sell_price,
                'stock' => (float) $product->stock,
                'unit' => $product->unit?->name,
                'image' => $product->imageSrc(),
                'icon' => $product->placeholderIcon(),
                'color' => $product->placeholderColor(),
                'is_low' => $product->isLowStock(),
                'item_type' => $product->type ?: Product::TYPE_PRODUCT,
                'is_service' => $product->isService(),
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'paid' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,promptpay,bank',
            'payment_account_id' => 'nullable|exists:payment_accounts,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_tax_id' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:1000',
            'customer_phone' => 'nullable|string|max:50',
        ]);

        $settings = Setting::current();
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $vatRate = (float) $settings->tax_rate;
        $payAccount = ! empty($data['payment_account_id'])
            ? PaymentAccount::find($data['payment_account_id'])
            : PaymentAccount::defaultAccount();

        if (in_array($paymentMethod, ['promptpay', 'bank'], true) && (! $payAccount || ! $payAccount->is_enabled)) {
            return response()->json(['ok' => false, 'message' => 'ยังไม่มีบัญชีรับเงินที่เปิดใช้'], 422);
        }

        try {
            $sale = DB::transaction(function () use ($data, $settings, $paymentMethod, $vatRate) {
                $lines = [];
                $subtotal = 0;

                foreach ($data['items'] as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    $qty = (float) $item['qty'];

                    if ($product->tracksStock() && $qty > (float) $product->stock) {
                        throw new \RuntimeException('สต๊อกไม่พอ: '.$product->name);
                    }

                    $lineTotal = (float) $product->sell_price * $qty;
                    $subtotal += $lineTotal;
                    $lines[] = compact('product', 'qty', 'lineTotal');
                }

                $discount = (float) ($data['discount'] ?? 0);
                $total = max(0, $subtotal - $discount);
                $paid = in_array($paymentMethod, ['promptpay', 'bank'], true) ? $total : (float) $data['paid'];

                if ($paid < $total) {
                    throw new \RuntimeException('จำนวนเงินที่รับมาไม่พอ');
                }

                $vatParts = Sale::splitVat($total, $vatRate);
                $receiptNo = 'INV'.now()->format('Ymd').'-'.str_pad((string) $settings->receipt_running, 4, '0', STR_PAD_LEFT);
                $settings->increment('receipt_running');

                $sale = Sale::create([
                    'receipt_no' => $receiptNo,
                    'user_id' => auth()->id(),
                    'sold_at' => now(),
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'paid' => $paid,
                    'change_amount' => $paid - $total,
                    'payment_method' => $paymentMethod,
                    'customer_name' => $data['customer_name'] ?? null,
                    'customer_tax_id' => $data['customer_tax_id'] ?? null,
                    'customer_address' => $data['customer_address'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'vat_rate' => $vatRate,
                    'net_amount' => $vatParts['net'],
                    'vat_amount' => $vatParts['vat'],
                ]);

                foreach ($lines as $line) {
                    /** @var Product $product */
                    $product = $line['product'];
                    $qty = $line['qty'];

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'qty' => $qty,
                        'unit_price' => $product->sell_price,
                        'cost_price' => $product->cost_price,
                    ]);

                    if ($product->tracksStock()) {
                        $product->decrement('stock', $qty);

                        StockMovement::create([
                            'product_id' => $product->id,
                            'type' => 'OUT',
                            'qty' => $qty,
                            'ref_sale_id' => $sale->id,
                            'note' => 'ขายหน้าร้าน '.$sale->receipt_no,
                        ]);
                    }
                }

                return $sale->load('items');
            });
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        try {
            LineNotifier::notifyPayment($sale, $settings);
        } catch (\Throwable $e) {
            // ไม่ให้แจ้งเตือน LINE ทำให้การขายล้ม
        }

        $promptpayId = $payAccount?->promptpay_id
            ?: ($settings->promptpay_id ?: null);

        return response()->json([
            'ok' => true,
            'sale' => [
                'id' => $sale->id,
                'receipt_no' => $sale->receipt_no,
                'sold_at' => $sale->sold_at->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'subtotal' => (float) $sale->subtotal,
                'discount' => (float) $sale->discount,
                'total' => (float) $sale->total,
                'paid' => (float) $sale->paid,
                'change' => (float) $sale->change_amount,
                'payment_method' => $sale->payment_method,
                'payment_account' => $payAccount ? [
                    'label' => $payAccount->displayTitle(),
                    'type' => $payAccount->type,
                    'promptpay_id' => $payAccount->promptpay_id,
                    'bank_name' => $payAccount->bank_name,
                    'bank_account_name' => $payAccount->bank_account_name,
                    'bank_account_no' => $payAccount->bank_account_no,
                ] : null,
                'net_amount' => (float) $sale->net_amount,
                'vat_amount' => (float) $sale->vat_amount,
                'vat_rate' => (float) $sale->vat_rate,
                'shop_name' => $settings->shop_name,
                'promptpay_id' => $promptpayId,
                'invoice_url' => route('invoices.tax', $sale),
                'receipt_url' => url('/r/'.$sale->id),
                'items' => $sale->items->map(fn ($it) => [
                    'name' => $it->product_name,
                    'qty' => (float) $it->qty,
                    'price' => (float) $it->unit_price,
                    'total' => $it->lineTotal(),
                ]),
                'qr' => url('/r/'.$sale->id),
            ],
        ]);
    }
}
