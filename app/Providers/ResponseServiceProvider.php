<?php

namespace App\Providers;

use App\Helpers\TranslateTextHelper;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response as HTTPResponse;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Response::macro('success', function ($data = null, $message = 'Success') {
            return Response::json([
                'success' => true,
                'message' => TranslateTextHelper::translate($message),
                'data' => $data,
            ], HTTPResponse::HTTP_OK);
        });

        Response::macro('paginatedSuccess', function ($data, $message = 'Success') {
            return Response::json([
                'success' => true,
                'message' => TranslateTextHelper::translate($message),
                'data' => $data->items(),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ],
            ], HTTPResponse::HTTP_OK);
        });

        Response::macro('created', function ($data = null, $message = 'Resource created successfully') {
            return Response::json([
                'success' => true,
                'message' => TranslateTextHelper::translate($message),
                'data' => $data,
            ], HTTPResponse::HTTP_CREATED);
        });

        Response::macro('noContent', function ($message = 'No content') {
            return Response::json([
                'success' => true,
                'message' => TranslateTextHelper::translate($message),
            ], HTTPResponse::HTTP_NO_CONTENT);
        });

        Response::macro('badRequest', function ($message = 'Bad request') {
            return Response::json([
                'success' => false,
                'message' => TranslateTextHelper::translate($message),
            ], HTTPResponse::HTTP_BAD_REQUEST);
        });

        Response::macro('unauthorized', function ($message = 'Unauthorized access') {
            return Response::json([
                'success' => false,
                'message' => TranslateTextHelper::translate($message),
            ], HTTPResponse::HTTP_UNAUTHORIZED);
        });

        Response::macro('forbidden', function ($message = 'Forbidden access') {
            return Response::json([
                'success' => false,
                'message' => TranslateTextHelper::translate($message),
            ], HTTPResponse::HTTP_FORBIDDEN);
        });

        Response::macro('notFound', function ($message = 'Resource not found') {
            return Response::json([
                'success' => false,
                'message' => TranslateTextHelper::translate($message),
            ], HTTPResponse::HTTP_NOT_FOUND);
        });

        Response::macro('internalServerError', function ($message = 'Internal server error') {
            return Response::json([
                'success' => false,
                'message' => TranslateTextHelper::translate($message),
            ], HTTPResponse::HTTP_INTERNAL_SERVER_ERROR);
        });

        Response::macro('unprocessableEntity', function ($errors, $message = 'Unprocessable entity') {
            return Response::json([
                'success' => false,
                'message' => TranslateTextHelper::translate($message),
                'errors' => $errors,
            ], HTTPResponse::HTTP_UNPROCESSABLE_ENTITY);
        });
    }
}
