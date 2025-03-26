<?php
/**
* WhatsAppMessageLogRepository.php - Repository file
*
* This file is part of the WhatsAppService component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel;
use App\Yantrana\Components\WhatsAppService\Interfaces\WhatsAppMessageLogRepositoryInterface;
use App\Yantrana\Services\PushBroadcast\PusherBeamsService; // Importar el servicio PusherBeamsService
use App\Yantrana\Components\Contact\Models\ContactModel; // Importar el modelo ContactModel

class WhatsAppMessageLogRepository extends BaseRepository implements WhatsAppMessageLogRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = WhatsAppMessageLogModel::class;

    /**
     * Método privado para centralizar el envío de notificaciones push
     *
     * @param string $title
     * @param string $body
     * @param int $vendorId
     * @param string $url
     * @return void
     */
    private function sendPushNotification(string $title, string $body, int $vendorId, string $url, string $contactUid)
    {
        // Truncar título y cuerpo a 25 caracteres
        $truncatedTitle = mb_strlen($title) > 25 ? mb_substr($title, 0, 22) . '...' : $title;
        $truncatedBody = mb_strlen($body) > 25 ? mb_substr($body, 0, 22) . '...' : $body;

        // Obtener una instancia del servicio PusherBeamsService
        $beamsService = app(PusherBeamsService::class);

        // Definir el interés (interest) basado en vendorId
        $interest = "vendor_{$vendorId}";

        // Definir el ícono del CRM logo
        $icon = asset('images/crm-logo.png'); // Reemplaza con la ruta real de tu ícono

        // Enviar la notificación push con la URL
        $beamsService->sendPushNotification($interest, $truncatedTitle, $truncatedBody, $icon, $url);
    }

    /**
     * Método privado para extraer el nombre del contacto desde el payload del webhook de WhatsApp.
     *
     * @param array $payload
     * @return string|null
     */
    private function extractContactName(array $payload): ?string
    {
        // Verificar si 'entry' existe y es un arreglo
        if (!isset($payload['entry']) || !is_array($payload['entry'])) {
            return null;
        }

        foreach ($payload['entry'] as $entry) {
            // Verificar si 'changes' existe y es un arreglo
            if (!isset($entry['changes']) || !is_array($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                // Verificar si 'value' existe y es un arreglo
                if (!isset($change['value']) || !is_array($change['value'])) {
                    continue;
                }

                $value = $change['value'];

                // Verificar si 'contacts' existe y es un arreglo
                if (!isset($value['contacts']) || !is_array($value['contacts'])) {
                    continue;
                }

                foreach ($value['contacts'] as $contact) {
                    // Verificar si 'profile' y 'name' existen
                    if (isset($contact['profile']) && isset($contact['profile']['name'])) {
                        return $contact['profile']['name'];
                    }
                }
            }
        }

        // Si no se encontró el nombre, retornar null
        return null;
    }

    /**
     * Obtener el nombre completo del contacto desde la base de datos usando el $contactId
     *
     * @param int $contactId
     * @return string|null
     */
    private function getContactFullName(int $contactId): ?string
    {
        $contact = ContactModel::find($contactId, ['first_name', 'last_name', '_uid']);
        if ($contact) {
            return trim("{$contact->first_name} {$contact->last_name}");
        }
        return null;
    }

    /**
     * Obtener el _uid del contacto desde la base de datos usando el $contactId
     *
     * @param int $contactId
     * @return string|null
     */
    private function getContactUid(int $contactId): ?string
    {
        $contact = ContactModel::find($contactId, ['_uid']);
        if ($contact) {
            return $contact->_uid;
        }
        return null;
    }

    public function updateOrCreateWhatsAppMessageFromWebhook(
        $phoneNumberId,
        $contactId,
        $vendorId,
        $messageRecipientId,
        $messageWamid,
        $messageStatus,
        $messageEntry,
        $message = '',
        $timestamp = null,
        ?array $mediaData = null,
        ?bool $preventCreation = false,
        ?array $options = []
    ) {
        if (!empty($options)) {
            $options = array_merge([
                'bot_reply' => false,
                'interaction_message_data' => null,
            ], $options);
        }

        $findTheExistingLogEntry = [
            'contact_wa_id' => (string) $messageRecipientId,
            'vendors__id' => $vendorId,
        ];

        if (isset($options['message_log_id'])) {
            $findTheExistingLogEntry['_id'] = $options['message_log_id'];
        } else {
            $findTheExistingLogEntry['wamid'] = $messageWamid;
        }

        $messageLogModel = $this->fetchIt($findTheExistingLogEntry);

        // Si no existe y se debe prevenir la creación, retornar false
        if (__isEmpty($messageLogModel) && $preventCreation) {
            return false;
        }

        $dataToUpdate = [
            'is_incoming_message' => 0,
        ];

        if (!__isEmpty($messageLogModel)) {
            if ($messageLogModel->status != 'read') {
                $dataToUpdate['status'] = $messageStatus;
            }
        } else {
            $dataToUpdate['status'] = $messageStatus;
        }

        if ($timestamp && ($messageStatus == 'delivered')) {
            $dataToUpdate['messaged_at'] = Carbon::createFromTimestamp($timestamp);
        }

        if ($message || $mediaData) {
            $dataToUpdate['message'] = $message;
            $dataToUpdate['wab_phone_number_id'] = $phoneNumberId;
            $dataToUpdate['__data'] = [
                'options' => Arr::only($options, [
                    'bot_reply',
                    'ai_bot_reply',
                ]),
                'interaction_message_data' => $options['interaction_message_data'] ?? null,
                'initial_response' => [
                    $messageStatus => $messageEntry,
                ],
            ];
            if (!empty($mediaData)) {
                $dataToUpdate['__data']['media_values'] = $mediaData;
            }
        }

        if (__isEmpty($messageLogModel)) {
            $dataToUpdate['contacts__id'] = $contactId;

            $storedMessage = $this->storeIt(arrayExtend($findTheExistingLogEntry, $dataToUpdate));

            // Obtener el nombre completo del contacto usando $contactId
            $contactFullName = $this->getContactFullName($contactId) ?? 'Nuevo Mensaje';
            $receivedMessage = $message ?? 'Has recibido un nuevo mensaje.';

            // Obtener el _uid del contacto
            $contactUid = $this->getContactUid($contactId);

            // Construir la URL usando el _uid
            $url = $contactUid 
                ? "https://crm.alfabusiness.app/vendor-console/whatsapp/contact/chat/{$contactUid}" 
                : "https://crm.alfabusiness.app/vendor-console/whatsapp/contact/chat/";

            // Enviar notificación push si el mensaje es recibido y el nombre está disponible
            if (($dataToUpdate['is_incoming_message'] ?? false) == 1 && $contactFullName) {
                $this->sendPushNotification($contactFullName, $receivedMessage, $vendorId, $url,$contactUid);
            }

            return $storedMessage;
        }

        if ($this->updateIt($findTheExistingLogEntry, arrayExtend($dataToUpdate, [
            '__data' => [
                'options' => $options,
                'webhook_responses' => [
                    $messageStatus => $messageEntry,
                ],
            ],
        ]))) {
            // Obtener el nombre completo del contacto usando $contactId
            $contactFullName = $this->getContactFullName($contactId) ?? 'Actualización de Mensaje';
            $updatedMessage = 'El estado de un mensaje ha sido actualizado.';

            // Obtener el _uid del contacto
            $contactUid = $this->getContactUid($contactId);

            // Construir la URL usando el _uid
            $url = $contactUid 
                ? "https://crm.alfabusiness.app/vendor-console/whatsapp/contact/chat/{$contactUid}" 
                : "https://crm.alfabusiness.app/vendor-console/whatsapp/contact/chat/";

            // Enviar notificación push si el mensaje es recibido y el nombre está disponible
            if (($dataToUpdate['is_incoming_message'] ?? false) == 1 && $contactFullName) {
                $this->sendPushNotification($contactFullName, $updatedMessage, $vendorId, $url,$contactUid);
            }

            return $findTheExistingLogEntry;
        }

        return false;
    }

    public function storeIncomingMessage(
        $phoneNumberId,
        $contactId,
        $vendorId,
        $messageRecipientId,
        $messageWamid,
        $messageEntry,
        $message,
        $timestamp,
        ?array $mediaData = null,
        ?string $repliedToMessage = null,
        ?bool $isForwarded = null,
    ) {
        $additionalData = [
            'webhook_responses' => [
                'incoming' => $messageEntry,
            ],
        ];
        if (!empty($mediaData)) {
            $additionalData['media_values'] = $mediaData;
        }

        $storedMessage = $this->storeIt([
            'wab_phone_number_id' => $phoneNumberId,
            'contact_wa_id' => $messageRecipientId,
            'wamid' => $messageWamid,
            'status' => 'received',
            'message' => $message,
            'is_incoming_message' => 1,
            'vendors__id' => $vendorId,
            'contacts__id' => $contactId,
            '__data' => $additionalData,
            'messaged_at' => is_numeric($timestamp) ? Carbon::createFromTimestamp($timestamp) : $timestamp,
            'replied_to_whatsapp_message_logs__uid' => $repliedToMessage,
            'is_forwarded' => $isForwarded,
        ]);

        // Obtener el nombre completo del contacto usando $contactId
        $contactFullName = $this->getContactFullName($contactId) ?? 'Nuevo Mensaje';
        $receivedMessage = $message ?? 'Has recibido un nuevo mensaje.';

        // Obtener el _uid del contacto
        $contactUid = $this->getContactUid($contactId);

        // Construir la URL usando el _uid
        $url = $contactUid 
            ? "https://crm.alfabusiness.app/vendor-console/whatsapp/contact/chat/{$contactUid}" 
            : "https://crm.alfabusiness.app/vendor-console/whatsapp/contact/chat/";

        // Enviar notificación push cuando se almacena un mensaje entrante y el nombre está disponible
        if (($storedMessage['is_incoming_message'] ?? false) == 1 && $contactFullName) {
            $this->sendPushNotification($contactFullName, $receivedMessage, $vendorId, $url,$contactUid);
        }

        return $storedMessage;
    }

    /**
     * Mark unread messages as read
     *
     * @param object $contact
     * @param integer $vendorId
     * @return mixed
     */
    public function markAsRead($contact, $vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();
        $updatedCount = $this->primaryModel::where([
            'contacts__id' => $contact->_id,
            'vendors__id' => $vendorId,
            'is_incoming_message' => 1,
            'status' => 'received',
        ])->update([
            'status' => 'read',
        ]);

        return $updatedCount;
    }

    /**
     * Get all messages of the particular contact
     *
     * @param integer $contactId
     * @return object
     */
    public function allMessagesOfContact(int $contactId)
    {
        return $this->primaryModel::where([
            'contacts__id' => $contactId,
        ])->latest()->orderBy('messaged_at', 'desc')->simplePaginate(16);
    }

    /**
     * Get the recent messages of the particular contact
     *
     * @param integer $contactId
     * @return object
     */
    public function recentMessagesOfContact(int $contactId)
    {
        return $this->allMessagesOfContact($contactId);
    }

    /**
     * Get unread count for vendor
     *
     * @param int $vendorId
     * @param int $phoneNumberId
     * @return int
     */
    public function getUnreadCount($vendorId = null, $phoneNumberId = null)
    {
        $vendorId = $vendorId ?: getVendorId();
        return $this->countIt([
            'vendors__id' => $vendorId,
            'is_incoming_message' => 1,
            [
                'contacts__id', '!=', null
            ],
            'status' => 'received',
        ]);
    }

    /**
     * Get unread count for vendor assigned to a specific user
     *
     * @param int $vendorId
     * @param int $phoneNumberId
     * @param string $userId
     * @return int
     */
    public function getMyAssignedUnreadMessagesCount($vendorId = null, $phoneNumberId = null, $userId = 'self')
    {
        $vendorId = $vendorId ?: getVendorId();
        return WhatsAppMessageLogModel::leftJoin('contacts', 'whatsapp_message_logs.contacts__id', '=', 'contacts._id')
            ->where([
                'whatsapp_message_logs.vendors__id' => $vendorId,
                'whatsapp_message_logs.is_incoming_message' => 1,
                [
                    'whatsapp_message_logs.contacts__id', '!=', null
                ],
                'whatsapp_message_logs.status' => 'received',
                'contacts.assigned_users__id' => ($userId == 'self') ? getUserID() : null,
            ])->count();
    }

    /**
     * Clear chat history all the messages excluding campaign messages
     *
     * @param int $contactId
     * @param int $vendorId
     * @return bool|mixed
     */
    public function clearChatHistory($contactId, $vendorId)
    {
        return $this->primaryModel::where([
            'vendors__id' => $vendorId,
            'contacts__id' => $contactId,
        ])->whereNull('campaigns__id')->delete();
    }
}