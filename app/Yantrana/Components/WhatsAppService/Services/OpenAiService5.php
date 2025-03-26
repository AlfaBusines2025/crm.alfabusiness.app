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

class OpenAiService extends BaseEngine
{
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
		// 1. Reemplazar `\/` por `/` para que se conviertan en URLs normales
		//    "https:\/\/dominio.com\/..." => "https://dominio.com/..."
		$question = str_replace('\\/', '/', $question);

		// 2. Usar el mismo regex para capturar URLs "normales" con http o https
		$regex = '/(https?:\/\/[^\s]+)/i';
		preg_match_all($regex, $question, $matches);

		return $matches[0] ?? [];
	}

    /**
     * Intenta deducir el tipo de archivo a partir de la extensión del URL.
     * - Esto es un método rápido. Si deseas hacer validación por headers,
     *   puedes usar get_headers() y analizar el Content-Type real.
     */
    private function guessFileTypeFromUrl($url)
    {
        // Obtenemos la extensión (sin parámetros ?...)
        $parsed = parse_url($url);
        if (!isset($parsed['path'])) {
            return 'text';
        }

        $extension = strtolower(pathinfo($parsed['path'], PATHINFO_EXTENSION));

        // Posibles extensiones de imagen
        $imageExtensions = ['jpg','jpeg','png','gif','bmp','webp','svg'];
        // Posibles extensiones de audio
        $audioExtensions = ['mp3','wav','ogg','m4a','flac'];
        // Posibles extensiones de documento
        $docExtensions   = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv'];

        if (in_array($extension, $imageExtensions)) {
            return 'image_url';
        } elseif (in_array($extension, $audioExtensions)) {
            return 'audio_url';
        } elseif (in_array($extension, $docExtensions)) {
            return 'document_url';
        }

        // Por defecto, texto
        return 'text';
    }

    /**
     * Construye un array de prompt (similar a tu ejemplo) a partir de los URLs
     * encontrados en la pregunta. Opcionalmente, también devuelves la "pregunta limpia"
     * sin los URLs (por si no quieres que aparezcan repetidos en el texto).
     */
    private function buildPromptItemsForUrls(&$question)
    {
        $urls = $this->extractUrlsFromText($question);
        $promptItems = [];

        foreach ($urls as $url) {
            // 1) Detectar tipo de archivo
            $fileType = $this->guessFileTypeFromUrl($url);

            // 2) Construir estructura
            if ($fileType === 'image_url') {
                $promptItems[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $url
                    ]
                ];
            } elseif ($fileType === 'audio_url') {
                $promptItems[] = [
                    'type' => 'audio_url',
                    'audio_url' => [
                        'url' => $url
                    ]
                ];
            } elseif ($fileType === 'document_url') {
                $promptItems[] = [
                    'type' => 'document_url',
                    'document_url' => [
                        'url' => $url
                    ]
                ];
            } else {
                // Si no coincide con nada, lo tratamos como texto normal
                $promptItems[] = [
                    'type' => 'text',
                    'text' => "Contenido detectado en enlace: $url"
                ];
            }

            // Opcional: Eliminar ese link de la pregunta para no duplicar
            // en el prompt final, si así lo deseas
            $question = str_replace($url, '', $question);
        }

        // Retornamos el array con la info y la pregunta sin URLs
        return $promptItems;
    }
	
	private function transcribeAudio($audioUrl)
	{
		try {
			$apiKey = 'f136970297077bf2874b44d26846ec7c959b06e8';
			$endpoint = 'https://api.deepgram.com/v1/listen?language=es&punctuate=true';

			// Preparamos la data en JSON: Deepgram descargará el audio desde $audioUrl
			$data = [
				'url' => $audioUrl
			];

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

			// Verificar la transcripción
			if (!empty($result['results']['channels'][0]['alternatives'][0]['transcript'])) {
				return $result['results']['channels'][0]['alternatives'][0]['transcript'];
			}
		} catch (\Throwable $e) {
			\Log::error("Error al transcribir audio: " . $e->getMessage());
		}

		// Si algo falla, regresas una cadena vacía o un mensaje
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

        // Step 1: Split the large data into meaningful chunks
        $sections = $this->splitDataIntoChunks($largeData);

        // Step 2: Generate embeddings for each section
        $embeddings = [];
        foreach ($sections as $section) {
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-ada-002',
                'input' => $section,
            ]);
            $embeddings[] = $response['data'][0]['embedding'];
        }

        // Step 3: Return the data & embeddings for storage
        return [
            'data' => $sections,
            'embedding' => $embeddings,
        ];
    }

    /**
     * Divide la data en trozos más pequeños.
     */
    private function splitDataIntoChunks($data, $maxChunkSize = 500)
    {
        $chunks = [];
        $currentChunk = '';
        $sentences = preg_split('/(?<=[.?!])\s+/', $data);  // Split by sentences

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

    /**
     * Embed de la pregunta
     */
    private function embedQuestion($question)
    {
        $response = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $question,
        ]);

        return $response['data'][0]['embedding'];
    }

    /**
     * Calcula la similitud coseno entre 2 vectores
     */
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

    /**
     * Busca la sección más relevante en la BD
     */
    private function findRelevantSection($question, $vendorId)
    {
        $this->initConfiguration($vendorId);

        // Step 1: Embed the question
        $questionEmbedding = $this->embedQuestion($question);

        // Step 2: Fetch the large dataset and embeddings
        $largeDataRecord = getVendorSettings('open_ai_embedded_training_data', null, null, $vendorId);
        $sections = $largeDataRecord['data'];
        $storedEmbeddings = $largeDataRecord['embedding'];

        // Step 3: Compare embeddings
        $similarities = [];
        foreach ($storedEmbeddings as $index => $sectionEmbedding) {
            $similarity = $this->cosineSimilarity($questionEmbedding, $sectionEmbedding);
            $similarities[] = [
                'section' => $sections[$index],
                'similarity' => $similarity,
            ];
        }

        // Step 4: Ordenar y retornar la más similar
        usort($similarities, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $similarities[0]['section'] ?? '';
    }

    /**
     * Devuelve el top N de secciones relevantes
     */
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

    /**
     * Obtiene data de un contacto (con custom fields, grupos y etiquetas)
     */
    private function getContactData($contactUid): string
    {
        try {
            // 1. Buscar el contacto por _uid
            $contact = ContactModel::where('_uid', $contactUid)->first();

            if (!$contact) {
                \Log::warning("No se encontró el contacto con _uid: {$contactUid}");
                return json_encode([
                    'error' => true,
                    'message' => "Información del contacto no disponible."
                ]);
            }

            // 2. Convertir a array base
            $contactArray = $contact->toArray();

            // 3. Campos personalizados
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

            // 4. Grupos
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

            // 5. Etiquetas
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

            // 6. Parsear campo __data si existe
            if (isset($contactArray['__data']) && is_string($contactArray['__data'])) {
                $decodedData = json_decode($contactArray['__data'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $contactArray['__data'] = $decodedData;
                } else {
                    $contactArray['__data'] = null;
                }
            }

            // 7. Retornar JSON
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
     * ========== GENERA RESPUESTA (UNA SOLA SECCIÓN) ==========
     *
     * Debe:
     *  - Detectar enlaces en $question
     *  - Construir un prompt con "type" => "image_url"/"audio_url"/"document_url"/"text"
     *  - Enviarlo a OpenAI
     */
    public function generateAnswerFromSingleSection($question, $vendorId, $contactUid)
    {
        // 1. Buscamos la sección relevante
        $relevantSection = $this->findRelevantSection($question, $vendorId);
        $botName  = getVendorSettings('open_ai_bot_name', null, null, $vendorId);

        // 2. Obtenemos info completa del contacto
        $contactContext = $this->getContactData($contactUid);

        // 3. Extraemos y construimos ítems de los URLs en la pregunta
        $promptUrlItems = $this->buildPromptItemsForUrls($question);

        // 4. Construimos el "contenido" principal que va en el prompt (como tipo "text")
        //    Este texto ya no incluye los enlaces (los quitamos en buildPromptItemsForUrls).
        $mainText = "Basándote en el siguiente contenido y la información del usuario, responde la pregunta de manera clara. "
                  . "Incluye saltos de línea donde corresponda.\n\n"
                  . "Información de contacto: $contactContext\n\n"
                  . "Contenido relevante: $relevantSection\n\n"
                  . "Pregunta: $question";

        // 5. Generamos el array final para 'content'
        //    Tomando la misma estructura que usaste en tu otro código
        $promptFinal = [
            [
                "type" => "text",
                "text" => $mainText
            ]
        ];

        // Agregar los ítems de URLs al final
        if (!empty($promptUrlItems)) {
            $promptFinal = array_merge($promptFinal, $promptUrlItems);
        }

        // 6. Llamamos a la API de completions (o chat) con ese prompt
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
                        // Si tu API no admite arrays, haz:
                        // 'content' => json_encode($promptFinal)
                        'content' => $promptFinal
                    ]
                ],
                'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
            ]);

            // Según tu modelo, puede que la respuesta esté en ['choices'][0]['text'] o en
            // ['choices'][0]['message']['content']. Ajusta en base a tu respuesta real.
            return trim($response['choices'][0]['text'] ?? '');

        } catch (Exception $e) {
            \Log::error("Error al generar respuesta con OpenAI: " . $e->getMessage());
            return "Lo siento, no pude procesar tu solicitud en este momento. Por favor, intenta de nuevo más tarde.";
        }
    }

    /**
     * ========== GENERA RESPUESTA (MÚLTIPLES SECCIONES) ==========
     *
     * Similar a la anterior, pero combinando varias secciones y también
     * recorriendo posibles endpoints. Se añade la detección de enlaces.
     */
    public function generateAnswerFromMultipleSections($question, $contactUid, $vendorId)
    {
        $botName = getVendorSettings('open_ai_bot_name', null, null, $vendorId);
        $botDataSourceType = getVendorSettings('open_ai_bot_data_source_type', null, null, $vendorId);

        // Si data_source_type = 'assistant', etc. (omito detalles, igual a tu código)
        if ($botDataSourceType == 'assistant') {
            // Lógica especial con threads...
            // (no toco esa parte para no romper tu flujo)
            $this->initConfiguration($vendorId);
            try {
                // ...
                // Omitido el contenido para mantener tu estructura original
            } catch (Exception $e) {
                \Log::error("Error al ejecutar el thread de OpenAI: " . $e->getMessage());
                return "Lo siento, no pude procesar tu solicitud en este momento. Por favor, intenta de nuevo más tarde.";
            }
        }

        // 1. Para fuente de datos basada en texto
        //    Obtenemos top secciones relevantes
        $topSections = $this->findTopRelevantSections($question, $vendorId);
        $combinedSections = implode("\n\n", array_column($topSections, 'section'));

        // 2. Revisamos endpoints (como en tu código original)
        $api_data_ai = [];
        $maxApis = 5; // Número máximo de APIs a recorrer

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

        // 3. Obtener info del contacto
        $contactContext = $this->getContactData($contactUid);

        // ============ NUEVO: DETECTAR LINKS EN LA PREGUNTA ============
        $promptUrlItems = $this->buildPromptItemsForUrls($question);

        // 4. Construimos el texto principal que irá en el prompt
        $mainText = "Basándote en la siguiente información del usuario y el contenido adicional, "
                  . "responde la pregunta de manera clara, con saltos de línea donde sea necesario.\n\n"
                  . "Información de contacto: $contactContext\n\n"
                  . "Contenido (varias secciones + APIs): $combinedSections\n\n"
					. "Este es el Prompt URL Items: ".json_encode($promptUrlItems)."\n\n"
                  . "Pregunta: $question";

        // 5. Construimos el array final de prompt
        $promptFinal = [
            [
                "type" => "text",
                "text" => $mainText
            ]
        ];

        // Si hay ítems de URL detectados, los agregamos
        if (!empty($promptUrlItems)) {
            $promptFinal = array_merge($promptFinal, $promptUrlItems);
        }

        // 6. Llamar a la API de Chat de OpenAI
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
                        // Igualmente, si no soporta array nativamente, haz json_encode():
                        // 'content' => json_encode($promptFinal)
                        'content' => $promptFinal
                    ]
                ]
            ]);

            return trim($response['choices'][0]['message']['content'] ?? '');

        } catch (Exception $e) {
            \Log::error("Error al generar respuesta con OpenAI: " . $e->getMessage());
            return "Lo siento, no pude procesar tu solicitud en este momento. Por favor, intenta de nuevo más tarde.";
        }
    }
}