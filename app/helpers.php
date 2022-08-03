<?php

use Illuminate\Support\Facades\Http;

function createPremiumAccess($data) {
    $url = env("SERVICE_COURSE_URL")."api/my-courses/premium";

    try {
        $response = Http::post($url, $data);

        $responseData = $response->json();
        $responseData["http_data"] = $response->getStatusCode();

        return $responseData;
    } catch (\Throwable $th) {
        return [
            "status" => "error",
            "http_code" => 500,
            "message" => "service course unavailable"
        ];
    }
}