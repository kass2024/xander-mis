<?php
/**
 * Marketing file analytics — OPENAI_API_KEY from .env
 */
declare(strict_types=1);

require_once __DIR__ . '/helpers/openai_env.php';

function ai_marketing_analyze_short($filesArray)
{
    $apiKey = xander_openai_api_key();
    if ($apiKey === '') {
        return 'AI Error: OPENAI_API_KEY not set in .env.';
    }

    if (!is_array($filesArray) || count($filesArray) === 0) {
        return 'No files available for analysis.';
    }

    $fileList = implode(', ', $filesArray);

    $prompt = "
Analyze these marketing filenames:

$fileList

Return EXACTLY:
1. Total files
2. Number of images
3. Number of videos
4. One short insight (max 10 words)

Keep the output clean, no decorations, no extra lines.
";

    $payload = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => 'You generate short, clean analytics summaries.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.2,
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return 'AI Error: ' . $curlErr;
    }

    $json = json_decode($response, true);

    if (!$json) {
        return 'AI Error: Invalid JSON response.';
    }

    if (isset($json['error'])) {
        return 'AI Error: ' . ($json['error']['message'] ?? 'Unknown API error.');
    }

    if (!isset($json['choices'][0]['message']['content'])) {
        return 'AI Error: Model returned an incomplete response.';
    }

    $output = trim($json['choices'][0]['message']['content']);

    if ($output === '' || strlen($output) < 3) {
        return 'AI Error: Empty or invalid response.';
    }

    return $output;
}
