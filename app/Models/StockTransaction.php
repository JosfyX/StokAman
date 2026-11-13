<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    protected $fillable = [
        'product_id',
        'batch_id',
        'type',
        'qty',
        'note',
        'transaction_date',
    ];

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }
}
