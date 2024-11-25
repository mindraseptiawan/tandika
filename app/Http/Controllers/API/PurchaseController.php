<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\Cashflow;
use App\Models\Kandang;


class PurchaseController extends Controller
{
    public function all()
    {
        $purchase = Purchase::with('supplier')->get();

        return ResponseFormatter::success(
            $purchase,
            'Data pembelian berhasil diambil'
        );
    }
    public function laporan()
    {
        $purchase = Purchase::with('supplier')->get();

        return ResponseFormatter::success(
            $purchase,
            'Data pembelian berhasil diambil'
        );
    }

    public function getPurchasesBySupplier($supplierId)
    {
        $suppliers = Purchase::where('supplier_id', $supplierId)->get();

        if (!$suppliers) {
            return ResponseFormatter::error(
                null,
                'Data Purchase tidak ditemukan',
                404
            );
        }

        return ResponseFormatter::success(
            $suppliers,
            'Data Purchase berhasil diambil'
        );
    }

    public function getPurchasesByKandang($kandangId)
    {
        $purchases = Purchase::where('kandang_id', $kandangId)->get();

        if ($purchases->isEmpty()) {
            return ResponseFormatter::error(
                null,
                'Data Purchase tidak ditemukan',
                404
            );
        }

        // Map each purchase to include current stock
        $purchasesWithStock = $purchases->map(function ($purchase) {
            $currentStock = $purchase->quantity
                - $purchase->stockMovements()
                ->where('type', 'out')
                ->sum('quantity');

            return [
                'id' => $purchase->id,
                'transaction_id' => $purchase->transaction_id,
                'kandang_id' => $purchase->kandang_id,
                'supplier_id' => $purchase->supplier_id,
                'quantity' => $purchase->quantity,
                'price_per_unit' => $purchase->price_per_unit,
                'ongkir' => $purchase->ongkir,
                'total_price' => $purchase->total_price,
                'created_at' => $purchase->created_at,
                'updated_at' => $purchase->updated_at,
                'date' => $purchase->created_at,
                'currentStock' => $currentStock,
            ];
        });

        return ResponseFormatter::success(
            $purchasesWithStock,
            'Data Purchase berhasil diambil'
        );
    }


