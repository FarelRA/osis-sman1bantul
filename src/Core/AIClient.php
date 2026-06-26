<?php

/**
 * AI Client - OpenAI Compatible
 * Handles communication with OpenAI-compatible APIs for document/payment verification
 * Uses Structured Outputs (JSON Schema) for guaranteed type-safe responses
 */
class AIClient
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;

    public function __construct(
        string $apiKey,
        string $apiUrl = 'https://api.openai.com/v1',
        string $model = 'gpt-4o'
    ) {
        $this->apiKey = $apiKey;
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->model = $model;
    }

    /**
     * Verify a student ID image
     */
    public function verifyStudentId(string $imagePath, array $userData): array
    {
        $prompt = $this->buildStudentIdPrompt($userData);
        $schema = $this->getStudentIdSchema();
        return $this->analyzeImage($imagePath, $prompt, $schema, 'student_id_verification');
    }

    /**
     * Verify a payment screenshot
     */
    public function verifyPaymentScreenshot(string $imagePath, array $paymentInfo): array
    {
        $prompt = $this->buildPaymentPrompt($paymentInfo);
        $schema = $this->getPaymentSchema();
        return $this->analyzeImage($imagePath, $prompt, $schema, 'payment_verification');
    }

    /**
     * JSON Schema for student ID verification response
     */
    private function getStudentIdSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'valid' => [
                    'type' => 'boolean',
                    'description' => 'Whether the student ID is valid and matches the provided data'
                ],
                'confidence' => [
                    'type' => 'integer',
                    'description' => 'Confidence score from 0 to 100'
                ],
                'name_match' => [
                    'type' => 'boolean',
                    'description' => 'Whether the name on the ID matches the provided name'
                ],
                'school_match' => [
                    'type' => 'boolean',
                    'description' => 'Whether the school on the ID matches the provided school'
                ],
                'is_physical_card' => [
                    'type' => 'boolean',
                    'description' => 'Whether this appears to be a physical card (not a screenshot or edited)'
                ],
                'detected_name' => [
                    'type' => ['string', 'null'],
                    'description' => 'The name detected on the card, or null if not readable'
                ],
                'detected_school' => [
                    'type' => ['string', 'null'],
                    'description' => 'The school detected on the card, or null if not readable'
                ],
                'issues' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'List of any concerns or issues found'
                ]
            ],
            'required' => ['valid', 'confidence', 'name_match', 'school_match', 'is_physical_card', 'detected_name', 'detected_school', 'issues'],
            'additionalProperties' => false
        ];
    }

    /**
     * JSON Schema for payment verification response
     */
    private function getPaymentSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'valid' => [
                    'type' => 'boolean',
                    'description' => 'Whether the payment proof is valid and matches expected details'
                ],
                'confidence' => [
                    'type' => 'integer',
                    'description' => 'Confidence score from 0 to 100'
                ],
                'amount_match' => [
                    'type' => 'boolean',
                    'description' => 'Whether the payment amount matches the expected amount'
                ],
                'account_match' => [
                    'type' => 'boolean',
                    'description' => 'Whether the destination account matches ANY of the valid bank accounts listed'
                ],
                'is_completed_transaction' => [
                    'type' => 'boolean',
                    'description' => 'Whether this shows a completed/successful transaction'
                ],
                'detected_amount' => [
                    'type' => ['string', 'null'],
                    'description' => 'The amount detected in the image, or null if not readable'
                ],
                'detected_account' => [
                    'type' => ['string', 'null'],
                    'description' => 'The account number detected in the image, or null if not readable'
                ],
                'issues' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'List of any concerns or issues found'
                ]
            ],
            'required' => ['valid', 'confidence', 'amount_match', 'account_match', 'is_completed_transaction', 'detected_amount', 'detected_account', 'issues'],
            'additionalProperties' => false
        ];
    }

    /**
     * Build prompt for student ID verification
     */
    private function buildStudentIdPrompt(array $userData): string
    {
        $name = $userData['full_name'] ?? 'Unknown';
        $school = $userData['school_origin'] ?? 'Unknown';

        return <<<PROMPT
You are verifying a student or child ID card image.
Analyze the image and check:

1. Is this a real, physical ID card (not a screenshot of a digital ID, not a photocopy, not clearly edited)?
2. Is the ID card for a student or child?
3. Can you read a name on the card? If so, does it reasonably match: "{$name}"?
4. Can you read a school or issuing organization name on the card? If so, does it reasonably match: "{$school}"?

Important considerations:
- Minor spelling variations or abbreviations are acceptable
- The name doesn't need to be an exact match (nicknames, order differences are OK)
- Child ID cards may not include a school name, focus on matching the student's/child's name
- Focus on whether this appears to be a legitimate ID card for a student or child
PROMPT;
    }

    /**
     * Build prompt for payment verification
     * Supports multiple bank accounts
     */
    private function buildPaymentPrompt(array $paymentInfo): string
    {
        $currentTime = date('Y-m-d H:i:s');
        $amount = $paymentInfo['amount'] ?? 0;
        $formattedAmount = number_format($amount, 0, ',', '.');
        $bankAccounts = $paymentInfo['bank_accounts'] ?? [];

        // Build list of valid bank accounts
        $accountsList = '';
        if (!empty($bankAccounts)) {
            foreach ($bankAccounts as $i => $account) {
                $num = $i + 1;
                $bankName = $account['bank_name'] ?? 'Unknown';
                $accountNumber = $account['account_number'] ?? 'Unknown';
                $accountHolder = $account['account_holder'] ?? 'Unknown';
                $accountsList .= "   {$num}. {$bankName} - {$accountNumber} - {$accountHolder}\n";
            }
        } else {
            $accountsList = "   No valid accounts configured\n";
        }

        return <<<PROMPT
You are verifying a bank/wallet transfer/payment screenshot.
Analyze the image and check:

1. Is this a real payment confirmation screenshot (not edited, shows actual transaction)?
2. Is the transaction marked as successful/completed?
3. Is the date and time of the transaction recent? Expected current time: {$currentTime}
4. Is the transfer amount correct? Expected around: Rp {$formattedAmount}
5. Is the destination account one of these valid accounts?
{$accountsList}

Important considerations:
- The screenshot should show a completed/successful transaction
- Small variations in displayed amount format are acceptable (usually because of fee)
- Partial account number matches are OK (banks often mask part of the number)
- The payment is VALID if it matches ANY of the listed bank accounts above
- Account holder name variations (nicknames, abbreviations) are acceptable
PROMPT;
    }

    /**
     * Send image to OpenAI-compatible API for analysis with Structured Outputs
     */
    private function analyzeImage(string $imagePath, string $prompt, array $schema, string $schemaName): array
    {
        if (!file_exists($imagePath)) {
            return [
                'success' => false,
                'error' => 'Image file not found',
                'valid' => false
            ];
        }

        // Read and encode image
        $imageData = file_get_contents($imagePath);
        $base64Image = base64_encode($imageData);
        $mimeType = mime_content_type($imagePath);

        // Build request payload with Structured Outputs
        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$base64Image}"
                            ]
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => $schemaName,
                    'strict' => true,
                    'schema' => $schema
                ]
            ],
            'max_tokens' => 1024,
            'temperature' => 0.1
        ];

        // Make API request
        $url = $this->apiUrl . '/chat/completions';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => 'API request failed: ' . $error,
                'valid' => false
            ];
        }

        if ($httpCode !== 200) {
            $errorBody = json_decode($response, true);
            $errorMessage = $errorBody['error']['message'] ?? 'Unknown error';
            return [
                'success' => false,
                'error' => 'API returned status ' . $httpCode . ': ' . $errorMessage,
                'valid' => false,
                'raw_response' => $response
            ];
        }

        // Parse response
        $result = json_decode($response, true);

        if (!isset($result['choices'][0]['message']['content'])) {
            return [
                'success' => false,
                'error' => 'Invalid API response structure',
                'valid' => false
            ];
        }

        $content = $result['choices'][0]['message']['content'];
        $parsed = json_decode($content, true);

        if (!$parsed) {
            return [
                'success' => false,
                'error' => 'Could not parse AI response',
                'valid' => false,
                'raw_text' => $content
            ];
        }

        return array_merge(['success' => true], $parsed);
    }
}
