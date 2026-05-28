<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Admin et manager voient tous les produits
        if (in_array($user->role, ['admin', 'manager'])) {
            return Product::with('sales')->get();
        }

        // Caissier/employé : voit uniquement les produits assignés à lui
        return Product::with('sales')
            ->where('declared_for_user_id', $user->id)
            ->get();
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if(!in_array($user->role, ['admin','manager'])){
            return response()->json(['message'=>'Forbidden'], 403);
        }

        if($user->role === 'admin'){
            $data = $request->validate([
                'name' => 'required|string',
                'purchase_price' => 'numeric',
                'cost_price' => 'numeric',
                'sale_price' => 'numeric',
                'stock' => 'integer|min:0',
                'declared_for_user_id' => 'nullable|integer'
            ]);
            $profit = ($data['sale_price'] ?? 0) - ($data['cost_price'] ?? 0);
            $product = Product::create(array_merge(
                $data,
                ['profit' => $profit, 'declared_by_user_id' => $user->id]
            ));
            return response()->json($product, 201);
        }

        // manager: must provide source_product_id to copy prices, cannot set prices or stock
        if($user->role === 'manager'){
            $data = $request->validate([
                'name'                 => 'required|string',
                'purchase_price'       => 'numeric|min:0',
                'cost_price'           => 'numeric|min:0',
                'sale_price'           => 'numeric|min:0',
                'stock'                => 'integer|min:0',
                'declared_for_user_id' => 'nullable|integer'
            ]);
            $profit = ($data['sale_price'] ?? 0) - ($data['cost_price'] ?? 0);
            $new = Product::create([
                'name'                 => $data['name'],
                'purchase_price'       => $data['purchase_price'] ?? 0,
                'cost_price'           => $data['cost_price'] ?? 0,
                'sale_price'           => $data['sale_price'] ?? 0,
                'profit'               => $profit,
                'stock'                => $data['stock'] ?? 0,
                'declared_by_user_id'  => $user->id,
                'declared_for_user_id' => $data['declared_for_user_id'] ?? null,
            ]);
            return response()->json($new, 201);
        }
    }

    public function destroy(Request $request, Product $product)
    {
        $user = $request->user();
        if($user->role === 'admin' || ($user->role === 'manager' && $product->declared_by_user_id === $user->id)){
            $product->delete();
            return response()->json(['message' => 'Produit supprimé']);
        }
        return response()->json(['message' => 'Forbidden'], 403);
    }

    public function show(Product $product)
    {
        return $product->load('sales');
    }

    // report per employee: products declared for employee and their sales
    public function reportForEmployee(Request $request, $employeeId)
    {
        $user = $request->user();
        $products = Product::where('declared_for_user_id', $employeeId)->get();
        $sales = Sale::whereIn('product_id', $products->pluck('id'))->get();

        $rows = [];
        foreach($products as $p){
            $salesForP = $sales->where('product_id', $p->id);
            foreach($salesForP as $s){
                $rows[] = [
                    'product' => $p->name,
                    'qty' => $s->qty,
                    'total_sale' => $s->total_sale,
                    'total_profit' => ($user->role==='admin') ? $s->total_profit : null,
                    'employee_id' => $employeeId,
                ];
            }
        }
        return response()->json($rows);
    }
}
