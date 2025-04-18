<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\orderDetails;
use App\Models\Orders;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use RealRashid\SweetAlert\Facades\Alert;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $title = "Orders";
        $datas = Orders::orderBy('id', 'desc')->get();
        return view('pos.index', compact('title', 'datas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // select * from categories order by id desc
        $categories = Categories::orderBy('id', 'desc')->get();
        return view('pos.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ORD-100425001
        $qOrderCode = Orders::max('id');
        $qOrderCode++;
        $orderCode = "ORD" . date("dmY") . sprintf("%03d", $qOrderCode);
        $data = [
            'order_code' => $orderCode,
            'order_date' => date("Y-m-d"),
            'order_amount' => $request->grandtotal,
            'order_change' => 1,
            'order_status' => 1,
        ];

        $order = Orders::create($data);

        $qty = $request->qty;
        foreach ($qty as $key => $data) {
            orderDetails::create([
                'order_id' => $order->id,
                'product_id' => $request->product_id[$key],
                'qty'        => $request->qty[$key],
                'order_price' => $request->order_price[$key],
                'order_subtotal' => $request->order_subtotal[$key],
            ]);
        }
        toast('Data Added Successfully', 'success');
        return redirect()->to('product');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $edit = Products::find($id);
        $categories = Categories::orderBy('id', 'desc')->get();
        return view('product.edit', compact('edit', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $data = [
            'category_id' => $request->category_id,
            'product_name' => $request->product_name,
            'product_price' => $request->product_price,
            'product_description' => $request->product_description,
            'is_active' => $request->is_active,
        ];

        $product = Products::find($id);
        if ($request->hasFile('product_photo')) {
            // jika gambar sudah ada dan mau diubah maka gambar lama kita hapus di ganti oleh gambar baru
            if ($product->product_photo) {
                File::delete(public_path('storage/' . $product->photo));
            }

            $photo = $request->file('product_photo')->store('product', 'public');
            $data['product_photo'] = $photo;
        }

        $product->update($data);
        // Alert::success('Success', 'Edit Succesfully');
        toast('Data Changed Successfully', 'success');
        return redirect()->to('product');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Products::find($id);
        File::delete(public_path('storage/' . $product->product_photo));
        $product->delete();
        toast('Data Deleted Successfully', 'success');
        return redirect()->to('product');
    }

    public function getProduct($category_id)
    {

        $products = Products::where('category_id', $category_id)->get();
        $response = ['status' => 'success', 'message' => 'Fetch product success', 'data' => $products];
        return response()->json($response, 200);
    }
}
