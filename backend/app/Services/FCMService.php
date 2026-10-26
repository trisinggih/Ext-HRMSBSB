<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path('firebase_credentials.json'));
        
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Send push notification to a single device token
     *
     * @param string $token FCM Device Token
     * @param string $title Title of the notification
     * @param string $body Body of the notification
     * @param array $data Additional payload data
     */
    public function sendToDevice($token, $title, $body, $data = [])
    {
        if (empty($token)) return false;

        if (!empty($data)) {
            $data = array_map('strval', $data);
        }

        try {
            $notification = Notification::create($title, $body);
            $message = CloudMessage::new()->withToken($token)
                ->withNotification($notification)
                ->withData($data);

            return $this->messaging->send($message);
        } catch (\Exception $e) {
            Log::error('FCM Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification to multiple device tokens
     *
     * @param array $tokens Array of FCM Device Tokens
     * @param string $title Title of the notification
     * @param string $body Body of the notification
     * @param array $data Additional payload data
     */
    public function sendToMultipleDevices(array $tokens, $title, $body, $data = [])
    {
        // Filter out empty tokens
        $tokens = array_filter($tokens);
        if (empty($tokens)) return false;

        if (!empty($data)) {
            $data = array_map('strval', $data);
        }

        try {
            $notification = Notification::create($title, $body);
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data);

            return $this->messaging->sendMulticast($message, $tokens);
        } catch (\Exception $e) {
            Log::error('FCM Multicast Error: ' . $e->getMessage());
            return false;
        }
    }
}
