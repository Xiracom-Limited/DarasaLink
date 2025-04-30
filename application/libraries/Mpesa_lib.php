<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mpesa_lib {
    public $config;

    function __construct(array $configs = array())
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load(); 

        $defaults = array(
            'env'               => 'sandbox',
            'type'              => 4,
            'shortcode'         => $_ENV['BUSINESS_SHORTCODE'],
            'headoffice'        => '174379',
            'key'               => '',
            'secret'            => '',
            'passkey' => $_ENV['MPESA_PASSKEY'],
            'validation_url' => $_ENV['MPESA_VALIDATION_URL'],
            'confirmation_url' => $_ENV['MPESA_CONFIRMATION_URL'],
            'callback_url' => $_ENV['MPESA_CALLBACK_URL'],
            'timeout_url' => $_ENV['MPESA_TIMEOUT_URL'],
            'results_url' => $_ENV['MPESA_RESULTS_URL'],
        );

        if (!isset($configs['headoffice']) || empty($configs['headoffice'])) {
            $configs['headoffice'] = $configs['shortcode'];
        }

        $parsed = array_merge($defaults, $configs);
        $this->config = (object)$parsed;
    }

    /**
     * Generate OAuth access token
     * @return string Access token
     */
    public function token()
    {
        $endpoint = ($this->config->env == 'live')
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode($this->config->key . ':' . $this->config->secret);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Enable in production
        $curl_response = curl_exec($curl);

        if ($curl_response === false) {
            log_message('error', 'cURL error in token generation: ' . curl_error($curl));
            curl_close($curl);
            return '';
        }

        $result = json_decode($curl_response, true);
        curl_close($curl);

        if (isset($result['access_token'])) {
            return $result['access_token'];
        }

        log_message('error', 'Failed to generate token: ' . $curl_response);
        return '';
    }

    /**
     * Handle validation callback
     * @param callable $callback Defined function or closure to process data and return true/false
     * @return array
     */
    public function validate($callback = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        log_message('info', 'Validate callback data: ' . json_encode($data));
        if (!$data) {
            return array('ResultCode' => 1, 'ResultDesc' => 'No data received');
        }

        if (is_null($callback)) {
            return array('ResultCode' => 0, 'ResultDesc' => 'Success');
        } else {
            return call_user_func_array($callback, array($data))
                ? array('ResultCode' => 0, 'ResultDesc' => 'Success')
                : array('ResultCode' => 1, 'ResultDesc' => 'Failed');
        }
    }

    /**
     * Handle confirmation callback
     * @param callable $callback Defined function or closure to process data and return true/false
     * @return array
     */
    public function confirm($callback = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        log_message('info', 'Confirm callback data: ' . json_encode($data));
        if (!$data) {
            return array('ResultCode' => 1, 'ResultDesc' => 'No data received');
        }

        if (is_null($callback)) {
            return array('ResultCode' => 0, 'ResultDesc' => 'Success');
        } else {
            return call_user_func_array($callback, array($data))
                ? array('ResultCode' => 0, 'ResultDesc' => 'Success')
                : array('ResultCode' => 1, 'ResultDesc' => 'Failed');
        }
    }

    /**
     * Handle STK Push reconciliation callback
     * @param callable $callback Defined function or closure to process data and return true/false
     * @return array
     */
    public function reconcile($callback = null)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        log_message('info', 'Reconcile callback data: ' . json_encode($data));
        if (!$data) {
            return array('ResultCode' => 1, 'ResultDesc' => 'No data received');
        }

        if (is_null($callback)) {
            return array('ResultCode' => 0, 'ResultDesc' => 'Success');
        } else {
            return call_user_func_array($callback, array($data))
                ? array('ResultCode' => 0, 'ResultDesc' => 'Success')
                : array('ResultCode' => 1, 'ResultDesc' => 'Failed');
        }
    }

    /**
     * Register validation and confirmation URLs
     * @param callable $callback Defined function or closure to process response
     * @return array
     */
    public function register($callback = null)
    {
        $token = $this->token();
        if (!$token) {
            log_message('error', 'Failed to register URLs: No access token');
            return array('Registration status' => 'Failed to generate access token');
        }

        $endpoint = ($this->config->env == 'live')
            ? 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl'
            : 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type:application/json',
                'Authorization:Bearer ' . $token
            )
        );

        $curl_post_data = array(
            'ShortCode'       => $this->config->shortcode,
            'ResponseType'    => 'Completed',
            'ConfirmationURL' => $this->config->confirmation_url,
            'ValidationURL'   => $this->config->validation_url
        );
        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $response = curl_exec($curl);

        if ($response === false) {
            log_message('error', 'cURL error in URL registration: ' . curl_error($curl));
            curl_close($curl);
            return array('Registration status' => 'cURL error: ' . curl_error($curl));
        }

        $content = json_decode($response, true);
        curl_close($curl);
        log_message('info', 'URL registration response: ' . $response);

        if (is_null($callback)) {
            if (isset($content['ResponseDescription'])) {
                $status = $content['ResponseDescription'];
            } elseif (isset($content['errorMessage'])) {
                $status = $content['errorMessage'];
            } else {
                $status = 'Sorry could not connect to Daraja. Check your connection or configuration.';
            }
            return array('Registration status' => $status);
        } else {
            return call_user_func_array($callback, array($content));
        }
    }

    /**
     * Simulate a C2B transaction
     * @param string $phone The MSISDN sending the funds
     * @param float $amount The amount to be transacted
     * @param string $reference Used with M-Pesa PayBills
     * @return array Response
     */
    public function simulate($phone, $amount = 10, $reference = 'TRX')
    {
        $token = $this->token();
        if (!$token) {
            log_message('error', 'Failed to simulate: No access token');
            return array('error' => 'Failed to generate access token');
        }

        $phone = (substr($phone, 0, 1) == '+') ? str_replace('+', '', $phone) : $phone;
        $phone = (substr($phone, 0, 1) == '0') ? preg_replace('/^0/', '254', $phone) : $phone;

        $endpoint = ($this->config->env == 'live')
            ? 'https://api.safaricom.co.ke/mpesa/c2b/v1/simulate'
            : 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/simulate';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type:application/json',
                'Authorization:Bearer ' . $token
            )
        );
        $curl_post_data = array(
            'ShortCode'     => $this->config->shortcode,
            'CommandID'     => ($this->config->type == 4) ? 'CustomerPayBillOnline' : 'CustomerBuyGoodsOnline',
            'Amount'        => round($amount),
            'Msisdn'        => $phone,
            'BillRefNumber' => $reference
        );
        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $response = curl_exec($curl);

        if ($response === false) {
            log_message('error', 'cURL error in simulate: ' . curl_error($curl));
            curl_close($curl);
            return array('error' => 'cURL error: ' . curl_error($curl));
        }

        curl_close($curl);
        log_message('info', 'Simulate response: ' . $response);
        return json_decode($response, true);
    }

    /**
     * Initiate STK Push transaction
     * @param string $phone The MSISDN sending the funds
     * @param float $amount The amount to be transacted
     * @param string $reference Used with M-Pesa PayBills
     * @param string $description A description of the transaction
     * @param string $remark Backward compatibility parameter
     * @return array Response
     */
    public function request($phone, $amount, $reference = 'ACCOUNT', $description = 'Transaction Description', $remark = 'Remark')
    {
        $token = $this->token();
        if (!$token) {
            log_message('error', 'Failed to initiate STK Push: No access token');
            return array('error' => 'Failed to generate access token');
        }

        $phone = (substr($phone, 0, 1) == '+') ? str_replace('+', '', $phone) : $phone;
        $phone = (substr($phone, 0, 1) == '0') ? preg_replace('/^0/', '254', $phone) : $phone;
        $timestamp = date('YmdHis');
        $password = base64_encode($this->config->shortcode . $this->config->passkey . $timestamp);

        $endpoint = ($this->config->env == 'live')
            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type:application/json',
                'Authorization:Bearer ' . $token
            )
        );
        $curl_post_data = array(
            'BusinessShortCode' => $this->config->headoffice,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => ($this->config->type == 4) ? 'CustomerPayBillOnline' : 'CustomerBuyGoodsOnline',
            'Amount'            => round($amount),
            'PartyA'            => $phone,
            'PartyB'            => $this->config->shortcode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $this->config->callback_url,
            'AccountReference'  => $reference,
            'TransactionDesc'   => $description
        );
        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $response = curl_exec($curl);

        if ($response === false) {
            log_message('error', 'cURL error in STK Push: ' . curl_error($curl));
            curl_close($curl);
            return array('error' => 'cURL error: ' . curl_error($curl));
        }

        curl_close($curl);
        log_message('info', 'STK Push response: ' . $response);
        return json_decode($response, true);
    }
}