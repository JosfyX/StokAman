<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'sku', 
        'name', 
        'min_stock'
    ];
    protected $appends = [
        'current_stock', 
        'is_low_stock'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function batches()
    {
        return $this->hasMany(StockBatch::class);
    }
    public function transactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function getCurrentStockAttribute(): int
    {
        return (int) $this->batches()->sum('remaining_qty');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }
}
