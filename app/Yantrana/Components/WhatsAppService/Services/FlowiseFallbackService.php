<?php

namespace App\Yantrana\Components\WhatsAppService\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Llama al endpoint de Flowise y devuelve el texto.
 */
class FlowiseFallbackService
{
    /**
     * @param  array  $params  = $customApiParams
     * @return string          texto de Flowise o vacío si falla
     */
    public function ask(array $params): string
    {
        // Registrar todos los params para debugging
        Log::info('DATA DEL PARAMS', $params);

        // Config toggles
        $sendHistory     = false;
        $sendPromptFinal = false;

        // 0) URL de Flowise
        $flowiseUrl = $params['flowise_url'] ?? '';
        if (empty($flowiseUrl)) {
            Log::info(
                PHP_EOL .
                '──────────── FLOWISE · SIN URL ────────────' . PHP_EOL .
                'Se omitió la llamada porque la URL llegó vacía.' . PHP_EOL .
                '────────────────────────────────────────────'
            );
            return '';
        }

        // 1) Construir QUESTION
        $question = '';
        if (!empty($params['mensajes_anteriores_contacto'])) {
            $hist = json_decode($params['mensajes_anteriores_contacto'], true);
            $raw  = $hist[0]['mensaje'] ?? '';
            $question = trim(preg_replace('/https?:\/\/\S+/i', '', $raw));
        }
        if ($question === '') {
            $qRaw = is_array($params['question'])
                ? ($params['question'][0]['text'] ?? '')
                : $params['question'];
            $asText = is_array($qRaw)
                ? json_encode($qRaw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string) $qRaw;
            $question = trim(preg_replace('/https?:\/\/\S+/i', '', $asText));
        }
        $question = preg_replace('/(\S)url:/i', '$1 url:', $question);
        Log::info("QUESTION FORMATEADA: {$question}");

        // 2) Variables para overrideConfig
        $contactContext = json_decode($params['contact_context'] ?? '{}', true);

        $vars = [
            'sessionId'      => $params['contact_uid'],
            'contactUid'     => $params['contact_uid'],
            'token'          => $params['vendor_access_token'] ?? '',
            'uidVendor'      => $params['vendor_uid'] ?? '',
            'contactContext' => $contactContext,
            'botName'        => $params['botName'] ?? '',
            'timezone'       => $params['timezone'] ?? '',
            'requiereSaludo' => $params['requiere_saludo'] ?? '',
			'userName' => $contactContext['first_name'] ?? '',

            // Variables adicionales obligatorias:
            'url'            => preg_replace('#^https?://#', '', rtrim($params['vendor_webhook_endpoint'] ?? '', '/')),
            'questionCrm'    => is_string($params['question'])
                ? $params['question']
                : json_encode($params['question'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'mensajeCliente' => is_string($params['question'])
                ? $params['question']
                : json_encode($params['question'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'uid'            => '',
            'initPrompt'     => $params['initPrompt'] ?? '',
        ];

        if ($sendHistory) {
            $vars['mensajesAnteriores'] = $params['mensajes_anteriores_contacto'] ?? '';
        }
        if ($sendPromptFinal) {
            $vars['promptFinal'] = $params['prompt_final'] ?? [];
        }

        // 3) Construir payload base
        $payload = [
            'question'       => $question,
            'sessionId'      => $params['contact_uid'],
            'overrideConfig' => ['sessionId' => $params['contact_uid'], 'vars' => $vars],
        ];

        // 3-A) Manejo de imágenes / uploads
        $uploads  = [];
        $urlItems = $params['uploads'] ?? $params['prompt_url_items'] ?? [];
        foreach ($urlItems as $item) {
            $rawUrl = $item['data'] ?? ($item['image_url']['url'] ?? null);
            if (! $rawUrl) continue;

            $cleanUrl = preg_replace('#(?<!:)//+#', '/', $rawUrl);
            $path     = parse_url($cleanUrl, PHP_URL_PATH);
            $ext      = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
            $mime     = match(strtolower($ext)) {
                'jpg','jpeg' => 'image/jpeg',
                'png'        => 'image/png',
                'gif'        => 'image/gif',
                default      => 'application/octet-stream',
            };

            try {
                $response = Http::timeout(60)->get($cleanUrl);
                if (! $response->successful()) {
                    Log::warning("No se pudo descargar imagen desde URL: {$cleanUrl}");
                    continue;
                }
                $binary    = $response->body();
                $base64Data= base64_encode($binary);
                $dataUri   = "data:{$mime};base64,{$base64Data}";
                $uploads[] = [
                    'data' => $dataUri,
                    'type' => 'file',
                    'name' => "image.{$ext}",
                    'mime' => $mime,
                ];
            } catch (\Throwable $e) {
                Log::warning("Error descargando imagen: {$e->getMessage()}");
                continue;
            }
        }
        if (! empty($uploads)) {
            $payload['uploads'] = $uploads;
            Log::info('UPLOADS BASE64 OK: '.count($uploads).' imagen(es)');
        }

        // 4) Log del payload final
        Log::debug(
            PHP_EOL .
            "╔════ PAYLOAD → FLOWISE ════" . PHP_EOL .
            json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) . PHP_EOL .
            "╚══════════════════════════"
        );

        // 5) POST a Flowise y retorno
        try {
            $resp   = Http::timeout(300)->post($flowiseUrl, $payload);
            $status = $resp->status();
            $body   = $resp->body();

            Log::debug(
                PHP_EOL .
                "╔════ RESPUESTA FLOWISE ════" . PHP_EOL .
                "║ Status: {$status}" . PHP_EOL .
                "║ Body  :" . PHP_EOL .
                $body . PHP_EOL .
                "╚══════════════════════════"
            );

            $json = $resp->json();
            return isset($json['text']) ? trim($json['text']) : '';
        } catch (\Throwable $e) {
            Log::warning(
                PHP_EOL .
                "╔════ ERROR FLOWISE ════════" . PHP_EOL .
                "║ {$e->getMessage()}" . PHP_EOL .
                "╚══════════════════════════"
            );
            return '';
        }
    }
}
