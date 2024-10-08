<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cashflow;

class CashflowController extends Controller
{
    public function index()
    {
        $cashflows = Cashflow::all();
        return ResponseFormatter::success(
            $cashflows,
            'Data Cashflow berhasil diambil'
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required',
            'type' => 'required',
            'amount' => 'required',
            'balance' => 'required',
        ]);

        $cashflow = Cashflow::create($request->all());
        return ResponseFormatter::success(
            $cashflow,
            'Data cas$cashflow berhasil ditambahkan'
        );
    }

    public function show($id)
    {
        $cashflow = Cashflow::find($id);
        if (!$cashflow) {
            return response()->json(['message' => 'Cashflow not found'], 404);
        }
        return response()->json($cashflow);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'transaction_id' => 'required',
            'type' => 'required',
            'amount' => 'required',
            'balance' => 'required',
        ]);

        $cashflow = Cashflow::find($id);
        if (!$cashflow) {
            return response()->json(['message' => 'Cashflow not found'], 404);
        }
        $cashflow->update($request->all());
        return response()->json($cashflow);
    }

    public function destroy($id)
    {
        $cashflow = Cashflow::find($id);
        if (!$cashflow) {
            return response()->json(['message' => 'Cashflow not found'], 404);
        }
        $cashflow->delete();
        return response()->json(['message' => 'Cashflow deleted successfully']);
    }
}
