<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'before_stock',
        'quantity',
        'after_stock',
        // added fields
        'user_id',
        'note',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // add relation to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
