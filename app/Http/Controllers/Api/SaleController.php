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

        // stock 0 = illimité, sinon vérifier
        if($product->stock > 0 && $product->stock < $qty){
            return response()->json(['message' => 'Stock insuffisant. Stock disponible: ' . $product->stock], 400);
        }

        $unitPrice   = (float) $product->sale_price;
        $unitProfit  = (float) $product->profit;
        $totalSale   = $unitPrice * $qty;
        $totalProfit = $unitProfit * $qty;

        // Décrémenter stock seulement si > 0
        if($product->stock > 0){
            $product->stock -= $qty;
            $product->save();
        }

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

        return response()->json($sale->load(['product', 'employee']), 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (in_array($user->role, ['admin', 'manager'])) {
            $sales = Sale::with(['product', 'employee'])->orderBy('created_at', 'desc')->get();
        } else {
            // Ventes où employee_id = user.id OU ventes sur les produits assignés à cet utilisateur
            $assignedProductIds = Product::where('declared_for_user_id', $user->id)->pluck('id');
            $sales = Sale::with(['product', 'employee'])
                ->where(function($q) use ($user, $assignedProductIds) {
                    $q->where('employee_id', $user->id)
                      ->orWhereIn('product_id', $assignedProductIds);
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json($sales->map(function($s) {
            return [
                'id'           => $s->id,
                'product_id'   => $s->product_id,
                'employee_id'  => $s->employee_id,
                'qty'          => (int) $s->qty,
                'unit_price'   => (float) $s->unit_price,
                'total_sale'   => (float) $s->total_sale,
                'total_profit' => (float) $s->total_profit,
                'created_at'   => $s->created_at,
                'productName'  => $s->product?->name ?? 'Inconnu',
                'employeeName' => $s->employee?->name ?? 'N/A',
            ];
        }));
    }

    public function stats(Request $request)
    {
        $user = $request->user();

        if (in_array($user->role, ['admin', 'manager'])) {
            $sales = Sale::with(['product', 'employee'])->orderBy('created_at', 'desc')->get();
        } else {
            $assignedProductIds = Product::where('declared_for_user_id', $user->id)->pluck('id');
            $sales = Sale::with(['product', 'employee'])
                ->where(function($q) use ($user, $assignedProductIds) {
                    $q->where('employee_id', $user->id)
                      ->orWhereIn('product_id', $assignedProductIds);
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $summary = [
            'total_sales' => (float) $sales->sum('total_sale'),
            'total_profit' => (float) $sales->sum('total_profit'),
            'total_qty' => (int) $sales->sum('qty'),
            'sales_count' => $sales->count(),
        ];

        $byEmployee = $sales->groupBy('employee_id')->map(function($group, $employeeId) {
            return [
                'employee_id' => $employeeId,
                'employee_name' => $group->first()->employee?->name ?? null,
                'total_sales' => (float) $group->sum('total_sale'),
                'total_profit' => (float) $group->sum('total_profit'),
                'total_qty' => (int) $group->sum('qty'),
                'sales_count' => $group->count(),
            ];
        })->values();

        return response()->json([
            'summary' => $summary,
            'by_employee' => $byEmployee,
            'sales' => $sales->map(function($s) {
                return [
                    'id'           => $s->id,
                    'product_id'   => $s->product_id,
                    'employee_id'  => $s->employee_id,
                    'qty'          => (int) $s->qty,
                    'unit_price'   => (float) $s->unit_price,
                    'total_sale'   => (float) $s->total_sale,
                    'total_profit' => (float) $s->total_profit,
                    'created_at'   => $s->created_at,
                    'productName'  => $s->product?->name ?? 'Inconnu',
                    'employeeName' => $s->employee?->name ?? 'N/A',
                ];
            })
        ]);
    }
}
