<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pakan;
use Illuminate\Http\Request;

class PakanController extends Controller
{
    // Mengambil semua data pakan
    public function all()
    {
        $pakans = Pakan::all();
        return response()->json($pakans);
    }

    // Mengambil data pakan tertentu berdasarkan ID
    public function show($id)
    {
        $pakan = Pakan::find($id);
        if ($pakan) {
            return response()->json($pakan);
        }
        return response()->json(['message' => 'Pakan not found'], 404);
    }

    // Menambahkan data pakan baru
    public function create(Request $request)
    {
        $request->validate([
            'jenis' => 'required|string|max:255',
            'sisa' => 'required|integer',
            'keterangan' => 'string|nullable',
        ]);

        $pakan = Pakan::create($request->all());
        return response()->json($pakan, 201);
    }

    // Memperbarui data pakan yang ada
    public function update(Request $request, $id)
    {
        $pakan = Pakan::find($id);
        if (!$pakan) {
            return response()->json(['message' => 'Pakan not found'], 404);
        }

        $request->validate([
            'jenis' => 'string|max:255',
            'sisa' => 'integer',
            'keterangan' => 'string|nullable',
        ]);

        $pakan->update($request->all());
        return response()->json($pakan);
    }

    // Menghapus (soft delete) data pakan
    public function destroy($id)
    {
        $pakan = Pakan::find($id);
        if (!$pakan) {
            return response()->json(['message' => 'Pakan not found'], 404);
        }

        $pakan->delete();
        return response()->json(['message' => 'Pakan deleted']);
    }

    // Mengambil semua pakan yang masih tersedia
    public function getAvailablePakan()
    {
        $pakans = Pakan::where('sisa', '>', 0)->get();
        return response()->json($pakans);
    }

    // Mengurangi jumlah sisa pakan
    public function decreaseStock(Request $request, $id)
    {
        $pakan = Pakan::find($id);
        if (!$pakan) {
            return response()->json(['message' => 'Pakan not found'], 404);
        }

        $request->validate([
            'jumlah' => 'required|integer|min:1',
        ]);

        $pakan->sisa -= $request->jumlah;
        $pakan->save();

        return response()->json($pakan);
    }

    // Menambahkan jumlah sisa pakan
    public function increaseStock(Request $request, $id)
    {
        $pakan = Pakan::find($id);
        if (!$pakan) {
            return response()->json(['message' => 'Pakan not found'], 404);
        }

        $request->validate([
            'jumlah' => 'required|integer|min:1',
        ]);

        $pakan->sisa += $request->jumlah;
        $pakan->save();

        return response()->json($pakan);
    }

    // (Optional) Mengambil pakan yang sudah kadaluwarsa
    public function getExpiredPakan()
    {
        // Asumsi ada kolom expired_date di tabel pakan
        $today = now();
        $pakans = Pakan::where('expired_date', '<', $today)->get();
        return response()->json($pakans);
    }
}
