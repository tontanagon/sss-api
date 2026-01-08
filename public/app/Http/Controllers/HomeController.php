<?php

namespace App\Http\Controllers;

use App\Models\BookingHistory;
use App\Models\Category;
use App\Models\CoreConfigs;
use App\Models\CoreConfigs\Banner;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function CategorySelector()
    {

        $categories = Category::where('status', 'active')->get();

        if (empty($categories)) {
            return response()->json([
                'status' => false,
                'message' => 'Category active not found'
            ], 404);
        }

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'id' => $category->id,
                'name' => $category->name,
                'image' => $category->image,
            ];
        }

        return response()->json($data, 200);
    }

    public function AllProduct(Request $request)
    {
        $paginate = 25;
        $page = $request->page ?? 1;
        $search_text = $request->search_text ?? '';

        $products = Product::with('type', 'categories')
            ->where(function ($query) use ($search_text) {
                $query->where('name', 'like', "%{$search_text}%")
                    ->orWhere('code', 'like', "%{$search_text}%");
            })
            ->where('status', 'active')->paginate($paginate, ['*'] ,'page', $page);

        if (empty($products)) {
            return response()->json([
                'status' => false,
                'message' => 'Product active not found'
            ], 404);
        }

        $data = [];
        foreach ($products->items() as $product) {
            $cate = [];
            foreach ($product->categories as $category) {
                $cate[] = $category->name;
            }

            $data[] = [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'image' => $product->image,
                'stock' => $product->stock,
                'unit' => $product->unit,
                'type' => $product->type->name,
                'category' => $cate,
            ];
        }

        $productsArray = $products->toArray();
        $productsArray['data'] = $data;

        return response()->json($productsArray, 200);
    }

    public function getBanner()
    {
        $banner = Banner::where('status', 'active')->get();

        $another_banner = CoreConfigs::where('group', 'banner')
            ->where('category', '!=', 'banner')
            ->where('status', 'active')
            ->get();

        $another_banner_data = [];
        foreach ($another_banner as $value) {
            $booking = BookingHistory::with('itemBookingHistories')->where('status', $value->category)->get();
            if ($booking->isNotEmpty()) {
                $data = [
                    'title' => $value->title,
                    'data' => $booking
                ];
                $another_banner_data[] = $data;
            }
        }


        return response()->json([
            'banner_data' => $banner,
            'another_banner_data' => $another_banner_data,
        ], 200);
    }
}
