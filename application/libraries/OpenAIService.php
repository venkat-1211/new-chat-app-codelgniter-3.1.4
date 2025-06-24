<?php

class OpenAIService
{
    protected $CI;
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        // Get CI instance
        $this->CI =& get_instance();

        // Load config if not already loaded
        $this->CI->load->config('config');

        // Assign values
        $this->apiKey = $this->CI->config->item('openai_api_key');
        $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
    }

    private function callOpenAI($payload)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 10, // Set timeout
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            log_message('error', 'cURL error while calling OpenAI: ' . $curlError);
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            log_message('error', 'OpenAI HTTP Error [' . $httpCode . ']: ' . $response);
            return false;
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'JSON decode error from OpenAI response: ' . json_last_error_msg());
            return false;
        }

        return $result;
    }


    // public function translate($message, $targetLanguage)
    // {
    //     $prompt = "Translate this text into $targetLanguage: $message";
    //     // $prompt = "Please translate the following message into {$targetLanguage}. Only return the translated text without explanation:\n\n\"{$message}\"";

    //     $payload = [
    //         'model' => 'gpt-4',
    //         'messages' => [
    //             ['role' => 'user', 'content' => $prompt]
    //         ],
    //         'max_tokens' => 1000,
    //         'temperature' => 0.7
    //     ];

    //     $response = $this->callOpenAI($payload);
    //     return $response ? trim($response['choices'][0]['message']['content']) : null;
    // }

    // public function translate($message, $targetLanguage)
    // {
    //     $prompt = "You are a professional translator. Translate the following message into {$targetLanguage}. 
    //     Only return the translated text, without any explanation or extra formatting:

    //     \"{$message}\"";

    //     $payload = [
    //         'model' => 'gpt-4',
    //         'messages' => [
    //             ['role' => 'user', 'content' => $prompt]
    //         ],
    //         'max_tokens' => 1000, // Enough room for long messages
    //         'temperature' => 0.0  // Lower = more accurate, less creative
    //     ];

    //     $response = $this->callOpenAI($payload);
    //     return $response ? trim($response['choices'][0]['message']['content'], "\"'") : null;
    // }

    public function translate($message, $targetLanguage, $previousMessage = null)
    {
        $conversationSnippet = $previousMessage
            ? "Conversation:\nA: {$previousMessage}\nB: {$message}\n\n"
            : '';
    
        $prompt = <<<PROMPT
        You are a professional translator. Translate only the final reply (B's message) into {$targetLanguage} using the conversation context.
        Only return the translated text without any explanation or formatting.
        
        {$conversationSnippet}
        B's Message:
        "{$message}"
        PROMPT;
        
            $payload = [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.0
            ];
        
            $response = $this->callOpenAI($payload);
            return $response ? trim($response['choices'][0]['message']['content'], "\"'") : null;
    }
    

    public function detectContext($message)
    {
        $prompt = <<<PROMPT
        You are an intelligent assistant. Based on the following message, briefly describe the likely context in one short sentence (e.g., "This message is a reply to a greeting about well-being." or "This is about paying a fine.").
        
        Message:
        "{$message}"
        PROMPT;
        
            $payload = [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 30,
                'temperature' => 0.3
            ];
        
            $response = $this->callOpenAI($payload);
            return $response ? trim($response['choices'][0]['message']['content'], "\"'") : null;
    }
    
    public function detect($msg)
    {
        $prompt = "Detect the language of the following text and respond ONLY with the name of the language:\n\n$msg";

        $payload = [
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 50,
            'temperature' => 0.0
        ];

        $response = $this->callOpenAI($payload);
        return $response ? trim($response['choices'][0]['message']['content']) : null;
    }

    public function generateResponse($message, $context = [], $imageBase64 = null)
    {
        $systemPrompt = "You are a helpful customer support assistant. Provide professional and helpful responses to customer inquiries.";

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($context as $msg) {
            $role = $msg['sender_type'] === 'customer' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $msg['message']];
        }

        if ($imageBase64) {
            $model = 'gpt-4-vision-preview';
            $messages[] = [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $message ?: 'Can you help me with this image?'
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:image/jpeg;base64,' . $imageBase64
                        ]
                    ]
                ]
            ];
        } else {
            $model = 'gpt-3.5-turbo';
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $imageBase64 ? 300 : 150,
            'temperature' => 0.0
        ];

        $response = $this->callOpenAI($payload);

        if (!$response && $imageBase64) {
            return "I can see you've shared an image, but I'm experiencing technical difficulties analyzing it. Please describe it for assistance.";
        }

        return $response ? $response['choices'][0]['message']['content'] : "I'm sorry, I'm having trouble processing your request.";
    }

    // public function generateSuggestedResponse($customerMessage, $conversationHistory = [], $imageBase64 = null)
    // {
    //     $systemPrompt = "You are assisting a customer support agent. Based on the customer's message and conversation history, suggest a helpful response.";

    //     $messages = [['role' => 'system', 'content' => $systemPrompt]];

    //     foreach ($conversationHistory as $msg) {
    //         $role = $msg['sender_type'] === 'customer' ? 'user' : 'assistant';
    //         $messages[] = ['role' => $role, 'content' => $msg['message']];
    //     }

    //     if ($imageBase64) {
    //         $model = 'gpt-4-vision-preview';
    //         $messages[] = [
    //             'role' => 'user',
    //             'content' => [
    //                 [
    //                     'type' => 'text',
    //                     'text' => $customerMessage ?: 'Customer has shared an image'
    //                 ],
    //                 [
    //                     'type' => 'image_url',
    //                     'image_url' => [
    //                         'url' => 'data:image/jpeg;base64,' . $imageBase64
    //                     ]
    //                 ]
    //             ]
    //         ];
    //     } else {
    //         $model = 'gpt-3.5-turbo';
    //         $messages[] = ['role' => 'user', 'content' => $customerMessage];
    //     }

    //     $payload = [
    //         'model' => $model,
    //         'messages' => $messages,
    //         'max_tokens' => 100,
    //         'temperature' => 0.5
    //     ];

    //     $response = $this->callOpenAI($payload);
    //     return $response ? $response['choices'][0]['message']['content'] : null;
    // }
    public function generateSuggestedResponse($conversationHistory = [])
    {
        $systemPrompt = "You are assisting a customer support agent. Based on the customer's message and conversation history, suggest a helpful response.";

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        $hasImage = false;

        foreach ($conversationHistory as $msg) {
            $role = $msg->sender_id == 0 ? 'user' : 'assistant';

            // Image Handling
            if ((int)$msg->message_type === 2 && !empty($msg->message_content)) {
                $hasImage = true;
                $imageUrl = base_url($msg->message_content);

                $messages[] = [
                    'role' => $role,
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => !empty($msg->message_translate) ? $msg->message_translate : 'Customer shared an image.'
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageUrl
                            ]
                        ]
                    ]
                ];
            } else {
                // Normal text message
                $messages[] = ['role' => $role, 'content' => $msg->message_translate];
            }
        }

        $payload = [
            'model' => $hasImage ? 'gpt-4-vision-preview' : 'gpt-3.5-turbo',
            'messages' => $messages,
            'max_tokens' => 1000,
            'temperature' => 0.0
        ];

        $response = $this->callOpenAI($payload);
        return $response['choices'][0]['message']['content'] ?? null;
    }


    public function analyzeImage($imageBase64, $prompt = null)
    {
        $defaultPrompt = "Please describe what you see in this image in detail. Focus on any text, objects, issues, or problems that might be relevant for customer support.";

        $messages = [[
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt ?: $defaultPrompt],
                ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,' . $imageBase64]]
            ]
        ]];

        $payload = [
            'model' => 'gpt-4-vision-preview',
            'messages' => $messages,
            'max_tokens' => 200,
            'temperature' => 0.0
        ];

        $response = $this->callOpenAI($payload);
        return $response ? $response['choices'][0]['message']['content'] : "Unable to analyze the image at this time.";
    }
}
