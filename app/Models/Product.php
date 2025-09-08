<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'category_id',
        'image',
        'barcode',
        'title',
        'description',
        'buy_price',
        'sell_price',
        'stock'
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // latest stock entry relation
    public function latestStockEntry()
    {
        return $this->hasOne(StockEntry::class)->latestOfMany();
    }

    // all stock entries relation (used for date filtering in reports)
    public function stockEntries()
    {
        return $this->hasMany(StockEntry::class);
    }

    /**
    * image
    *
    * @return Attribute
    */
    protected function image(): Attribute
    {
    return Attribute::make(
    get: fn ($value) => url('/storage/products/' . $value),
    );
    }
}
