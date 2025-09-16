<?php

namespace App\Http\Controllers;

use App\Traits\Authorizable;
use Illuminate\Routing\Controller as RoutingController;
abstract class Controller extends RoutingController
{
//    use Authorizable;
    public static function inertiaSuccess($data, $redirectRouteName = 'dashboard', $message = 'Success', $statusCode = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'redirect' => route($redirectRouteName, absolute: false),
        ], $statusCode);
    }
    public static function success($data = [], $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public function rawSuccess($data, $message = 'Success', $statusCode = 200)
    {
        return response()->json($data, $statusCode);
    }

    public static function error($message = 'An error occurred', $statusCode = 400, $errors = [], $extraData = [])
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
            'extra_data' => $extraData,
        ], $statusCode);
    }

    public static function notfound($message = 'Record not found', $statusCode = 204)
    {
        return response()->json([
            'status' => false,
            'message' => $message,

        ], $statusCode);
    }

    public static function warning($message = 'Record not found', $statusCode = 409)
    {
        return response()->json([
            'warning' => true,
            'message' => $message,

        ], $statusCode);
    }
}
