<?php

/**
 * CustomApisService.php
 *
 * Servicio para manejar APIs personalizadas por proveedor.
 */

namespace App\Yantrana\Components\WhatsAppService\Services;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
// Modelos base
use App\Yantrana\Components\Contact\Models\ContactModel;
use App\Yantrana\Services\PushBroadcast\PusherBeamsService; // Importar el servicio PusherBeamsService
//Open AI
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;



class CustomApisService
{
  /**
   * Procesa la solicitud API personalizada basada en el vendor_id.
   *
   * @param string $jsonParams JSON con todos los parámetros necesarios.
   * @return JsonResponse Respuesta de la función personalizada o mensaje de error.
   */
  public function processVendorApi($jsonParams)
  {
    // Decodificar el JSON recibido
    $params = json_decode($jsonParams, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      // Retornar una respuesta genérica de error pero sin detalles
      return response()->json([
        'error' => true,
        'message' => 'Datos proporcionados inválidos.'
      ], 200); // Usar 200 para evitar errores HTTP
    }

    // Verificar la existencia de 'vendor_id'
    if (!isset($params['vendor_id'])) {
      return response()->json([
        'error' => true,
        'message' => 'El parámetro "vendor_id" es obligatorio.'
      ], 200); // Usar 200 para evitar errores HTTP
    }

    $vendorId = $params['vendor_id'];

    // Construir el nombre de la función personalizada
    $methodName = 'handleVendor_' . $vendorId;

    // Verificar si la función existe dentro de esta clase
    if (method_exists($this, $methodName)) {
      try {
        // Ejecutar la función personalizada y retornar su resultado
        return $this->{$methodName}($params);
      } catch (Exception $e) {
        // Manejar excepciones y retornar un mensaje de error genérico
        \Log::error("Error en la función personalizada {$methodName}: " . $e->getMessage());
        return response()->json([
          'error' => true,
          'message' => 'Ocurrió un error al procesar la solicitud.'
        ], 200); // Usar 200 para evitar errores HTTP
      }
    } else {
      // Llamar al manejador por defecto
      return $this->handleVendor_Default($params);
    }
  }

  /**
   * Función por defecto para manejar vendor_ids sin un handler específico.
   *
   * @param array $params Parámetros decodificados del JSON.
   * @return JsonResponse Respuesta personalizada.
   */
  private function handleVendor_Default($params)
  {
	  
		if(strlen($params['domain_variable_vendor'])>4)
		{
			return $this->handleVendorWebhookGenerator($params);
		}
		
		//domain_variable_vendor
		// Puedes personalizar esta respuesta según tus necesidades
		return response()->json([
		  'error' => false,
		  'clave_secreta' => "boomer",
		  'vendor_id' => $params['vendor_id'],
		  'message' => 'Proveedor no tiene una función personalizada. Respuesta por defecto.'
		], 200);
  }
	
