<?php

/**
 * OpenAiService.php -
 *
 *-----------------------------------------------------------------------------
 */

namespace App\Yantrana\Components\WhatsAppService\Services;


use Exception;
use OpenAI\Client;
use Carbon\Carbon;
use App\Yantrana\Base\BaseRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Yantrana\Base\BaseEngine;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

// Modelos base
use App\Yantrana\Components\Contact\Models\ContactModel;
use App\Yantrana\Components\Contact\Models\ContactCustomFieldModel;       // NUEVO
use App\Yantrana\Components\Contact\Models\ContactCustomFieldValueModel; // NUEVO
use App\Yantrana\Components\Contact\Models\ContactGroupModel;            // NUEVO
use App\Yantrana\Components\Contact\Models\ContactLabelModel;            // NUEVO
use App\Yantrana\Components\Contact\Models\GroupContactModel;            // NUEVO
use App\Yantrana\Components\Contact\Models\LabelModel;                   // NUEVO

use App\Yantrana\Components\Vendor\Models\VendorModel;


// Importar el modelo existente
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel;

// Agregar dependencias de funciones específicas
use App\Yantrana\Components\WhatsAppService\Services\CustomApisService;

use Illuminate\Support\Facades\Log;


class OpenAiService extends BaseEngine
{
  /**
   * @var CustomApisService
   */
  protected $customApisService;

  /**
   * Constructor para inyectar CustomApisService.
   *
   * @param CustomApisService $customApisService
   */
  public function __construct(CustomApisService $customApisService)
  {
    $this->customApisService = $customApisService;
  }

  /**
   * Inicializa la configuración de OpenAI
   */
  protected function initConfiguration($vendorId = null, $accessKey = null, $orgKey = null)
  {
    if (!$vendorId) {
      $vendorId = getVendorId();
    }
    config([
      'openai.api_key' =>  $accessKey ?: getVendorSettings('open_ai_access_key', null, null, $vendorId),
      'openai.organization' => $orgKey ?: getVendorSettings('open_ai_organization', null, null, $vendorId),
    ]);
  }

  /**
   * ===========================================
   * ============= NUEVAS UTILIDADES ==========
   * ===========================================
   */

  /**
   * Extrae todos los links de tipo http(s) a partir del string $question.
   */
  private function extractUrlsFromText($question)
  {
    // 1. Reemplazar `\/` por `/`
    $question = str_replace('\\/', '/', $question);

    // 2. Capturar URLs con http(s)
    $regex = '/(https?:\/\/[^\s]+)/i';
    preg_match_all($regex, $question, $matches);

    return $matches[0] ?? [];
  }

