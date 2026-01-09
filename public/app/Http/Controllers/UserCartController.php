<?php

namespace App\Http\Controllers;

use App\Models\CoreConfigs\Subject;
use App\Models\Product;
use App\Models\User;
use App\Models\UserCart;
use Illuminate\Http\Request;

class UserCartController extends Controller
{
    public function getCart(Request $request)
    {
        // $user = auth('sanctum')->user();
        $user = User::find(1);

        $cart = UserCart::where('user_id', $user->id)->first();
        if (!$cart) {
            return response()->json([
                'cart_items' => [],
            ], 200);
        }

        $data = [];
        $data['cart_items'] = $cart->cart_items;

        return response()->json($data, 200);
    }

    public function addCart(Request $request)
    {
        $user = auth('sanctum')->user();
        $new_item = $request->cart_items;
        $product_stock = Product::select('stock')->where('id', $new_item['id'])->first();

        $MAX_ITEMS = 200;
        // UserCart::where('user_id', $user->id)->first();

        if ($new_item['quantity'] > $product_stock->stock) {
            return response()->json([
                'status' => false,
                'message' => 'จำนวนวัสดุ-อุปกรณ์มากกว่าของในคลัง กรุณารีเฟรชหน้าแล้วลองอีกครั้ง',
            ], 422);
        }

        if ($new_item['quantity'] > $MAX_ITEMS) {
            return response()->json([
                'status' => false,
                'message' => 'จำนวนวัสดุ-อุปกรณ์ทั้งหมด สามารถยืมได้สูงสุด 200 ต่อ 1 การจอง',
            ], 422);
        }

        $cart = $user->userCart;
        if (!$cart) {
            $cart = $user->UserCart()->create([
                'cart_items' => json_encode([$new_item])
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Add cart successful',
                'cart_items' => [$new_item],
            ], 200);
        }

        $cart_items = json_decode($cart->cart_items, true) ?? [];
        $cart_count  = $new_item['quantity'] + collect($cart_items)->sum('quantity');
        if ($cart_count > $MAX_ITEMS) {
            return response()->json([
                'status' => false,
                'message' => 'จำนวนวัสดุ-อุปกรณ์ทั้งหมด สามารถยืมได้สูงสุด 200 ต่อ 1 การจอง',
            ], 422);
        }

        $foundIndex = collect($cart_items)->search(function ($item) use ($new_item) {
            return is_array($item) && isset($item['id']) && $item['id'] == $new_item['id'];
        });

        if ($foundIndex !== false) {
            if ($new_item['quantity'] > 0) {
                $cart_items[$foundIndex]['quantity'] += $new_item['quantity'];
            } else {
                unset($cart_items[$foundIndex]);
                $cart_items = array_values($cart_items);
            }
        } else {
            if ($new_item['quantity'] > 0) {
                $cart_items[] = $new_item;
            }
        }

        $cart->cart_items = json_encode($cart_items);
        $cart->save();

        return response()->json([
            'status' => true,
            'message' => 'Update cart successful',
            'cart_items' => $cart_items,
        ], 200);
    }

    public function updateCart(Request $request)
    {
        $user = auth('sanctum')->user();
        $new_item = $request->cart_items;

        $cart = UserCart::where('user_id', $user->id)->first();

        $cart_items = json_decode($cart->cart_items, true) ?? [];

        $foundIndex = collect($cart_items)->search(function ($item) use ($new_item) {
            return is_array($item) && isset($item['id']) && $item['id'] == $new_item['id'];
        });

        if ($new_item['quantity'] > 0) {
            $cart_items[$foundIndex]['quantity'] = $new_item['quantity'];
        } else {
            unset($cart_items[$foundIndex]);
            $cart_items = array_values($cart_items);
        }

        $cart->cart_items = json_encode($cart_items);
        $cart->save();

        return response()->json([
            'status' => true,
            'message' => 'Update cart successful',
        ], 200);
    }

    public function getSubjectSelect()
    {
        $subjects = Subject::where("status", 'active')->get();

        if (!$subjects) {
            return response()->json([
                'status' => false,
                'message' => 'Subject not found.',
            ], 404);
        }

        $data = [];

        foreach ($subjects as $subject) {
            $data[] = [
                "value" => $subject->code,
                "label" => "{$subject->code} {$subject->name}",
            ];
        }

        return response()->json($data, 200);
    }
}

