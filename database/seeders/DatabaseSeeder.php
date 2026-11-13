<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockTransaction;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $sembako = Category::create(['name' => 'Sembako']);
        $minuman = Category::create(['name' => 'Minuman']);

        $supplierA = Supplier::create([
            'name'    => 'UD Makmur Jaya',
            'phone'   => '081234567890',
            'address' => 'Jl. Rungkut Industri No. 12, Surabaya',
        ]);
        $supplierB = Supplier::create([
            'name'    => 'PT Sumber Segar',
            'phone'   => '081987654321',
            'address' => 'Jl. Ahmad Yani No. 45, Surabaya',
        ]);

        $beras = Product::create([
            'category_id' => $sembako->id,
            'sku'         => 'SMB-001',
            'name'        => 'Beras Premium 5kg',
            'min_stock'   => 10,
        ]);

        $airMineral = Product::create([
            'category_id' => $minuman->id,
            'sku'         => 'MNM-001',
            'name'        => 'Air Mineral 600ml (Dus)',
            'min_stock'   => 15,
        ]);

        $batch1 = StockBatch::create([
            'product_id'    => $beras->id,
            'supplier_id'   => $supplierA->id,
            'batch_code'    => 'BCH-20261101-AAA1',
            'initial_qty'   => 15,
            'remaining_qty' => 15,
            'received_at'   => '2026-11-01 09:00:00',
        ]);

        StockTransaction::create([
            'product_id'       => $beras->id,
            'batch_id'         => $batch1->id,
            'type'             => 'IN',
            'qty'              => 15,
            'note'             => 'Stok awal Batch 1',
            'transaction_date' => '2026-11-01 09:00:00',
        ]);

        $batch2 = StockBatch::create([
            'product_id'    => $beras->id,
            'supplier_id'   => $supplierA->id,
            'batch_code'    => 'BCH-20261105-BBB2',
            'initial_qty'   => 10,
            'remaining_qty' => 10,
            'received_at'   => '2026-11-05 14:00:00',
        ]);

        StockTransaction::create([
            'product_id'       => $beras->id,
            'batch_id'         => $batch2->id,
            'type'             => 'IN',
            'qty'              => 10,
            'note'             => 'Restock Batch 2',
            'transaction_date' => '2026-11-05 14:00:00',
        ]);

        $batch3 = StockBatch::create([
            'product_id'    => $airMineral->id,
            'supplier_id'   => $supplierB->id,
            'batch_code'    => 'BCH-20261102-CCC3',
            'initial_qty'   => 5,
            'remaining_qty' => 5,
            'received_at'   => '2026-11-02 10:00:00',
        ]);

        StockTransaction::create([
            'product_id'       => $airMineral->id,
            'batch_id'         => $batch3->id,
            'type'             => 'IN',
            'qty'              => 5,
            'note'             => 'Stok awal minuman',
            'transaction_date' => '2026-11-02 10:00:00',
        ]);
    }
}