	/**
	 * Función unificada para procesar el webhook del vendor.
	 *
	 * Esta función realiza las siguientes acciones:
	 * 1. Valida los parámetros mínimos requeridos.
	 * 2. Construye dinámicamente los endpoints a partir de los parámetros recibidos.
	 * 3. Obtiene las últimas 10 interacciones del usuario filtrando agentes no deseados.
	 * 4. Obtiene los datos globales de la web.
	 * 5. Llama al endpoint de keywords (comerciales) para obtener un listado de palabras clave.
	 * 6. Genera un prompt y utiliza OpenAI para obtener un JSON con un array de palabras clave relevantes.
	 * 7. Convierte el array de keywords en una cadena separada por comas.
	 * 8. Realiza la búsqueda de productos usando las keywords generadas y procesa la respuesta para obtener una cadena con los productos separados por comas.
	 * 9. Obtiene los datos de sucursales (ubicaciones) desde el endpoint, para mostrarlos de forma informativa.
	 * 10. Consolida y devuelve la respuesta final en formato JSON.
	 *
	 * Cada paso se registra en el log para facilitar el debugging.
	 *
	 * @param array $params Parámetros decodificados del JSON.
	 * @return JsonResponse Respuesta consolidada.
	 */
	
	
	
	
	
////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
///////////////////////////////////////////
///////////////////////////////////////////
///////////////////////////////////////////
	
	
	
private function handleVendorWebhookGenerator($params) {
    $question                     = $params['question'] ?? null;	
    $contactUid                   = $params['contact_uid'] ?? null;
    $mensajes_anteriores_contacto = $params['mensajes_anteriores_contacto'] ?? null;
    $apiKey                       = $params['open_ai_access_key'] ?? null;
    $idOrg                        = $params['open_ai_organization'] ?? null;
    $mensaje_cliente              = $question;
    $topSections                  = $params['top_sections'] ?? [];
    $combinedSections             = $params['combined_sections'] ?? '';
    $api_data_ai                  = $params['api_data_ai'] ?? [];
    $contactContext               = $params['contact_context'] ?? '';
    $prompt_url_items             = $params['prompt_url_items'] ?? [];
    $prompt_final                 = $params['prompt_final'] ?? [];
	
		/*
	
			// 7. Preparar parámetros para CustomApisService
        $customApiParams = [
            'vendor_id' => $vendorId,
            'question' => $question,
			'mensajes_anteriores_contacto'=> $mensajes_anteriores_contacto,
            'contact_uid' => $contactUid,
            'top_sections' => $topSections,
            'combined_sections' => $combinedSections,
            'api_data_ai' => $api_data_ai,
            'contact_context' => $contactContext,
            'prompt_url_items' => $promptUrlItems,
            'prompt_final' => $promptFinal,
			// Parámetros de OpenAI
			'open_ai_access_key'      => $openAiApiKey,
			'open_ai_organization'=> $openAiOrgKey,
			'vendor_webhook_endpoint'=> $vendor_webhook_endpoint,
			// dominio webhook, vendor acces token y uid vendor
			'domain_variable_vendor'=> $domain_variable_vendor,
			'vendor_uid'=> $vendorUid,
			'vendor_access_token'=> $vendorAccessToken,
            // Agrega más parámetros según sea necesario
			'wa_id_contact'=> $wa_id_contact,
			//configuraciones adicionalesvendor:
			'requiere_saludo'=> $requiere_saludo,
			'timezone'=> $timezone,
			'hora_actual'=> $hora_actual,
			'botName'=> $botName,
			'hora_actual'=> $hora_actual,
			'hora_actual'=> $hora_actual,
			'prompt'=> $openAIDataPrompt,
			//$botName ,
        ];
		
	*/


 /*****************************************************************************************
 *  Bloque completo actualizado –  ENVÍO A FLOWISE CON TODAS LAS VARIABLES NECESARIAS
 *  (asume que $params, $question y $contactUid ya están definidos tal como en tu código)
 *****************************************************************************************/
if (
    $contactUid === "b605e0be-a621-497d-9831-6643229309c7" ||
    $contactUid === "d1234567-abcd-1234-efgh-567890abcdef" ||
    $contactUid === "a9876543-zyxw-4321-vuts-0987654321ba"
) {
    Log::info("Data de WHATSAPP-----------------------------------");
    Log::info($params);
    Log::info("FIN de WHATSAPP------------------------------------");
    Log::info("cgl: Condición especial detectada – invocando Flowise API.");

    /* --------------------------------------------------------------------
       1. Prepara variables de apoyo
    -------------------------------------------------------------------- */
    $flowiseApiUrl = "https://workflow.alfabusiness.app/api/v1/prediction/b0f491b8-a49b-4de8-8de8-14d5621e7471";

    // Imagen del primer prompt_url_items (si hubiera)
    $imageUrl = $params['prompt_url_items'][0]['image_url']['url'] ?? null;

    // Decodifica el contexto de contacto para extraer first_name, etc.
    $contactContext = json_decode($params['contact_context'] ?? '{}', true);

    /* --------------------------------------------------------------------
       2. Construye el bloque base de variables que SIEMPRE envías
    -------------------------------------------------------------------- */
    $varsBase = [
        'url'            => ($params['domain_variable_vendor'] ?? '') . "/wp-json/alfabusiness/api/v1",
        'questionCrm'    => '',
        'sessionId'      => $contactUid,
        'contactUid'     => $contactUid,
        'mensajeCliente' => '',
        'token'          => $params['vendor_access_token'] ?? '',
        'uidVendor'      => $params['vendor_uid']         ?? '',
        'uid'            => '',
        'contactContext' => $contactContext,
        'initPrompt'     => $params['prompt']             ?? '',
        'firstName'      => $contactContext['first_name'] ?? '',
    ];

    /* --------------------------------------------------------------------
       3. Arreglo SOLO con las NUEVAS claves que ahora quieres adjuntar
          (tomadas directamente de $params)
    -------------------------------------------------------------------- */
    $extraVars = [
        'vendorId'              => $params['vendor_id']                 ?? null,
        'mensajesAnteriores'    => $params['mensajes_anteriores_contacto'] ?? '',
        'topSections'           => $params['top_sections']              ?? [],
        'combinedSections'      => $params['combined_sections']         ?? '',
        'apiDataAi'             => $params['api_data_ai']               ?? [],
        'promptUrlItems'        => $params['prompt_url_items']          ?? [],
        'promptFinal'           => $params['prompt_final']              ?? [],
        'openAiAccessKey'       => $params['open_ai_access_key']        ?? '',
        'openAiOrganization'    => $params['open_ai_organization']      ?? '',
        'vendorWebhookEndpoint' => $params['vendor_webhook_endpoint']   ?? '',
        'domainVariableVendor'  => $params['domain_variable_vendor']    ?? '',
        'vendorUid'             => $params['vendor_uid']                ?? '',
        'vendorAccessToken'     => $params['vendor_access_token']       ?? '',
        'waIdContact'           => $params['wa_id_contact']             ?? '',
		'horaActual'            => $params['hora_actual']               ?? '',	
        'requiereSaludo'        => $params['requiere_saludo']           ?? '',	// alias
        'timezone'              => $params['timezone']                  ?? '',	// alias
        'botName'               => $params['botName']                   ?? '',	// alias
        'promptInit'            => $params['prompt']                    ?? '',  // alias
    ];

    /* --------------------------------------------------------------------
       4. Une ambos arrays SIN sobrescribir las claves existentes
          (el operador + mantiene el valor de la izquierda si la clave se repite)
    -------------------------------------------------------------------- */
    $varsFinal = $varsBase + $extraVars;

    /* --------------------------------------------------------------------
       5. Payload final para Flowise
    -------------------------------------------------------------------- */
    $payloadFlowise = [
        'question'  => $question,
        'sessionId' => $contactUid,
        'overrideConfig' => [
            'vars' => $varsFinal,
        ],
    ];

    // Adjunta imagen como “uploads” si está presente
    if ($imageUrl) {
        $payloadFlowise['uploads'] = [[
            'data' => $imageUrl,
            'type' => 'url',
            'name' => basename(parse_url($imageUrl, PHP_URL_PATH)),
            'mime' => 'image/' . pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION),
        ]];
    }

    Log::info("cgl: Payload para Flowise: " . json_encode($payloadFlowise));

    /* --------------------------------------------------------------------
       6. Llamada a Flowise
    -------------------------------------------------------------------- */
    try {
        $responseFlowise = Http::timeout(60)->post($flowiseApiUrl, $payloadFlowise);

        if ($responseFlowise->failed()) {
            Log::error("cgl: Flowise API call failed", [
                'url'    => $flowiseApiUrl,
                'status' => $responseFlowise->status(),
            ]);
            return response()->json([
                'error' => true,
                'msg'   => 'Error al obtener respuesta de Flowise API.',
            ], 200);
        }

        $flowiseData = json_decode($responseFlowise->body(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("cgl: Error decoding Flowise response: " . json_last_error_msg());
        }
    } catch (\Exception $e) {
        Log::error("cgl: Exception calling Flowise API", ['message' => $e->getMessage()]);
        return response()->json([
            'error' => true,
            'msg'   => 'Excepción al llamar a Flowise API.',
        ], 200);
    }

    Log::info("cgl: Flowise response - text: "      . ($flowiseData['text'] ?? ''));
    Log::info("cgl: Flowise response - usedTools: " . json_encode($flowiseData['usedTools'] ?? []));
    Log::info("cgl: Flowise response - question: "  . ($flowiseData['question'] ?? ''));
    Log::info("cgl: DATA RESPONDE: " . json_encode($flowiseData));

    /* --------------------------------------------------------------------
       7. Respuesta final al frontend / CRM
    -------------------------------------------------------------------- */
    $finalText = "INSTRUCCIÓN: Utiliza la respuesta de Flowise para formular la respuesta final siempre. "
               . ($flowiseData['text'] ?? '');

    return response()->json([
        'error'          => false,
        'vendor_id'      => $params['vendor_id'] ?? 13,
        'contact_uid'    => $contactUid,
        'processed_data' => $finalText,
    ], 200);
}
/* ----- FIN DEL BLOQUE DE CONDICIÓN ESPECIAL ---------------------------------- */






	
	///// SECCION ANTERIOR

    $contact = ContactModel::where('_uid', $contactUid)->first();
    $general_endpoint = "https://" . $params['domain_variable_vendor'] . "/wp-json/alfabusiness/api/v1/";
    $general_parameters_endpoint = "?token=" . $params['vendor_access_token'] . "&uid_vendor=" . $params['vendor_uid'] . "&uid=" . $contactUid;
    $web_endpoint                   = $general_endpoint . "web" . $general_parameters_endpoint;
    $keywords_general_endpoint      = $general_endpoint . "keywords/general" . $general_parameters_endpoint;
    $keywords_commerce_endpoint     = $general_endpoint . "keywords/commerce" . $general_parameters_endpoint;
    $interacciones_usuario_endpoint = $general_endpoint . "user" . $general_parameters_endpoint;
    $products_search_endpoint       = $general_endpoint . "search/products" . $general_parameters_endpoint;
    $pages_search_endpoint          = $general_endpoint . "search/pages" . $general_parameters_endpoint;
    $general_search_endpoint        = $general_endpoint . "search/general" . $general_parameters_endpoint;
    $locations_endpoint             = $general_endpoint . "sucursales" . $general_parameters_endpoint;
    $ultimas_interacciones_usuario_web = "";

    try {
        $responseInteracciones = Http::timeout(60)->get($interacciones_usuario_endpoint);
        Log::info("handleVendorWebhookGenerator - Llamada a interacciones: " . $interacciones_usuario_endpoint);
        if ($responseInteracciones->successful()) {
            $dataInteracciones = $responseInteracciones->json();
            $dataInteracciones = array_filter($dataInteracciones, function ($item) {
                $userAgent = $item['user_agent'] ?? '';
                return (stripos($userAgent, 'guzzle') === false && stripos($userAgent, 'postman') === false);
            });
            usort($dataInteracciones, function ($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });
            $ultimasInteracciones = array_slice($dataInteracciones, 0, 10);
            $datosRelevantes = array_map(function ($item) {
                return [
                    'url'        => $item['url'] ?? null,
                    'parametros' => $item['parametros'] ?? null,
                    'fecha'      => $item['fecha'] ?? null,
                    'location'   => $item['location'] ?? null,
                ];
            }, $ultimasInteracciones);
            $ultimas_interacciones_usuario_web = json_encode($datosRelevantes, JSON_PRETTY_PRINT);
            Log::info("handleVendorWebhookGenerator - Últimas interacciones procesadas: " . $ultimas_interacciones_usuario_web);
        } else {
            Log::error("handleVendorWebhookGenerator - Error al obtener interacciones", [
                'url'    => $interacciones_usuario_endpoint,
                'status' => $responseInteracciones->status()
            ]);
        }
    } catch (\Exception $e) {
        Log::error("handleVendorWebhookGenerator - Exception al obtener interacciones", ['message' => $e->getMessage()]);
    }

    $web_data = "";
    try {
        $responseWeb = Http::timeout(60)->get($web_endpoint);
        Log::info("handleVendorWebhookGenerator - Llamada a datos web: " . $web_endpoint);
        if ($responseWeb->failed()) {
            Log::error("handleVendorWebhookGenerator - Error al obtener datos web", [
                'url'    => $web_endpoint,
                'status' => $responseWeb->status()
            ]);
        } else {
            $web_data = $responseWeb->body();
            Log::info("handleVendorWebhookGenerator - Datos web obtenidos: " . $web_data);
        }
    } catch (\Exception $e) {
        Log::error("handleVendorWebhookGenerator - Exception al obtener datos web", ['message' => $e->getMessage()]);
    }

    $keywordsList = [];
    try {
        $responseKeywords = Http::timeout(10)->get($keywords_commerce_endpoint);
        Log::info("handleVendorWebhookGenerator - Llamada a keywords: " . $keywords_commerce_endpoint);
        if ($responseKeywords->failed()) {
            Log::error("handleVendorWebhookGenerator - Error al obtener keywords", [
                'url'    => $keywords_commerce_endpoint,
                'status' => $responseKeywords->status()
            ]);
            return response()->json([
                'error' => true,
                'msg'   => 'No se pudo obtener el listado de palabras clave.'
            ], 200);
        }
        $keywordsList = $responseKeywords->json();
        Log::info("handleVendorWebhookGenerator - Keywords obtenidas: " . json_encode($keywordsList));
    } catch (\Exception $e) {
        Log::error("handleVendorWebhookGenerator - Exception al obtener keywords", ['message' => $e->getMessage()]);
        return response()->json([
            'error' => true,
            'msg'   => 'Excepción al obtener palabras clave.'
        ], 200);
    }

    $promptText = "Basado en el siguiente mensaje del usuario: \"$mensaje_cliente\", genera una lista de palabras clave relevantes para identificar productos en nuestro catálogo. " .
                  "Utiliza únicamente las siguientes palabras clave disponibles: " . json_encode($keywordsList) . ". " .
                  "Incluye también palabras o sinónimos presentes en el mensaje. " .
                  "Devuelve únicamente un JSON que contenga un array de palabras clave. Ejemplo: [\"android\", \"64\"]";
    $payloadKeywords = [
        'model'      => 'gpt-4o-mini',
        'messages'   => [
            [
                'role'    => 'user',
                'content' => $promptText
            ]
        ],
        'max_tokens' => 150,
    ];
    $responseGeneratedKeywords = $this->callOpenAi($apiKey, $idOrg, $payloadKeywords);
    if ($responseGeneratedKeywords['error']) {
        Log::error("handleVendorWebhookGenerator - Error al generar palabras clave", [
            'msg' => $responseGeneratedKeywords['msg']
        ]);
        return response()->json([
            'error' => true,
            'msg'   => 'Error al generar palabras clave.'
        ], 200);
    }
    $generatedKeywordsContent = $responseGeneratedKeywords['content'] ?? '[]';
    Log::info("handleVendorWebhookGenerator - Palabras clave generadas: " . $generatedKeywordsContent);

    function extraer_json_array($texto) {
        preg_match('/\[(.*?)\]/s', $texto, $matches);
        if (!empty($matches)) {
            return '[' . $matches[1] . ']';
        }
        return null;
    }
    $jsonKeywordsExtracted = extraer_json_array($generatedKeywordsContent);
    $finalKeywords = json_decode($jsonKeywordsExtracted, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error("handleVendorWebhookGenerator - Error al decodificar JSON de palabras clave generadas", [
            'jsonError' => json_last_error_msg()
        ]);
        return response()->json([
            'error' => true,
            'msg'   => 'Error al decodificar el JSON de palabras clave generadas.'
        ], 200);
    }
    Log::info("handleVendorWebhookGenerator - JSON final de palabras clave: " . json_encode($finalKeywords));
    $keywordsString = is_array($finalKeywords) ? implode(",", $finalKeywords) : "";
    $searchUrl = $general_endpoint . "search/products" . $general_parameters_endpoint . "&keywords=" . $keywordsString . "&limit=10";
    Log::info("handleVendorWebhookGenerator - URL de consulta: " . $searchUrl);
    if (isset($params['stock'])) {
        $searchUrl .= "&stock=" . $params['stock'];
    }
    try {
        $responseProducts = Http::timeout(60)->get($searchUrl);
        Log::info("handleVendorWebhookGenerator - Llamada a productos: " . $searchUrl);
        if ($responseProducts->failed()) {
            Log::error("handleVendorWebhookGenerator - Error al obtener productos", [
                'url'    => $searchUrl,
                'status' => $responseProducts->status()
            ]);
            return response()->json([
                'error' => true,
                'msg'   => 'No se pudieron obtener productos.'
            ], 200);
        }
        $productosData = $responseProducts;
        $productos_ecommerce = $productosData;
        Log::info("handleVendorWebhookGenerator - Productos obtenidos: " . $productos_ecommerce);
    } catch (\Exception $e) {
        Log::error("handleVendorWebhookGenerator - Exception al obtener productos", ['message' => $e->getMessage()]);
        return response()->json([
            'error' => true,
            'msg'   => 'Excepción al obtener productos.'
        ], 200);
    }

    $businessData = [];
    try {
        $responseLocations = Http::timeout(60)->get($locations_endpoint);
        Log::info("handleVendorWebhookGenerator - Llamada a sucursales: " . $locations_endpoint);
        if ($responseLocations->successful()) {
            $businessData = $responseLocations->json();
            Log::info("handleVendorWebhookGenerator - Sucursales obtenidas: " . json_encode($businessData));
        } else {
            Log::error("handleVendorWebhookGenerator - Error al obtener sucursales", [
                'url'    => $locations_endpoint,
                'status' => $responseLocations->status()
            ]);
        }
    } catch (\Exception $e) {
        Log::error("handleVendorWebhookGenerator - Exception al obtener sucursales", ['message' => $e->getMessage()]);
    }

    $respuestaFinal = [
        'ultimas_interacciones_usuario_web' => $ultimas_interacciones_usuario_web,
        'web'                               => "Datos de la web: " . $web_data,
        'productos'                         => "Listado de productos: " . $productos_ecommerce,
        'sucursales_informativas'           => "Información de sucursales: " . json_encode($businessData)
    ];
    Log::info("handleVendorWebhookGenerator - Respuesta final preparada: " . json_encode($respuestaFinal));
    return response()->json([
        'error'         => false,
        'vendor_id'     => $params['vendor_id'] ?? 13,
        'contact_uid'   => $contactUid,
        'processed_data'=> json_encode($respuestaFinal)
    ], 200);
}

	
	
	
	
	
	
//////////////////////////////////////////
//////////////////////////////////////////
//////////////////////////////////////////
//////////////////////////////////////////	
////////////////////////////////////////////////////////////////////	
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
	
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

