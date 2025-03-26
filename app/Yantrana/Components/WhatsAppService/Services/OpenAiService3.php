<?php
/**
* OpenAiService.php -
*
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Services;

use Exception;
use OpenAI\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Yantrana\Base\BaseEngine;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiService extends BaseEngine
{
    protected function initConfiguration($vendorId = null, $accessKey = null, $orgKey = null)
    {
        if (!$vendorId) {
            $vendorId = getVendorId();
        }
        config([
            'openai.api_key' =>  $accessKey ?: getVendorSettings('open_ai_access_key', null, null, $vendorId),
            'openai.organization' => $orgKey ?: getVendorSettings('open_ai_organization_id', null, null, $vendorId),
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
                'model' => 'text-embedding-3-small',
                'input' => $section,
            ]);

            $embeddings[] = $response['data'][0]['embedding'];
        }

        // Step 3: Store the data and embeddings in the database
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
            'model' => 'text-embedding-3-small',
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

        // Step 2: Fetch the large dataset and embeddings from the database
        $largeDataRecord = getVendorSettings('open_ai_embedded_training_data', null, null, $vendorId);
        $sections = $largeDataRecord['data'];
        $storedEmbeddings = ($largeDataRecord['embedding']);

        // Step 3: Compare the embeddings
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

        return $similarities[0]['section'];
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
        $storedEmbeddings = ($largeDataRecord['embedding']);

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
     * Generate an answer using the most relevant section.
     */
    public function generateAnswerFromSingleSection($question, $vendorId)
    {
        // Step 1: Find the most relevant section
        $relevantSection = $this->findRelevantSection($question, $vendorId);
        $botName  = getVendorSettings('open_ai_bot_name', null, null, $vendorId);
        // Step 2: Use OpenAI completion API to generate a refined answer
        $response = OpenAI::completions()->create([
            'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Eres una persona normal y servicial que responde de manera clara y comprensible." . ($botName ? ' Tu nombre es '. $botName : ''),
                ],
                [
                    'role' => 'user',
                    'content' => "Basándote en el siguiente contenido, responde la pregunta de manera clara, utilizando un lenguaje natural. Por favor, incluye saltos de línea y párrafos donde corresponda:\n\nContenido: {$relevantSection}\n\nPregunta: {$question}",
                ]
            ],
            'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
        ]);

        return trim($response['choices'][0]['text']);
    }

    /**
     * Generate an answer by combining multiple relevant sections for broader context.
     */
    public function generateAnswerFromMultipleSections($question, $contactUid, $vendorId)
    {
        $botName = getVendorSettings('open_ai_bot_name', null, null, $vendorId);
        $botDataSourceType = getVendorSettings('open_ai_bot_data_source_type', null, null, $vendorId);
        if ($botDataSourceType == 'assistant') {
            $this->initConfiguration($vendorId);
            $threadRun = $response = OpenAI::threads()->createAndRun([
                'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
                'assistant_id' => getVendorSettings('open_ai_assistant_id', null, null, $vendorId),
                'thread' => [
                    'messages' => [
                        [
                            'role' => 'assistant',
                            'content' => "Eres una persona normal y servicial" . ($botName ? ' tu nombre es '. $botName : '') . ", responde de manera clara, utilizando un lenguaje comprensible, con saltos de línea y párrafos donde corresponda.",
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
                return getVendorSettings('open_ai_failed_message', null, null, $vendorId) ?: 'La solicitud falló, por favor inténtalo de nuevo.';
            }
            $messageList = OpenAI::threads()->messages()->list(
                threadId: $threadRun->threadId,
            );
            return $messageList->data[0]->content[0]->text->value;
        }

        // Para fuente de datos basada en texto
        // Step 1: Find the top relevant sections
        $topSections = $this->findTopRelevantSections($question, $vendorId);
        $combinedSections = implode("\n\n", array_column($topSections, 'section'));
        // Step 2: Use OpenAI completion API to generate a refined answer
        $response = OpenAI::chat()->create([
            'model' => getVendorSettings('open_ai_model_key', null, null, $vendorId),
            'max_tokens' => getVendorSettings('open_ai_max_token', null, null, $vendorId),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Eres una persona normal y servicial que genera respuestas claras y comprensibles." . ($botName ? ' Tu nombre es '. $botName : ''),
                ],
                [
                    'role' => 'user',
                    'content' => "Basándote en el siguiente contenido, responde la pregunta de manera clara, utilizando un lenguaje natural. Por favor, incluye saltos de línea y párrafos donde corresponda:\n\nContenido: {$combinedSections}\n\nPregunta: {$question}",
                ]
            ]
        ]);
        return trim($response['choices'][0]['message']['content']);
    }
}