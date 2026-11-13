<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockTransactionController extends Controller
{
    public function stockIn(Request $request)
    {
        $data = $request->validate([
            'product_id'  => 'required|exists:products,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'qty'         => 'required|integer|min:1',
            'received_at' => 'nullable|date',
            'note'        => 'nullable|string|max:255',
        ]);

        $receivedAt = $data['received_at'] ?? now();

        $batch = DB::transaction(function () use ($data, $receivedAt) {
            $batch = StockBatch::create([
                'product_id'    => $data['product_id'],
                'supplier_id'   => $data['supplier_id'],
                'batch_code'    => 'BCH-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'initial_qty'   => $data['qty'],
                'remaining_qty' => $data['qty'],
                'received_at'   => $receivedAt,
            ]);

            StockTransaction::create([
                'product_id'       => $data['product_id'],
                'batch_id'         => $batch->id,
                'type'             => 'IN',
                'qty'              => $data['qty'],
                'note'             => $data['note'] ?? 'Barang masuk',
                'transaction_date' => $receivedAt,
            ]);

            return $batch;
        });

        return response()->json(['message' => 'Stok masuk dicatat', 'data' => $batch], 201);
    }


    public function stockOut(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty'        => 'required|integer|min:1',
            'note'       => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $batches = StockBatch::where('product_id', $data['product_id'])
                    ->where('remaining_qty', '>', 0)
                    ->orderBy('received_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $totalAvailable = $batches->sum('remaining_qty');
                if ($totalAvailable < $data['qty']) {
                    throw new \DomainException("Stok tidak mencukupi. Tersedia: {$totalAvailable}");
                }

                $needed = $data['qty'];
                foreach ($batches as $batch) {
                    if ($needed <= 0) break;

                    $deduct = min($batch->remaining_qty, $needed);
                    $batch->decrement('remaining_qty', $deduct);

                    StockTransaction::create([
                        'product_id'       => $data['product_id'],
                        'batch_id'         => $batch->id,
                        'type'             => 'OUT',
                        'qty'              => $deduct,
                        'note'             => $data['note'] ?? 'Barang keluar',
                        'transaction_date' => now(),
                    ]);

                    $needed -= $deduct;
                }
            });

            return response()->json(['message' => 'Stok keluar berhasil diproses (FIFO)'], 200);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function stockCard(Product $product)
    {
        $transactions = $product->transactions()
            ->with('batch:id,batch_code,received_at')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = 0;
        $card = $transactions->map(function ($tx) use (&$runningBalance) {
            $runningBalance += ($tx->type === 'IN') ? $tx->qty : -$tx->qty;
            return [
                'id'               => $tx->id,
                'transaction_date' => $tx->transaction_date,
                'batch_code'       => $tx->batch->batch_code,
                'type'             => $tx->type,
                'qty'              => $tx->qty,
                'balance'          => $runningBalance,
                'note'             => $tx->note,
            ];
        });

        return response()->json([
            'product'       => $product,
            'current_stock' => $runningBalance,
            'history'       => $card,
        ]);
    }
}
