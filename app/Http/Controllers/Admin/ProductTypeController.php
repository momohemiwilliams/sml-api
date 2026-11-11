<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    public function index()
    {
        $types = ProductType::withCount('securities')->get();
        return view('admin.product_types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_types',
            'description' => 'nullable|string',
        ]);

        ProductType::create($validated);

        return back()->with('success', 'Product Type created successfully.');
    }

    public function update(Request $request, ProductType $productType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_types,name,' . $productType->id,
            'description' => 'nullable|string',
        ]);

        $productType->update($validated);

        return back()->with('success', 'Product Type updated successfully.');
    }

    public function destroy(ProductType $productType)
    {
        if ($productType->securities_count > 0) {
            return back()->with('error', 'Cannot delete Product Type with existing securities.');
        }

        $productType->delete();

        return back()->with('success', 'Product Type deleted successfully.');
    }
}
