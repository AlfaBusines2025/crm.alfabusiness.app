<?php
/**
* WhatsAppServiceController.php - Controller file
*
* This file is part of the WhatsAppService component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Controllers;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use App\Yantrana\Components\Vendor\VendorSettingsEngine;
use App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine;
use App\Yantrana\Components\WhatsAppService\WhatsAppTemplateEngine;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppServiceController extends BaseController
{
    /**
     * @var WhatsAppServiceEngine - WhatsAppService Engine
     */
    protected $whatsAppServiceEngine;

    /**
     * @var VendorSettingsEngine - VendorSettings  Engine
     */
    protected $vendorSettingsEngine;
    /**
     * @var WhatsAppTemplateEngine - WhatsApp TemplateEngine  Engine
     */
    protected $whatsAppTemplateEngine;

    /**
     * Constructor
     *
     * @param  WhatsAppServiceEngine  $whatsAppServiceEngine  - WhatsAppService Engine
     * @param  VendorSettingsEngine  $vendorSettingsEngine  - VendorSettings Engine
     * @param  WhatsAppTemplateEngine  $whatsAppTemplateEngine  - WhatsApp Template Engine
     *
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(
        WhatsAppServiceEngine $whatsAppServiceEngine,
        VendorSettingsEngine $vendorSettingsEngine,
        WhatsAppTemplateEngine $whatsAppTemplateEngine
    ) {
        $this->whatsAppServiceEngine = $whatsAppServiceEngine;
        $this->vendorSettingsEngine = $vendorSettingsEngine;
        $this->whatsAppTemplateEngine = $whatsAppTemplateEngine;
    }

    /**
     * Send Template Message View
     *
     * @param string $contactUid
     * @return view
     */
    public function sendTemplateMessageView($contactUid)
    {
        validateVendorAccess('messaging');
        $sendMessageResponseData = $this->whatsAppServiceEngine->sendMessageData($contactUid);
        // load the view
        return $this->loadView('whatsapp.template-send-message', $sendMessageResponseData->data());
    }

    /**
     * Template Based Send Message Process
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function sendTemplateMessageProcess(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'template_uid' => 'required',
            'contact_uid' => 'required',
        ]);
        $processReaction = $this->whatsAppServiceEngine->processSendMessageForContact($request);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }

        return $this->responseAction(
            $this->processResponse($processReaction),
            $this->redirectTo('vendor.chat_message.contact.view', [
                'contactUid' => $processReaction->data('contactUid'),
            ], [
                $processReaction->message(),
                'success',
            ])
        );
    }

    /**
     * Schedule Campaign
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function scheduleCampaign(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_campaigns');
        $request->validate([
            'template_uid' => 'required',
            'contact_group' => 'required',
            'timezone' => 'required',
            'title' => 'required',
        ]);
        $processReaction = $this->whatsAppServiceEngine->processCampaignCreate($request);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }

        return $this->responseAction(
            $this->processResponse($processReaction),
            $this->redirectTo('vendor.campaign.status.view', [
                'campaignUid' => $processReaction->data('campaignUid'),
            ], [
                $processReaction->message(),
                'success',
            ])
        );
    }

    /**
     * Create new Campaign View
     *
     * @return view
     */
    public function createNewCampaign()
    {
        validateVendorAccess('manage_campaigns');
        $campaignRequiredData = $this->whatsAppServiceEngine->campaignRequiredData();
        // load the view
        return $this->loadView('whatsapp.template-send-message', $campaignRequiredData->data());
    }

    /**
     * Check if has API feature enabled in plan or abort
     *
     * @param int $vendorId
     * @return void
     */
    protected function apiAccessAllowedOrAbort($vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();
        // check the feature limit
        $vendorPlanDetails = vendorPlanDetails('api_access', 0, $vendorId);
        abortIf(!$vendorPlanDetails['is_limit_available'], 401, 'API access is not available in your plan, please upgrade your subscription plan.');
    }

    /**
     * Send Chat Message
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function sendChatMessage(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }
        $request->validate([
            'contact_uid' => 'required',
            'message_body' => 'required',
        ]);

        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }
        return $this->processResponse($processReaction);
    }
    /**
     * Send Chat Message
     *
     * @param BaseRequestTwo $request
     * @since - 2.0.0
     *
     * @return json
     */
    public function apiSendChatMessage(BaseRequestTwo $request, $vendorUid)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }
        // validate the inputs
        $request->validate([
            'phone_number' => 'required',
            'message_body' => 'required',
        ]);
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contact']['_uid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
     * Send Chat Media Based Chat Message
     *
     * @param BaseRequestTwo $request
     * @since - 2.0.0
     *
     * @return json
     */
    public function apiSendMediaChatMessage(BaseRequestTwo $request)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }
        // validate the inputs
        $request->validate([
            'phone_number' => 'required',
            'media_type' => [
                'required',
                Rule::in([
                    'image',
                    'video',
                    'document',
                    'audio',
                ])
            ],
            'media_url' => 'required|url',
        ]);
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request, true);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contact']['_uid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }
	
	/**
	 * Send Chat Media Based Chat Message (AI‑generated image)
	 *
	 * @param  BaseRequestTwo $request
	 * @since  2.0.0
	 *
	 * @return json
	 */
	public function apiSendImageGeneration(BaseRequestTwo $request)
	{
		$this->apiAccessAllowedOrAbort();
		validateVendorAccess('messaging');

		if (!isWhatsAppBusinessAccountReady()) {
			return $this->processApiResponse([
				'result'  => 'failed',
				'message' => 'Please complete your WhatsApp Cloud API Setup first',
			]);
		}

		/* ── 1. Validación mínima: número y prompt ───────────────────────── */
		$request->validate([
			'phone_number' => 'required',
			'order_message' => 'required',
			'mode' => 'required'
		]);

		/* ── 2. Generar la imagen con IA ─────────────────────────────────── */
		try {
			//"https://filesystem.site/cdn/20250421/mt5Z6ETS9pB1J77xdF9hmikahlySlB.png"; //
			if($request->input('mode') == "production"){
				$imageUrl = $this->generateAiImageUrl($request->input('order_message'));
				if($imageUrl==""){
					$imageUrl = $this->generateAiImageUrl($request->input('order_message'));
				}
			}else{
				$imageUrl = "https://filesystem.site/cdn/20250421/mt5Z6ETS9pB1J77xdF9hmikahlySlB.png";
			}
		} catch (\Throwable $e) {
			return $this->processApiResponse([
				'result'  => 'failed',
				'message' => $e->getMessage(),
			]);
		}

		/* ── 3. Inyectar datos de media y validar la estructura estándar ── */
		$request->merge([
			'media_type' => 'image',
			'media_url'  => $imageUrl,
		]);

		/* ── 4. Enviar el mensaje ────────────────────────────────────────── */
		if($request->input('uid_vendor') == "f2b6bb5e-be23-49e2-8571-a4507aad0fcc"){
							if($imageUrl == ""){
								$imageUrl = "https://a10.ec/ai.php";
							}
			//a10_image_generator_ai en español con el $imageUrl
							// URL dinámica
								$url = "https://crm.alfabusiness.app/api/f2b6bb5e-be23-49e2-8571-a4507aad0fcc/contact/send-template-message?token=AOWaDMqIHa75XmhUBO0cIroTgVW5vRGqId9xt9YZCIlinvAsZ7hKfl5893OjJwnZ";

								// Cabeceras y payload
								$headers = [
									'Content-Type'  => 'application/json',
									'Authorization' => 'Bearer AOWaDMqIHa75XmhUBO0cIroTgVW5vRGqId9xt9YZCIlinvAsZ7hKfl5893OjJwnZ',
								];
								$payload = [
									'phone_number'        => $request->input('phone_number'),
									'template_name'       => 'a10_image_generator_ai',
									'template_language'   => 'es',
									'button_0'            => $imageUrl,
								];

								// Envía la petición y guarda la respuesta
								$response = Http::withHeaders($headers)->post($url, $payload);
								
								/*
								\Log::info("📦 Respuesta HTTP", [
									'status' => $response->status(),
									'body'   => $response->body(),
								]);
								*/
			
		}else{
			
			$processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request, true);
			$processedData   = $processReaction->data();

			/* ── 5. Respuesta ────────────────────────────────────────────────── */
			return $this->processApiResponse($processReaction, [
				'log_uid'              => $processedData['log_message']['_uid']         ?? null,
				'contact_uid'          => $processedData['contact']['_uid']             ?? null,
				'phone_number'         => $processedData['log_message']['contact_wa_id']?? null,
				'wamid'                => $processedData['log_message']['wamid']        ?? null,
				'status'               => $processedData['log_message']['status']       ?? null,
			]);
			//processSendMessageForContact
		}
		
	}

	/* ────────────────────────────────────────────────────────────────────── */

	private function generateAiImageUrl(string $question): string
	{
		$apiKey = "bb25681683382cbec668ec54f3ff0b91de08cd2a825997648740eb4927e25c05";

		// Extraemos URL de imagen si las hubiera
		preg_match_all('/https?:\/\/[^\s]+?\.(?:png|jpe?g|webp|gif)/i', $question, $m);
		$urls = $m[0] ?? [];

		// Reemplazamos las URLs por marcadores [imagen_X]
		$text = $question;
		foreach ($urls as $i => $u) {
			$text = str_replace($u, '[imagen_' . ($i + 1) . ']', $text);
		}
		
		

		// Preparamos el contenido según si hay imágenes o no
		$content = [];
		foreach ($urls as $u) {
			
		  	$imageUrl = $u;
			
			$ext = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
			$mime = [
					'jpg'  => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'png'  => 'image/png',
					'gif'  => 'image/gif',
					'webp' => 'image/webp',
				][$ext] ?? 'application/octet-stream';

			$base64    = base64_encode(file_get_contents($imageUrl));
			$dataUri   = "data:{$mime};base64,{$base64}";
			
			$u = $dataUri;
			
			$content[] = [
				'type'      => 'image_url',
				'image_url' => ['url' => $u],
			];
		}
		// Siempre agregamos el bloque de texto
		$content[] = [
			'type' => 'text',
			'text' => trim($text),
		];

		$payload = [
			'model'    => 'gpt-4o-image-preview',
			'stream'   => false,
			'messages' => [
				['role' => 'user', 'content' => $content]
			],
		];

		//\Log::info("Request Image Generation: " . json_encode($payload));

		// Llamada al endpoint de PIAPI
		$ch = curl_init('https://api.piapi.ai/v1/chat/completions');
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST           => true,
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $apiKey,
			],
			CURLOPT_POSTFIELDS     => json_encode($payload),
		]);

		$res = curl_exec($ch);
		if ($res === false) {
			throw new \Exception('Error en PIAPI: ' . curl_error($ch));
		}
		curl_close($ch);

		// Buscamos una URL en el response
		$urlRegex = '/https?:\/\/filesystem\.site\/[^\s)]+\.png/i';
		$json = json_decode($res, true);

		
		\Log::info("Respuesta Image Generation: " . json_encode($res));

		if (json_last_error() === JSON_ERROR_NONE) {
			$body = $json['choices'][0]['message']['content'] ?? '';
			if (preg_match($urlRegex, $body, $match)) {
				
				\Log::info("URL de Imagen generada por Image Generation: " . $match[0]);
				return $match[0];
			}
		}
		if (preg_match($urlRegex, $res, $match)) {
			\Log::info("URL de Imagen generada por Image Generation: " . $match[0]);
			return $match[0];
			
		}
		
		return "";

		throw new \Exception('No se encontró una URL válida en la respuesta.');
	}

    /**
    * Send Template Chat Message
    *
    * @param BaseRequestTwo $request
    * @since - 2.0.0
    *
    * @return json
    */
    public function apiSendTemplateChatMessage(BaseRequestTwo $request, $vendorUid)
    {
        $this->apiAccessAllowedOrAbort();
        validateVendorAccess('messaging');
        // check if account failed
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processApiResponse([
                'result' => 'failed',
                'message' => 'Please complete your WhatsApp Cloud API Setup first',
            ]);
        }
        // validate the inputs
        $request->validate([
            'phone_number' => 'required',
            'template_name' => 'required',
            'template_language' => 'required',
            'header_image' => 'sometimes|url',
            'header_video' => 'sometimes|url',
            'header_document' => 'sometimes|url',
        ]);
        // send message
        $processReaction = $this->whatsAppServiceEngine->processSendMessageForContact($request);
        // processed data
        $processedData = $processReaction->data();
        // get back the response
        return $this->processApiResponse($processReaction, [
            'log_uid' => $processedData['log_message']['_uid'] ?? null,
            'contact_uid' => $processedData['contactUid'] ?? null,
            'phone_number' => $processedData['log_message']['contact_wa_id'] ?? null,
            'wamid' => $processedData['log_message']['wamid'] ?? null,
            'status' => $processedData['log_message']['status'] ?? null,
        ]);
    }

    /**
     * Prepare Upload Media for the message
     *
     * @param BaseRequestTwo $request
     * @param string $mediaType
     * @return json
     */
    public function prepareSendMediaUploader(BaseRequestTwo $request, $mediaType = 'image')
    {
        if (! in_array($mediaType, [
            'image',
            'video',
            'audio',
            'document',
        ])) {
            return $this->processResponse(2, [
                __tr('Invalid media type'),
            ]);
        }

        return $this->processResponse(1, [], [
            'uploadTitle' => __tr('Select __mediaType__', [
                '__mediaType__' => $mediaType,
            ]),
            'mediaType' => $mediaType,
        ]);
    }

    /**
     * Send Chat Media Based Chat Message
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function sendChatMessageMedia(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }

        $request->validate([
            'contact_uid' => 'required',
            'media_type' => 'required',
            'uploaded_media_file_name' => 'required',
        ]);

        $processReaction = $this->whatsAppServiceEngine->processSendChatMessage($request, true);

        // get back with response
        if ($processReaction->failed()) {
            return $this->processResponse($processReaction);
        }

        return $this->processResponse($processReaction);
    }

    /**
     * Load Chat View
     *
     * @param string $contactUid
     * @return view
     */
    public function chatView($contactUid = null)
    {
        validateVendorAccess('messaging');
        if(!isVendorAdmin(getVendorId()) and hasVendorAccess('assigned_chats_only')) {
            request()->merge([
                'assigned' => 'to-me'
            ]);
        }
        $assigned = request()->assigned;
        $chatData = $this->whatsAppServiceEngine->chatData($contactUid, $assigned);

        if(request()->ajax()) {
            updateClientModels($chatData->data(), 'append');
            return $this->processResponse(1, [], [
                'currentlyAssignedUserUid' => $chatData->data('currentlyAssignedUserUid'),
            ]);
        }
        // load the view
        return $this->loadView('whatsapp.chat', $chatData->data());
    }

    /**
     * Get the contact chat data
     *
     * @param string $contactUid
     * @return json
     */
    public function getContactChatData($contactUid, $way = 'append')
    {
        validateVendorAccess('messaging');
        $processReaction = $this->whatsAppServiceEngine->contactChatData($contactUid);
        updateClientModels([
            'whatsappMessageLogs' => $processReaction->data('whatsappMessageLogs'),
        ], $way);

        return $this->processResponse($processReaction);
    }

    /**
     * Get the contacts list
     *
     * @param string $contactUid
     * @return void
     */
    public function getContactsData(BaseRequestTwo $request, $contactUid = null)
    {
        validateVendorAccess('messaging');
        $assigned = $request->assigned;
        $processReaction = $this->whatsAppServiceEngine->contactsData($contactUid, $assigned);
        updateClientModels($processReaction->data(), $request->way);

        return $this->processResponse($processReaction);
    }

    /**
     * Clear the user chat history on our system
     *
     * @param BaseRequestTwo $request
     * @param string $contactUid
     * @return void
     */
    public function clearChatHistory(BaseRequestTwo $request, $contactUid)
    {
        validateVendorAccess('messaging');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $processReaction = $this->whatsAppServiceEngine->processClearChatHistory($contactUid);

        return $this->processResponse($processReaction);
    }

    /**
     * Change Template
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function changeTemplate(BaseRequestTwo $request)
    {
        validateVendorAccess([
            'manage_campaigns',
            'messaging',
        ]);
        $request->validate([
            'template_selection' => [
                'required',
                'uuid',
            ],
        ]);
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->processTemplateChange($request->get('template_selection'));
        if ($processReaction->success()) {
            return $this->responseAction(
                $this->processResponse($processReaction, [], [
                    '_uid' => $request->get('template_selection')
                ]),
                $this->replaceContent($processReaction->data('template'), '#lwTemplateStructureContainer')
            );
        }

        // get back with response
        return $this->processResponse($processReaction);
    }

    /**
     * Run Campaign Schedule mostly using Cron
     *
     * @param BaseRequestTwo $request
     * @param string $token - not in use for now
     * @return json
     */
    public function runCampaignSchedule(BaseRequestTwo $request, $token = '')
    {
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->processCampaignSchedule();
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * WhatsApp Webhook for the notifications from WhatsApp
     *
     * @param BaseRequestTwo $request
     * @param string $vendorUid
     * @return void
     */
    public function webhook(BaseRequestTwo $request, $vendorUid)
    {
        // webhook verification process
        if ($request->isMethod('get')) {
            if ($request->has('hub_challenge') and $request->has('hub_verify_token')) {
                $verifyToken = sha1($vendorUid);
                if ($request->get('hub_verify_token') === $verifyToken) {
                    // if its base webhook call from service
                    if($vendorUid == 'service-whatsapp') {
                        return response($request->get('hub_challenge'));
                    }
                    $vendorId = getPublicVendorId($vendorUid);
                    if (!$vendorId) {
                        return false;
                    }
                    // update configuration for webhook
                    $this->vendorSettingsEngine->updateProcess('whatsapp_cloud_api_setup', [
                        'webhook_verified_at' => now()
                    ], $vendorId);
                    updateModelsViaVendorBroadcast($vendorUid, [
                        'isWebhookVerified' => true
                    ]);
                    return response($request->get('hub_challenge'));
                }
            }
            return response('Invalid request', 403);
        }
        // process the other update requests
        $this->whatsAppServiceEngine->processWebhook($request, $vendorUid);
        return response('done', 200);
    }

    /**
     * Get unread message count for vendor
     *
     * @return json
     */
    public function unreadCount()
    {
        validateVendorAccess([
            'manage_campaigns',
            'messaging',
        ]);
        $processReaction = $this->whatsAppServiceEngine->updateUnreadCount();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Refresh WhatsApp Business Account Health Info
     *
     * @return json
     */
    public function getHealthStatus()
    {
        validateVendorAccess('administrative');
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }

        $processReaction = $this->whatsAppServiceEngine->refreshHealthStatus();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Refresh WhatsApp Business Account Health Info
     *
     * @return json
     */
    public function syncPhoneNumbers()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }

        $processReaction = $this->whatsAppServiceEngine->processSyncPhoneNumbers();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Store the tokens and info
     *
     * @param BaseRequestTwo $request
     * @return array
     */
    public function embeddedSignUpProcess(BaseRequestTwo $request)
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $request->validate([
            'request_code' => [
                'required'
            ],
            'waba_id' => [
                'required',
                'numeric'
            ],
            'phone_number_id' => [
                'required',
                'numeric'
            ],
        ]);
        $processReaction = $this->whatsAppServiceEngine->setupWhatsAppEmbeddedSignUpProcess($request);
        if($processReaction->success()) {
            // sync templates
            $this->whatsAppTemplateEngine->processSyncTemplates();
            return $this->processResponse(21, [], [
                'reloadPage' => true
            ], true);
        }
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Disconnect Base Webhook
     *
     * @return json
     */
    public function disconnectWebhook()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->processDisconnectWebhook();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Disconnect Base Webhook
     *
     * @return json
     */
    public function disconnectAccount()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->processDisconnectAccount();
        if($processReaction->success()) {
            return $this->processResponse(21, [], [
                'reloadPage' => true,
                'show_message' => true,
                'messageType' => 'success',
            ], true);
        }
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Connect Base Webhook
     *
     * @return json
     */
    public function connectWebhook()
    {
        validateVendorAccess('administrative');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->whatsAppServiceEngine->processConnectWebhook();
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Requeue failed messages for processing
     *
     * @param BaseRequestTwo $request
     * @param string $campaignUid  campaign uid
     * @return json
     */
    public function requeueCampaignFailedMessages(BaseRequestTwo $request, $campaignUid)
    {
        validateVendorAccess('manage_campaigns');
        $processReaction = $this->whatsAppServiceEngine->processRequeueFailedMessages($request, $campaignUid);
        // get back with response
        if ($processReaction->success()) {
            return $this->processResponse($processReaction, [], [
                // reload datatable on success
                'reloadDatatableId' => '#lwCampaignQueueLog'
            ]);
        }
        return $this->processResponse($processReaction);
    }

    /**
     * Get Business Profile
     *
     * @param int $phoneNumberId
     * @return json
     */
    function getBusinessProfile($phoneNumberId) {
        validateVendorAccess('administrative');
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->requestBusinessProfile($phoneNumberId);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    function updateBusinessProfile(BaseRequestTwo $request) {
        validateVendorAccess('administrative');
        $request->validate([
            'address' => [
                'nullable',
                'max:256',
            ],
            'description' => [
                'nullable',
                'max:256',
            ],
            'about' => [
                'nullable',
                'max:139',
            ],
            'about' => [
                'nullable',
                'max:139',
            ],
            'email' => [
                'nullable',
                'email',
                'max:128',
            ],
        ]);
        // ask engine to process the request
        $processReaction = $this->whatsAppServiceEngine->requestUpdateBusinessProfile($request);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
}
