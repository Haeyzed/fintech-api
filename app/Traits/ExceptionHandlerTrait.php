<?php

namespace App\Traits;

use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Exception;

/**
 * Trait ExceptionHandlerTrait
 *
 * Provides methods for handling exceptions in API controllers.
 *
 * @package App\Traits
 */
trait ExceptionHandlerTrait
{
    /**
     * Handle various types of exceptions and return appropriate JSON responses.
     *
     * @param Exception $e The caught exception
     * @param string $action The action being performed when the exception occurred
     * @return JsonResponse
     */
    protected function handleException(Exception $e, string $action): JsonResponse
    {
        if ($e instanceof ApiException) {
            return $this->handleApiException($e);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return $this->handleNotFoundException($e);
        }

        Log::error("Error in " . class_basename($this) . " while {$action}: " . $e->getMessage());
        return response()->internalServerError("An error occurred while {$action}.");
    }

    /**
     * Handle ApiException instances.
     *
     * @param ApiException $e The caught ApiException
     * @return JsonResponse
     */
    protected function handleApiException(ApiException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'error_code' => $e->getErrorCode()
        ], $e->getStatusCode());
    }

    /**
     * Handle ModelNotFoundException and NotFoundHttpException instances.
     *
     * @param Exception $e The caught exception
     * @return JsonResponse
     */
    protected function handleNotFoundException(Exception $e): JsonResponse
    {
        $modelName = $e instanceof ModelNotFoundException
            ? class_basename($e->getModel())
            : 'Resource';

        return response()->notFound("{$modelName} not found");
    }
}
