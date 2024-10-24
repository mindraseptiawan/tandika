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
    public function laporan()
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
