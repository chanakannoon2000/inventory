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
        if ($sale->isCancelled()) {
            return back()->with('error', 'บิลนี้ถูกยกเลิกไปแล้ว');
        }

        $data = $request->validate([
            'cancel_reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($sale, $data) {
            $sale->load('items');

            foreach ($sale->items as $item) {
                $this->returnStock($sale, $item, (float) $item->qty, 'ยกเลิกบิล '.$sale->receipt_no);
            }

            $sale->update([
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancel_reason' => $data['cancel_reason'] ?? null,
            ]);
        });

        return back()->with('success', 'ยกเลิกบิล '.$sale->receipt_no.' แล้ว และคืนสต๊อกเรียบร้อย');
    }

    public function removeItem(Request $request, Sale $sale, SaleItem $item)
    {
        if ($sale->isCancelled()) {
            return response()->json(['ok' => false, 'message' => 'บิลนี้ถูกยกเลิกไปแล้ว'], 422);
        }

        if ($item->sale_id !== $sale->id) {
            return response()->json(['ok' => false, 'message' => 'รายการไม่ตรงกับบิล'], 422);
        }

        $data = $request->validate([
            'qty' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $voidQty = isset($data['qty']) ? (float) $data['qty'] : (float) $item->qty;
        if ($voidQty > (float) $item->qty) {
            return response()->json(['ok' => false, 'message' => 'จำนวนที่ยกเลิกเกินในบิล'], 422);
        }

        $reason = $data['reason'] ?? 'ลูกค้าขอยกเลิกรายการ';

        $result = DB::transaction(function () use ($sale, $item, $voidQty, $reason) {
            $this->returnStock(
                $sale,
                $item,
                $voidQty,
                'ยกเลิกรายการบิล '.$sale->receipt_no.($reason ? ' · '.$reason : '')
            );

            $remaining = (float) $item->qty - $voidQty;
            if ($remaining <= 0.0001) {
                $item->delete();
            } else {
                $item->update(['qty' => $remaining]);
            }

            $sale->refresh()->load('items');

            if ($sale->items->isEmpty()) {
                $sale->update([
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

            $sale->recalculateTotals();

            return ['cancelled_bill' => false];
        });

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
        if (! $product) {
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