    public function create(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:255',
            'supplier_phone' => 'required|string|max:15',
            'alamat' => 'required|string|min:1',
            'quantity' => 'required|numeric',
            'price_per_unit' => 'required|numeric',
            'ongkir' => 'nullable|numeric',
            'kandang_id' => 'required|exists:kandang,id',
        ]);

        try {
            DB::beginTransaction();

            // Cek apakah supplier sudah ada berdasarkan nomor telepon
            $supplier = Supplier::where('phone', $request->supplier_phone)->first();

            if (!$supplier) {
                // Jika supplier belum ada, buat supplier baru
                $supplier = Supplier::create([
                    'name' => $request->supplier_name,
                    'phone' => $request->supplier_phone,
                    'alamat' => $request->input('alamat'),
                ]);
            }

            // Buat transaksi baru
            $transaction = Transaction::create([
                'user_id' => auth()->id(),
                'type' => 'purchase',
                'amount' => ($request->quantity * $request->price_per_unit) + $request->ongkir,
                'keterangan' => 'Purchase',
            ]);

            // Buat purchase baru
            $purchase = Purchase::create([
                'transaction_id' => $transaction->id,
                'supplier_id' => $supplier->id,
                'quantity' => $request->quantity,
                'price_per_unit' => $request->price_per_unit,
                'ongkir' => $request->ongkir,
                'total_price' => ($request->quantity * $request->price_per_unit) + $request->ongkir,
                'kandang_id' => $request->kandang_id,
            ]);

            $previousBalance = Cashflow::latest()->first()->balance ?? 0;
            $cashflow = Cashflow::create([
                'transaction_id' => $transaction->id,
                'type' => 'out',
                'amount' => ($request->quantity * $request->price_per_unit) + $request->ongkir,
                'balance' => $previousBalance - ($request->quantity * $request->price_per_unit) - $request->ongkir,
            ]);

            // Create a new StockMovement record
            StockMovement::create([
                'kandang_id' => $request->kandang_id,
                'purchase_id' => $purchase->id,
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
                throw new \Exception('Kapasitas di kandang tidak mencukupi');
            }

            // Tambah stok di kandang
            $kandang->jumlah_real += $request->quantity;
            $kandang->save();

            DB::commit();

            return ResponseFormatter::success(
                [
                    'purchase' => $purchase,
                    'transaction' => $transaction,
                    'cashflow' => $cashflow,
                    'supplier' => $supplier
                ],
                'Data Purchase berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                null,
                'Data Purchase gagal ditambahkan: ' . $e->getMessage(),
                500
            );
        }
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'supplier_name' => 'sometimes|required|string|max:255',
            'supplier_phone' => 'sometimes|required|string|max:15',
            'alamat' => 'sometimes|required|string|min:1',
            'quantity' => 'sometimes|required|numeric',
            'price_per_unit' => 'sometimes|required|numeric',
            'ongkir' => 'sometimes|required|numeric',
            'kandang_id' => 'sometimes|required|exists:kandang,id',
        ]);

        try {
            DB::beginTransaction();

            $purchase = Purchase::findOrFail($id);
            $oldQuantity = $purchase->quantity;
            $oldTotalPrice = $purchase->total_price;
            $oldKandangId = $purchase->kandang_id;

            // Update purchase
            if ($request->has('quantity')) {
                $purchase->quantity = $request->quantity;
            }
            if ($request->has('price_per_unit')) {
                $purchase->price_per_unit = $request->price_per_unit;
            }
            if ($request->has('ongkir')) {
                $purchase->ongkir = $request->ongkir;
            }
            // Update total price termasuk ongkir
            $purchase->total_price = ($purchase->quantity * $purchase->price_per_unit) + $purchase->ongkir;
            if ($request->has('kandang_id')) {
                $purchase->kandang_id = $request->kandang_id;
            }
            $purchase->save();

            // Update supplier if supplier details are provided
            $supplier = Supplier::findOrFail($purchase->supplier_id);
            $supplierUpdated = false;

            if ($request->filled('supplier_name')) {
                $supplier->name = $request->supplier_name;
                $supplierUpdated = true;
            }
            if ($request->filled('supplier_phone')) {
                $supplier->phone = $request->supplier_phone;
                $supplierUpdated = true;
            }
            if ($request->filled('alamat')) {
                $supplier->alamat = $request->alamat;
                $supplierUpdated = true;
            }

            if ($supplierUpdated) {
                $supplier->save();
            }

            // Update transaction with new amount including ongkir
            $transaction = Transaction::findOrFail($purchase->transaction_id);
            $transaction->amount = $purchase->total_price + $purchase->ongkir;
            $transaction->save();

            // Update cashflow
            $cashflow = Cashflow::where('transaction_id', $transaction->id)->firstOrFail();
            $totalAmount = $purchase->total_price + $purchase->ongkir;
            $oldTotalAmount = $oldTotalPrice + $purchase->ongkir;
            $balanceDifference = $totalAmount - $oldTotalAmount;
            $cashflow->amount = $totalAmount;
            $cashflow->balance -= $balanceDifference;
            $cashflow->save();

            // Update subsequent cashflows
            Cashflow::where('id', '>', $cashflow->id)
                ->decrement('balance', $balanceDifference);

            // Check kandang capacity if quantity changed
            if ($request->has('quantity') && $oldQuantity != $purchase->quantity) {
                $kandang = Kandang::findOrFail($purchase->kandang_id);
                $quantityDifference = $purchase->quantity - $oldQuantity;

                if ($kandang->kapasitas < $kandang->jumlah_real + $quantityDifference) {
                    throw new \Exception('Kapasitas di kandang tidak mencukupi');
                }

                // Update kandang stock
                $kandang->jumlah_real += $quantityDifference;
                $kandang->save();
            }

            // Update stock movement if quantity or kandang has changed
            if ($oldKandangId != $purchase->kandang_id || $oldQuantity != $purchase->quantity) {
                // Find existing stock movement
                $stockMovement = StockMovement::where([
                    'reference_id' => $purchase->id,
                    'reference_type' => Purchase::class,
                    'type' => 'in'
                ])->firstOrFail();

                // Update stock movement with new values
                $stockMovement->kandang_id = $purchase->kandang_id;
                $stockMovement->quantity = $purchase->quantity;
                $stockMovement->notes = "Update pembelian ayam ke kandang #{$purchase->kandang_id}";
                $stockMovement->save();
            }

            DB::commit();

            return ResponseFormatter::success(
                [
                    'purchase' => $purchase,
                    'transaction' => $transaction,
                    'cashflow' => $cashflow,
                    'supplier' => $supplier
                ],
                'Data Purchase berhasil diperbarui'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                null,
                'Data Purchase gagal diperbarui: ' . $e->getMessage(),
                500
            );
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $purchase = Purchase::findOrFail($id);

            // Delete related stock movement
            StockMovement::where([
                'reference_id' => $purchase->id,
                'reference_type' => Purchase::class,
                'type' => 'in'
            ])->delete();

            // Delete related cashflow
            $cashflow = Cashflow::where('transaction_id', $purchase->transaction_id)->first();
            if ($cashflow) {
                $deletedAmount = $cashflow->amount;

                // Update subsequent cashflows
                Cashflow::where('id', '>', $cashflow->id)
                    ->increment('balance', $deletedAmount);

                $cashflow->delete();
            }

            // Delete related transaction
            Transaction::destroy($purchase->transaction_id);

            // Finally, delete the purchase
            $purchase->delete();

            DB::commit();

            return ResponseFormatter::success(
                null,
                'Data Purchase berhasil dihapus'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                null,
                'Data Purchase gagal dihapus: ' . $e->getMessage(),
                500
            );
        }
    }

    public function show($id)
    {
        $purchase = Purchase::with('supplier')->findOrFail($id);

        return response()->json($purchase);
    }
}
