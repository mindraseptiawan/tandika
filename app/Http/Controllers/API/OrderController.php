<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;

class OrderController extends Controller
{
    public function all(Request $request, $customer_id = null)
    {
        $customer_id = $request->input('customer_id', $customer_id);
        if ($customer_id) {
            $customer = Customer::find($customer_id);

            if (!$customer) {
                return ResponseFormatter::error(
                    null,
                    'Data customer tidak ditemukan',
                    404
                );
            }

            $orders = $customer->orders()->paginate(100);

            return ResponseFormatter::success(
                $orders,
                'Data Orderan customer berhasil diambil'
            );
        } else {
            $orders = Order::paginate(100);

            return ResponseFormatter::success(
                $orders,
                'Data Orderan berhasil diambil'
            );
        }
        // // Ambil data orders dengan relasi customer
        // $orders = Order::with('customer')->get();

        // // Siapkan respons dengan struktur seperti di kode kedua
        // $response = [
        //     'success' => true,
        //     'message' => 'Sukses menampilkan data',
        //     'data' => $orders
        // ];

        // // Return respons JSON
        // return response()->json($response);
    }
    public function getOrdersByCustomer($customerId)
    {
        $orders = Order::where('customer_id', $customerId)->get();

        if (!$orders) {
            return ResponseFormatter::error(
                null,
                'Data Order tidak ditemukan',
                404
            );
        }

        return ResponseFormatter::success(
            $orders,
            'Data Pemeliharaan berhasil diambil'
        );
    }

    // Store a new order
    public function store(Request $request)
    {
        // Validasi data dari request (biasanya dari chatbot)
        $validatedData = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:15',
            'quantity' => 'required|integer|min:1',
            'alamat' => 'required|string|min:1',
        ]);

        // Cek apakah pelanggan sudah ada berdasarkan nomor telepon
        $customer = Customer::where('phone', $validatedData['customer_phone'])->first();

        if (!$customer) {
            // Jika pelanggan belum ada, buat pelanggan baru
            $customer = Customer::create([
                'name' => $validatedData['customer_name'],
                'phone' => $validatedData['customer_phone'],
                'alamat' => $request->input('alamat'),
            ]);
        }

        // Buat order baru dengan order_date otomatis
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_date' => now(), // Menggunakan tanggal saat ini secara otomatis
            'status' => 'pending',
            'quantity' => $validatedData['quantity'],
            'alamat' => $validatedData['alamat'],
        ]);

        // Tambahkan relasi customer dalam order
        $orderWithCustomer = Order::with('customer')->find($order->id);

        // Kembalikan respons dalam format yang diinginkan
        return ResponseFormatter::success($orderWithCustomer, 'Order berhasil dibuat', 201);
    }

    public function setPricePerUnit(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validatedData = $request->validate([
            'price_per_unit' => 'required|numeric|min:0',
        ]);

        $order->status = 'price_set';
        $order->save();

        // Buat Sale baru dengan harga yang ditentukan
        $sale = Sale::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'quantity' => $order->quantity,
            'price_per_unit' => $validatedData['price_per_unit'],
            'total_price' => $order->quantity * $validatedData['price_per_unit'],
            'transaction_id' => null
        ]);

        return ResponseFormatter::success(['order' => $order, 'sale' => $sale], 'Harga per unit berhasil diatur');
    }

    public function processOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $sale = Sale::where('order_id', $order->id)->firstOrFail();

        if ($order->status !== 'price_set') {
            return ResponseFormatter::error(null, 'Harga per unit belum diatur', 400);
        }

        // Create a transaction
        $transaction = Transaction::create([
            'user_id' => auth()->id(),
            'type' => 'sale',
            'amount' => $sale->total_price, // Menggunakan total harga
            'keterangan' => 'Sale of chickens',
        ]);

        // Create a sale with the calculated total price
        $sale->transaction_id = $transaction->id;
        $sale->save();

        // Update order status
        $order->status = 'awaiting_payment';
        $order->save();

        return ResponseFormatter::success(['order' => $order, 'sale' => $sale, 'transaction' => $transaction], 'Order berhasil diproses');
    }

    public function submitPaymentProof(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'payment_method' => 'required|in:cash,transfer',
            'payment_proof' => 'required_if:payment_method,transfer|file|image',
        ]);

        $order->payment_method = $request->payment_method;
        if ($request->hasFile('payment_proof')) {
            $path = $request->file('payment_proof')->store('payment_proofs');
            $order->payment_proof = $path;
        }
        $order->status = 'payment_verification';
        $order->save();

        return ResponseFormatter::success($order, 'Bukti pembayaran berhasil disubmit');
    }

    public function verifyPayment(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->status = 'completed';
        $order->payment_verified_at = now();
        $order->payment_verified_by = auth()->id();
        $order->save();

        return ResponseFormatter::success($order, 'Pembayaran berhasil diverifikasi');
    }

    // public function completeOrder($id)
    // {
    //     $order = Order::findOrFail($id);

    //     if ($order->status !== 'processed') {
    //         return ResponseFormatter::error(null, 'Order belum diproses', 400);
    //     }

    //     $order->status = 'completed';
    //     $order->save();

    //     return ResponseFormatter::success($order, 'Order berhasil diselesaikan');
    // }

    // Update an order
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        // Update order status
        $order->status = $request->input('status', $order->status);
        $order->save();

        return response()->json($order);
    }

    // Show a specific order
    public function show($id)
    {
        $order = Order::with('customer', 'sales')->findOrFail($id);

        return response()->json($order);
    }

    // Delete an order
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }

    // Process an order (this could be expanded with more logic)

}
