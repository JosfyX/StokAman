<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StokAmanTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_200(): void
    {
        $this->getJson('/health')
            ->assertStatus(200)
            ->assertJson(['status' => 'healthy']);
    }

    public function test_product_sku_must_be_unique(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        Product::create(['category_id' => $category->id, 'sku' => 'SKU-001', 'name' => 'Kabel HDMI', 'min_stock' => 5]);

        $this->postJson('/api/products', [
            'category_id' => $category->id,
            'sku'         => 'SKU-001',
            'name'        => 'Kabel LAN',
            'min_stock'   => 5,
        ])->assertStatus(422)->assertJsonValidationErrors(['sku']);
    }

    public function test_stock_cannot_be_set_manually_via_product_endpoint(): void
    {
        $category = Category::create(['name' => 'ATK']);

        $response = $this->postJson('/api/products', [
            'category_id'   => $category->id,
            'sku'           => 'SKU-002',
            'name'          => 'Kertas A4',
            'min_stock'     => 10,
            'current_stock' => 999,
        ])->assertStatus(201);

        $this->assertEquals(0, $response->json('data.current_stock'));
    }

    public function test_stock_out_fails_when_insufficient_stock(): void
    {
        $category = Category::create(['name' => 'Minuman']);
        $supplier = Supplier::create(['name' => 'PT Sumber Segar']);
        $product  = Product::create(['category_id' => $category->id, 'sku' => 'SKU-003', 'name' => 'Air Mineral', 'min_stock' => 5]);

        $this->postJson('/api/transactions/in', [
            'product_id'  => $product->id,
            'supplier_id' => $supplier->id,
            'qty'         => 10,
        ])->assertStatus(201);

        $this->postJson('/api/transactions/out', [
            'product_id' => $product->id,
            'qty'        => 15,
        ])->assertStatus(422);
    }

    public function test_stock_out_deducts_batches_using_fifo_order(): void
    {
        $category = Category::create(['name' => 'Sembako']);
        $supplier = Supplier::create(['name' => 'UD Makmur']);
        $product  = Product::create(['category_id' => $category->id, 'sku' => 'SKU-004', 'name' => 'Beras 5kg', 'min_stock' => 5]);

        $this->postJson('/api/transactions/in', [
            'product_id'  => $product->id,
            'supplier_id' => $supplier->id,
            'qty'         => 10,
            'received_at' => '2026-11-01 08:00:00',
        ]);

        $this->postJson('/api/transactions/in', [
            'product_id'  => $product->id,
            'supplier_id' => $supplier->id,
            'qty'         => 10,
            'received_at' => '2026-11-05 08:00:00',
        ]);

        $this->postJson('/api/transactions/out', [
            'product_id' => $product->id,
            'qty'        => 12,
        ])->assertStatus(200);

        $batches = StockBatch::where('product_id', $product->id)->orderBy('received_at', 'asc')->get();

        $this->assertEquals(0, $batches[0]->remaining_qty);
        $this->assertEquals(8, $batches[1]->remaining_qty);
    }
}
