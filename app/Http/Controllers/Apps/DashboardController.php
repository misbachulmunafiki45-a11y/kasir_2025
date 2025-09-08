<?php

namespace App\Http\Controllers\Apps;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Profit;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        // inisialisasi array untuk chart
        $sales_date = [];
        $grand_total = [];
        $product = [];
        $total = [];

        // range 7 hari terakhir (termasuk hari ini)
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // chart sales 7 hari terakhir
        $chart_sales_week = DB::table('transactions')
            ->addSelect(DB::raw('DATE(created_at) as date, SUM(grand_total) as grand_total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // mapping hasil query menjadi ["Y-m-d" => total]
        $mapSales = $chart_sales_week->pluck('grand_total', 'date');

        // isi 7 hari (0 untuk hari tanpa transaksi)
        for ($i = 0; $i <= 6; $i++) {
            $date = Carbon::now()->subDays(6 - $i)->toDateString();
            $sales_date[] = $date;
            $grand_total[] = (int) ($mapSales[$date] ?? 0);
        }

        // hitung data hari ini dengan whereDate agar hanya tanggal hari ini yang dihitung
        $today = Carbon::today();
        $count_sales_today = Transaction::whereDate('created_at', $today)->count();
        $sum_sales_today = Transaction::whereDate('created_at', $today)->sum('grand_total');
        $sum_profits_today = Profit::whereDate('created_at', $today)->sum('total');

        // produk stok terendah (top 5)
        $products_limit_stock = Product::with('category')
            ->orderBy('stock', 'ASC')
            ->limit(5)
            ->get();

        // chart produk terlaris (top 5)
        $chart_best_products = DB::table('transaction_details')
            ->addSelect(DB::raw('products.title as title, SUM(transaction_details.qty) as total'))
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->groupBy('transaction_details.product_id', 'products.title')
            ->orderBy('total', 'DESC')
            ->limit(5)
            ->get();

        foreach ($chart_best_products as $data) {
            $product[] = $data->title;
            $total[] = (int) $data->total;
        }

        return Inertia::render('Apps/Dashboard/Index', [
            'sales_date'           => $sales_date,
            'grand_total'          => $grand_total,
            'count_sales_today'    => (int) $count_sales_today,
            'sum_sales_today'      => (int) $sum_sales_today,
            'sum_profits_today'    => (int) $sum_profits_today,
            'products_limit_stock' => $products_limit_stock,
            'product'              => $product,
            'total'                => $total
        ]);
    }
}