        // Recuperar el assigned_users__id basado en contactUid
        $contact = ContactModel::where('_uid', $contactUid)->first(['assigned_users__id']);
        $assignedUserId = $contact->assigned_users__id ?? 'no_assigned_user';

        // Preparar los datos adicionales para la notificación
        $additionalData = [
            'assigned_user_id' => $assignedUserId,
        ];

        // Enviar la notificación push con la URL y los datos adicionales
        $beamsService->sendPushNotification($interest, $truncatedTitle, $truncatedBody, $icon, $url, $additionalData);
    }

  /**
   * Llama a la API de OpenAI con el payload proporcionado.
   *
   * @param string $apiKey Clave de OpenAI.
   * @param string $idOrg Organización de OpenAI.
   * @param array $payload Payload para la solicitud.
   * @return array Resultado de la llamada, con claves 'error', 'message', y 'content'.
   */
  private function callOpenAi($apiKey, $idOrg, $payload)
  {
    try {
      $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'OpenAI-Organization' => $idOrg,
        'Content-Type' => 'application/json',
      ])->timeout(300)->post('https://api.openai.com/v1/chat/completions', $payload);

      if ($response->failed()) {
        \Log::error("handleVendor_ OpenAI Error. Code: {$response->status()}. Response: {$response->body()}");
        return [
          'error'   => true,
          'message' => 'Error en la respuesta de OpenAI.',
          'content' => null
        ];
      }

      $arrayResponse = $response->json();

      // Extraer sólo el contenido
      $contenido = $arrayResponse['choices'][0]['message']['content'] ?? '';

      return [
        'error'   => false,
        'message' => null,
        'content' => $contenido
      ];
    } catch (Exception $e) {
      \Log::error("handleVendor_10 OpenAI Exception: " . $e->getMessage());
      return [
        'error'   => true,
        'message' => 'Error al conectar con OpenAI.',
        'content' => null
      ];
    }
  }

  /**
   * Llama a una API externa.
   *
   * @param string $url URL de la API externa.
   * @param int $timeout Tiempo de espera en segundos.
   * @return array Resultado de la llamada, con claves 'error', 'message', y 'data'.
   */
  private function callExternalApi($url, $timeout = 15)
  {
    try {
      $response = Http::timeout($timeout)->get($url);

      if ($response->failed()) {
        \Log::error("handleVendor_10 External API Error. Code: {$response->status()}. Response: {$response->body()}");
        return [
          'error'   => true,
          'message' => 'Error al consumir la API externa.',
          'data'    => null
        ];
      }

      $dataProductos = $response->json();

      return [
        'error'   => false,
        'message' => null,
        'data'    => $dataProductos
      ];
    } catch (Exception $e) {
      \Log::error("handleVendor_10 External API Exception: " . $e->getMessage());
      return [
        'error'   => true,
        'message' => 'Error de conexión con la API externa.',
        'data'    => null
      ];
    }
  }
	
  	/**
	 * Función personalizada para vendor_id = 12.
	 *
	 * @param array $params Parámetros decodificados del JSON.
	 * @return JsonResponse Respuesta personalizada.
	 */
	private function handleVendor_12($params)
	{
		//\Log::info('handleVendor_12 iniciada.', ['params' => $params]);

		// 1. Asignar las claves necesarias.
		$mensaje_cliente      = $params['question'] ?? null;
		$apiKey               = $params['open_ai_access_key'] ?? null;         // Clave de OpenAI
		$idOrg                = $params['open_ai_organization'] ?? null;         // Organización de OpenAI
		$contactUid           = $params['contact_uid'] ?? null;                  // UID del contacto
		$topSections          = $params['top_sections'] ?? [];
		$combinedSections     = $params['combined_sections'] ?? '';
		$api_data_ai          = $params['api_data_ai'] ?? [];
		$contactContext       = $params['contact_context'] ?? '';
		$prompt_url_items     = $params['prompt_url_items'] ?? [];
		$prompt_final         = $params['prompt_final'] ?? [];
		$wa_id_contact        = $params['wa_id_contact'] ?? null;                // Número de teléfono o ID de contacto

		// Quitar '593' del inicio, si existe.
		if ($wa_id_contact && strpos($wa_id_contact, '593') === 0) {
			$wa_id_contact = substr($wa_id_contact, 3);
		}

		// 2. Configurar la URL de consulta a la API de Airtable.
		$endpoint_api_airtable = "https://api.airtable.com/v0/appxaXtwTFOh9Wmv1/Participantes?view=Participantes&pageSize=1&filterByFormula=%7BTel%C3%A9fono%7D%3D%220". $wa_id_contact . "%22";
		$ApiKeyAirtableStartQuito = "patUBecDqybwppnNm.095e93870fe85f634d2cc07405e0350922d0bf369d93be4c74412f06be5f1e51"; // Clave por defecto

		// 3. Ejecutar la consulta usando cURL.
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL            => $endpoint_api_airtable,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'GET',
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Bearer ' . $ApiKeyAirtableStartQuito
			),
		));

		$response = curl_exec($curl);

		// Verificar si hubo error en la consulta
		if (curl_errno($curl)) {
			$error_msg = curl_error($curl);
			curl_close($curl);
			return response()->json([
				'error'   => false,
				'message' => $error_msg
			], 200);
		}

		curl_close($curl);

		// 4. Procesar la respuesta de la API (se espera un JSON)
		$datos_adicionales_contacto = json_decode($response, true);

		// 5. Preparar la respuesta final
		$respuestaFinal = [
			'datos_adicionales_contacto' => $datos_adicionales_contacto,
		];

		return response()->json([
			'error'         => false,
			'vendor_id'     => 12,
			'contact_uid'   => $contactUid,
			'processed_data'=> json_encode($respuestaFinal)
		], 200);
	}

  /**
   * Función personalizada para vendor_id = 10.
   *
   * @param array $params Parámetros decodificados del JSON.
   * @return JsonResponse Respuesta personalizada.
   */
  private function handleVendor_11($params)
  {
    //\Log::info('handleVendor_11 iniciada.'. json_encode($params));

    /**
     * 1. Asignar las claves necesarias.
     */
    $ApiKeyA10 = "A102025102289bgdnj"; // Clave por defecto
    $mensaje_cliente = $params['question'] ?? null;
	$question = $mensaje_cliente;
    $apiKey = $params['open_ai_access_key'] ?? null; // Clave de OpenAI
    $idOrg = $params['open_ai_organization'] ?? null; // Organización de OpenAI
    $contactUid = $params['contact_uid'] ?? null; // UID del contacto
    $topSections = $params['top_sections'] ?? [];
    $combinedSections = $params['combined_sections'] ?? '';
    $api_data_ai = $params['api_data_ai'] ?? [];
    $contactContext = $params['contact_context'] ?? '';
    $prompt_url_items = $params['prompt_url_items'] ?? [];
    $prompt_final = $params['prompt_final'] ?? [];
	
	  
	
	$contact = ContactModel::where('_uid', $contactUid)->first();

		if (!$question || !$contactUid) {
			\Log::error("handleVendor_11 - Falta alguno de los parámetros requeridos", [
				'question'   => $question,
				'contactUid' => $contactUid
			]);
			return response()->json([
				'error'   => false,
				'message' => 'Faltan parámetros "question" o "contact_uid" para vendor_id 13.'
			], 200);
		}
	
    /**
     * 2. Construir y procesar prompt_url_items
     */
    $processedPromptUrlItems = [];
    foreach ($prompt_url_items as $item) {
      if ($item['type'] === 'audio_url' && isset($item['audio_url']['url'])) {
      } elseif ($item['type'] === 'image_url') {
		  
		  $item = $item['image_url']['url'];
		  $imageUrl = $item;
			
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
			
			$imageUrl = $dataUri;
		  
        // Añadir imágenes tal cual al prompt
        $processedPromptUrlItems[] = [
          'type' => 'image_url',
          "image_url" => [
            'url' =>  $imageUrl
          ],
		  //'text_transcription' => $item['image_url']['text_transcription']
        ];
        //\Log::debug('handleVendor_10 - Añadida image_url al prompt.', ['image_url' => $item['image_url']['url']]);
      } elseif ($item['type'] === 'document_url') {
        // Añadir documentos tal cual al prompt
        $processedPromptUrlItems[] = [
          'type' => 'document_url',
          "document_url" => [
            'url' =>  $item['document_url']['url']
          ]
        ];

        //\Log::debug('handleVendor_10 - Añadida document_url al prompt.', ['document_url' => $item['document_url']['url']]);
      } else {
        // Otros tipos de contenido
        $processedPromptUrlItems[] = $item;
        //\Log::debug('handleVendor_10 - Añadido otro tipo de contenido al prompt.', ['item' => $item]);
      }
    }


    /**
     * 3. Obtener las palabras clave desde la URL proporcionada
     */
    $palabras_clave_url = "https://a10.ec/app/public/api/datos_agrupados_productos";
    // $palabras_clave_url = "https://a10.ec/palabras_clave.json";

    // Obtener y decodificar las palabras clave usando Laravel HTTP Client
    try {
      //\Log::info('handleVendor_10 - Realizando solicitud para obtener palabras clave.', ['url' => $palabras_clave_url]);
      $responsePalabrasClave = Http::timeout(60)->get($palabras_clave_url);

      if ($responsePalabrasClave->failed()) {
        \Log::error("handleVendor_10 - Error al obtener palabras clave. Code: {$responsePalabrasClave->status()}.");
        return response()->json([
          'error' => true,
          'msg' => 'No se pudo obtener el archivo de palabras clave.'
        ], 200);
      }

      $palabras_clave = $responsePalabrasClave->json();

      if (json_last_error() !== JSON_ERROR_NONE) {
        \Log::error("handleVendor_10 - Error al decodificar JSON de palabras clave: " . json_last_error_msg());
        return response()->json([
          'error' => true,
          'msg' => 'Error al decodificar el JSON de palabras clave.'
        ], 200);
      }

      //\Log::info('handleVendor_10 - Palabras clave obtenidas exitosamente.', ['palabras_clave' => $palabras_clave]);
    } catch (Exception $e) {
      \Log::error("handleVendor_10 - Exception al obtener palabras clave: " . $e->getMessage());
      return response()->json([
        'error' => true,
        'msg' => 'No se pudo obtener el archivo de palabras clave.'
      ], 200);
    }

    /**
     * 3.5. Generar descripción de la imagen (si existe) con GPT-4o-mini y agregarle a mensaje_cliente antes del prompt
     */

    if (!empty($processedPromptUrlItems)) {
      foreach ($processedPromptUrlItems as $key => $item) {
        if ($item['type'] === 'image_url') {
          $imageUrl = $item['image_url']['url'];
          // Armar el prompt en la misma estructura JSON, incluyendo $mensaje_cliente como contexto
          $promptForImageDescription = [
            [
              'type' => 'text',
              'text' => "Basado en el siguiente mensaje del usuario: "
                . $mensaje_cliente
                . ". Ahora, describe de forma breve y concisa la siguiente imagen. para ello debes ocupar el siguiente glosario de palabras que seocupan para acabados de construcción, el objetivo es que entre la imagen y el mensaje del cliente logres  sintetizar un nuevo mensaje del cliente escrito. a continuación te proporciono el glosario de palabras clave en acabados de construcciónque debes ocupar para esta descripción: " . json_encode($palabras_clave)
            ],
            [
              'type' => 'image_url',
              'image_url' => [
                'url' => $imageUrl
              ]
            ]
          ];

          // Construye el payload para llamar a GPT-4o-mini
          $payloadImageDescription = [
            'model'    => 'gpt-4o-mini', // Ajusta según el modelo que uses
            'messages' => [
              [
                'role'    => 'user',
                'content' => $promptForImageDescription
              ]
            ],
            'max_tokens' => 300,
          ];

          //\Log::info('handleVendor_10 - Solicitando descripción de la imagen a OpenAI.', ['payloadImageDescription' => $payloadImageDescription]);

          // Llamada a OpenAI para obtener la descripción de la imagen
          $responseImageDescription = $this->callOpenAi($apiKey, $idOrg, $payloadImageDescription);
			
			\Log::error('handleVendor_11 - Error al describir la imagen con GPT-4o-mini.'.json_encode($responseImageDescription));
          // Manejo de la respuesta
          if ($responseImageDescription['error']) {
			  		// Contenido devuelto por GPT-4o-mini
					$descripcionImagen = "";
					// Concatenamos la descripción de la imagen a $mensaje_cliente
					// para que el motor la considere al generar las palabras clave más tarde
					$mensaje_cliente .= ' ' . $descripcionImagen;
					\Log::error('handleVendor_11 - Error al describir la imagen con GPT-4o-mini.'.json_encode($responseImageDescription));
          } else {
            // Contenido devuelto por GPT-4o-mini
            $descripcionImagen = trim($responseImageDescription['content'] ?? '');

            \Log::info('handleVendor_10 - Descripción de la imagen recibida.', [ 'descripcionImagen' => $descripcionImagen]);

            // Concatenamos la descripción de la imagen a $mensaje_cliente
            // para que el motor la considere al generar las palabras clave más tarde
            $mensaje_cliente .= ' ' . $descripcionImagen;
			  
          }
        }
      }
    }

    /**
     * 4. Preparar el prompt para generar palabras clave
     */
    $promptText = '

                        Basado en el mensaje del usuario: ' . $mensaje_cliente . ', quiero que generes una lista de palabras clave que coincidan con los productos relacionados.

                        Las respuestas deben estar **limitadas a las siguientes palabras clave**:

                        ' . json_encode($palabras_clave) . '

                        Además, incluye las palabras clave de la consulta del usuario, con un par de sinónimos:

                        **Ejemplo:**

                        Si el mensaje del usuario es: "deseo un piso para mi piscina, quiero que sea de color azul o celeste"

                        La respuesta basada en las palabras clave que te pase debería ser:

                        [
                            //acabado_producto
                            "BRILLO",
                            "SEMIBRILLANTE",
                            "MATE",
                            "ANTIDESLIZANTE",
                            //material_producto
                            "PORCELANATO",
                            "CERÁMICA",
                            "PISO",
                            //color_producto
                            "AZUL",
                            "AZUL CELESTE",
                            "CELESTE",
                            //tamano_producto
                            //pais_de_origen_producto
                            //cualquier otro filtro adicional que exista
                            "piscina", //palabra clave 1 de la consulta
                            "pileta", //sinonimo 1 de palabra clave de la consulta
                            "piso", //palabra clave 1 de la consulta
                            "suelo", //sinonimo 1 de palabra clave de la consulta
                            //cualquier otra palabra clave con su sinónimo adicional en la consulta
                        ]

                        Como respuesta, **devuelve únicamente el JSON** con las palabras clave correspondientes. (omite los comentarios dentro de las palabras clave, eso es sólo para ser mas explicativo)

                        Las palabras clave debe ser entre 10 a 40 palabras clave mas relevantes
                        - Si no encuentras palabras claves sencillamente no coloques

		';

    /**
     * 5. Incluir imágenes adjuntas si existen
     */
    $prompt = [
      [
        "type" => "text",
        "text" => $promptText
      ]
    ];

    // Añadir imágenes o documentos al prompt si existen
    if (!empty($processedPromptUrlItems)) {
      foreach ($processedPromptUrlItems as $item) {
        $prompt[] = $item;
        //\Log::debug('handleVendor_10 - Añadido elemento al prompt.', ['item' => $item]);
      }
    }

    /**
     * 6. Preparar y enviar el prompt de palabras clave a OpenAI
     */
    $payloadKeywords = [
      'model'    => 'gpt-4o-mini', // Asegúrate de usar un modelo válido
      'messages' => [
        [
          'role'    => 'user',
          'content' => $prompt
        ]
      ],
      'max_tokens' => 800,
    ];

    //\Log::info('handleVendor_10 - Enviando solicitud a OpenAI.', ['payloadKeywords' => $payloadKeywords]);

    // Llamar a OpenAI para generar palabras clave
    $responseKeywords = $this->callOpenAi($apiKey, $idOrg, $payloadKeywords);

    if ($responseKeywords['error']) {
      \Log::error('handleVendor_10 - Error al llamar a OpenAI.', ['message' => $responseKeywords['msg']]);
      return response()->json([
        'error' => true,
        'msg' => $responseKeywords['msg'] ?? 'Error en la respuesta de OpenAI.'
      ], 200);
    }

    //\Log::info('handleVendor_10 - Respuesta de OpenAI recibida.', ['content' => $responseKeywords['content']]);

    $keywords_content = $responseKeywords['content'] ?? '[]';


    // Procesar la respuesta para extraer el JSON con las palabras clave
    function extraer_json($texto)
    {
      // Expresión regular para capturar el contenido entre [ y ]
      preg_match('/\[(.*?)\]/s', $texto, $matches);

      // Verificar si se encontró un JSON válido
      if (!empty($matches)) {
        return '[' . $matches[1] . ']'; // Retornar el JSON extraído
      }

      return null; // Retornar null si no se encuentra un JSON
    }

    $json_response_keywords_encode = extraer_json($keywords_content);

    // Decodificar las palabras clave obtenidas
    $keywordsDecodificadas = json_decode($json_response_keywords_encode, true);



    if (json_last_error() !== JSON_ERROR_NONE) {
      // Manejo de errores al decodificar JSON
      \Log::error("handleVendor_10 - Error al decodificar JSON de palabras clave generadas: " . json_last_error_msg());
      return response()->json([
        'error' => true,
        'msg' => 'Error al decodificar las palabras clave generadas.'
      ], 200);
    }

    \Log::debug('handleVendor_10 - Palabras clave decodificadas.', ['keywordsDecodificadas' => $keywordsDecodificadas]);

    /**
     * 7. Consumir la API de productos (comparar_mensaje_con_productos).
     */
    $endpointProductos = "https://a10.ec/app/public/api/comparar_mensaje_con_productos";
    $parametrosProductos = [
      'apikey_openai_alfaingenius'        => $apiKey,           // Mantener nombres esperados por el endpoint
      'organizacion_openai_alfaingenius'  => $idOrg,            // Idem
      'mensaje_cliente'                   => $mensaje_cliente,
      'apikey'                            => $ApiKeyA10,
      'keywords'                          => implode(',', $keywordsDecodificadas),
    ];
    $urlProductos = $endpointProductos . '?' . http_build_query($parametrosProductos);

    //\Log::info('handleVendor_10 - Consumiento API de productos.', ['urlProductos' => $urlProductos]);

    // Consumir la API de productos:
    $responseProductos = $this->callExternalApi($urlProductos, 60);

    if ($responseProductos['error']) {
      \Log::error('handleVendor_10 - Error al consumir la API de productos.', ['message' => $responseProductos['msg']]);
      return response()->json([
        'error' => true,
        'msg' => $responseProductos['msg'] ?? 'Error al consumir la API externa.'
      ], 200);
    }

    $dataProductos = $responseProductos['data'];
    //\Log::debug('handleVendor_10 - Datos de productos recibidos.', ['dataProductos' => $dataProductos]);

    // Procesar la respuesta de la API de productos según tus necesidades
    if (isset($dataProductos['productos_similares']) && count($dataProductos['productos_similares']) > 0) {
      //\Log::info('handleVendor_10 - Productos similares encontrados.', ['count' => count($dataProductos['productos_similares'])]);

      $productos = $dataProductos['productos_similares'];
      $productos_respuesta = [];

      // **Modificación: Añadir contactUid al campo url_link_producto**
      foreach ($productos as $producto) {
        $producto_respuesta_variable = [
          'uid_deltamontero_producto' => $producto['uid_deltamontero_producto'],  // Asegúrate que el campo se llame 'uid_deltamontero_producto' en la consulta
          'detalle_producto' => $producto['detalle_producto'],
		  'descripcion_producto' => $producto['descripcion_producto'],
          'breve_descripcion_producto' => $producto['breve_descripcion_producto'],
          'categorias_producto' => $producto['categorias_producto'],
          'precio_producto' => $producto['precio_producto'],
          'stock_metros_producto' => $producto['stock_metros_producto'],
          'stock_provider' => $producto['stock_provider'],
          // **Construcción de la URL con contactUid añadido**
          'url_link_producto' => 'https://a10.ec/perfilProductos.php?id=' . $producto['uid_deltamontero_producto'] . '&uid=' . $contactUid,
		  'coincidencias' => $producto['coincidencias'],  // Añades las coincidencias
          'similarity' => $producto['similarity'],  // Añades la similitud calculada al array
        ];

        if (!empty($producto['imagen_producto'])) {
          $producto_respuesta_variable['imagen_producto'] = $producto['imagen_producto'];
        }

        if (!empty($producto['imagen_aplicado_producto'])) {
          $producto_respuesta_variable['imagen_aplicado_producto'] = $producto['imagen_aplicado_producto'];
        }

        if (!empty($producto['fecha_max_descuento_producto']) && strtotime($producto['fecha_max_descuento_producto']) >= strtotime(date('Y-m-d'))) {
          $producto_respuesta_variable['precio_descuento_producto'] = $producto['precio_descuento_producto'];
          $producto_respuesta_variable['descuento_producto'] = "Producto con descuento del :" . ($producto['descuento_producto'] * 100) . " %";
        }

        $productos_respuesta[] = $producto_respuesta_variable;

        //\Log::debug('handleVendor_10 - Producto procesado.', ['producto_respuesta_variable' => $producto_respuesta_variable]);
      }

      // Filtrar productos con imagen válida y similitud mayor a 0.55
      $productos_filtrados = array_filter($productos_respuesta, function ($producto) {
        return (isset($producto['imagen_producto']) && strlen($producto['imagen_producto']) > 10);// && ($producto['similarity'] > 0.35);
      });

      //\Log::info('handleVendor_10 - Productos filtrados.', ['count' => count($productos_filtrados)]);
	  /*
      // Ordenar los productos filtrados por 'similarity' de mayor a menor
      usort($productos_filtrados, function ($a, $b) {
        return $b['coincidencias'] <=> $a['coincidencias'];
      });
	  */

      //\Log::info('handleVendor_10 - Productos ordenados por similitud.');

      // Seleccionar hasta 3 productos
      $productos_enviados = array_slice($productos_filtrados, 0, 15);

      //\Log::info('handleVendor_10 - Productos seleccionados para enviar.', ['productos_enviados' => $productos_enviados]);
		
		
		// Opciones de promociones:
		// Definir el endpoint y los parámetros de consulta para la API de descuentos
		$endpointDescuentos = "https://a10.ec/app/public/api/showproducts";
		$parametrosDescuentos = [
			'limit'     => 5,
			'sorts'     => 'hs_images',
			'discounts' => 1,
		];

		// Construir la URL completa usando http_build_query
		$urlDescuentos = $endpointDescuentos . '?' . http_build_query($parametrosDescuentos);

		// Inicializar la variable de respuesta de descuentos
		$respuestaDescuentos = [];

		// Consumir la API de descuentos (asegúrate de tener definida la función callExternalApi)
		$responseDescuentos = $this->callExternalApi($urlDescuentos, 60);

		if (!$responseDescuentos['error']) {
			// Verificar si 'data' ya es un array o es un string JSON
			$data = $responseDescuentos['data'];
			if (is_string($data)) {
				$data_descuentos = json_decode($data, true);
			} else {
				$data_descuentos = $data;
			}

			// Obtener el total de descuentos
			$totalDescuentos = $data_descuentos['total'] ?? 0;

			if ($totalDescuentos > 0) {
				// Obtener las opciones de productos de descuentos
				$productosDescuento = $data_descuentos['results'] ?? [];

				$productosProcesadosDescuento = [];
				foreach ($productosDescuento as $producto) {
					// Quitar el campo 'embedding_producto' para evitar conflictos
					if (isset($producto['embedding_producto'])) {
						unset($producto['embedding_producto']);
					}
					// Solo conservar los campos necesarios
					$productosProcesadosDescuento[] = [
						'uid_deltamontero_producto' => $producto['uid_deltamontero_producto'],
						'detalle_producto'          => $producto['detalle_producto'],
						'precio_producto'           => $producto['precio_provisional_descuento_producto'],
						'precio_descuento_producto'           => $producto['precio_descuento_producto'],
						'fecha_max_descuento_producto'           => $producto['fecha_max_descuento_producto'],
						'stock_metros_producto'     => $producto['stock_metros_producto'],
						'stock_provider'            => $producto['stock_provider'],
						'url_link_producto'         => 'https://a10.ec/perfilProductos.php?id=' . $producto['uid_deltamontero_producto'] . '&uid=' . $contactUid,
					];
				}

				// Construir el mensaje de promoción
				$mensajePromocion = "Si el usuario pregunta por promociones, dile que actualmente contamos con $totalDescuentos opciones de promociones, " .
									"dale estas opciones de promociones: " . json_encode($productosProcesadosDescuento) .
									" y que puede ver más en el siguiente enlace: https://a10.ec/descuentos?uid={{_uid}}";

				// Construir la respuesta de descuentos
				$respuestaDescuentos = [
					'total_descuentos'    => $totalDescuentos,
					'opciones_descuentos' => $productosProcesadosDescuento,
					'mensaje_promocion'   => $mensajePromocion,
				];
				
				\Log::info('handleVendor_10 - Productos en desceunto prompt: '.json_encode($respuestaDescuentos));
			}
		}
		
      if (count($productos_enviados) >= 1) {
        // Respuesta exitosa con productos
        $resuestaFinal = [
          'mensaje' => 'Productos seleccionados del E-Commerce de A10 para responder solicitud del contacto, envía máximo 3 opciones de productos, en caso que sea un producto especifico el que pide información, dale esa información y el contacto de con quien puede conseguirlo del departamento comercial, de preferencia dale el número celular de Sandra Salcedo al siguiente número +593995341925 para que agenden una visita en el showroom para ver el material. recuerda que te pueden preguntar de un producto en esepecífico mediante nombre del producto o uid_deltamontero_producto que puedes ser AD00000 . Por otro lado, si ningún producto responde la solicitud del contacto, omite dar información de productos que no te esta pidiendo.',
          'productos' => $productos_enviados,
			'datos_adicionales' => $respuestaDescuentos,
			'horarios_de_atencion' => "Los horarios de Atención son de 9h00 a 18h00 de Lunes a Viernes y Sábados se atiende de 10h00 a 14h00",
        ];

        //\Log::info('handleVendor_10 - Respuesta final preparada con productos.');
      } else {
        // Respuesta sin productos
        $resuestaFinal = [
          'mensaje' => '.',
        ];

        //\Log::info('handleVendor_10 - Respuesta final sin productos.');
      }

      //return response()->json($resuestaFinal, 200);

      return response()->json([
        'error' => false,
        'vendor_id' => 1000,
        'processed_data' => json_encode($resuestaFinal),
        'contact_uid' => $contactUid
      ], 200);
    } else {
      // No se encontraron productos
      //\Log::info('handleVendor_10 - No se encontraron productos similares.');
      return response()->json(['mensaje' => '.'], 200);
    }
  }



  /*************************************************/
  /*     CAMBIOS MATEO                            */
  /*************************************************/

	
	/**
	 * Función personalizada para vendor_id = 13.
	 *
	 * @param array $params Parámetros decodificados del JSON.
	 * @return JsonResponse Respuesta personalizada.
	 */
	private function handleVendor_13($params)
	{
		// 1. Validación mínima de parámetros
		$question                     = $params['question'] ?? null;
		$contactUid                   = $params['contact_uid'] ?? null;
		$mensajes_anteriores_contacto = $params['mensajes_anteriores_contacto'] ?? null;
		$apiKey                       = $params['open_ai_access_key'] ?? null;
		$idOrg                        = $params['open_ai_organization'] ?? null;
		$mensaje_cliente = $question;
		$topSections = $params['top_sections'] ?? [];
		$combinedSections = $params['combined_sections'] ?? '';
		$api_data_ai = $params['api_data_ai'] ?? [];
		$contactContext = $params['contact_context'] ?? '';
		$prompt_url_items = $params['prompt_url_items'] ?? [];
		$prompt_final = $params['prompt_final'] ?? [];
		
		$contact = ContactModel::where('_uid', $contactUid)->first();

		if (!$question || !$contactUid) {
			\Log::error("handleVendor_13 - Falta alguno de los parámetros requeridos", [
				'question'   => $question,
				'contactUid' => $contactUid
			]);
			return response()->json([
				'error'   => true,
				'message' => 'Faltan parámetros "question" o "contact_uid" para vendor_id 13.'
			], 400);
		}

		/*
		 * 0. Obtener las últimas 10 interacciones del usuario en la web
		 * Se realiza una llamada al endpoint que devuelve todas las interacciones,
		 * se filtran aquellas interacciones que provengan únicamente de la web (omitiendo las de herramientas como Guzzle o Postman),
		 * se ordenan por fecha descendente y se extraen sólo los datos relevantes.
		 */
		$interaccionesUrl = "https://www.nyc.com.ec/wp-json/custom/v1/user/?uid=" . $contactUid;
		$ultimas_interacciones_usuario_web = "";

		try {
			$responseInteracciones = Http::timeout(60)->get($interaccionesUrl);

			if ($responseInteracciones->successful()) {
				$dataInteracciones = $responseInteracciones->json();

				// Filtrar interacciones: omitir aquellas cuyo user_agent contenga "guzzle" o "postman"
				$dataInteracciones = array_filter($dataInteracciones, function ($item) {
					$userAgent = $item['user_agent'] ?? '';
					return (stripos($userAgent, 'guzzle') === false && stripos($userAgent, 'postman') === false);
				});

				// Ordenar las interacciones por fecha descendente
				usort($dataInteracciones, function ($a, $b) {
					return strtotime($b['fecha']) - strtotime($a['fecha']);
				});

				// Tomar sólo las 10 interacciones más recientes
				$ultimasInteracciones = array_slice($dataInteracciones, 0, 10);

				// Extraer sólo los datos relevantes para el contexto de la IA
				$datosRelevantes = array_map(function ($item) {
					return [
						'url'        => $item['url'] ?? null,
						'parametros' => $item['parametros'] ?? null,
						'fecha'      => $item['fecha'] ?? null,
						'location'   => $item['location'] ?? null,
					];
				}, $ultimasInteracciones);

				$ultimas_interacciones_usuario_web = json_encode($datosRelevantes, JSON_PRETTY_PRINT);
				\Log::info("handleVendor_13 - ultimas interacciones usuario: " . $ultimas_interacciones_usuario_web);
			} else {
				\Log::error("handleVendor_13 - Error al obtener interacciones del usuario", [
					'url'    => $interaccionesUrl,
					'status' => $responseInteracciones->status()
				]);
			}
		} catch (\Exception $e) {
			\Log::error("handleVendor_13 - Exception al obtener interacciones del usuario", ['message' => $e->getMessage()]);
		}

		// 2. Obtener Datos Web "globales" (sin filtros)
		$web_url  = "https://www.nyc.com.ec/wp-json/custom/v1/web";
		$web_data = "";
		try {
			$responseWeb = Http::timeout(60)->get($web_url);
			if ($responseWeb->failed()) {
				\Log::error("handleVendor_13 - Error al obtener datos web", [
					'url'    => $web_url,
					'status' => $responseWeb->status()
				]);
			} else {
				$web_data = $responseWeb->body();
			}
		} catch (\Exception $e) {
			\Log::error("handleVendor_13 - Exception al obtener datos web", ['message' => $e->getMessage()]);
		}
		
		
		$productos_ecommerce = "";
		// 2. Obtener listado de keywords desde el endpoint de NYC Technology
		$keywordsEndpoint = "https://www.nyc.com.ec/wp-json/custom/v1/commerce/keywords-productos";
		try {
			$responseKeywords = Http::timeout(10)->get($keywordsEndpoint);
			if ($responseKeywords->failed()) {
				\Log::error("handleVendor_NYC - Error al obtener keywords", [
					'url'    => $keywordsEndpoint,
					'status' => $responseKeywords->status()
				]);
				return response()->json([
					'error' => true,
					'msg'   => 'No se pudo obtener el listado de palabras clave.'
				], 200);
			}
			$keywordsList = $responseKeywords->json();
			// Se espera que $keywordsList sea un array, por ejemplo: ["keyword1", "keyword2", "keyword3"]
		} catch (\Exception $e) {
			\Log::error("handleVendor_NYC - Exception al obtener keywords", ['message' => $e->getMessage()]);
			return response()->json([
				'error' => true,
				'msg'   => 'Excepción al obtener palabras clave.'
			], 200);
		}

		// 3. Construir el prompt para generar las palabras clave relevantes a partir del mensaje del usuario
		$promptText = "Basado en el siguiente mensaje del usuario: \"$mensaje_cliente\", genera una lista de palabras clave que sean relevantes para identificar productos en nuestro catálogo. " .
					  "Utiliza únicamente las siguientes palabras clave disponibles: " . json_encode($keywordsList) . ". " .
					  "Incluye también palabras o sinónimos que estén en la consulta del usuario. " .
					  "En las palabras clave tambien incluye todas las palabras que estén en el mensaje del usuario Ej: si el usuario te dijo: Quiero un iphone 16 , vas a agrear como resta al JSON final: [\"apple\", \"iphone\", \"quiero\", \"un\", \"iphone\", \"16\"]. " .
					  "Devuelve únicamente un JSON que contenga un array de palabras clave. Ejemplo: [\"android\", \"64\"]";

		$payloadKeywords = [
			'model'      => 'gpt-4o-mini', // Ajusta el modelo según corresponda
			'messages'   => [
				[
					'role'    => 'user',
					'content' => $promptText
				]
			],
			'max_tokens' => 150,
		];

		$responseGeneratedKeywords = $this->callOpenAi($apiKey, $idOrg, $payloadKeywords);

		if ($responseGeneratedKeywords['error']) {
			\Log::error("handleVendor_NYC - Error al generar palabras clave", [
				'msg' => $responseGeneratedKeywords['msg']
			]);
			return response()->json([
				'error' => true,
				'msg'   => 'Error al generar palabras clave.'
			], 200);
		}

		$generatedKeywordsContent = $responseGeneratedKeywords['content'] ?? '[]';
		
		

		// Función auxiliar para extraer un JSON (array) de la respuesta textual
		function extraer_json_array($texto)
		{
			preg_match('/\[(.*?)\]/s', $texto, $matches);
			if (!empty($matches)) {
				return '[' . $matches[1] . ']';
			}
			return null;
		}

		$jsonKeywordsExtracted = extraer_json_array($generatedKeywordsContent);
		$finalKeywords = json_decode($jsonKeywordsExtracted, true);
		//\Log::info("Keywords productos handle vendor 13: ".json_encode($finalKeywords));
		if (json_last_error() !== JSON_ERROR_NONE) {
			\Log::error("handleVendor_NYC - Error al decodificar JSON de palabras clave generadas", [
				'jsonError' => json_last_error_msg()
			]);
			return response()->json([
				'error' => true,
				'msg'   => 'Error al decodificar el JSON de palabras clave generadas.'
			], 200);
		}

		// 4. Consumir el endpoint de productos usando las palabras clave generadas
		// Se espera utilizar el endpoint: 
		// https://www.nyc.com.ec/wp-json/custom/v1/commerce/products?keywords=["android","64"]
		$searchUrl = "https://www.nyc.com.ec/wp-json/custom/v1/commerce/products?keywords=" . urlencode(json_encode($finalKeywords))."&limit=10";
		// Si se recibe un parámetro opcional 'stock', se añade a la consulta (stock=1 o stock=0)
		if (isset($params['stock'])) {
			$searchUrl .= "&stock=" . $params['stock'];
		}
		
		\Log::info("handleVendor_NYC - URL Search: ".$searchUrl );
		
		try {
			$responseProducts = Http::timeout(60)->get($searchUrl);
			if ($responseProducts->failed()) {
				\Log::error("handleVendor_NYC - Error al obtener productos", [
					'url'    => $searchUrl,
					'status' => $responseProducts->status()
				]);
				return response()->json([
					'error' => true,
					'msg'   => 'No se pudieron obtener productos.'
				], 200);
			}
			
			// Usamos json_decode() sobre el body de la respuesta para obtener un array asociativo.
			$productos_ecommerce = $responseProducts;
		} catch (\Exception $e) {
			\Log::error("handleVendor_NYC - Exception al obtener productos", ['message' => $e->getMessage()]);
			return response()->json([
				'error' => true,
				'msg'   => 'Excepción al obtener productos.'
			], 200);
		}
		//\Log::info("productos handle vendor 13: ".$productos_ecommerce);

		// 4. Datos de las sucursales (business data) incluyendo el id del usuario correspondiente
		$businessData = [
			[
				'id'        => 'nyctechnologyccelcaracol',
				'user_id'   => '36',
				'nombre'    => 'C.C. El Caracol - Quito',
				'direccion' => 'Norte de Quito, Av amazonas y naciones unidas, centro comercial Caracol, local 46 PB, Quito',
				'descripcion'=> '',
            	'horarios'   => 'Todos los días: 10h00 a 20h00 y Domingos: 10h00 a 17h00',
				'lat'       => -0.176281,
				'lng'       => -78.485821,
				'telefono'  => '+593984190433'
			],
			[
				'id'        => 'nyctechnologyccelespiral',
				'user_id'   => '37',
				'nombre'    => 'C.C. El Espiral - Quito',
				'direccion' => 'Centro de Quito, Av amazonas y Jorge Washington, centro comercial Espiral, local 64 PB',
				'descripcion'=> '',
            	'horarios'   => 'Todos los días: 10h00 a 20h00 y Domingos: 10h00 a 17h00',
				'lat'       => -0.206399,
				'lng'       => -78.495719,
				'telefono'  => '+593981538416'
			],
			[
				'id'        => 'nyctechnologyccelrecreo',
				'user_id'   => '38',
				'nombre'    => 'C.C. El Recreo - Quito',
				'direccion' => 'Sur de Quito, Av Maldonado, centro comercial el recreo, entrando por el acceso 7, local L18 esquinero',
				'descripcion'=> '',
            	'horarios'   => 'Todos los días de 10:00hr a 20:00hr',
				'lat'       => -0.252429,
				'lng'       => -78.523060,
				'telefono'  => '+593964039569'
			],
			[
				'id'        => 'null',
				'user_id'   => 'null',
				'nombre'    => 'Ninguna de las anteriores',
				'direccion' => 'Quito - Ecuador',
				'descripcion'=> '',
            	'horarios'   => 'Todos los días: 10h00 a 20h00 y Domingos: 10h00 a 17h00',
				'lat'       => -0.176281,
				'lng'       => -78.485821,
				'telefono'  => '+593987904123'
			]
		];

		// 5. Preparar el prompt para determinar la ubicación más conveniente
		$promptText = "Analiza los siguientes mensajes anteriores del usuario: " . $mensajes_anteriores_contacto .
                      "\n\nY su consulta actual: " . $question .
					  "\n\nY El usuario y sucursal actualmente asignado es: user_id= " .$contact->assigned_users__id . " sin embargo cambialo solamente si es necesario, caso contrario le dejas en su actual ubicación. si es null el user_id y no ves la necesidad de cambiarle de ubicación, no lo hagas.".
                      "\n\nTeniendo en cuenta esta información, determina cuál de las siguientes sucursales es la más conveniente para el usuario:" .
                      "\n1. C.C. El Caracol - Quito" .
                      "\n2. C.C. El Espiral - Quito" .
                      "\n3. C.C. El Recreo - Quito" .
					  "\n4. Ninguna de las anteriores" .
					  "\n\n IMPORTANTE: Si no menciona una sucursal explicitamente no debes cambiar y debes seleccionar si o si Ninguna de las anteriores." .
                      "\n\nAdemás, se proporcionan los datos de las sucursales (incluyendo el id del usuario correspondiente):" .
                      "\n" . json_encode($businessData, JSON_PRETTY_PRINT) .
                      "\n\nResponde únicamente con un JSON que contenga la ubicación seleccionada y su id de usuario, por ejemplo:" .
                      "\n{\"ubicacion\": \"C.C. El Caracol - Quito\", \"user_id\": \"36\"}" .
                      "\n\nSi no existe interés en ninguna sucursal en particular, responde con:" .
                      "\n{\"ubicacion\": null, \"user_id\": null}";

		$payloadLocation = [
			'model'      => 'gpt-4o-mini',
			'messages'   => [
				[
					'role'    => 'user',
					'content' => $promptText
				]
			],
			'max_tokens' => 150,
		];

		// 6. Llamada a OpenAI para determinar la ubicación
		$responseLocation = $this->callOpenAi($apiKey, $idOrg, $payloadLocation);

		if ($responseLocation['error']) {
			\Log::error("handleVendor_13 - Error al llamar a OpenAI para determinar ubicación", [
				'msg' => $responseLocation['msg']
			]);
			return response()->json([
				'error'   => true,
				'message' => 'Error al determinar la ubicación.'
			], 200);
		}

		$locationContent = $responseLocation['content'] ?? '';

		// Función para extraer el JSON de la respuesta
		if (!function_exists('extraer_json')) {
			function extraer_json($texto)
			{
				preg_match('/\{(.*?)\}/s', $texto, $matches);
				if (!empty($matches)) {
					return '{' . $matches[1] . '}';
				}
				return null;
			}
		}

		$jsonLocation = extraer_json($locationContent);

		$ubicacionSeleccionada = null;
		$userIdSeleccionado    = null;
		if ($jsonLocation) {
			$ubicacionData = json_decode($jsonLocation, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				if (array_key_exists('ubicacion', $ubicacionData)) {
					$ubicacionSeleccionada = $ubicacionData['ubicacion'];
				}
				if (array_key_exists('user_id', $ubicacionData)) {
					$userIdSeleccionado = $ubicacionData['user_id'];
				}
			} else {
				\Log::error("handleVendor_13 - Error al decodificar JSON", [
					'jsonError' => json_last_error_msg()
				]);
			}
		} else {
			\Log::error("handleVendor_13 - No se pudo extraer JSON de la respuesta de OpenAI");
		}

		// 7. Validar la respuesta y ajustar los valores en función de las sucursales conocidas
		// Actualizamos el branch mapping para que devuelva valores enteros (de acuerdo a los datos de negocio)
		$branchMapping = [
			"C.C. El Caracol - Quito" => 36,
			"C.C. El Espiral - Quito"  => 37,
			"C.C. El Recreo - Quito"   => 38
		];

		if (!array_key_exists($ubicacionSeleccionada, $branchMapping)) {
			$ubicacionSeleccionada = null;
			$userIdSeleccionado    = null;
		} else {
			$userIdSeleccionado = $branchMapping[$ubicacionSeleccionada];
		}

		// 8. Actualizar en la tabla de contactos el campo assigned_users__id
		$assignedUserValue = ($userIdSeleccionado !== null) ? (int)$userIdSeleccionado : null;
		// Se actualiza el contacto identificado por _uid ($contactUid)
		if ($assignedUserValue !== null) {
			try {
				ContactModel::where('_uid', $contactUid)
					->update(['assigned_users__id' => $assignedUserValue]);
				
				
				$contactFullName = $contact->first_name ." ".$contact->last_name ??'Nuevo Cliente';
				$receivedMessage = $question ?? 'Has recibido un nuevo mensaje.';

				// Construir la URL usando el _uid
				$url = $contactUid 
					? "https://crm.alfabusiness.app/vendor-console/whatsapp/contact/chat/{$contactUid}" 
					: "https://crm.alfabusiness.app/vendor-console/whatsapp/contact/chat/";
				$vendorId = $contact->vendors__id;
				
				// Enviar notificación push si el mensaje es recibido y el nombre está disponible
				$this->sendPushNotification($contactFullName, $receivedMessage, $vendorId, $url, $contactUid);
				
			} catch (\Exception $e) {
				\Log::error("handleVendor_13 - Error actualizando ContactModel", [
					'message' => $e->getMessage()
				]);
			}
		}

		// 9. Preparar la respuesta final
		$respuestaFinal = [
			'ultimas_interacciones_usuario_web' => $ultimas_interacciones_usuario_web,
			'ubicacion_seleccionada' => "IMPORTANTE: Los datos de la ubicación asignada para el usuario es esta: ".$ubicacionSeleccionada." . En caso que no tenga ubicacion_seleccionada sea así: {\"ubicacion\": null, \"user_id\": null} y quiere concretar un pedido, pregúntale por la ubicación que le pueda quedar más cercana.",
			'ubicacion_o_sucursales' => "IMPORTANTE: Todas estas son las ubicaciones o sucursales disponibles: ".json_encode($businessData),
			'user_id_seleccionado'                => $userIdSeleccionado,
			'web'                    => "Los datos de la página web son: " . $web_data." . IMPORTANTE: Si no tienes información suficiente para responder una consulta y no esta en la web. No te inventes, sino pásale el contacto del asesor asignado ".$userIdSeleccionado." . en caso que sea null, pregúntale su ubicación y redirigele con el asesor más cercano a su ubicación.",
			'productos'             => "El Listado de Productos NYC Technology. IMPORTANTE: Siempre pasa el Link / URL y reemplaza el {{uid}} con este uid= ".$contactUid." en los enlaces de los productos. y siempre que proporciones información del producto, envía el enlace correspondiente: " . $productos_ecommerce." . IMPORTANTE: Responde con productos sólo en caso que responda a la pregunta del contacto: ' ".$question." '. Si no es necesario responderle con productos, entonces no lo hagas, porfavor no me muestres productos que no te he pedido. IMPORTANTE: Intenta ser preciso con tus respuestas acerca de lo que hay de disponibilidad de productos. no te inventes productos que no existen. en ese caso dale alternativas de productos. Comenta al usuario que por le pago en efectivo o transferencia los productos tienen un 20 % de descuento. Si el usuario te dice que quiere comprar un celular, pásale el contacto de la ubicación más cercana para que realice una llamada para coordinar la visita para ver el producto o para comprar. eso sólo en caso que ya tenga una ubicacion_seleccionada , caso contrario pregúntale cuál de las sucursales le queda más cerca. y si el user_id_seleccionado esta seleccionado y no es null, pásale sus datos de contacto para que llame a ese usuario o vaya a visitar la tienda",
		];

		return response()->json([
			'error'         => false,
			'vendor_id'     => 13,
			'contact_uid'   => $contactUid,
			'processed_data'=> json_encode($respuestaFinal)
		], 200);
	}
	
}
