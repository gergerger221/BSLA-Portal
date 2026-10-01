<?php
// backend/services/PayMongoService.php
declare(strict_types=1);

namespace App\Services;

use App\Config\Env;
use Exception;

class PayMongoService {
    private const API_BASE = 'https://api.paymongo.com/v1';

    /**
     * Get the PayMongo Secret Key from environment.
     */
    public static function getSecretKey(): string {
        Env::load();
        $key = (string)(Env::get('PAYMONGO_SECRET_KEY') ?: '');
        return trim($key);
    }

    /**
     * Get the PayMongo Public Key from environment.
     */
    public static function getPublicKey(): string {
        Env::load();
        $key = (string)(Env::get('PAYMONGO_PUBLIC_KEY') ?: '');
        return trim($key);
    }

    /**
     * Verify that secret key is configured.
     */
    public static function isConfigured(): bool {
        return !empty(self::getSecretKey());
    }

    /**
     * Create a PayMongo Checkout Session.
     *
     * @param array $params [
     *   'amount'          => (float) in PHP pesos (e.g. 3000.00),
     *   'description'     => (string),
     *   'reference_no'    => (string),
     *   'customer_name'   => (string),
     *   'customer_email'  => (string),
     *   'customer_phone'  => (string),
     *   'success_url'     => (string),
     *   'cancel_url'      => (string),
     *   'line_item_name'  => (string|null)
     * ]
     * @return array PayMongo Checkout Session data
     * @throws Exception
     */
    public static function createCheckoutSession(array $params): array {
        $secretKey = self::getSecretKey();
        if (empty($secretKey)) {
            throw new Exception('PayMongo Secret Key is not configured in backend/.env');
        }

        // Amount in centavos (e.g. 3000.00 PHP -> 300000 centavos)
        $amountPesos = (float)($params['amount'] ?? 0);
        if ($amountPesos < 20.00) {
            throw new Exception('Minimum payment amount for PayMongo is ₱20.00.');
        }
        $amountCentavos = (int)round($amountPesos * 100);

        $lineItemName = $params['line_item_name'] ?? 'Tuition Downpayment';
        $description = $params['description'] ?? 'School Downpayment';
        $referenceNo = $params['reference_no'] ?? ('REF-' . time());

        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => [
                        'name'  => $params['customer_name'] ?? 'Student',
                        'email' => !empty($params['customer_email']) ? $params['customer_email'] : 'student@sia.edu.ph',
                        'phone' => !empty($params['customer_phone']) ? $params['customer_phone'] : '09123456789'
                    ],
                    'send_email_receipt' => true,
                    'show_description'   => true,
                    'show_line_items'    => true,
                    'line_items' => [
                        [
                            'currency'    => 'PHP',
                            'amount'      => $amountCentavos,
                            'description' => $description,
                            'name'        => $lineItemName,
                            'quantity'    => 1
                        ]
                    ],
                    'payment_method_types' => [
                        'gcash',
                        'paymaya',
                        'card',
                        'dob',
                        'billease'
                    ],
                    'description'      => $description,
                    'reference_number' => $referenceNo,
                    'success_url'      => $params['success_url'],
                    'cancel_url'       => $params['cancel_url']
                ]
            ]
        ];

        $response = self::executeRequest('POST', '/checkout_sessions', $payload, $secretKey);
        return $response['data'] ?? [];
    }

    /**
     * Retrieve details of an existing Checkout Session.
     *
     * @param string $sessionId PayMongo Checkout Session ID (cs_...)
     * @return array
     * @throws Exception
     */
    public static function getCheckoutSession(string $sessionId): array {
        $secretKey = self::getSecretKey();
        if (empty($secretKey)) {
            throw new Exception('PayMongo Secret Key is not configured in backend/.env');
        }

        $sessionId = trim($sessionId);
        if (empty($sessionId)) {
            throw new Exception('Checkout Session ID is required.');
        }

        $response = self::executeRequest('GET', '/checkout_sessions/' . urlencode($sessionId), null, $secretKey);
        return $response['data'] ?? [];
    }

    /**
     * Execute cURL request to PayMongo REST API.
     */
    private static function executeRequest(string $method, string $endpoint, ?array $data, string $secretKey): array {
        $url = self::API_BASE . $endpoint;
        $ch = curl_init($url);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            // PayMongo Basic auth: base64(secret_key + ':')
            'Authorization: Basic ' . base64_encode($secretKey . ':')
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $rawBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawBody === false || !empty($curlError)) {
            throw new Exception('Failed to connect to PayMongo API: ' . $curlError);
        }

        $decoded = json_decode($rawBody, true);

        if ($httpCode >= 400) {
            $errorMsg = 'PayMongo API Error (' . $httpCode . ')';
            if (isset($decoded['errors']) && is_array($decoded['errors'])) {
                $details = [];
                foreach ($decoded['errors'] as $err) {
                    $details[] = $err['detail'] ?? ($err['code'] ?? 'Unknown error');
                }
                $errorMsg .= ': ' . implode('; ', $details);
            }
            throw new Exception($errorMsg);
        }

        return $decoded ?: [];
    }
}
