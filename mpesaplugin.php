<?php

class PaymentPlugin {

    private $config;
    private $maxRetries = 3;
    private $retryDelay = 2; // seconds

    // Initialize the plugin with configuration settings
    public function init($config) {
        $this->config = $config;
    }

    // Initiates payment request and handles errors/retries
    public function pay($amount, $phone, $orderId) {
        // Emit pending event
        $this->emitEvent('onPending', ['orderId' => $orderId, 'amount' => $amount, 'phone' => $phone]);

        $retryCount = 0;
        $success = false;

        while ($retryCount < $this->maxRetries && !$success) {
            try {
                // Call the backend (Developer A) helper for STK Push
                $response = $this->makePaymentRequest($amount, $phone, $orderId);

                if ($response['status'] === 'success') {
                    // Emit success event
                    $this->emitEvent('onSuccess', ['orderId' => $orderId, 'response' => $response]);
                    $success = true;
                } else {
                    throw new Exception('Payment failed: ' . $response['message']);
                }
            } catch (Exception $e) {
                // Handle network error and retry
                if ($retryCount < $this->maxRetries - 1) {
                    $this->emitEvent('onFail', ['orderId' => $orderId, 'error' => $e->getMessage()]);
                    sleep($this->retryDelay);
                } else {
                    $this->emitEvent('onFail', ['orderId' => $orderId, 'error' => $e->getMessage()]);
                }
            }
            $retryCount++;
        }
    }

    // Makes the payment request to Developer A’s API
    private function makePaymentRequest($amount, $phone, $orderId) {
        // Example of calling Developer A's STK Push helper
        // Replace with actual request to backend (A)
        $apiUrl = $this->config['api_url'] . '/stkpush';
        $payload = [
            'amount' => $amount,
            'phone' => $phone,
            'orderId' => $orderId,
            'consumerKey' => $this->config['consumerKey'],
            'consumerSecret' => $this->config['consumerSecret']
        ];

        $response = $this->makeApiRequest($apiUrl, $payload);
        return $response;
    }

    // Makes an API request to the provided URL
    private function makeApiRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Curl error: $error");
        }

        return json_decode($response, true);
    }

    // Emits events (e.g., onPending, onSuccess, onFail)
    private function emitEvent($eventName, $data) {
        echo "Event: $eventName, Data: " . json_encode($data) . "\n";
    }
}

?>
