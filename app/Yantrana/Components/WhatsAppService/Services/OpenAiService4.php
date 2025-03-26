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
    protected function initConfiguration($vendorId = null, $accessKey = null, $orgKey = null)
    {
        if (!$vendorId) {
            $vendorId = getVendorId();
        }
        config([
            'openai.api_key' =>  $accessKey ?: getVendorSettings('open_ai_access_key', null, null, $vendorId),
            'openai.organization' => $orgKey ?: getVendorSettings('openai.organization', null, null, $vendorId),
        ]);
    }

    /**
     * Generate embeddings for large data and store it in the database.
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
     * Split the large dataset into smaller meaningful chunks.
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
     * Embed the user's question.
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
     * Calculate cosine similarity between two vectors.
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
     * Find the most relevant section based on the user's question.
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

        // Step 4: Sort by similarity and return the top section
        usort($similarities, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $similarities[0]['section'] ?? '';
    }

    /**
     * Find the top N relevant sections for broader context.
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
     * Obtiene la información del contacto a partir de su _uid y la retorna en formato JSON,
     * incluyendo campos personalizados, grupos y etiquetas que pertenezcan al contacto.
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

            // 3. Campos personalizados del contacto ---------------------------------- // NUEVO
            //    Buscar en la tabla contact_custom_field_values los registros donde
            //    contacts__id = $contact->_id e incluir datos del campo (customField).
            $customFieldValues = ContactCustomFieldValueModel::where('contacts__id', $contact->_id)
                ->with('customField') // Trae también el modelo ContactCustomFieldModel
                ->get();

            // 3.1 Formatear esos datos para añadirlos al array
            $contactArray['custom_fields'] = $customFieldValues->map(function ($cfv) {
                // $cfv->customField es la relación con ContactCustomFieldModel
                return [
                    'custom_field_uid'   => $cfv->customField->_uid ?? null,
                    'input_name'         => $cfv->customField->input_name ?? null,
                    'input_type'         => $cfv->customField->input_type ?? null,
                    'field_value'        => $cfv->field_value,
                ];
            })->toArray();

            // 4. Grupos a los que pertenece el contacto ------------------------------- // NUEVO
            //    Primero obtenemos las filas group_contacts donde contacts__id = $contact->_id
            $contactGroups = GroupContactModel::where('contacts__id', $contact->_id)->get();
            //    Luego obtenemos los IDs de grupos
            $groupIds = $contactGroups->pluck('contact_groups__id')->unique()->values();

            // 4.1 Obtenemos la información de cada grupo
            $groups = ContactGroupModel::whereIn('_id', $groupIds)->get();
            $contactArray['groups'] = $groups->map(function ($group) {
                return [
                    'group_uid'   => $group->_uid,
                    'title'       => $group->title,
                    'description' => $group->description,
                ];
            })->toArray();

            // 5. Etiquetas asociadas al contacto -------------------------------------- // NUEVO
            //    Similar a los grupos, se busca en contact_labels donde contacts__id = $contact->_id
            $contactLabels = ContactLabelModel::where('contacts__id', $contact->_id)->get();
            $labelIds = $contactLabels->pluck('labels__id')->unique()->values();

            // 5.1 Obtenemos la información de cada etiqueta en la tabla labels
            $labels = LabelModel::whereIn('_id', $labelIds)->get();
            $contactArray['labels'] = $labels->map(function ($label) {
                return [
                    'label_uid'  => $label->_uid,
                    'title'      => $label->title,
                    'text_color' => $label->text_color,
                    'bg_color'   => $label->bg_color,
                ];
            })->toArray();
            // -------------------------------------------------------------------------

            // 6. Validar si hay un campo __data que venga en formato JSON y decodificarlo
            if (isset($contactArray['__data']) && is_string($contactArray['__data'])) {
                $decodedData = json_decode($contactArray['__data'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $contactArray['__data'] = $decodedData;
                } else {
                    $contactArray['__data'] = null;
                }
            }

            // 7. Retornar el arreglo como JSON
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
     * Generate an answer using the most relevant section.
     */
    public function generateAnswerFromSingleSection($question, $vendorId, $contactUid)
    {
        // Step 1: Find the most relevant section
        $relevantSection = $this->findRelevantSection($question, $vendorId);
        $botName  = getVendorSettings('open_ai_bot_name', null, null, $vendorId);

        // Step 2: Obtener información completa del contacto (incl. grupos, etiquetas, etc.)
        $contactContext = $this->getContactData($contactUid);

        // Step 3: Llamar a la API de Completions de OpenAI
        //    (Aunque se usa messages, la mayoría de los proyectos ya migran a chat()->create(...).
        //     Si quieres usar roles, se recomienda la API de Chat.)
        try {
            $response = OpenAI::completions()->create([
                'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "Eres una persona normal y servicial que responde de manera clara y comprensible."
                            . ($botName ? ' Tu nombre es ' . $botName : ''),
                    ],
                    [
                        'role' => 'user',
                        'content' => "Basándote en el siguiente contenido y la información del usuario, "
                            . "responde la pregunta de manera clara, utilizando un lenguaje natural. "
                            . "Por favor, incluye saltos de línea y párrafos donde corresponda:\n\n"
                            . "{$contactContext}\n\n"
                            . "Contenido: {$relevantSection}\n\n"
                            . "Pregunta: {$question}",
                    ]
                ],
                'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
            ]);

            // Si tu modelo lo admite, puede que el texto aparezca en otra llave
            return trim($response['choices'][0]['text']);

        } catch (Exception $e) {
            \Log::error("Error al generar respuesta con OpenAI: " . $e->getMessage());
            return "Lo siento, no pude procesar tu solicitud en este momento. Por favor, intenta de nuevo más tarde.";
        }
    }

    /**
     * Generate an answer by combining multiple relevant sections for broader context.
     */
    public function generateAnswerFromMultipleSections($question, $contactUid, $vendorId)
    {
        $botName = getVendorSettings('open_ai_bot_name', null, null, $vendorId);
        $botDataSourceType = getVendorSettings('open_ai_bot_data_source_type', null, null, $vendorId);

        // Caso especial: si data_source_type = 'assistant', se usa la lógica de threads
        if ($botDataSourceType == 'assistant') {
            $this->initConfiguration($vendorId);
            try {
                $threadRun = OpenAI::threads()->createAndRun([
                    'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
                    'assistant_id' => getVendorSettings('open_ai_assistant_id', null, null, $vendorId),
                    'thread' => [
                        'messages' => [
                            [
                                'role' => 'assistant',
                                'content' => "Eres una persona normal y servicial"
                                    . ($botName ? ' tu nombre es ' . $botName : '')
                                    . ", responde de manera clara, utilizando un lenguaje comprensible, "
                                    . "con saltos de línea y párrafos donde corresponda.",
                            ],
                            [
                                'role' => 'user',
                                'content' => $question,
                            ]
                        ],
                    ],
                ]);

                while (in_array($threadRun->status, ['queued', 'in_progress'])) {
                    $threadRun = OpenAI::threads()->runs()->retrieve(
                        threadId: $threadRun->threadId,
                        runId: $threadRun->id,
                    );
                }

                if ($threadRun->status !== 'completed') {
                    return getVendorSettings('open_ai_failed_message', null, null, $vendorId)
                        ?: 'La solicitud falló, por favor inténtalo de nuevo.';
                }

                $messageList = OpenAI::threads()->messages()->list(
                    threadId: $threadRun->threadId,
                );

                return $messageList->data[0]->content[0]->text->value;

            } catch (Exception $e) {
                \Log::error("Error al ejecutar el thread de OpenAI: " . $e->getMessage());
                return "Lo siento, no pude procesar tu solicitud en este momento. Por favor, intenta de nuevo más tarde.";
            }
        }

        // Para fuente de datos basada en texto
        // Step 1: Find the top relevant sections
        $topSections = $this->findTopRelevantSections($question, $vendorId);
        $combinedSections = implode("\n\n", array_column($topSections, 'section'));

        // Definir los endpoints desde los cuales se obtendrá información adicional
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

        // Step 2: Obtener información de los endpoints utilizando file_get_contents
        foreach ($api_data_ai as $api_data) {
            $endpoint = $api_data['endpoint'];
            if (!empty($endpoint)) {
                $fetchedData = @file_get_contents($endpoint);
                if ($fetchedData !== false) {
                    $decodedData = json_decode($fetchedData, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Si existe una key particular, la usamos
                        if (isset($decodedData['desired_key'])) {
                            $combinedSections .= "\n\n" . $decodedData['desired_key'];
                        } else {
                            // Si no, agregamos todo el JSON
                            $combinedSections .= "\n\n" . $fetchedData;
                        }
                    } else {
                        // No es JSON, se concatena tal cual
                        $combinedSections .= "\n\n" . $fetchedData;
                    }
                } else {
                    \Log::error("Error al obtener datos del endpoint: {$endpoint}");
                    $combinedSections .= "\n\n[Error al obtener datos de {$api_data['name']}]";
                }
            }
        }

        // Step 3: Obtener información del contacto (campos, grupos, etiquetas, etc.)
        $contactContext = $this->getContactData($contactUid);

        // Step 4: Llamar a la API de Chat de OpenAI
        try {
            $response = OpenAI::chat()->create([
                'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
                'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "Eres una persona normal y servicial que genera respuestas claras y comprensibles."
                            . ($botName ? ' Tu nombre es ' . $botName : ''),
                    ],
                    [
                        'role' => 'user',
                        'content' => "Basándote en la siguiente información del usuario y el contenido proporcionado, "
                            . "responde la pregunta de manera clara, utilizando un lenguaje natural. "
                            . "Por favor, incluye saltos de línea y párrafos donde corresponda:\n\n"
                            . "{$contactContext}\n\n"
                            . "Contenido: {$combinedSections}\n\n"
                            . "Pregunta: {$question}",
                    ]
                ]
            ]);

            return trim($response['choices'][0]['message']['content']);

        } catch (Exception $e) {
            \Log::error("Error al generar respuesta con OpenAI: " . $e->getMessage());
            return "Lo siento, no pude procesar tu solicitud en este momento. Por favor, intenta de nuevo más tarde.";
        }
    }
}