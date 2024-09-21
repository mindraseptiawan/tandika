<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;

class SaleController extends Controller
{
    public function all()
    {
        $sales = Sale::with('order', 'transaction', 'customer')->get();
        return ResponseFormatter::success($sales, 'Data penjualan berhasil diambil');
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'transaction_id' => 'required|exists:transactions,id',
    //         'customer_id' => 'required|exists:customers,id',
    //         'quantity' => 'required|numeric',
    //         'price_per_unit' => 'required|numeric',
    //         'total_price' => 'required|numeric'
    //     ]);
    //     $sale = Sale::create($request->all());

    //     return response()->json($sale, 201);
    // }


    public function update($id, Request $request)
    {
        $request->validate([
            'quantity' => 'sometimes|required|numeric',
            'price_per_unit' => 'sometimes|required|numeric',
            'total_price' => 'sometimes|required|numeric'
        ]);

        $sale = Sale::findOrFail($id);
        $sale->update($request->only(['quantity', 'price_per_unit', 'total_price']));

        return ResponseFormatter::success($sale, 'Data penjualan berhasil diperbarui');
    }

    public function destroy($id)
    {
        $sale = Sale::findOrFail($id);
        $sale->delete();

        return ResponseFormatter::success(null, 'Data penjualan berhasil dihapus');
    }

    public function show($id)
    {
        $sale = Sale::findOrFail($id);

        return ResponseFormatter::success($sale, 'Data penjualan berhasil diambil');
    }

    public function getSalesByCustomer($customerId)
    {
        $sales = Sale::where('customer_id', $customerId)->get();

        return ResponseFormatter::success($sales, 'Data penjualan pelanggan berhasil diambil');
    }
}
// // Database Migration untuk menambah kolom di tabel orders
// Schema::table('orders', function (Blueprint $table) {
//     $table->enum('payment_method', ['cash', 'transfer'])->nullable();
//     $table->string('payment_proof')->nullable();
//     $table->timestamp('payment_verified_at')->nullable();
//     $table->unsignedBigInteger('payment_verified_by')->nullable();
//     $table->foreign('payment_verified_by')->references('id')->on('users');
// });

// // Model: app/Models/Order.php
// namespace App\Models;

// use Illuminate\Database\Eloquent\Model;

// class Order extends Model
// {
//     protected $fillable = [
//         // ... kolom yang sudah ada
//         'payment_method',
//         'payment_proof',
//         'payment_verified_at',
//         'payment_verified_by'
//     ];

//     public function verifiedBy()
//     {
//         return $this->belongsTo(User::class, 'payment_verified_by');
//     }
// }

// // Controller: app/Http/Controllers/OrderController.php
// namespace App\Http\Controllers;

// use App\Models\Order;
// use Illuminate\Http\Request;
// use App\Helpers\ResponseFormatter;

// class OrderController extends Controller
// {
//     // ... metode lain tetap sama

//     public function processOrder($id)
//     {
//         $order = Order::findOrFail($id);
//         // ... proses seperti sebelumnya
//         $order->status = 'awaiting_payment';
//         $order->save();
//         return ResponseFormatter::success($order, 'Order diproses dan menunggu pembayaran');
//     }

//     public function submitPaymentProof(Request $request, $id)
//     {
//         $order = Order::findOrFail($id);
//         $request->validate([
//             'payment_method' => 'required|in:cash,transfer',
//             'payment_proof' => 'required_if:payment_method,transfer|file|image',
//         ]);

//         $order->payment_method = $request->payment_method;
//         if ($request->hasFile('payment_proof')) {
//             $path = $request->file('payment_proof')->store('payment_proofs');
//             $order->payment_proof = $path;
//         }
//         $order->status = 'payment_verification';
//         $order->save();

//         return ResponseFormatter::success($order, 'Bukti pembayaran berhasil disubmit');
//     }

//     public function verifyPayment(Request $request, $id)
//     {
//         $order = Order::findOrFail($id);
//         $order->status = 'completed';
//         $order->payment_verified_at = now();
//         $order->payment_verified_by = auth()->id();
//         $order->save();

//         return ResponseFormatter::success($order, 'Pembayaran berhasil diverifikasi');
//     }
// }