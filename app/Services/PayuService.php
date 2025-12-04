<?php

namespace App\Services;

class PayuService
{
    protected string $merchantId;

    protected string $merchantKey;

    protected string $salt;

    protected string $paymentUrl;

    protected string $serviceProvider;

    public function __construct()
    {
        $this->merchantId = config('services.payu.merchant_id');
        $this->merchantKey = config('services.payu.merchant_key');
        $this->salt = config('services.payu.salt');
        $this->serviceProvider = config('services.payu.service_provider');
        $mode = config('services.payu.mode', 'test');
        $this->paymentUrl = $mode === 'test' ? config('services.payu.test_url') : config('services.payu.live_url');
    }

    /**
     * Generate hash for PayU payment.
     */
    public function generateHash(array $params): string
    {
        // Ensure all UDF fields are strings, even if empty
        $udf1 = trim((string) ($params['udf1'] ?? ''));
        $udf2 = trim((string) ($params['udf2'] ?? ''));
        $udf3 = trim((string) ($params['udf3'] ?? ''));
        $udf4 = trim((string) ($params['udf4'] ?? ''));
        $udf5 = trim((string) ($params['udf5'] ?? ''));

        // Ensure all required fields are strings and trimmed
        $txnid = trim((string) $params['txnid']);
        $amount = trim((string) $params['amount']);
        $productinfo = trim((string) $params['productinfo']);
        $firstname = trim((string) $params['firstname']);
        $email = trim((string) $params['email']);

        // Build hash string exactly as per PayU formula:
        // sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)
        // After udf2, we need exactly 9 pipes total when udf3,4,5 are empty
        $hashString = $this->merchantKey.'|'
            .$txnid.'|'
            .$amount.'|'
            .$productinfo.'|'
            .$firstname.'|'
            .$email.'|'
            .$udf1.'|'
            .$udf2.'|'
            .$udf3.'|'
            .$udf4.'|'
            .$udf5.'|'
            .'|||||'
            .$this->salt;

        // Temporary debug logging
        \Illuminate\Support\Facades\Log::info('PayU Hash Debug', [
            'hash_string' => $hashString,
            'hash_string_length' => strlen($hashString),
            'generated_hash' => strtolower(hash('sha512', $hashString)),
        ]);

        return strtolower(hash('sha512', $hashString));
    }

    /**
     * Verify hash from PayU response.
     */
    public function verifyHash(array $response): bool
    {
        $hashString = '';
        $hashString .= $this->salt.'|';
        $hashString .= $response['status'].'|';
        $hashString .= '||||||||';
        $hashString .= $response['udf5'].'|';
        $hashString .= $response['udf4'].'|';
        $hashString .= $response['udf3'].'|';
        $hashString .= $response['udf2'].'|';
        $hashString .= $response['udf1'].'|';
        $hashString .= $response['email'].'|';
        $hashString .= $response['firstname'].'|';
        $hashString .= $response['productinfo'].'|';
        $hashString .= $response['amount'].'|';
        $hashString .= $response['txnid'].'|';
        $hashString .= $this->merchantKey;

        $calculatedHash = strtolower(hash('sha512', $hashString));

        return hash_equals($calculatedHash, strtolower($response['hash'] ?? ''));
    }

    /**
     * Prepare payment data for PayU.
     */
    public function preparePaymentData(array $data): array
    {
        $paymentData = [
            'key' => $this->merchantKey,
            'txnid' => $data['transaction_id'],
            'amount' => number_format($data['amount'], 2, '.', ''),
            'productinfo' => $data['product_info'],
            'firstname' => $data['firstname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'surl' => $data['success_url'],
            'furl' => $data['failure_url'],
            'service_provider' => $this->serviceProvider,
            'udf1' => $data['udf1'] ?? '',
            'udf2' => $data['udf2'] ?? '',
            'udf3' => $data['udf3'] ?? '',
            'udf4' => $data['udf4'] ?? '',
            'udf5' => $data['udf5'] ?? '',
        ];

        $paymentData['hash'] = $this->generateHash($paymentData);

        return $paymentData;
    }

    /**
     * Get payment gateway URL.
     */
    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }
}
