<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;

class PurchaseController extends Controller
{
    public function all()
    {
        return Purchase::all();
    }
    public function create(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|numeric',
            'price_per_unit' => 'required|numeric',
            'total_price' => 'required|numeric'
        ]);

        $purchase = Purchase::create($request->all());

        return response()->json($purchase, 201);
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
