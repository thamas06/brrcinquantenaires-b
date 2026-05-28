<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if(!in_array($user->role, ['admin','manager','caissier','employee'])){
            return response()->json(['message'=>'Forbidden'], 403);
        }

        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'employee_id' => 'nullable|integer|exists:users,id',
            'qty' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($data['product_id']);
        $qty = $data['qty'];

        if($product->stock < $qty){
            return response()->json(['message' => 'Stock insuffisant'], 400);
        }

        $unitPrice = $product->sale_price;
        $unitProfit = $product->profit;
        $totalSale = $unitPrice * $qty;
        $totalProfit = $unitProfit * $qty;

        $product->stock -= $qty;
        $product->save();

        // Forcer employee_id à l'utilisateur connecté pour caissier/employee
        $employeeId = in_array($user->role, ['caissier', 'employee'])
            ? $user->id
            : ($data['employee_id'] ?? $user->id);

        $sale = Sale::create([
            'product_id'   => $product->id,
            'employee_id'  => $employeeId,
            'qty'          => $qty,
            'unit_price'   => $unitPrice,
            'total_sale'   => $totalSale,
            'total_profit' => $totalProfit,
        ]);

        return response()->json($sale->load('product'), 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Admin et manager voient toutes les ventes
        if (in_array($user->role, ['admin', 'manager'])) {
            return Sale::with('product')->get();
        }

        // Caissier/employé : voit uniquement ses propres ventes
        return Sale::with('product')
            ->where('employee_id', $user->id)
            ->get();
    }
}
