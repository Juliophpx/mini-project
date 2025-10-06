<?php
require_once __DIR__ . '/../../helpers/auth.php';

function generate_text_from_ai() {
    try {
        // Authenticate the user
        requireAuth();

        // Get input data
        $data = json_decode(file_get_contents('php://input'), true);
        $prompt = $data['prompt'] ?? '';

        if (empty($prompt)) {
            http_response_code(400);
            return ['error' => 'Prompt is required'];
        }

        // Load OpenAI API key
        $openai_config = require __DIR__ . '/../../config/openai_config.php';
        $api_key = $openai_config['api_key'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => "gpt-3.5-turbo-instruct",
            "prompt" => $prompt,
            "max_tokens" => 150
        ]));

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);

        $response = json_decode($result, true);

        if (isset($response['choices'][0]['text'])) {
            return ['text' => $response['choices'][0]['text']];
        } else {
            error_log("OpenAI API Error: " . $result);
            http_response_code(500);
            return ['error' => 'Failed to get response from AI', 'details' => $response];
        }

    } catch (Exception $e) {
        error_log("AI generation error: " . $e->getMessage());
        http_response_code(500);
        return ['error' => 'An internal server error occurred.'];
    }
}
?>