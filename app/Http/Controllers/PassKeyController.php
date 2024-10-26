<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\PassKey;
use App\Models\User;
use App\Traits\ExceptionHandlerTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;

/**
 * Class PassKeyController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags PassKey, Authentication
 *
 * Handles all passkey-related operations including registration and authentication.
 */
class PassKeyController extends Controller
{
    use ExceptionHandlerTrait;

    /**
     * PassKeyController constructor.
     *
     * @param Server $server
     */
    public function __construct(private Server $server)
    {
    }

    /**
     * Create options for passkey registration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createOptions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $userEntity = new PublicKeyCredentialUserEntity(
                $user->username,
                $user->sqid,
                $user->name
            );

            $creationOptions = $this->server->generatePublicKeyCredentialCreationOptions(
                $userEntity,
                PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE
            );

            $request->session()->put('publicKeyCredentialCreationOptions', $creationOptions);

            return response()->json($creationOptions);
        } catch (Exception $e) {
            return $this->handleException($e, 'creating passkey options');
        }
    }

    /**
     * Register a new passkey for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $creationOptions = $request->session()->get('publicKeyCredentialCreationOptions');
            $credentialSource = $this->server->loadAndCheckAttestationResponse(
                $request->input('credential'),
                $creationOptions,
                $request
            );

            $passKey = new PassKey([
                'user_id' => $request->user()->id,
                'name' => $request->input('name', 'Default'),
                'credential_id' => $credentialSource->getPublicKeyCredentialId(),
                'public_key' => $credentialSource->getPublicKey(),
                'sign_count' => $credentialSource->getCounter(),
            ]);

            $passKey->save();

            return response()->success('Passkey registered successfully');
        } catch (Exception $e) {
            return $this->handleException($e, 'registering passkey');
        }
    }

    /**
     * Get authentication options for a user's passkeys.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOptions(Request $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->input('email'))->firstOrFail();

            $userEntity = new PublicKeyCredentialUserEntity(
                $user->username,
                $user->sqid,
                $user->name
            );

            $credentialSources = $user->passKeys->map(function ($passKey) {
                return new PublicKeyCredentialSource(
                    $passKey->credential_id,
                    'public-key',
                    [],
                    'none',
                    $passKey->public_key,
                    $passKey->user_id,
                    $passKey->sign_count
                );
            })->toArray();

            $requestOptions = $this->server->generatePublicKeyCredentialRequestOptions(
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                $credentialSources
            );

            $request->session()->put('publicKeyCredentialRequestOptions', $requestOptions);

            return response()->success($requestOptions);
        } catch (Exception $e) {
            return $this->handleException($e, 'getting passkey options');
        }
    }

    /**
     * Authenticate a user using a passkey.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticate(Request $request): JsonResponse
    {
        try {
            $requestOptions = $request->session()->get('publicKeyCredentialRequestOptions');
            $credential = $this->server->loadAndCheckAssertionResponse(
                $request->input('credential'),
                $requestOptions,
                null,
                $request
            );

            $passKey = PassKey::where('credential_id', $credential->getPublicKeyCredentialId())->firstOrFail();
            $user = $passKey->user;

            // Update the sign count
            $passKey->sign_count = $credential->getCounter();
            $passKey->last_used_at = now();
            $passKey->save();

            // Log the user in
            auth()->login($user);

            return response()->success(['user' => new UserResource($user)], 'Authentication successful');
        } catch (Exception $e) {
            return $this->handleException($e, 'authenticating with passkey');
        }
    }
}
