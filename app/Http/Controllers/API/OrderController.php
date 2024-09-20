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
        $response = [
            'success' => true,
            'message' => 'Order berhasil dibuat',
            'data' => $orderWithCustomer
        ];

        return response()->json($response, 201);
    }



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
    public function processOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validatedData = $request->validate([
            'price_per_unit' => 'required|numeric|min:0', // Manual input for price per unit
        ]);

        // Hitung total price berdasarkan price per unit yang dimasukkan operator
        $totalPrice = $validatedData['price_per_unit'] * $order->quantity;

        // Create a transaction
        $transaction = Transaction::create([
            'user_id' => auth()->id(),
            'type' => 'sale',
            'amount' => $totalPrice, // Menggunakan total harga
            'keterangan' => 'Sale of chickens',
        ]);

        // Create a sale with the calculated total price
        Sale::create([
            'transaction_id' => $transaction->id,
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'quantity' => $order->quantity, // Directly use the quantity from the order
            'price_per_unit' => $validatedData['price_per_unit'], // Use price per unit from the request
            'total_price' => $totalPrice, // Calculated total price
        ]);

        // Update order status
        $order->status = 'processed';
        $order->save();

        return response()->json(['message' => 'Order processed successfully', 'transaction' => $transaction]);
    }
}
