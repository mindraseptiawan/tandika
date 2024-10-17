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

    public function create(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:255',
            'supplier_phone' => 'required|string|max:15',
            'alamat' => 'required|string|min:1',
            'quantity' => 'required|numeric',
            'price_per_unit' => 'required|numeric',
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

            $previousBalance = CashFlow::latest()->first()->balance ?? 0;
            $cashflow = CashFlow::create([
                'transaction_id' => $transaction->id,
                'type' => 'out',
                'amount' => $request->quantity * $request->price_per_unit,
                'balance' => $previousBalance - $request->quantity * $request->price_per_unit,
            ]);

            // Create a new StockMovement record
            StockMovement::create([
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
            $purchase->total_price = $purchase->quantity * $purchase->price_per_unit;
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

            // Update transaction
            $transaction = Transaction::findOrFail($purchase->transaction_id);
            $transaction->amount = $purchase->total_price;
            $transaction->save();

            // Update cashflow
            $cashflow = Cashflow::where('transaction_id', $transaction->id)->firstOrFail();
            $balanceDifference = $purchase->total_price - $oldTotalPrice;
            $cashflow->amount = $purchase->total_price;
            $cashflow->balance -= $balanceDifference;
            $cashflow->save();

            // Update subsequent cashflows
            Cashflow::where('id', '>', $cashflow->id)
                ->decrement('balance', $balanceDifference);

            // Update stock movements if quantity or kandang has changed
            if ($oldKandangId != $purchase->kandang_id || $oldQuantity != $purchase->quantity) {
                // Remove old stock movement
                StockMovement::where([
                    'reference_id' => $purchase->id,
                    'reference_type' => Purchase::class,
                    'type' => 'in'
                ])->delete();

                // Create new stock movement
                StockMovement::create([
                    'kandang_id' => $purchase->kandang_id,
                    'type' => 'in',
                    'quantity' => $purchase->quantity,
                    'reason' => 'purchase',
                    'reference_id' => $purchase->id,
                    'reference_type' => Purchase::class,
                    'notes' => "Update pembelian ayam ke kandang #{$purchase->kandang_id}",
                ]);

                // Update old kandang
                $oldKandang = Kandang::find($oldKandangId);
                if ($oldKandang) {
                    $oldKandang->jumlah_real -= $oldQuantity;
                    $oldKandang->save();
                }

                // Update new kandang
                $newKandang = Kandang::find($purchase->kandang_id);
                if ($newKandang) {
                    if ($newKandang->kapasitas < $newKandang->jumlah_real + $purchase->quantity) {
                        throw new \Exception('Kapasitas di kandang baru tidak mencukupi');
                    }
                    $newKandang->jumlah_real += $purchase->quantity;
                    $newKandang->save();
                } else {
                    throw new \Exception('Kandang baru tidak ditemukan');
                }
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

            // Update kandang if it exists
            $kandang = Kandang::find($purchase->kandang_id);
            if ($kandang) {
                $kandang->jumlah_real -= $purchase->quantity;
                $kandang->save();
            }

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
