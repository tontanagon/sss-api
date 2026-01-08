<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryPageController extends Controller
{
    public function getProductByCategory(Request $request, $cate)
    {
        // $products = Product::with(['categories', 'type'])
        //     ->where('status', 'active')
        //     ->whereHas('categories', function ($q) use ($cate) {
        //         $q->with('products');
        //         $q->where('id', $cate);
        //     })
        //     ->get();

        // dd($products[0]->categories[0]->products[0]);
        // if (isset($products)) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Category not found'
        //     ], 404);
        // }

        $search_text = '';
        $paginate = 25;

        if (isset($request->search_text)) {
            $search_text = $request->search_text;
        }

        $category = Category::with(['products' => function ($q) use ($search_text) {
            $q->with('type')
                ->where('status', 'active')
                ->where(function ($sub) use ($search_text) {
                    $sub->where('name', 'like', "%{$search_text}%")
                        ->orWhere('code', 'like', "%{$search_text}%");
                });
        }])
            ->where('id', $cate)
            ->where('status', 'active')
            ->paginate($paginate);

        // ->first();
        if (empty($category)) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $products = $category->items()[0]->products;
        $cate_name = $category->items()[0]->name;

        $data = [];
        foreach ($products as $product) {
            $cate_product = [];

            foreach ($product->categories as $cate) {
                $cate_product[] = $cate->name;
            }

            $data[] = [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'image' => $product->image,
                'stock' => $product->stock,
                'unit' => $product->unit,
                'type' => $product->type->name,
                'category' => $cate_product,
            ];
        }
        // dd($data);
        $categoryArray = $category->toArray();
        $categoryArray['data'] = $data;

        return response()->json([
            'cate_name' => $cate_name,
            'cate_data_paginate' => $categoryArray
        ], 200);
    }
}
