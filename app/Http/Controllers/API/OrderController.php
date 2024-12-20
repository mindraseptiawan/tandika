<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Kandang;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\StockMovement;
use App\Models\Customer;
use App\Models\Cashflow;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Log;

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
            'Data Order berhasil diambil'
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
            'ongkir' => 'nullable|numeric|min:0',
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

            $cashflow = Cashflow::create([
                'transaction_id' => $transaction->id,
                'type' => 'in',
                'amount' => null, // Amount masih null
                'balance' => Cashflow::latest()->first()->balance ?? 0,
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
                    'ongkir' => $validatedData['ongkir'],
                    'total_price' => ($order->quantity * $validatedData['price_per_unit']) + $validatedData['ongkir'],
                    'transaction_id' => $transaction->id, // Set transaction_id pada sale
                ]);
            } else {
                // Update sale yang sudah ada
                $sale->price_per_unit = $validatedData['price_per_unit'];
                $sale->total_price = ($order->quantity * $validatedData['price_per_unit']) + $validatedData['ongkir'];
                $sale->save();
            }

            DB::commit();
            return ResponseFormatter::success(['order' => $order, 'sale' => $sale, 'transaction' => $transaction, 'cashflow' => $cashflow], 'Harga per unit berhasil diatur');
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

        $request->validate([
            'kandang_id' => 'required|exists:kandang,id',
            'purchase_ids' => 'required|array',
            'purchase_ids.*' => 'exists:purchases,id',
        ]);

        DB::beginTransaction();
        try {
            if (!$sale->transaction_id) {
                return ResponseFormatter::error(null, 'Transaksi belum dibuat. Silakan set harga per unit terlebih dahulu.', 400);
            }

            $kandang = Kandang::findOrFail($request->kandang_id);
            $totalStockToDeduct = $order->quantity;
            $remainingStockToDeduct = $totalStockToDeduct;

            $stockMovements = [];

            foreach ($request->purchase_ids as $purchaseId) {
                $purchase = Purchase::findOrFail($purchaseId);

                if ($purchase->kandang_id != $kandang->id) {
                    throw new \Exception("Batch #{$purchaseId} bukan milik kandang yang dipilih");
                }

                $currentStock = $purchase->quantity
                    - $purchase->stockMovements()
                    ->where('type', 'out')
                    ->sum('quantity');

                if ($currentStock <= 0) {
                    continue; // Jika stok kosong, lewati batch ini
                }

                $deductFromThisBatch = min($remainingStockToDeduct, $currentStock);
                $remainingStockToDeduct -= $deductFromThisBatch;

                // Catat pergerakan stok untuk batch ini
                $stockMovement = StockMovement::create([
                    'kandang_id' => $kandang->id,
                    'purchase_id' => $purchase->id,
                    'type' => 'out',
                    'quantity' => $deductFromThisBatch,
                    'reason' => 'sale',
                    'reference_id' => $sale->id,
                    'reference_type' => Sale::class,
                    'notes' => "Pengurangan stok untuk order #{$order->id}",
                ]);

                $stockMovements[] = $stockMovement;

                if ($remainingStockToDeduct <= 0) {
                    break; // Jika stok sudah terpenuhi, hentikan iterasi
                }
            }

            if ($remainingStockToDeduct > 0) {
                throw new \Exception('Stok di kandang tidak mencukupi untuk memenuhi order');
            }

            // Kurangi stok di kandang
            $kandang->jumlah_real -= $totalStockToDeduct;
            $kandang->save();

            // Update order status
            $order->status = 'awaiting_payment';
            $order->kandang_id = $kandang->id;
            $order->save();

            DB::commit();
            return ResponseFormatter::success([
                'order' => $order,
                'sale' => $sale,
                'stock_movements' => $stockMovements
            ], 'Order berhasil diproses');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(null, 'Terjadi kesalahan saat memproses order: ' . $e->getMessage(), 500);
        }
        Log::info($request->all());
    }


    public function cancelOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status === 'completed') {
            return ResponseFormatter::error(null, 'Order yang sudah selesai tidak dapat dibatalkan', 400);
        }

        DB::beginTransaction();
        try {
            switch ($order->status) {
                case 'pending':
                    // No additional action needed for pending orders
                    break;

                case 'price_set':
                    // Delete associated Sale
                    $sale = Sale::where('order_id', $order->id)->first();
                    if ($sale) {
                        // Delete associated Transaction and Cashflow
                        if ($sale->transaction_id) {
                            Cashflow::where('transaction_id', $sale->transaction_id)->delete();
                            Transaction::destroy($sale->transaction_id);
                        }
                        $sale->delete();
                    }
                    break;

                case 'awaiting_payment':
                    // Revert stock reduction
                    $kandang = Kandang::findOrFail($order->kandang_id);
                    $kandang->jumlah_real += $order->quantity;
                    $kandang->save();

                    // Delete StockMovement record
                    StockMovement::where('reference_id', $order->id)
                        ->where('reference_type', Order::class)
                        ->where('type', 'out')
                        ->delete();

                    // Delete associated Sale, Transaction, and Cashflow
                    $sale = Sale::where('order_id', $order->id)->first();
                    if ($sale) {
                        if ($sale->transaction_id) {
                            Cashflow::where('transaction_id', $sale->transaction_id)->delete();
                            Transaction::destroy($sale->transaction_id);
                        }
                        $sale->delete();
                    }
                    break;

                case 'payment_verification':
                    // Similar to 'awaiting_payment', but we might want to keep a record of the payment proof
                    $kandang = Kandang::findOrFail($order->kandang_id);
                    $kandang->jumlah_real += $order->quantity;
                    $kandang->save();

                    StockMovement::where('reference_id', $order->id)
                        ->where('reference_type', Order::class)
                        ->where('type', 'out')
                        ->delete();

                    $sale = Sale::where('order_id', $order->id)->first();
                    if ($sale) {
                        if ($sale->transaction_id) {
                            Cashflow::where('transaction_id', $sale->transaction_id)->delete();
                            Transaction::destroy($sale->transaction_id);
                        }
                        $sale->delete();
                    }
                    // We're keeping the payment_proof in the order record for reference
                    break;

                default:
                    throw new \Exception('Invalid order status for cancellation');
            }

            // Update order status and add cancellation details
            $order->status = 'cancelled';
            $order->created_at = now();
            $order->payment_verified_by = auth()->id();
            $order->save();

            DB::commit();
            return ResponseFormatter::success(['order' => $order], 'Order berhasil dibatalkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(null, 'Terjadi kesalahan saat membatalkan order: ' . $e->getMessage(), 500);
        }
    }
    public function submitPaymentProof(Request $request, $orderId)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'payment_proof' => 'nullable|image|max:2048',
        ]);

        $order = Order::findOrFail($orderId);

        if ($request->hasFile('payment_proof')) {
            $path = $request->file('payment_proof')->store('payment_proofs', 'public');
            $order->payment_proof = $path;
        }

        $order->payment_method = $request->payment_method;
        $order->save();

        return response()->json(['message' => 'Payment proof submitted successfully', 'order' => $order], 200);
    }


    public function verifyPayment(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $sale = Sale::where('order_id', $order->id)->firstOrFail();
        $order->status = 'completed';
        $order->payment_verified_at = now();
        $order->payment_verified_by = auth()->id();
        $order->save();

        $transaction = Transaction::findOrFail($sale->transaction_id);
        $transaction->amount = $sale->total_price;
        $transaction->keterangan = 'Sale of chickens';
        $transaction->save();

        $cashflow = Cashflow::where('transaction_id', $sale->transaction_id)->firstOrFail();
        $cashflow->amount = $sale->total_price;
        $cashflow->balance = $cashflow->balance + $sale->total_price;
        $cashflow->save();

        // Include the payment proof URL in the response
        return response()->json([
            'message' => 'Payment successfully verified',
            'payment_proof' => asset('storage/' . $order->payment_proof) // Return the image URL
        ]);
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
