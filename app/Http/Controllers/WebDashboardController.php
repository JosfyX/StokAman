<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockTransaction;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebDashboardController extends Controller
{
    public function index(Request $request)
    {
        $categories   = Category::all();
        $suppliers    = Supplier::all();
        $products     = Product::with('category')->get();
        $lowStockList = $products->filter(fn($p) => $p->is_low_stock);

        // Kartu Stok untuk produk yang dipilih (default produk pertama)
        $selectedProduct = $request->filled('product_id')
            ? Product::find($request->product_id)
            : $products->first();

        $stockCard = collect();
        if ($selectedProduct) {
            $balance = 0;
            $stockCard = $selectedProduct->transactions()
                ->with('batch')
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($tx) use (&$balance) {
                    $balance += ($tx->type === 'IN') ? $tx->qty : -$tx->qty;
                    $tx->running_balance = $balance;
                    return $tx;
                });
        }

        return view('dashboard', compact(
            'categories',
            'suppliers',
            'products',
            'lowStockList',
            'selectedProduct',
            'stockCard'
        ));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        Category::create($data);
        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function storeSupplier(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);
        Supplier::create($data);
        return back()->with('success', 'Pemasok berhasil ditambahkan.');
    }

    public function storeProduct(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'sku'         => 'required|string|unique:products,sku',
            'name'        => 'required|string|max:255',
            'min_stock'   => 'required|integer|min:0',
        ]);
        Product::create($data);
        return back()->with('success', 'Produk berhasil ditambahkan (stok awal 0).');
    }

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

        DB::transaction(function () use ($data, $receivedAt) {
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
                'note'             => $data['note'] ?? 'Barang masuk via Web',
                'transaction_date' => $receivedAt,
            ]);
        });

        return back()->with('success', 'Transaksi barang masuk berhasil dicatat.');
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
                    throw new \DomainException("Stok tidak mencukupi! Tersedia: {$totalAvailable}, diminta: {$data['qty']}.");
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
                        'note'             => $data['note'] ?? 'Barang keluar via Web (FIFO)',
                        'transaction_date' => now(),
                    ]);

                    $needed -= $deduct;
                }
            });

            return back()->with('success', 'Barang keluar berhasil diproses dengan metode FIFO.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
