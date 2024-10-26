<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Notifications\FCMNotification;
use Exception;
use Illuminate\Support\Facades\Notification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;

class FCMService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Validate a single FCM token.
     *
     * @param string $token
     * @return bool
     * @throws FirebaseException
     */
    public function validateToken(string $token): bool
    {
        try {
            $this->messaging->validateRegistrationTokens([$token]);
            return true;
        } catch (MessagingException $e) {
            return false;
        }
    }

    /**
     * Validate multiple FCM tokens.
     *
     * @param array $tokens
     * @return array
     * @throws FirebaseException
     */
    public function validateTokens(array $tokens): array
    {
        $invalidTokens = [];
        foreach ($tokens as $token) {
            if (!$this->validateToken($token)) {
                $invalidTokens[] = $token;
            }
        }
        return $invalidTokens;
    }

    /**
     * Send a Firebase Cloud Message to a single device.
     *
     * @param string $token The FCM token of the target device
     * @param array $notification The notification payload
     * @param array $data Additional data payload
     * @param string|null $image URL of the image to be included in the notification
     * @return array The FCM API response
     * @throws Exception|FirebaseException If the API request fails
     */
    public function sendToDevice(string $token, array $notification, array $data = [], ?string $image = null): array
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withDefaultSounds() // Enables default notifications sounds on iOS and Android devices.
            ->withApnsConfig(
                ApnsConfig::new()
                    ->withSound('bingbong.aiff')
                    ->withBadge(1)
            )
            ->withData($data);

        if ($image) {
            $message = $message->withWebpushConfig([
                'notification' => [
                    'image' => $image
                ]
            ]);
        }

        try {
            $response = $this->messaging->send($message);
            $this->logNotificationInDatabase($token, $notification, $data, $image);
            return ['success' => true, 'response' => $response];
        } catch (MessagingException $e) {
            throw new Exception('FCM request failed: ' . $e->getMessage());
        }
    }

    /**
     * Send a Firebase Cloud Message to multiple devices.
     *
     * @param array $tokens An array of FCM tokens of the target devices
     * @param array $notification The notification payload
     * @param array $data Additional data payload
     * @param string|null $image URL of the image to be included in the notification
     * @return array The FCM API responses
     * @throws Exception|FirebaseException If the API request fails
     */
    public function sendToDevices(array $tokens, array $notification, array $data = [], ?string $image = null): array
    {
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data);

        if ($image) {
            $message = $message->withWebpushConfig([
                'notification' => [
                    'image' => $image
                ]
            ]);
        }

        try {
            $response = $this->messaging->sendMulticast($message, $tokens);
            foreach ($tokens as $token) {
                $this->logNotificationInDatabase($token, $notification, $data, $image);
            }
            return [
                'success' => true,
                'responses' => [
                    'success_count' => $response->successes()->count(),
                    'failure_count' => $response->failures()->count(),
                    'tokens_with_errors' => $this->getTokensWithErrors($response),
                ],
            ];
        } catch (MessagingException $e) {
            throw new Exception('FCM request failed: ' . $e->getMessage());
        }
    }

    /**
     * Get tokens with errors from the MulticastSendReport.
     *
     * @param MulticastSendReport $report
     * @return array
     */
    private function getTokensWithErrors(MulticastSendReport $report): array
    {
        $tokensWithErrors = [];
        foreach ($report->failures()->getItems() as $failure) {
            $tokensWithErrors[] = [
                'token' => $failure->target()->value(),
                'error' => $failure->error()->getMessage(),
            ];
        }
        return $tokensWithErrors;
    }

    /**
     * Log the notification in the Laravel notification database.
     *
     * @param string $token
     * @param array $notification
     * @param array $data
     * @param string|null $image
     */
    private function logNotificationInDatabase(string $token, array $notification, array $data, ?string $image): void
    {
        $deviceToken = DeviceToken::where('token', $token)->first();
        if ($deviceToken && $deviceToken->user) {
            Notification::send($deviceToken->user, new FCMNotification($notification, $data, $image));
        }
    }
}
