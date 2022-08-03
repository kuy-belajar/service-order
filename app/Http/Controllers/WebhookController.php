<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handler(Request $request)
    {
        $data = $request->all();

        $signatureKey = $data["signature_key"];
        $orderId = $data["order_id"];
        $statusCode = $data["status_code"];
        $grossAmount = $data["gross_amount"];
        $transactionStatus = $data["transaction_status"];
        $paymentType = $data["payment_type"];
        $fraudStatus = $data["fraud_status"];

        $serverKey = env("MIDTRANS_SERVER_KEY");

        $validSignatureKey = hash("sha512", $orderId.$statusCode.$grossAmount.$serverKey);

        if ($signatureKey !== $validSignatureKey) {
            return response()->json([
                "status" => "error",
                "message" => "invalid signature"
            ], 400);
        }

        $validOrderId = explode("-", $orderId)[0];
        $order = Order::find($validOrderId);

        if (!$order) {
            return response()->json([
                "status" => "error",
                "message" => "order id not found"
            ], 404);
        }

        if ($order->status === "success") {
            return response()->json([
                "status" => "error",
                "message" => "operation not permitted"
            ], 405);
        }

        if ($transactionStatus == "capture"){
            if ($fraudStatus == "challenge"){
                
                $order->status = "challenge";

            } else if ($fraudStatus == "accept"){
                
                $order->status = "success";

            }
        } else if ($transactionStatus == "settlement"){
            
            $order->status = "success";

        } else if ($transactionStatus == "cancel" || $transactionStatus == "deny" || $transactionStatus == "expire"){
          
            $order->status = "failure";
            
        } else if ($transactionStatus == "pending"){
            
            $order->status = "pending";

        }

        $logData = [
            "status" => $transactionStatus,
            "raw_response" => json_encode($data),
            "order_id" => $validOrderId,
            "payment_type" => $paymentType,
        ];

        PaymentLog::create($logData);

        $order->save();

        if ($order->status === "success") {
            createPremiumAccess([
                "user_id" => $order->user_id,
                "course_id" => $order->course_id,
            ]);
        }

        return response()->json("ok");
    }
}
