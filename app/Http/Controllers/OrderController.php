<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Midtrans\Snap;
use Midtrans\Config;

class OrderController extends Controller
{
    private function getMidtransSnapUrl($params)
    {
        // Set your Merchant Server Key
        Config::$serverKey = env("MIDTRANS_SERVER_KEY");
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        Config::$isProduction = (bool) env("MIDTRANS_PRODUCTION");
        // Set sanitization on (default)
        Config::$isSanitized = (bool) env("MIDTRANS_SANITIZED");
        // Set 3DS transaction for credit card to true
        Config::$is3ds = (bool) env("MIDTRANS_3DS");

        $snap_url = Snap::createTransaction($params)->redirect_url;

        return $snap_url;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $request->input("user_id");

        $orders = Order::query();

        $orders->when($userId, function($query) use ($userId) {
            return $query->where("user_id", "=", $userId);
        });

        return response()->json([
            "status" => "success",
            "data" => $orders->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $request->input("user");
        $course = $request->input("user");

        $order = Order::create([
            "user_id" => $user["id"],
            "course_id" => $course["id"]
        ]);

        $transactionDetails = [
            "order_id" => $order->id."-".Str::random(6),
            "gross_amount" => $course["price"]
        ];

        $itemDetails = [
            [
                "id" => $course["id"],
                "price" => $course["price"],
                "quantity" => 1,
                "name" => $course["name"],
                "brand" => "Kuy Belajar",
                "category" => "Online Class",
            ]
        ];

        $customerDetails = [
            "first_name" => $user["name"],
            "email" => $user["email"],
        ];

        $midtransParams = [
            "transaction_details" => $transactionDetails,
            "item_details" => $itemDetails,
            "customer_details" => $customerDetails
        ];

        $midtransSnapUrl = $this->getMidtransSnapUrl($midtransParams);

        return $midtransSnapUrl;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
