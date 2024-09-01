<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    public function errorResponse($message, $errors, $code) {
        return response()->json([
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    public function successResponse($message, $data, $code = 200) {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public function extractErrors($errors) {
        $result = [];
        array_walk_recursive($errors, function ($item) use (&$result) {
            $result[] = $item;
        });
        return $result;
    }
}
