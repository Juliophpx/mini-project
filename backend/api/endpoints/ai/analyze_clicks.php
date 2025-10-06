<?php
// No database connection needed in this file directly.
// It will receive data from the router.

function analyze_clicks_with_ai($click_data) {
    try {
        if (empty($click_data)) {
            http_response_code(200);
            return ['analysis' => 'No click data available to analyze yet. Please click some buttons first.'];
        }

        // Format the data into a prompt for the AI
        $prompt_data = "As a marketing expert, analyze the following user click data and provide one actionable business insight. Be concise and direct. Data: ";
        foreach ($click_data as $row) {
            $prompt_data .= "Button '" . htmlspecialchars($row['button_id']) . "' has " . $row['click_count'] . " clicks. ";
        }

        // Call OpenAI API
        $openai_config = require __DIR__ . '/../../config/openai_config.php';
        $api_key = $openai_config['api_key'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => "gpt-3.5-turbo-instruct",
            "prompt" => $prompt_data,
            "max_tokens" => 100,
            "temperature" => 0.7
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

        // Return the AI's response
        if (isset($response['choices'][0]['text'])) {
            return ['analysis' => trim($response['choices'][0]['text'])];
        } else {
            error_log("OpenAI API Error: " . $result);
            http_response_code(500);
            return ['error' => 'Failed to get analysis from AI', 'details' => $response];
        }

    } catch (Exception $e) {
        error_log("AI analysis error: " . $e->getMessage());
        http_response_code(500);
        return ['error' => 'An internal server error occurred.'];
    }
}
?>