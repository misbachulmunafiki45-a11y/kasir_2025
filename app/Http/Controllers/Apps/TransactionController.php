<?php

namespace App\Http\Controllers\Apps;

use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
 
class TransactionController extends Controller
{
    // middleware permission dipindahkan ke routes agar kompatibel dengan IDE analyzer

    /**
     * index
     *
     * @return void
     */
    public function index(): InertiaResponse
    {
        //get cart
        $carts = Cart::with('product')->where('cashier_id', Auth::id())->latest()->get();

        //get all customers
        $customers = Customer::latest()->get();

        return Inertia::render('Apps/Transactions/Index', [
            'carts'         => $carts,
            'carts_total'   => $carts->sum('price'),
            'customers'     => $customers
        ]);
    }

    /**
     * searchProduct
     *
     * @param  mixed $request
     * @return void
     */
    public function searchProduct(Request $request): JsonResponse
    {
        //find product by barcode
        $product = Product::where('barcode', $request->barcode)->first();

        if($product) {
            return response()->json([
                'success' => true,
                'data'    => $product
            ]);
        }

        return response()->json([
            'success' => false,
            'data'    => null
        ]);
    }

    /**
     * addToCart
     *
     * @param  mixed $request
     * @return void
     */
    public function addToCart(Request $request): RedirectResponse
    {
        //check stock product
        if(Product::whereId($request->product_id)->first()->stock < $request->qty) {

            //redirect
            return redirect()->back()->with('error', 'Out of Stock Product!.');
        }

        //check cart
        $cart = Cart::with('product')
                ->where('product_id', $request->product_id)
                ->where('cashier_id', Auth::id())
                ->first();

        if($cart) {

            //increment qty
            $cart->increment('qty', $request->qty);

            //sum price * quantity
            $cart->price  = $cart->product->sell_price * $cart->qty;

            $cart->save();

        } else {

            //insert cart
            Cart::create([
                'cashier_id'    => Auth::id(),
                'product_id'    => $request->product_id,
                'qty'           => $request->qty,
                'price'         => $request->sell_price * $request->qty,
            ]);

        }

        return redirect()->route('apps.transactions.index')->with('success', 'Product Added Successfully!.');
    }

    /**
     * destroyCart
     *
     * @param  mixed $request
     * @return void
     */
    public function destroyCart(Request $request): RedirectResponse
    {
        //find cart by ID
        $cart = Cart::with('product')
            ->whereId($request->cart_id)
            ->first();

        //delete cart
        $cart->delete();

        return redirect()->back()->with('success', 'Product Removed Successfully!.');
    }

    /**
     * store
     *
     * @param  mixed $request
     * @return void
     */
    public function store(Request $request): JsonResponse
    {
        /**
        * algorithm generate no invoice
        */
        $length = 10;
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= rand(0, 1) ? rand(0, 9) : chr(rand(ord('a'), ord('z')));
        }

        //generate no invoice
        $invoice = 'TRX-'.Str::upper($random);

        // tentukan customer_id: utamakan input manual jika ada
        $customerId = null;
        $manualName = trim((string) $request->input('manual_customer_name', ''));
        if ($manualName !== '') {
            $existing = Customer::where('name', $manualName)->first();
            if ($existing) {
                $customerId = $existing->id;
            } else {
                // buat customer baru minimal dengan nama saja
                $newCustomer = Customer::create([
                    'name' => $manualName,
                    'no_telp' => '-',
                    'address' => '-',
                ]);
                $customerId = $newCustomer->id;
            }
        } elseif ($request->filled('customer_id')) {
            $customerId = $request->customer_id;
        }

        //insert transaction
        $transaction = Transaction::create([
            'cashier_id'    => Auth::id(),
            'customer_id'   => $customerId,
            'invoice'       => $invoice,
            'cash'          => $request->cash,
            'change'        => $request->change,
            'discount'      => $request->discount,
            'grand_total'   => $request->grand_total,
        ]);

        //get carts
        $carts = Cart::where('cashier_id', Auth::id())->get();

        //insert transaction detail
        foreach($carts as $cart) {

            //insert transaction detail
            $transaction->details()->create([
                'transaction_id'    => $transaction->id,
                'product_id'        => $cart->product_id,
                'qty'               => $cart->qty,
                'price'             => $cart->price,
            ]);

            //get price
            $total_buy_price  = $cart->product->buy_price * $cart->qty;
            $total_sell_price = $cart->product->sell_price * $cart->qty;

            //get profits
            $profits = $total_sell_price - $total_buy_price;

            //insert provits
            $transaction->profits()->create([
                'transaction_id'    => $transaction->id,
                'total'             => $profits,
            ]);

            //update stock product
            $product = Product::find($cart->product_id);
            $product->stock = $product->stock - $cart->qty;
            $product->save();

        }

        //delete carts
        Cart::where('cashier_id', Auth::id())->delete();

        return response()->json([
            'success' => true,
            'data'    => $transaction
        ]);
    }

    /**
     * print
     *
     * @param  mixed $request
     * @return void
     */
    public function print(Request $request): View
    {
        //get transaction
        $transaction = Transaction::with('details.product', 'cashier', 'customer')->where('invoice', $request->invoice)->firstOrFail();

        //return view
        return view('print.nota', compact('transaction'));
    }

    /**
     * searchInvoices
     * Autocomplete untuk mencari invoice berdasarkan nomor atau nama customer.
     */
    public function searchInvoices(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        // normalisasi input: hilangkan tanda kutip tunggal/ganda dan spasi berlebih
        $q = preg_replace('/["\']+/', '', $q);
        $date = trim($request->input('date', ''));

        $query = Transaction::query()
            ->with('customer')
            ->orderBy('created_at', 'DESC');

        // filter by search text (invoice or customer name)
        if ($q !== '') {
            $query->where(function($w) use ($q) {
                $w->where('invoice', 'LIKE', "%$q%")
                  ->orWhereHas('customer', function($c) use ($q) {
                      $c->where('name', 'LIKE', "%$q%");
                  });
            });
        }

        // if date provided (Y-m-d), filter one day
        if ($date !== '') {
            $query->whereDate('created_at', $date);
        }

        $query->limit(50);

        $rows = $query->get()->map(function($t) {
            return [
                'invoice'     => $t->invoice,
                'customer'    => optional($t->customer)->name,
                'grand_total' => (int) $t->grand_total,
                'date'        => $t->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $rows,
        ]);
    }
    /**
     * searchProducts
     *
     * @param  mixed $request
     * @return void
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if ($q === '') {
            return response()->json([
                'success' => true,
                'data'    => []
            ]);
        }

        $products = Product::query()
            ->select(['id', 'title', 'barcode', 'sell_price', 'stock'])
            ->where(function($w) use ($q) {
                $w->where('title', 'LIKE', "%$q%")
                  ->orWhere('barcode', 'LIKE', "%$q%");
            })
            ->orderBy('title')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $products
        ]);
    }
}
