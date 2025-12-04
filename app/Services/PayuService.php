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
        // key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT
        // After udf5|, there must be exactly 6 pipes (||||||) before SALT
        // This matches PayU's documented formula and the working previous implementation
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
            .'||||||'
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
        // Ensure all fields are strings and handle missing fields
        $status = trim((string) ($response['status'] ?? ''));
        $udf1 = trim((string) ($response['udf1'] ?? ''));
        $udf2 = trim((string) ($response['udf2'] ?? ''));
        $udf3 = trim((string) ($response['udf3'] ?? ''));
        $udf4 = trim((string) ($response['udf4'] ?? ''));
        $udf5 = trim((string) ($response['udf5'] ?? ''));
        $email = trim((string) ($response['email'] ?? ''));
        $firstname = trim((string) ($response['firstname'] ?? ''));
        $productinfo = trim((string) ($response['productinfo'] ?? ''));
        $amount = trim((string) ($response['amount'] ?? ''));
        $txnid = trim((string) ($response['txnid'] ?? ''));
        $receivedHash = strtolower(trim((string) ($response['hash'] ?? '')));

        // Build hash string as per PayU formula for response verification:
        // salt|status||||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key
        $hashString = $this->salt.'|'
            .$status.'|'
            .'||||||||'
            .$udf5.'|'
            .$udf4.'|'
            .$udf3.'|'
            .$udf2.'|'
            .$udf1.'|'
            .$email.'|'
            .$firstname.'|'
            .$productinfo.'|'
            .$amount.'|'
            .$txnid.'|'
            .$this->merchantKey;

        $calculatedHash = strtolower(hash('sha512', $hashString));

        // Log hash verification details for debugging
        \Illuminate\Support\Facades\Log::info('PayU Hash Verification', [
            'calculated_hash' => $calculatedHash,
            'received_hash' => $receivedHash,
            'hash_match' => hash_equals($calculatedHash, $receivedHash),
            'hash_string' => $hashString,
        ]);

        return hash_equals($calculatedHash, $receivedHash);
    }

    /**
     * Prepare payment data for PayU.
     * 
     * Mandatory parameters: key, txnid, amount, productinfo, firstname, email, phone, surl, furl, hash
     */
    public function preparePaymentData(array $data): array
    {
        // Validate mandatory fields
        $requiredFields = ['transaction_id', 'amount', 'product_info', 'firstname', 'email', 'phone', 'success_url', 'failure_url'];
        $missingFields = array_diff($requiredFields, array_keys($data));
        
        if (! empty($missingFields)) {
            throw new \InvalidArgumentException('Missing required PayU parameters: '.implode(', ', $missingFields));
        }

        // Build payment data array with all mandatory fields
        $paymentData = [
            'key' => $this->merchantKey,
            'txnid' => $data['transaction_id'],
            'amount' => number_format((float) $data['amount'], 2, '.', ''),
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

        // Generate hash using the payment data
        $paymentData['hash'] = $this->generateHash($paymentData);

        // Log payment data preparation for debugging (without sensitive data)
        \Illuminate\Support\Facades\Log::info('PayU Payment Data Prepared', [
            'txnid' => $paymentData['txnid'],
            'amount' => $paymentData['amount'],
            'surl' => $paymentData['surl'],
            'furl' => $paymentData['furl'],
            'hash_length' => strlen($paymentData['hash']),
        ]);

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
