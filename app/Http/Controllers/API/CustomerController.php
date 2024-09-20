<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{

    public function all()
    {
        return Customer::all();
    }
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'alamat' => 'required|string',
            'phone' => 'required|string'
        ]);

        $customer = Customer::create([
            'name' => $request->name,
            'alamat' => $request->alamat,
            'phone' => $request->phone,
        ]);

        return response()->json($customer, 201);
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'name' => 'sometimes|required|string',
            'alamat' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string'
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update($request->only(['name', 'alamat', 'phone']));

        return response()->json($customer);
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json(null, 204);
    }

    public function show($id)
    {
        $customer = Customer::findOrFail($id);

        return response()->json($customer);
    }
}
