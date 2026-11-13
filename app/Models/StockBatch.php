<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id',
        'supplier_id',
        'batch_code',
        'initial_qty',
        'remaining_qty',
        'received_at',
    ];
}
