<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
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

    public function getOrdersByStatus(Request $request, $status)
    {
        $orders = Order::where('status', $status)->get();

        if ($orders->isEmpty()) {
            return ResponseFormatter::error(null, 'Data order tidak ditemukan', 404);
        }

        return ResponseFormatter::success($orders, 'Data order berhasil diambil');
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

        if ($order->status !== 'pending') {
            return ResponseFormatter::error(null, 'Harga hanya dapat diatur untuk order dengan status pending', 400);
        }

        $validatedData = $request->validate([
            'price_per_unit' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $order->status = 'price_set';
            $order->save();

            // Buat transaksi awal dengan amount null
            $transaction = Transaction::create([
                'user_id' => auth()->id(),
                'type' => 'sale',
                'amount' => null, // Amount masih null
                'keterangan' => 'Sale pending amount',
            ]);

            // Cek apakah sudah ada sale untuk order ini
            $sale = Sale::where('order_id', $order->id)->first();
            if (!$sale) {
                // Buat Sale baru jika belum ada
                $sale = Sale::create([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'quantity' => $order->quantity,
                    'price_per_unit' => $validatedData['price_per_unit'],
                    'total_price' => $order->quantity * $validatedData['price_per_unit'],
                    'transaction_id' => $transaction->id, // Set transaction_id pada sale
                ]);
            } else {
                // Update sale yang sudah ada
                $sale->price_per_unit = $validatedData['price_per_unit'];
                $sale->total_price = $order->quantity * $validatedData['price_per_unit'];
                $sale->save();
            }

            DB::commit();
            return ResponseFormatter::success(['order' => $order, 'sale' => $sale, 'transaction' => $transaction], 'Harga per unit berhasil diatur');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(null, 'Terjadi kesalahan saat mengatur harga: ' . $e->getMessage(), 500);
        }
    }


    public function processOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $sale = Sale::where('order_id', $order->id)->firstOrFail();

        if ($order->status !== 'price_set') {
            return ResponseFormatter::error(null, 'Order harus dalam status price_set untuk diproses', 400);
        }

        DB::beginTransaction();
        try {
            // Cek apakah sudah ada transaksi untuk sale ini
            if (!$sale->transaction_id) {
                return ResponseFormatter::error(null, 'Transaksi belum dibuat. Silakan set harga per unit terlebih dahulu.', 400);
            }

            // Ambil transaksi yang sudah dibuat saat set price
            $transaction = Transaction::findOrFail($sale->transaction_id);

            // Update amount di transaksi dengan total harga sale
            $transaction->amount = $sale->total_price;
            $transaction->save();

            // Update order status
            $order->status = 'awaiting_payment';
            $order->save();

            DB::commit();
            return ResponseFormatter::success(['order' => $order, 'sale' => $sale, 'transaction' => $transaction], 'Order berhasil diproses');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(null, 'Terjadi kesalahan saat memproses order: ' . $e->getMessage(), 500);
        }
    }


    public function submitPaymentProof(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'payment_method' => 'required|in:cash,transfer',
            'payment_proof' => 'nullable|file|image|max:2048', // Optional for both methods, max 2MB
        ]);

        $order->payment_method = $request->payment_method;

        if ($request->hasFile('payment_proof')) {
            $path = $request->file('payment_proof')->store('payment_proofs');
            $order->payment_proof = $path;
        } else {
            $order->payment_proof = null; // Clear any existing proof if no new file is uploaded
        }

        $order->status = 'payment_verification';
        $order->save();

        $message = 'Bukti pembayaran berhasil disubmit';
        if ($request->payment_method === 'transfer' && !$request->hasFile('payment_proof')) {
            $message .= '. Namun, disarankan untuk menyertakan bukti transfer untuk mempercepat proses verifikasi.';
        }

        return ResponseFormatter::success($order, $message);
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
