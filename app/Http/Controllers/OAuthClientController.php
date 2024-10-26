<?php

namespace App\Http\Controllers;

use App\Http\Requests\{IndexRequest, OAuthClientRequest};
use App\Http\Resources\OAuthClientResource;
use App\Http\Resources\OAuthTokenResource;
use App\Traits\ExceptionHandlerTrait;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB, Hash};
use Laravel\Passport\{Client, ClientRepository, Token};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class OAuthClientController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags OAuth Clients
 *
 * ${Description}
 */
class OAuthClientController extends Controller
{
    use ExceptionHandlerTrait;

    protected ClientRepository $clientRepository;

    public function __construct(ClientRepository $clientRepository) {
        $this->clientRepository = $clientRepository;
    }

    /**
     * Display a listing of the OAuth clients.
     *
     * @param IndexRequest $request
     * @return JsonResponse
     */
    public function index(IndexRequest $request): JsonResponse
    {
        try {
            $query = Client::query()->when($request->search, fn($q, $search) => app('search')->apply($q, $search, ['name', 'client_app']))
                ->when($request->order_by, fn($q, $orderBy) => $q->orderBy($orderBy ?? 'name', $request->order_direction ?? 'asc'));

            $clients = $query->paginate($request->per_page ?? config('app.per_page'));
            return response()->paginatedSuccess(OAuthClientResource::collection($clients), 'OAuth clients retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching OAuth clients');
        }
    }

    /**
     * Store a newly created OAuth password grant client in storage.
     *
     * @param OAuthClientRequest $request
     * @return JsonResponse
     */
    public function store(OAuthClientRequest $request): JsonResponse
    {
        try {
            $client = DB::transaction(function () use ($request) {
                $client = $this->clientRepository->createPasswordGrantClient(
                    null,
                    $request->name,
                    $request->redirect ?? config('app.url'),
                );
                $client->vendor_id = $request->vendor_id;
                $client->vendor = $request->vendor_name;
                $client->client_app = $request->client_app;
                $client->save();

                return $client;
            });

            return response()->created(new OAuthClientResource($client->fresh()), 'OAuth password grant client created successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'creating the OAuth password grant client');
        }
    }

    /**
     * Display the specified OAuth client.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $client = $this->clientRepository->find($id);
            return response()->success(new OAuthClientResource($client), 'OAuth client retrieved successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'retrieving OAuth client');
        }
    }

    /**
     * Update the specified OAuth client in storage.
     *
     * @param OAuthClientRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(OAuthClientRequest $request, string $id): JsonResponse
    {
        try {
            $client = $this->clientRepository->find($id);
            DB::transaction(function () use ($client, $request) {
                $this->clientRepository->update(
                    $client,
                    $request->name,
                    $request->redirect ?? config('app.url'),
                );
                $client->vendor_id = $request->vendor_id;
                $client->vendor = $request->vendor_name;
                $client->client_app = $request->client_app;
                $client->save();

                return $client;
            });
            return response()->success(new OAuthClientResource($client->fresh()), 'OAuth client updated successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'updating OAuth client');
        }
    }

    /**
     * Remove the specified OAuth client from storage.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $client = $this->clientRepository->find($id);
            if (!$client->revoked){
                return response()->badRequest('Un-revoked oauth client cannot be deleted');
            }
            $this->clientRepository->delete($client);
            return response()->success(null, 'OAuth client deleted successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'deleting OAuth client');
        }
    }

    /**
     * Show the encrypted secret of the OAuth client.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function showSecret(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate(['password' => ['required', 'string']]);
            $client = Client::findOrFail($id);
            if (!Hash::check($request->password, auth()->user()->password)) {
                return response()->unauthorized('Invalid password');
            }
            return response()->success(['secret' => $client->secret], 'OAuth client secret retrieved successfully');
        } catch (NotFoundHttpException|ModelNotFoundException $e) {
            return $this->handleNotFoundException($e);
        } catch (Exception $e) {
            return $this->handleException($e, 'retrieving OAuth client secret');
        }
    }

    /**
     * Display a listing of access tokens for the authenticated user.
     *
     * @param IndexRequest $request
     * @return JsonResponse
     */
    public function listTokens(IndexRequest $request): JsonResponse
    {
        try {
            $tokens = auth()->user()->tokens()->paginate($request->per_page ?? config('app.per_page'));
            return response()->paginatedSuccess($tokens, 'Access tokens retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching access tokens');
        }
    }

    /**
     * Revoke the specified access token.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function revokeToken(string $id): JsonResponse
    {
        try {
            $client = $this->clientRepository->find($id);
            if ($client->personal_access_client == 1){
                return response()->badRequest('This client cannot be revoked.');
            }
            $this->clientRepository->delete($client);
            return response()->success(null, 'Access token revoked successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'revoking access token');
        }
    }

    /**
    use Laravel\Passport\Token;
    use App\Http\Resources\OAuthTokenResource;

    /**
     * Display a list of all OAuth tokens issued to users.
     *
     * @param IndexRequest $request
     * @return JsonResponse
     */
    public function listAllTokens(IndexRequest $request): JsonResponse
    {
        try {
            $query = Token::query()->with(['user', 'client'])
                ->when($request->search, fn($q, $search) => app('search')->apply($q, $search, ['name', 'client_app']))
                ->when($request->order_by, fn($q, $orderBy) => $q->orderBy($orderBy ?? 'name', $request->order_direction ?? 'asc'));

            $tokens = $query->paginate($request->per_page ?? config('app.per_page'));

            return response()->paginatedSuccess(OAuthTokenResource::collection($tokens), 'OAuth tokens retrieved successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'fetching all OAuth tokens');
        }
    }

    /**
     * Delete the specified access token.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function deleteToken(string $id): JsonResponse
    {
        try {
            $token = Token::find($id);

            if (!$token) {
                return response()->notFound('Token not found');
            }

            // Check if the token belongs to the authenticated user or if the user has permission to delete any token
            if ($token->user_id !== auth()->id() && !auth()->user()->can('delete-any-token')) {
                return response()->forbidden('You do not have permission to delete this token');
            }

            $token->delete();

            return response()->success(null, 'Access token deleted successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'deleting access token');
        }
    }
}
