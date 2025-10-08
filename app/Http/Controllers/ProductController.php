<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('vendor', 'category')->paginate(20);

        return view('products.index', compact('products'));
    }

    public function categories()
    {
        $categories = Category::all();

        return view('products.categories', compact('categories'));
    }

    public function category($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $products = Product::where('category_id', $category->id)->paginate(20);

        return view('products.category', compact('category', 'products'));
    }

    public function show($id)
    {
        $product = Product::with('vendor', 'category')->findOrFail($id);

        return view('products.show', compact('product'));
    }

    public function quickView($id)
    {
        $product = Product::findOrFail($id);

        return response()->json($product);
    }

    public function priceHistory($id)
    {
        $product = Product::findOrFail($id);

        // Add price history logic here
        return response()->json(['history' => []]);
    }

    public function toggleFavorite($id, Request $request)
    {
        // Add favorite logic here
        return response()->json(['success' => true]);
    }
}
