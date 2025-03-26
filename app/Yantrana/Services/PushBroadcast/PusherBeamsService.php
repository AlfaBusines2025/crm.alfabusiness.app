<?php

namespace App\Yantrana\Services\PushBroadcast;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PusherBeamsService
{
    protected $client;
    protected $instanceId;
    protected $secretKey;

    public function __construct()
    {
        $this->instanceId = config('services.pusher_beams.instance_id');
        $this->secretKey = config('services.pusher_beams.secret_key');

        $this->client = new Client([
            'base_uri' => "https://{$this->instanceId}.pushnotifications.pusher.com",
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$this->secretKey}",
            ],
        ]);
    }

    /**
     * Enviar una notificación push a un interés específico.
     *
     * @param string $interest
     * @param string $title
     * @param string $body
     * @param string $icon
     * @param string $url
     * @return bool
     */
    public function sendPushNotification(string $interest, string $title, string $body, string $icon, string $url, array $additionalData = []): bool
    {
		
        $notification = [
            "title" => $title,
            "body" => $body,
            "icon" => "https://crm.alfabusiness.app/media-storage/small_logo/675f22f4dcb21---logo-mini-alfa-whatscrm.png",
			"deep_link" => $url, // Agregar el URL aquí
            "data" => [
                "url" => $url // Incluir la URL en los datos de la notificación
            ]
        ];
		
		
		// Si existe 'assigned_user_id' y no es null, agregarlo a los intereses
        if ($additionalData['assigned_user_id'] != "no_assigned_user") {
            $assignedUserId = $additionalData['assigned_user_id'];

            // Agregar el 'assigned_user_id' a los intereses
            $interest= "user_{$assignedUserId}";
            // Opcional: Incluir 'assigned_user_id' en los datos de la notificación
            //$notification['data']['assigned_user_id'] = $assignedUserId;
        }


        $data = [
            "interests" => [$interest],
            "web" => [
                "notification" => $notification,
            ]
        ];

        try {
            $response = $this->client->post("/publish_api/v1/instances/{$this->instanceId}/publishes", [
                'json' => $data,
            ]);

            if ($response->getStatusCode() === 200) {
                Log::info("Notificación push enviada exitosamente a interés '{$interest}'.");
                return true;
            } else {
                Log::error("Error al enviar notificación push: " . $response->getBody());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Excepción al enviar notificación push: " . $e->getMessage());
            return false;
        }
    }
}