<?php

namespace App\Http\Controllers;

abstract class Controller
{
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

    public static function error($message = 'An error occurred', $statusCode = 400, $errors = [])
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
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
