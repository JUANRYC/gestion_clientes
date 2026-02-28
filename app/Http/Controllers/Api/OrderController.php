<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('customer');

        if ($request->has('search')) {
            $query->where('orderNumber', 'like', '%' . $request->search . '%');
        }

        return response()->json(
            $query->orderBy('id', 'desc')->get()
        );
    }

    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customerId'   => 'required|exists:customers,id',
            'orderNumber'  => 'nullable|string|unique:orders,orderNumber',
            'status'       => 'required|in:CREATED,PAID,CANCELLED',
            'totalAmount'  => 'required|numeric|min:0',
            'notes'        => 'nullable|string'
        ]);

        // Si no se envió orderNumber, generar uno automático
        if (empty($validated['orderNumber'])) {
            $validated['orderNumber'] = $this->generateOrderNumber();
        }

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
            'customerId'   => 'required|exists:customers,id',
            'orderNumber'  => [
                'nullable',
                'string',
                Rule::unique('orders')->ignore($order->id)
            ],
            'status' => 'required|in:CREATED,PAID,CANCELLED',
            'totalAmount'  => 'required|numeric|min:0',
            'notes'        => 'nullable|string'
        ]);

        // Si se envía vacío (null o cadena vacía), no se actualiza el número de pedido se conserva el actual)
        if (empty($validated['orderNumber'])) {
            unset($validated['orderNumber']);
        }

        $order->update($validated);

        return response()->json($order->load('customer'));
    }

    /**
     * Función que genera un número de pedido único (ejemplo: ORD-20250228-0001)
     */
    private function generateOrderNumber()
    {
        $prefix = 'ORD-';
        $date = now()->format('Ymd');
        $lastOrder = Order::whereDate('created_at', today())
                    ->where('orderNumber', 'like', $prefix . $date . '%')
                    ->orderBy('id', 'desc')
                    ->first();

        if ($lastOrder) {
            // Extrae los últimos 4 dígitos y suma 1
            $lastNumber = intval(substr($lastOrder->orderNumber, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . '-' . $newNumber;
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(null, 204);
    }
}