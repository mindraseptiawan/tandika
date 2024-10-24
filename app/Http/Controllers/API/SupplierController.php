<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;

class SupplierController extends Controller
{

    public function all()
    {
        return Supplier::all();
    }
    public function laporan()
    {
        return Supplier::all();
    }
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'alamat' => 'required|string',
            'phone' => 'required|string'
        ]);

        $supplier = Supplier::create([
            'name' => $request->name,
            'alamat' => $request->alamat,
            'phone' => $request->phone,
        ]);
        return ResponseFormatter::success(
            $supplier,
            'Data Supplier Berhasil di ambil'
        );
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'name' => 'sometimes|required|string',
            'alamat' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string'
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->only(['name', 'alamat', 'phone']));

        return response()->json($supplier);
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return response()->json(null, 204);
    }

    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);

        return response()->json($supplier);
    }
}
