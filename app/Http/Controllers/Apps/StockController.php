<?php

namespace App\Http\Controllers\Apps;

use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Models\StockEntry;
use App\Exports\StocksExport; // added
use Maatwebsite\Excel\Facades\Excel; // added
use Barryvdh\DomPDF\Facade\Pdf; // added
use Illuminate\Support\Facades\Auth; // added to satisfy IDE on id()
use Symfony\Component\HttpFoundation\BinaryFileResponse; // explicit return types
use Illuminate\Http\Response; // for DomPDF download()

class StockController extends Controller implements HasMiddleware
{
    /**
     * middleware
     *
     * @return array
     */
    public static function middleware(): array
    {
        return [
            new Middleware(['permission:stocks.index'], only: ['index', 'filter', 'export', 'pdf']),
            new Middleware(['permission:stocks.update'], only: ['update', 'add']),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        //get products
        $products = Product::with(['category', 'latestStockEntry.user'])
            ->when(request()->q, function($query) {
                $query->where('title', 'like', '%'. request()->q . '%');
            })
            ->latest()
            ->paginate(10);
        
        //return inertia
        return Inertia::render('Apps/Stocks/Index', [
            'products' => $products
        ]);
    }

    /**
     * Filter list by date range (based on stock entries created_at)
     *
     * @return \Inertia\Response
     */
    public function filter(Request $request)
    {
        $start_date = $request->get('start_date');
        $end_date   = $request->get('end_date');
        $q          = $request->get('q');

        $products = Product::with(['category', 'latestStockEntry.user'])
            ->when($q, function($query) use ($q) {
                $query->where('title', 'like', '%'.$q.'%');
            })
            ->when($start_date && $end_date, function($query) use ($start_date, $end_date) {
                $query->whereHas('stockEntries', function($q) use ($start_date, $end_date) {
                    $q->whereDate('created_at', '>=', $start_date)
                      ->whereDate('created_at', '<=', $end_date);
                });
            })
            ->orderBy('title')
            ->paginate(10)
            ->appends(['start_date' => $start_date, 'end_date' => $end_date, 'q' => $q]);

        return Inertia::render('Apps/Stocks/Index', [
            'products'    => $products,
            'start_date'  => $start_date,
            'end_date'    => $end_date,
        ]);
    }

    /**
     * Update product stock
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Product $product)
    {
        //validate request
        $request->validate([
            'stock' => 'required|integer',
        ]);

        //update product stock
        $product->update([
            'stock' => $request->stock
        ]);

        //redirect
        return redirect()->route('apps.stocks.index');
    }

    /**
     * Add new stock quantity (increment) and record entry with timestamp
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function add(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:1000',
        ]);

        $before = (int) $product->stock;
        $quantity = (int) $validated['quantity'];
        $after = $before + $quantity;

        // record stock entry (created_at will store the date & time automatically)
        StockEntry::create([
            'product_id'   => $product->id,
            'user_id'      => Auth::id(),
            'before_stock' => $before,
            'quantity'     => $quantity,
            'after_stock'  => $after,
            'note'         => $validated['note'] ?? null,
        ]);

        // update product stock to new value
        $product->update(['stock' => $after]);

        return redirect()->route('apps.stocks.index');
    }

    /**
     * Export current stock list to Excel
     */
    public function export(Request $request): BinaryFileResponse
    {
        $q = $request->get('q');
        $start_date = $request->get('start_date');
        $end_date   = $request->get('end_date');
        $filename = 'stocks'.($q ? ' - '.str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $q) : '').($start_date && $end_date ? ' '.$start_date.' — '.$end_date : '').'.xlsx';
        return Excel::download(new StocksExport($start_date, $end_date, $q), $filename);
    }

    /**
     * Export current stock list to PDF
     */
    public function pdf(Request $request): Response
    {
        $q = $request->get('q');
        $start_date = $request->get('start_date');
        $end_date   = $request->get('end_date');
        $products = Product::with(['category', 'latestStockEntry.user'])
            ->when($q, function($query) use ($q) {
                $query->where('title', 'like', '%'.$q.'%');
            })
            ->when($start_date && $end_date, function($query) use ($start_date, $end_date) {
                $query->whereHas('stockEntries', function($q) use ($start_date, $end_date) {
                    $q->whereDate('created_at', '>=', $start_date)
                      ->whereDate('created_at', '<=', $end_date);
                });
            })
            ->orderBy('title')
            ->get();

        $pdf = Pdf::loadView('exports.stocks', compact('products'));
        $timestamp = date('Ymd_His');
        $filename = 'stocks'.($q ? ' - '.str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $q) : '').($start_date && $end_date ? ' '.$start_date.' — '.$end_date : '').' - '.$timestamp.'.pdf';
        $response = $pdf->download($filename);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }
}