  /**
   * Intenta deducir el tipo de archivo a partir de la extensión del URL.
   */
  private function guessFileTypeFromUrl($url)
  {
    $parsed = parse_url($url);
    if (!isset($parsed['path'])) {
      return 'text';
    }

    $extension = strtolower(pathinfo($parsed['path'], PATHINFO_EXTENSION));

    // Extensiones de imagen
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
    // Extensiones de audio
    $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];
    // Extensiones de documento
    $docExtensions   = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];

    if (in_array($extension, $imageExtensions)) {
      return 'image_url';
    } elseif (in_array($extension, $audioExtensions)) {
      return 'audio_url';
    } elseif (in_array($extension, $docExtensions)) {
      return 'document_url';
    }

    return 'text';
  }

  private function transcribeImagen($url)
  {
    // Obtén tu API key y Org ID (ajusta según tu configuración)
    $apiKey = "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";
    $idOrg   = "org-5H1dwf0eKn5PCR8XaZYINbxo";

    /*
		 * 2) Construye el prompt para la transcripción
		 */
    $promptForTranscription = [
      [
        'type'  => 'text',
        'text'  => 'Describe todo el contenido visible de la siguiente imagen, preservando la mayor fidelidad posible y sin agregar explicaciones adicionales:'
      ],
      [
        'type'       => 'image_url',
        'image_url'  => [
          'url'    => $url,      // ← ahora es una data-URL, no una URL remota
        ]
      ]
    ];




    // Payload para la llamada a OpenAI
    $payload = [
      'model'      => 'gpt-4o-mini',
      'messages'   => [
        [
          'role'    => 'user',
          'content' => $promptForTranscription,
        ],
      ],
      'max_tokens' => 1024,
    ];

    try {
      $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'OpenAI-Organization' => $idOrg,
        'Content-Type' => 'application/json',
      ])->timeout(600)->post('https://api.openai.com/v1/chat/completions', $payload);

      //\Log::info("Respuesta al generar la IMagen de Open AI: ".$response);

      if ($response->failed()) {
        \Log::error("Hubo un error al generar la descripcion de la Imagen: {$response->status()}. Response: {$response->body()}");
        $respuesta = "";
        return $respuesta;
      }

      $arrayResponse = $response->json();

      // Extraer sólo el contenido
      $respuesta = $arrayResponse['choices'][0]['message']['content'] ?? '';

      // Retorna el texto transcrito
      return $respuesta;
    } catch (Exception $e) {
      \Log::error("La imagen no se ha podido generar" . $e->getMessage());
      $respuesta = "";
      return $respuesta;
    }
  }

  /**
   * Construye un array de prompt a partir de los URLs encontrados en la pregunta.
   */
  private function buildPromptItemsForUrls(&$question)
  {
    $urls = $this->extractUrlsFromText($question);
    $promptItems = [];

    foreach ($urls as $url) {
      $fileType = $this->guessFileTypeFromUrl($url);

      if ($fileType === 'image_url') {
        //$text_transcription = $this->transcribeImagen($url);
        $text_transcription = "";
        $promptItems[] = [
          'type' => 'image_url',
          'image_url' => [
            'url' => $url
          ],
          'text_transcription' => $text_transcription,
        ];

        \Log::info('texto al transcribir la imagen: ' .  $text_transcription);
      } elseif ($fileType === 'audio_url') {

        $text_transcription = $this->transcribeAudio($url);

        $promptItems[] = [
          'type' => 'audio_url',
          'audio_url' => [
            'url' => $url
          ],
          'text_transcription' => $text_transcription,
        ];

        \Log::info('texto al transcribir el audio: ' .  $text_transcription);
      } elseif ($fileType === 'document_url') {
        $promptItems[] = [
          'type' => 'document_url',
          'document_url' => [
            'url' => $url
          ]
        ];
      } else {
        // Caso genérico: texto
        $promptItems[] = [
          'type' => 'text',
          'text' => "Contenido detectado en enlace: $url"
        ];
      }

      // (Opcional) Eliminar el link para que no aparezca repetido
      $question = str_replace($url, '', $question);
    }

    return $promptItems;
  }

  /**
   * Transcribe Audio (Deepgram u otro servicio)
   */
  private function transcribeAudio($audioUrl)
  {
    try {
      $apiKey = 'f136970297077bf2874b44d26846ec7c959b06e8';
      $endpoint = 'https://api.deepgram.com/v1/listen?language=es&punctuate=true';

      $data = ['url' => $audioUrl];
      $ch = curl_init($endpoint);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Token $apiKey",
        "Content-Type: application/json",
      ]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $response = curl_exec($ch);
      if ($response === false) {
        throw new \Exception(curl_error($ch));
      }
      curl_close($ch);

      $result = json_decode($response, true);
      if (!empty($result['results']['channels'][0]['alternatives'][0]['transcript'])) {
        return $result['results']['channels'][0]['alternatives'][0]['transcript'];
      }
    } catch (\Throwable $e) {
      \Log::error("Error al transcribir audio: " . $e->getMessage());
    }

    // Retorna vacío si falla
    return '';
  }

  /**
   * ===========================================
   * ============ MÉTODOS PRINCIPALES =========
   * ===========================================
   */

  /**
   * Genera embeddings para chunks de texto grandes.
   */
  public function embedLargeData($largeData, $options = [])
  {
    $options  = array_merge([
      'open_ai_access_key' => null,
      'open_ai_organization_id' => null
    ], $options);

    $this->initConfiguration(null, $options['open_ai_access_key'], $options['open_ai_organization_id']);

    $sections = $this->splitDataIntoChunks($largeData);
    $embeddings = [];

    foreach ($sections as $section) {
      $response = OpenAI::embeddings()->create([
        'model' => 'text-embedding-ada-002',
        'input' => $section,
      ]);
      $embeddings[] = $response['data'][0]['embedding'];
    }

    return [
      'data' => $sections,
      'embedding' => $embeddings,
    ];
  }

  private function splitDataIntoChunks($data, $maxChunkSize = 500)
  {
    $chunks = [];
    $currentChunk = '';
    $sentences = preg_split('/(?<=[.?!])\s+/', $data);

    foreach ($sentences as $sentence) {
      if (strlen($currentChunk . ' ' . $sentence) > $maxChunkSize) {
        $chunks[] = trim($currentChunk);
        $currentChunk = $sentence;
      } else {
        $currentChunk .= ' ' . $sentence;
      }
    }

    if (!empty($currentChunk)) {
      $chunks[] = trim($currentChunk);
    }

    return $chunks;
  }

  private function embedQuestion($question)
  {
    $response = OpenAI::embeddings()->create([
      'model' => 'text-embedding-ada-002',
      'input' => $question,
    ]);

    return $response['data'][0]['embedding'];
  }

  private function cosineSimilarity($vecA, $vecB)
  {
    $dotProduct = array_sum(array_map(function ($a, $b) {
      return $a * $b;
    }, $vecA, $vecB));

    $magnitudeA = sqrt(array_sum(array_map(function ($a) {
      return $a ** 2;
    }, $vecA)));
    $magnitudeB = sqrt(array_sum(array_map(function ($b) {
      return $b ** 2;
    }, $vecB)));

    return $dotProduct / ($magnitudeA * $magnitudeB);
  }

  private function findRelevantSection($question, $vendorId)
  {
    $this->initConfiguration($vendorId);
    $questionEmbedding = $this->embedQuestion($question);

    $largeDataRecord = getVendorSettings('open_ai_embedded_training_data', null, null, $vendorId);
    $sections = $largeDataRecord['data'];
    $storedEmbeddings = $largeDataRecord['embedding'];

    $similarities = [];
    foreach ($storedEmbeddings as $index => $sectionEmbedding) {
      $similarity = $this->cosineSimilarity($questionEmbedding, $sectionEmbedding);
      $similarities[] = [
        'section' => $sections[$index],
        'similarity' => $similarity,
      ];
    }

    usort($similarities, function ($a, $b) {
      return $b['similarity'] <=> $a['similarity'];
    });

    return $similarities[0]['section'] ?? '';
  }

  private function findTopRelevantSections($question, $vendorId, $topN = 3)
  {
    $this->initConfiguration($vendorId);

    $questionEmbedding = $this->embedQuestion($question);
    $largeDataRecord = getVendorSettings('open_ai_embedded_training_data', null, null, $vendorId);
    $sections = $largeDataRecord['data'];
    $storedEmbeddings = $largeDataRecord['embedding'];

    $similarities = [];
    foreach ($storedEmbeddings as $index => $sectionEmbedding) {
      $similarity = $this->cosineSimilarity($questionEmbedding, $sectionEmbedding);
      $similarities[] = [
        'section' => $sections[$index],
        'similarity' => $similarity,
      ];
    }

    usort($similarities, function ($a, $b) {
      return $b['similarity'] <=> $a['similarity'];
    });

    return array_slice($similarities, 0, $topN);
  }

  private function getContactData($contactUid): string
  {
    try {
      $contact = ContactModel::where('_uid', $contactUid)->first();

      if (!$contact) {
        \Log::warning("No se encontró el contacto con _uid: {$contactUid}");
        return json_encode([
          'error' => true,
          'message' => "Información del contacto no disponible."
        ]);
      }

      $contactArray = $contact->toArray();

      $customFieldValues = ContactCustomFieldValueModel::where('contacts__id', $contact->_id)
        ->with('customField')
        ->get();

      $contactArray['custom_fields'] = $customFieldValues->map(function ($cfv) {
        return [
          'custom_field_uid'   => $cfv->customField->_uid ?? null,
          'input_name'         => $cfv->customField->input_name ?? null,
          'input_type'         => $cfv->customField->input_type ?? null,
          'field_value'        => $cfv->field_value,
        ];
      })->toArray();

      $contactGroups = GroupContactModel::where('contacts__id', $contact->_id)->get();
      $groupIds = $contactGroups->pluck('contact_groups__id')->unique()->values();

      $groups = ContactGroupModel::whereIn('_id', $groupIds)->get();
      $contactArray['groups'] = $groups->map(function ($group) {
        return [
          'group_uid'   => $group->_uid,
          'title'       => $group->title,
          'description' => $group->description,
        ];
      })->toArray();

      $contactLabels = ContactLabelModel::where('contacts__id', $contact->_id)->get();
      $labelIds = $contactLabels->pluck('labels__id')->unique()->values();

      $labels = LabelModel::whereIn('_id', $labelIds)->get();
      $contactArray['labels'] = $labels->map(function ($label) {
        return [
          'label_uid'  => $label->_uid,
          'title'      => $label->title,
          'text_color' => $label->text_color,
          'bg_color'   => $label->bg_color,
        ];
      })->toArray();

      if (isset($contactArray['__data']) && is_string($contactArray['__data'])) {
        $decodedData = json_decode($contactArray['__data'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $contactArray['__data'] = $decodedData;
        } else {
          $contactArray['__data'] = null;
        }
      }

      return json_encode($contactArray, JSON_PRETTY_PRINT);
    } catch (\Throwable $e) {
      \Log::error("Error al obtener datos del contacto: " . $e->getMessage());
      return json_encode([
        'error'   => true,
        'message' => "Información del contacto no disponible debido a un error.",
        'details' => $e->getMessage()
      ]);
    }
  }

  /**
   * Obtiene los últimos 10 mensajes de un contacto específico.
   *
   * @param int $vendorId
   * @param int $contactId
   * @return string
   */
  private function obtenerMensajesAnteriores($vendorId, $contactId)
  {
    try {
      $mensajes = WhatsAppMessageLogModel::where('vendors__id', $vendorId)
        ->where('contacts__id', $contactId)
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get(['message', 'created_at'])
        ->reverse() // Para ordenar cronológicamente
        ->map(function ($mensaje) {
          return [
            'mensaje' => $mensaje->message,
            'fecha' => $mensaje->created_at->toDateTimeString(),
          ];
        })
        ->toArray();

      return json_encode($mensajes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
      \Log::error("Error al obtener mensajes anteriores: " . $e->getMessage());
      return json_encode([
        'error' => true,
        'message' => "No se pudieron obtener los mensajes anteriores."
      ]);
    }
  }

  /**
   * ========== GENERA RESPUESTA (UNA SOLA SECCIÓN) ==========
   */
  public function generateAnswerFromSingleSection($question, $vendorId, $contactUid)
  {

    // 1. Sección relevante
    $relevantSection = $this->findRelevantSection($question, $vendorId);
    $botName  = getVendorSettings('open_ai_bot_name', null, null, $vendorId);

    // 2. Info del contacto
    $contactContext = $this->getContactData($contactUid);

    // 3. Detectar URLs
    $promptUrlItems = $this->buildPromptItemsForUrls($question);

    // --- Nueva Lógica: transcribir audios antes de formar el prompt final
    foreach ($promptUrlItems as &$item) {
      if ($item['type'] === 'audio_url' && isset($item['audio_url']['url'])) {
        $urlAudio = $item['audio_url']['url'];
        $textoTranscrito = $this->transcribeAudio($urlAudio);

        // Convertirlo a "type" => "text"
        $item['type'] = 'text';
        $item['text'] = "Transcripción del audio: {$textoTranscrito}";

        // Eliminar 'audio_url'
        unset($item['audio_url']);
      }
    }
    unset($item);

    // 4. Texto principal
    $mainText = "Basándote en el siguiente contenido y la información del usuario, responde la pregunta de manera clara. "
      . "Incluye saltos de línea donde corresponda.\n\n"
      . "Información de contacto: $contactContext\n\n"
      . "Contenido relevante: $relevantSection\n\n"
      . "Pregunta: $question";

    // 5. Array final
    $promptFinal = [
      [
        "type" => "text",
        "text" => $mainText
      ]
    ];

    // 6. Agregar items
    if (!empty($promptUrlItems)) {
      $promptFinal = array_merge($promptFinal, $promptUrlItems);
    }

    // 7. Petición a OpenAI (completions o chat, según tu modelo)
    try {
      $response = OpenAI::completions()->create([
        'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
        'messages' => [
          [
            'role' => 'system',
            'content' => "Eres una persona normal y servicial que responde de forma clara."
              . ($botName ? " Tu nombre es $botName." : ""),
          ],
          [
            'role' => 'user',
            // Si tu API no admite arrays, haz json_encode($promptFinal)
            'content' => $promptFinal
          ]
        ],
        'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
      ]);

      // Ajusta la forma de leer la respuesta según sea Chat o Completions
      return trim($response['choices'][0]['text'] ?? '');
    } catch (Exception $e) {
      \Log::error("Error al generar respuesta con OpenAI: " . $e->getMessage());
      return "Lo siento, no pude procesar tu solicitud en este momento. Por favor, intenta de nuevo más tarde.";
    }
  }

  /**
   * ========== GENERA RESPUESTA (MÚLTIPLES SECCIONES) ==========
   */
  public function generateAnswerFromMultipleSections($question, $contactUid, $vendorId)
  {

    \Log::info("question: " . $question);

    $botName = getVendorSettings('open_ai_bot_name', null, null, $vendorId);
    $botDataSourceType = getVendorSettings('open_ai_bot_data_source_type', null, null, $vendorId);

    $timezone = getVendorSettings('timezone', null, null, $vendorId);

    if ($timezone == null) {
      $timezone = 'America/Bogota';
    }

    if ($botDataSourceType == 'assistant') {
      // ... tu lógica especial
      $this->initConfiguration($vendorId);
      try {
        // ...
      } catch (Exception $e) {
        \Log::error("Error al ejecutar el thread de OpenAI: " . $e->getMessage());
        return "Lo siento, no pude procesar tu solicitud en este momento. Por favor, intenta de nuevo más tarde.";
      }
    }

    // 1. Obtener el contacto para obtener su ID
    $contact = ContactModel::where('_uid', $contactUid)->first();

    if (!$contact) {
      \Log::warning("No se encontró el contacto con _uid: {$contactUid}");
      return "Información del contacto no disponible.";
    }


    $contactId = $contact->_id;
    $wa_id_contact = $contact->wa_id;

    // 2. Obtener los mensajes anteriores
    $mensajes_anteriores_contacto = $this->obtenerMensajesAnteriores($vendorId, $contactId);



    $hora_actual = Carbon::now($timezone)->format('Y-m-d H:i:s'); // Restar 5 horas
    $dia_hoy = Carbon::now($timezone)->locale('es')->isoFormat('dddd'); // Día en español

    //\Log::info("Hora actual obtenida con Carbon: {$hora_actual}");
    //\Log::info("Hoy es: {$dia_hoy}");


    // 1. Secciones relevantes
    $topSections = $this->findTopRelevantSections($question, $vendorId);
    $combinedSections = implode("\n\n", array_column($topSections, 'section'));

    // 2. Endpoints
    $api_data_ai = [];
    $maxApis = 5;
    for ($i = 0; $i < $maxApis; $i++) {
      $apiName = getVendorSettings("api_data_ai_{$i}_name", null, null, $vendorId) ?? "";
      $apiEndpoint = getVendorSettings("api_data_ai_{$i}_endpoint", null, null, $vendorId) ?? "";
      if (!empty($apiEndpoint)) {
        $api_data_ai[] = [
          'name' => $apiName,
          'endpoint' => $apiEndpoint,
        ];
      }
    }

    foreach ($api_data_ai as $api_data) {
      $endpoint = $api_data['endpoint'];
      if (!empty($endpoint)) {
        $fetchedData = @file_get_contents($endpoint);
        if ($fetchedData !== false) {
          $decodedData = json_decode($fetchedData, true);
          if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($decodedData['desired_key'])) {
              $combinedSections .= "\n\n" . $decodedData['desired_key'];
            } else {
              $combinedSections .= "\n\n" . $fetchedData;
            }
          } else {
            $combinedSections .= "\n\n" . $fetchedData;
          }
        } else {
          \Log::error("Error al obtener datos del endpoint: {$endpoint}");
          $combinedSections .= "\n\n[Error al obtener datos de {$api_data['name']}]";
        }
      }
    }

    // 3. Info contacto
    $contactContext = $this->getContactData($contactUid);

    //\Log::info("Contact Context" . json_encode($contactContext));

    // 4. Detectar URLs (incluyendo audios)
    $promptUrlItems = $this->buildPromptItemsForUrls($question);

    // --- Transcribir audios aquí también
    foreach ($promptUrlItems as &$item) {
      if ($item['type'] === 'audio_url' && isset($item['audio_url']['url'])) {
        $urlAudio = $item['audio_url']['url'];
        $textoTranscrito = $urlAudio = $item['text_transcription'];

        // Convertimos a tipo => "text"
        $item['type'] = 'text';
        $item['text'] = "Transcripción del audio: {$textoTranscrito}";

        unset($item['audio_url']);
      }
    }
    unset($item);

    // Obtener el penúltimo mensaje del contacto
    $penultimoMensaje = WhatsAppMessageLogModel::where('vendors__id', $vendorId)
      ->where('contacts__id', $contactId)
      ->orderBy('created_at', 'desc')
      ->skip(1)
      ->first();

    // Inicializar variable de saludo
    $saludo = "";
    $requiere_saludo = "no";


    if (!$penultimoMensaje) {
      // Maneja este caso según tu lógica. Por ejemplo, podrías usar el último mensaje o asignar un saludo por defecto.
      $saludo = "Salúdale al contacto, Dile, 'Hola! cómo estás?' o 'Buenos Días/Buenas Tardes/ Buenas Noches' dependiendo de la hora actual y preséntate, da un saludo y una breve descripción de lo que haces";
    } else {
      // Convertir la fecha del penúltimo mensaje a objeto Carbon y ajustarla a la zona horaria deseada
      $fechaPenultimoMensaje = Carbon::parse($penultimoMensaje->created_at)
        ->setTimezone($timezone);

      // Definir la fecha límite: 24 horas atrás desde el momento actual
      $limite24Horas = Carbon::now($timezone)->subHours(12);

      // Comparar las fechas para determinar el saludo
      if ($fechaPenultimoMensaje->lessThan($limite24Horas)) {
        // El penúltimo mensaje fue enviado hace más de 24 horas
        $saludo = "Salúdale al contacto, Dile, 'Hola! cómo estás?' o 'Buenos Días/Buenas Tardes/ Buenas Noches' dependiendo de la hora actual";
        //requiere_saludo

        $requiere_saludo = "si";
      } else {
        // Menos de 24 horas han pasado desde el penúltimo mensaje
        $saludo = "NUNCA SALUDES, a menos que en la Pregunta te salude";
        $requiere_saludo = "no";
      }
    }



    $vendor = VendorModel::where([
      '_id' => $vendorId
    ])->first();

    $vendorUid = $vendor->_uid;

    //4.1 Traer datos de Términos y Condiciones y Políticas de Privacidad
    $terms_and_conditions_vendor = "https://crm.alfabusiness.app/legal/terms-and-conditions/" . $vendor->_uid;
    //$privacy_policy_vendor = "https://crm.alfabusiness.app/legal/privacy-policy/".getVendorUid();


    //4.2.- cambiar combinacion de secciones por todo el prompt de data
    $openAITariningData = getVendorSettings('open_ai_embedded_training_data', null, null, $vendorId);
    $openAIDataPrompt = json_encode($openAITariningData['data']);



    //$combinedSections = $openAIDataPrompt;

    // 5. Texto principal
    $mainText = "Basándote en la siguiente información del usuario y el contenido adicional, "
      . "responde la pregunta de manera clara, consisa a lo que necesita el contacto en su pregunta, toma en cuenta los mensajes anteriores del contacto en caso que sea necesario, y coloca saltos de línea donde sea necesario. Intenta parecer mas una respuesta humana.\n\n"
      . " $saludo\n\n"
      . "Información de contacto: $contactContext\n\n"
      . "Mensajes anteriores del contacto: $mensajes_anteriores_contacto\n\n"
      . "IMPORTANTE: Procura no repetir información que haz mencionado anteriormente en los mensajes anteriores ya que el usuario ya sabe\n\n"
      . "Contenido (varias secciones + APIs): $openAIDataPrompt\n\n"
      . "Este es el Prompt URL Items: " . json_encode($promptUrlItems) . "\n\n"
      . "La Hora y fecha actual es: {$hora_actual} y el día es: {$dia_hoy}" . "\n\n"
      . "Si te preguntan por los términos y condiciones o políticas de Privacidad di que en este URL van a poder encontrar toda la información: {$terms_and_conditions_vendor}" . "\n\n"
      . "ABSOLUTAMENTE IMPORTANTE: En tu respuesta, NO UTILICES paréntesis, asteriscos ni corchetes al incluir enlaces. "
      . "Asegúrate de que los enlaces se presenten separados por espacios para evitar que se corten o alteren.\n\n"
      . "Pregunta: $question";

    // 6. prompt final
    $promptFinal = [
      [
        "type" => "text",
        "text" => $mainText
      ]
    ];

    if (!empty($promptUrlItems)) {
      $promptFinal = array_merge($promptFinal, $promptUrlItems);
    }

    //apis acces open ai
    $openAiApiKey = getVendorSettings('open_ai_access_key', null, null, $vendorId);
    $openAiOrgKey = getVendorSettings('open_ai_organization', null, null, $vendorId);



    //conexionweb
    $vendor_webhook_endpoint = getVendorSettings('vendor_webhook_endpoint', null, null, $vendorId);
    $flowise_url = getVendorSettings('flowise_url', null, null, $vendorId);
	  
	//\Log::info("CONTACT CONTEXT = " . $contactContext);
	
	// ► Nombre del usuario (usa full_name si existe; de lo contrario first_name + last_name)
	$nombreUsuario = $contactContext['full_name']
	?? trim(($contactContext['first_name'] ?? '') . ' ' . ($contactContext['last_name'] ?? ''));


    // Extraemos el host de la URL usando parse_url() Ej: bluemagic.ec
    $domain_variable_vendor = parse_url($vendor_webhook_endpoint, PHP_URL_HOST);
    //vendorUid


    $vendorAccessToken = getVendorSettings('vendor_api_access_token', null, null, $vendorUid);


    // 7. Preparar parámetros para CustomApisService
    $customApiParams = [
      'vendor_id' => $vendorId,
      'question' => $question,
      'mensajes_anteriores_contacto' => $mensajes_anteriores_contacto,
      'contact_uid' => $contactUid,
      'top_sections' => $topSections,
      'combined_sections' => $combinedSections,
      'api_data_ai' => $api_data_ai,
      'contact_context' => $contactContext,
      'prompt_url_items' => $promptUrlItems,
      'prompt_final' => $promptFinal,
      // Parámetros de OpenAI
      'open_ai_access_key'      => $openAiApiKey,
      'open_ai_organization' => $openAiOrgKey,
      // Agrega más parámetros según sea necesario
      'vendor_webhook_endpoint' => $vendor_webhook_endpoint,
      // dominio webhook, vendor acces token y uid vendor
      'domain_variable_vendor' => $domain_variable_vendor,
      'vendor_uid' => $vendorUid,
      'vendor_access_token' => $vendorAccessToken,
      // Agrega más parámetros según sea necesario
      'wa_id_contact' => $wa_id_contact,
      //configuraciones adicionalesvendor:
      'requiere_saludo' => $requiere_saludo,
      'timezone' => $timezone,
      'hora_actual' => $hora_actual,
      'botName' => $botName,
      'hora_actual' => $hora_actual,
      'hora_actual' => $hora_actual,
      'prompt' => $openAIDataPrompt,
	   // --------------- AÑADE ESTA LÍNEA ---------------
      'flowise_url'              => $flowise_url,
      // -----------------------------------------------
    ];


    ///////////////////////
    ///////////////////////
    ///////////////////////
    ///////////////////////
    ///////////////////////
    ///////////////////////
	  
	  

	  \Log::info('FLOWISE URL = ' . $customApiParams['flowise_url']);

	  

	 // ░░░ BLOQUE "MODO TEST" ░░░
	  
	   
     if ($flowise_url !== null &&
		in_array($contactUid, [
			'7c49bdae-a204-4763-b254-54282c869f26',
			'b605e0be-a621-497d-9831-6643229309c7',
			'd1234567-abcd-1234-efgh-567890abcdef',
			'2d5d8113-d180-4b5e-96c6-4e9927f1ab22',
			'a9876543-zyxw-4321-vuts-0987654321ba',
		], true)){
	  
//if ( 1 == 1000) {
    \Log::info('MODO TEST → llamando a Flowise');

    $flowiseService = new FlowiseFallbackService();/*
	\Log::info('\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\');
	\Log::info('\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\');
	 // Alternativamente, Laravel acepta pasar el array como contexto:
		 Log::info(
			 'customApiParams detallado',
			 $customApiParams
		 );


	\Log::info('\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\');
	\Log::info('\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\');*/
    $textoFlowise   = $flowiseService->ask($customApiParams);   // ← sólo POST

    /* … (bloque previo que obtiene $textoFlowise) … */

if ($textoFlowise !== '') {

    /* 1) Si ya viene con el nombre correcto, igual queremos “pulirlo”  */
    if (Str::contains($textoFlowise, $nombreUsuario)) {
        \Log::info('Pulido con OpenAI (el nombre ya es correcto)');
    } else {
        \Log::info('Nombre potencialmente incorrecto → se enviará a OpenAI igual, pero sin cambios de nombre');
    }

    /* 2) Pulir con OpenAI **sin cambiar nombres ni contenido** */
    config([
        'openai.api_key'      => $openAiApiKey,
        'openai.organization' => $openAiOrgKey ?: null,
    ]);

    $resp = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
        'model'    => getVendorSettings('open_ai_model_key', null, null, $vendorId),
        'messages' => [
            [
                'role'    => 'system',
                'content' =>
                    "Reescribe el siguiente mensaje corrigiendo *solo* ortografía, tildes y puntuación. " .
                    "Mantén exactamente los nombres propios y el resto del contenido. " .
                    "No añadas, elimines ni cambies nada más. Conserva los saltos de línea y emojis.",
            ],
            [
                'role'    => 'user',
                'content' => $textoFlowise,
            ],
        ],
    ]);

    return trim($resp['choices'][0]['message']['content'] ?? '');
}

