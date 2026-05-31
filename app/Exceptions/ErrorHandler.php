<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

// JWT Exception
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class ErrorHandler
{
    public static function handle(Exception $e, bool $isToken = false): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return self::handleValidationException($e);
        } else if ($e instanceof QueryException) {
            return self::handleQueryException($e);
        } else if ($e instanceof ModelNotFoundException) {
            return self::handleModelNotFoundException($e);
        } else if ($e instanceof HttpException) {
            return self::handleHttpException($e);
        } else if($e instanceof TokenInvalidException) {
            return self::handleTokenInvalidException($e);
        } else if ($e instanceof TokenExpiredException) {
            return self::handleTokenExpiredException($e);
        } else {
            return self::handleGenericException($e, $isToken);
        }
    }

    private static function handleTokenInvalidException(TokenInvalidException $e): JsonResponse
    {
        return response()->json([
            'status' => 'Invalid Token',
            'message' => $e->getMessage(),
        ], 400);
    }

    private static function handleTokenExpiredException(TokenExpiredException $e): JsonResponse
    {
        return response()->json([
            'status' => 'Expired Token',
            'message' => $e->getMessage(),
        ], 401);
    }

    private static function handleValidationException(ValidationException $e): JsonResponse
    {
        return response()->json([
            'status' => 'Validation Failed',
            'message' => $e->getMessage(),
            'errors' => $e->validator->errors()
        ], 422);
    }

    private static function handleQueryException(QueryException $e): JsonResponse
    {
        Log::error('Database query error', [
            'message' => $e->getMessage(),
            'sql' => $e->getSql(),
            'bindings' => $e->getBindings(),
        ]);

        return response()->json([
            'status' => 'Database Error',
            'message' => 'Terjadi masalah pada database. Pastikan migrasi sudah dijalankan atau hubungi administrator.'
        ], 500);
    }

    private static function handleModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        return response()->json([
            'status' => 'Model Not Found',
            'message' => $e->getMessage()
        ], 404);
    }

    private static function handleHttpException(HttpException $e): JsonResponse
    {
        return response()->json([
            'status' => 'HTTP Error',
            'message' => $e->getMessage(),
        ], $e->getStatusCode());
    }

    private static function handleGenericException(Exception $e, $isToken): JsonResponse
    {
        if ($isToken) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Access denied. Token not found',
            ], 401);
        }

        return response()->json([
            'status' => 'Internal Server Error',
            'message' => $e->getMessage()
        ], 500);
    }
}
