<?php

if (!function_exists('error_response')) {
    function error_response($message, $errors, $code) {
        return response()->json([
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
}

if (!function_exists('success_response')) {
    function success_response($message, $data = [], $code = 200) {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], $code);
    }
}

if (!function_exists('extract_errors')) {
    function extract_errors($errors) {
        $result = [];
        array_walk_recursive($errors, function ($item) use (&$result) {
            $result[] = $item;
        });
        return $result;
    }
}