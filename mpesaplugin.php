<?php

class MpesaSdk {
    private $config;
    private $listeners = [];

    public function init(array $config) {
        $this->config = $config;
    }

    public function pay($amount, $phone, $orderId) {
        $payload = [
            'amount' => $amount,
            'phone' => $phone,
            'orderId' => $orderId,
        ];

        try {
            $response = $this->stkPush($payload);

            if ($response['status'] == 'Pending') {
                $this->emit('onPending', $response);
            } elseif ($response['status'] == 'Success') {
                $this->emit('onSuccess', $response);
            } else {
                $this->emit('onFail', $response);
            }
        } catch (Exception $e) {
            $this->emit('onFail', ['error' => $e->getMessage()]);
        }
    }

    public function on($event, callable $callback) {
        $this->listeners[$event][] = $callback;
    }

    private function emit($event, $data) {
        if (!empty($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $listener($data);
            }
        }
    }

    private function stkPush($payload) {
        $accessToken = $this->generateAccessToken();

        $timestamp = date('YmdHis');
        $password = base64_encode(
            $this->config['shortcode'] .
            $this->config['passkey'] .
            $timestamp
        );

        $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'; 

        $stkPayload = [
            'BusinessShortCode' => $this->config['shortcode'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $payload['amount'],
            'PartyA' => $payload['phone'],
            'PartyB' => $this->config['shortcode'],
            'PhoneNumber' => $payload['phone'],
            'CallBackURL' => $this->config['callback_url'],
            'AccountReference' => $payload['orderId'],
            'TransactionDesc' => 'Payment for Order ' . $payload['orderId'],
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($stkPayload));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception('STK Push failed: ' . curl_error($curl));
        }

        curl_close($curl);

        $responseData = json_decode($response, true);

        if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == "0") {
            return [
                'status' => 'Pending',
                'MerchantRequestID' => $responseData['MerchantRequestID'],
                'CheckoutRequestID' => $responseData['CheckoutRequestID'],
                'orderId' => $payload['orderId'],
                'amount' => $payload['amount'],
                'phone' => $payload['phone']
            ];
        } else {
            throw new Exception('STK Push Error: ' . ($responseData['errorMessage'] ?? 'Unknown Error'));
        }
    }

    private function generateAccessToken() {
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode(
            $this->config['consumer_key'] . ':' . $this->config['consumer_secret']
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception('Access Token request failed: ' . curl_error($curl));
        }

        curl_close($curl);

        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            return $data['access_token'];
        } else {
            throw new Exception('Access token not received');
        }
    }
}