\Log::info('Flowise vacío → se usará flujo normal');

}



	\Log::info('SALIENDO DEL FLUJO -----------------------');
	 return;
	\Log::info('EJECUCION DE RESPALDO');


	  
	 
    ///////////////////////
    ///////////////////////
    ///////////////////////
    ///////////////////////
    ///////////////////////
    ///////////////////////



    // 8. Llamar a la función personalizada de CustomApisService
    // Convertir los parámetros a JSON
    $jsonParams = json_encode($customApiParams);

    $customApiResponse = $this->customApisService->processVendorApi($jsonParams);
    //\Log::info(" customApiResponse: " . json_encode($customApiResponse));

    // Obtener los datos de la respuesta personalizada
    $customApiData = json_decode($customApiResponse->getContent(), true);

    //\Log::info(" customApiData: " . json_encode($customApiData));

    // 9. Integrar $customApiData en $promptFinal
    if (isset($customApiData['error']) && $customApiData['error']) {
      // Si hay un error, agregar el mensaje de error al prompt
      $errorMessage = $customApiData['message'] ?? 'Ocurrió un error con la API personalizada.';
      /*
				$promptFinal[] = [
					"type" => "text",
					"text" => "Información adicional: {$errorMessage}"
				];
				*/
    } else {
      // Si no hay error, integrar los datos personalizados
      $customDataText = "";
      foreach ($customApiData as $key => $value) {
        if (!in_array($key, ['error', 'message', 'vendor_id'])) {
          $customDataText .= "{$key}: {$value}; ";
        }
      }

      if (!empty($customDataText)) {
        $promptFinal[] = [
          "type" => "text",
          "text" => $customDataText
        ];
      }
    }

    // 7. Petición a Chat de OpenAI
    try {



      $response = OpenAI::chat()->create([
        'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
        'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
        'messages' => [
          [
            'role' => 'system',
            'content' => "Eres una persona normal y servicial que genera respuestas claras."
              . ($botName ? " Tu nombre es $botName." : ""),
          ],
          [
            'role' => 'user',
            // Si tu API no admite arrays, usa 'content' => json_encode($promptFinal)
            'content' => json_encode($promptFinal)
          ]
        ]
      ]);

      // Ajusta la forma de extraer la respuesta final
      return trim($response['choices'][0]['message']['content'] ?? '');
    } catch (Exception $e) {
      \Log::error("Error al generar respuesta con OpenAI: " . $e->getMessage());
      return "En unos momentos te atenderemos.";
    }
  }
	
	
	
	
	/////////////////////////////////////////////////////////////
}
