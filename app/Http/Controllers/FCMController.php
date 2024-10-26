<?php

namespace App\Http\Controllers;

use App\Rules\SqidExists;
use App\Services\FCMService;
use App\Traits\ExceptionHandlerTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kreait\Firebase\Exception\FirebaseException;
use Illuminate\Support\Facades\Log;
use App\Models\User;

/**
 * Class FCMController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags FCM
 *
 * ${Description}
 */
class FCMController extends Controller
{
    use ExceptionHandlerTrait;
    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send a notification to a single device.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws FirebaseException
     */
    public function sendToDevice(Request $request): JsonResponse
    {
        $request->validate([
            /**
             * The FCM token of the target device.
             * @var string $token
             * @example "fMIRMc1kF0M:APA91bHqL8xuNZhVJPzlXTov9m9r8KCWsFS..."
             */
            'token' => 'required|string',

            /**
             * The title of the notification.
             * @var string $title
             * @example "New Message"
             */
            'title' => 'required|string',

            /**
             * The body content of the notification.
             * @var string $body
             * @example "You have received a new message."
             */
            'body' => 'required|string',

            /**
             * Additional data payload for the notification.
             * @var array $data
             * @example {"message_id": "123", "sender": "John Doe", "link": "https://example.com/update"}
             */
            'data' => 'nullable|array',

            /**
             * The URL of the image to be included in the notification.
             * @var string $image
             * @example "https://example.com/notification-image.jpg"
             */
            'image' => 'nullable|url',
        ]);

        try {
            // Validate the token
            $isValid = $this->fcmService->validateToken($request->token);
            if (!$isValid) {
                return response()->badRequest('Invalid FCM token');
            }

            $response = $this->fcmService->sendToDevice(
                $request->token,
                [
                    'title' => $request->title,
                    'body' => $request->body,
                ],
                $request->data ?? [],
                $request->image
            );

            // Log the notification
            $this->logNotification($request->token, $request->title, $request->body, $request->data ?? [], $request->image);

            return response()->json($response);
        } catch (Exception $e) {
            return $this->handleException($e, 'sending FCM notification');
        }
    }

    /**
     * Send a notification to multiple devices.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws FirebaseException
     */
    public function sendToDevices(Request $request): JsonResponse
    {
        $request->validate([
            /**
             * An array of FCM tokens for the target devices.
             * @var array $tokens
             * @example ["fMIRMc1kF0M:APA91bHqL8xuNZhVJPzlXTov9m9r8KCWsFS...", "cMIRMc2kF1N:BPB92bHqL9xuNZhVJPzlXTov0m0r9KCWsGT..."]
             */
            'tokens' => 'required|array',
            'tokens.*' => 'required|string',

            /**
             * The title of the notification.
             * @var string $title
             * @example "New Update"
             */
            'title' => 'required|string',

            /**
             * The body content of the notification.
             * @var string $body
             * @example "A new version of the app is available."
             */
            'body' => 'required|string',

            /**
             * Additional data payload for the notification.
             * @var array $data
             * @example {"version": "2.0", "link": "https://example.com/update"}
             */
            'data' => 'nullable|array',

            /**
             * The URL of the image to be included in the notification.
             * @var string $image
             * @example "https://example.com/notification-image.jpg"
             */
            'image' => 'nullable|url',
        ]);

        try {
            // Validate all tokens
            $invalidTokens = $this->fcmService->validateTokens($request->tokens);
            if (!empty($invalidTokens)) {
                return response()->badRequest('Invalid FCM tokens');
            }

            $response = $this->fcmService->sendToDevices(
                $request->tokens,
                [
                    'title' => $request->title,
                    'body' => $request->body,
                ],
                $request->data ?? [],
                $request->image
            );

            // Log the notifications
            foreach ($request->tokens as $token) {
                $this->logNotification($token, $request->title, $request->body, $request->data ?? [], $request->image);
            }

            return response()->json($response);
        } catch (Exception $e) {
            return $this->handleException($e, 'sending FCM notification');
        }
    }

    /**
     * Send a login notification.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws FirebaseException
     */
    public function sendLoginNotification(Request $request): JsonResponse
    {
        $request->validate([
            /**
             * The SQID of the user to send the login notification to.
             * @var string $user_id
             * @example "01H3JTXQXQJ8N8Z9XY1Q7B0000"
             */
            'user_id' => ['required', new SqidExists('users')],

            /**
             * The URL of the image to be included in the notification.
             * @var string $image
             * @example "https://example.com/login-notification-image.jpg"
             */
            'image' => 'nullable|url',
        ]);

        try {
            $user = User::findBySqidOrFail($request->user_id);
            if (!$user->device_token) {
                return response()->badRequest('User does not have a device token');
            }

            $response = $this->fcmService->sendToDevice(
                $user->device_token,
                [
                    'title' => 'New Login',
                    'body' => 'Your account was just logged into.',
                ],
                ['type' => 'login_notification'],
                $request->image
            );

            // Log the notification
            $this->logNotification($user->device_token, 'New Login', 'Your account was just logged into.', ['type' => 'login_notification'], $request->image);

            return response()->json($response);
        } catch (Exception $e) {
            return $this->handleException($e, 'sending login notification');
        }
    }

    /**
     * Log the notification.
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string|null $image
     */
    private function logNotification(string $token, string $title, string $body, array $data, ?string $image = null): void
    {
        Log::info('FCM Notification sent', [
            'token' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'image' => $image,
        ]);
    }
}
