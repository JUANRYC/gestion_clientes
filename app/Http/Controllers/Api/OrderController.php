<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('customer');
        if ($request->has('search')) {
            $query->where('orderNumber', 'like', '%' . $request->search . '%');
        }
        return response()->json($query->orderBy('id', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'orderNumber' => 'required|string|unique:orders,orderNumber',
            'status' => 'required|in:CREATED,PAID,CANCELLED',
            'totalAmount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $order = Order::create($validated);
        return response()->json($order->load('customer'), 201);
    }

    public function show($id)
    {
        $order = Order::with('customer')->findOrFail($id);
        return response()->json($order);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $validated = $request->validate([
            'customerId' => 'required|exists:customers,id',
            'orderNumber' => 'required|string|unique:orders,orderNumber,' . $order->id,
            'status' => 'required|in:CREATED,PAID,CANCELLED',
            'totalAmount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $order->update($validated);
        return response()->json($order->load('customer'));
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();
        return response()->json(null, 204);
    }
}
