<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function show(Sale $sale)
    {
        $sale->load('items');

        return response()->json([
            'ok' => true,
            'sale' => [
                'id' => $sale->id,
                'receipt_no' => $sale->receipt_no,
                'sold_at' => $sale->sold_at->format('d/m/Y H:i'),
                'total' => (float) $sale->total,
                'discount' => (float) $sale->discount,
                'is_cancelled' => $sale->isCancelled(),
                'items' => $sale->items->map(fn (SaleItem $it) => [
                    'id' => $it->id,
                    'name' => $it->product_name,
                    'qty' => (float) $it->qty,
                    'price' => (float) $it->unit_price,
                    'total' => $it->lineTotal(),
                ]),
            ],
        ]);
    }

    public function cancel(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'cancel_reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($sale, $data) {
                // ล็อกแถวบิลกันกดยกเลิกซ้ำพร้อมกันจนคืนสต๊อกซ้ำสองรอบ
                $locked = Sale::lockForUpdate()->findOrFail($sale->id);

                if ($locked->isCancelled()) {
                    throw new \RuntimeException('บิลนี้ถูกยกเลิกไปแล้ว');
                }

                $locked->load('items');

                foreach ($locked->items as $item) {
                    $this->returnStock($locked, $item, (float) $item->qty, 'ยกเลิกบิล '.$locked->receipt_no);
                }

                $locked->update([
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancel_reason' => $data['cancel_reason'] ?? null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'ยกเลิกบิล '.$sale->receipt_no.' แล้ว และคืนสต๊อกเรียบร้อย');
    }

    public function removeItem(Request $request, Sale $sale, SaleItem $item)
    {
        $data = $request->validate([
            'qty' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $reason = $data['reason'] ?? 'ลูกค้าขอยกเลิกรายการ';

        try {
            $result = DB::transaction(function () use ($sale, $item, $data, $reason) {
                // ล็อกทั้งบิลและรายการกันกดยกเลิกซ้ำพร้อมกันจนคืนสต๊อก/หักยอดซ้ำ
                $lockedSale = Sale::lockForUpdate()->findOrFail($sale->id);

                if ($lockedSale->isCancelled()) {
                    throw new \RuntimeException('บิลนี้ถูกยกเลิกไปแล้ว');
                }

                $lockedItem = SaleItem::lockForUpdate()->findOrFail($item->id);

                if ($lockedItem->sale_id !== $lockedSale->id) {
                    throw new \RuntimeException('รายการไม่ตรงกับบิล');
                }

                $voidQty = isset($data['qty']) ? (float) $data['qty'] : (float) $lockedItem->qty;
                if ($voidQty > (float) $lockedItem->qty) {
                    throw new \RuntimeException('จำนวนที่ยกเลิกเกินในบิล');
                }

                $this->returnStock(
                    $lockedSale,
                    $lockedItem,
                    $voidQty,
                    'ยกเลิกรายการบิล '.$lockedSale->receipt_no.($reason ? ' · '.$reason : '')
                );

                $remaining = (float) $lockedItem->qty - $voidQty;
                if ($remaining <= 0.0001) {
                    $lockedItem->delete();
                } else {
                    $lockedItem->update(['qty' => $remaining]);
                }

                $lockedSale->refresh()->load('items');

                if ($lockedSale->items->isEmpty()) {
                    $lockedSale->update([
                        'subtotal' => 0,
                        'discount' => 0,
                        'total' => 0,
                        'paid' => 0,
                        'change_amount' => 0,
                        'net_amount' => 0,
                        'vat_amount' => 0,
                        'cancelled_at' => now(),
                        'cancelled_by' => auth()->id(),
                        'cancel_reason' => $reason ?: 'ยกเลิกทุกรายการในบิล',
                    ]);

                    return ['cancelled_bill' => true];
                }

                $lockedSale->recalculateTotals();

                return ['cancelled_bill' => false];
            });
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $msg = $result['cancelled_bill']
            ? 'ยกเลิกรายการครบแล้ว — บิลถูกยกเลิกทั้งใบ และคืนสต๊อกแล้ว'
            : 'ยกเลิกรายการแล้ว คืนสต๊อกและคำนวณยอดบิลใหม่แล้ว';

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $msg, 'cancelled_bill' => $result['cancelled_bill']]);
        }

        return back()->with('success', $msg);
    }

    private function returnStock(Sale $sale, SaleItem $item, float $qty, string $note): void
    {
        if (! $item->product_id || $qty <= 0) {
            return;
        }

        $product = Product::lockForUpdate()->find($item->product_id);
        if (! $product || ! $product->tracksStock()) {
            return;
        }

        $product->increment('stock', $qty);

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'qty' => $qty,
            'ref_sale_id' => $sale->id,
            'note' => $note,
        ]);
    }
}
