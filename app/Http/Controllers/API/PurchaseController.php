<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\Kandang;


class PurchaseController extends Controller
{
    public function all()
    {
        return Purchase::all();
    }
    public function create(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:255',
            'supplier_phone' => 'required|string|max:15',
            'quantity' => 'required|numeric',
            'price_per_unit' => 'required|numeric',
            'kandang_id' => 'required|exists:kandang,id',
        ]);

        // Cek apakah supplier sudah ada berdasarkan nomor telepon
        $supplier = Supplier::where('phone', $request->supplier_phone)->first();

        if (!$supplier) {
            // Jika supplier belum ada, buat supplier baru
            $supplier = Supplier::create([
                'name' => $request->supplier_name,
                'phone' => $request->supplier_phone,
            ]);
        }

        // Buat transaksi baru
        $transaction = Transaction::create([
            'user_id' => auth()->id(),
            'type' => 'purchase',
            'amount' => $request->quantity * $request->price_per_unit,
            'keterangan' => 'Purchase',
        ]);

        // Buat purchase baru
        $purchase = Purchase::create([
            'transaction_id' => $transaction->id,
            'supplier_id' => $supplier->id,
            'quantity' => $request->quantity,
            'price_per_unit' => $request->price_per_unit,
            'total_price' => $request->quantity * $request->price_per_unit,
            'kandang_id' => $request->kandang_id,
        ]);

        // Create a new StockMovement record
        $stockMovement = StockMovement::create([
            'kandang_id' => $request->kandang_id,
            'type' => 'in',
            'quantity' => $request->quantity,
            'reason' => 'purchase',
            'reference_id' => $purchase->id,
            'reference_type' => Purchase::class,
            'notes' => "Pembelian ayam ke kandang #{$request->kandang_id}",
        ]);

        // Perbarui jumlah real di kandang
        $kandang = Kandang::findOrFail($request->kandang_id);

        if ($kandang->kapasitas < $kandang->jumlah_real + $request->quantity) {
            return ResponseFormatter::error(null, 'Kapasitas di kandang tidak mencukupi', 400);
        }

        // Tambah stok di kandang
        $kandang->jumlah_real += $request->quantity;
        $kandang->save();

        return ResponseFormatter::success(
            ['purchase' => $purchase, 'transaction' => $transaction],
            'Data Purchase berhasil ditambahkan'
        );
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'quantity' => 'sometimes|required|numeric',
            'price_per_unit' => 'sometimes|required|numeric',
            'total_price' => 'sometimes|required|numeric'
        ]);

        $purchase = Purchase::findOrFail($id);
        $purchase->update($request->only(['quantity', 'price_per_unit', 'total_price']));

        return response()->json($purchase);
    }

    public function destroy($id)
    {
        $purchase = Purchase::findOrFail($id);
        $purchase->delete();

        return response()->json(null, 204);
    }

    public function show($id)
    {
        $purchase = Purchase::findOrFail($id);

        return response()->json($purchase);
    }

    public function getPurchasesBySupplier($supplierId)
    {
        $purchases = Purchase::where('supplier_id', $supplierId)->get();

        return response()->json($purchases);
    }
}
