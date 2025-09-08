<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class StocksExport implements FromView
{
    protected $start_date;
    protected $end_date;
    protected $q;

    public function __construct($start_date = null, $end_date = null, $q = null)
    {
        $this->start_date = $start_date;
        $this->end_date   = $end_date;
        $this->q          = $q;
    }

    public function view(): View
    {
        $products = Product::with(['category', 'latestStockEntry.user'])
            ->when($this->q, function($query) {
                $query->where('title', 'like', '%'.$this->q.'%');
            })
            ->when($this->start_date && $this->end_date, function($query) {
                $query->whereHas('stockEntries', function($q) {
                    $q->whereDate('created_at', '>=', $this->start_date)
                      ->whereDate('created_at', '<=', $this->end_date);
                });
            })
            ->orderBy('title')
            ->get();

        return view('exports.stocks', [
            'products' => $products,
        ]);
    }
}