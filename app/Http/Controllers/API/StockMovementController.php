<?php

namespace App\Http\Controllers\API;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\ResponseFormatter;

class StockMovementController extends Controller
{
    public function all()
    {
        $stockMovements = StockMovement::with('kandang')->get();
        return ResponseFormatter::success($stockMovements, 'Data Stock berhasil diambil');
    }


    public function store(Request $request)
    {
        $request->validate([
            'kandang_id' => 'required|exists:kandangs,id',
            'type' => 'required|string',
            'quantity' => 'required|numeric',
            'reason' => 'required|string',
            'reference_id' => 'required|numeric',
            'reference_type' => 'required|string',
            'notes' => 'required|string',
        ]);

        $stockMovement = StockMovement::create($request->all());
        return ResponseFormatter::success($stockMovement, 'Data Stock berhasil ditambah');
    }

    public function show($id)
    {
        $stockMovement = StockMovement::find($id);
        if (!$stockMovement) {
            return response()->json(['message' => 'Stock movement not found'], 404);
        }
        return ResponseFormatter::success($stockMovement, 'Data Stock berhasil diambil');
    }

    public function update(Request $request, $id)
    {
        $stockMovement = StockMovement::find($id);
        if (!$stockMovement) {
            return response()->json(['message' => 'Stock movement not found'], 404);
        }

        $request->validate([
            'kandang_id' => 'required|exists:kandangs,id',
            'type' => 'required|string',
            'quantity' => 'required|numeric',
            'reason' => 'required|string',
            'reference_id' => 'required|numeric',
            'reference_type' => 'required|string',
            'notes' => 'required|string',
        ]);

        $stockMovement->update($request->all());
        return ResponseFormatter::success($stockMovement, 'Data Stock berhasil diupdate');
    }

    public function destroy($id)
    {
        $stockMovement = StockMovement::find($id);
        if (!$stockMovement) {
            return response()->json(['message' => 'Stock movement not found'], 404);
        }

        $stockMovement->delete();
        return response()->json(['message' => 'Stock movement deleted successfully']);
    }

    public function getByKandangId($kandangId)
    {
        $stockMovements = StockMovement::where('kandang_id', $kandangId)->get();
        return ResponseFormatter::success($stockMovements, 'Data Stock berhasil diambil');
    }
}
