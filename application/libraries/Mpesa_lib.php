<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mpesa_lib {
    public $config;
	
	function __construct(array $configs = array())
	{
		$defaults = array(
			'env'               => 'sandbox',
			'type'              => 4,
			'shortcode'         => '174379',
			'headoffice'        => '174379',
		    'key'               => 'wh11KbBRa7SfTBosfKEMwEOPUGO61AUA5wxgZV74A8Xy2sEJ',
			'secret'            => 'SbA8RTxBmwVyYD7QG5anNENMDsWKPpiHXDnRa5eWOJLywGDBnT7nGSPkPGiIM7ah',
			'passkey'           => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
			'base_url'          => 'https://demo.darasalink.co.ke/mpesa/reconcile', // Base URL for your application
			'validation_url'    => '/mpesa/validate',
			'confirmation_url'  => '/mpesa/confirm',
			'callback_url'      => '/mpesa/reconcile',
			'timeout_url'       => '/mpesa/timeout',
			'results_url'       => '/mpesa/results',
		);

		// Fix for undefined array key - only check for headoffice if shortcode is set
		if(isset($configs['shortcode']) && (!isset($configs['headoffice']) || empty($configs['headoffice']))){
			$defaults['headoffice'] = $configs['shortcode'];
		}

		$parsed = array_merge($defaults, $configs);

		// Set the base URL if not provided
		if (empty($parsed['base_url'])) {
			$parsed['base_url'] = $this->get_base_url();
		}

		// Ensure all URLs are absolute
		$url_fields = ['validation_url', 'confirmation_url', 'callback_url', 'timeout_url', 'results_url'];
		foreach ($url_fields as $field) {
			$parsed[$field] = $this->ensure_absolute_url($parsed[$field], $parsed['base_url']);
		}

		$this->config = (object)$parsed;
	}

	/**
	 * Ensure a URL is absolute (starts with http:// or https://)
	 * 
	 * @param string $url The URL to check and format
	 * @param string $base_url The base URL to prepend if $url is relative
	 * @return string The absolute URL
	 */
	private function ensure_absolute_url($url, $base_url) 
	{
		// If URL already starts with http:// or https://, it's already absolute
		if (preg_match('/^https?:\/\//i', $url)) {
			return $url;
		}
		
		// Make sure the base URL doesn't have a trailing slash if the URL has a leading slash
		if (substr($url, 0, 1) === '/' && substr($base_url, -1) === '/') {
			$base_url = rtrim($base_url, '/');
		} 
		// Add a trailing slash to base URL if the URL doesn't have a leading slash
		elseif (substr($url, 0, 1) !== '/' && substr($base_url, -1) !== '/') {
			$base_url .= '/';
		}
		
		return $base_url . ltrim($url, '/');
	}

	/**
	 * Attempt to determine the base URL for the application
	 * 
	 * @return string The base URL
	 */
	private function get_base_url() 
	{
		// Try to get base URL from CodeIgniter if available
		if (function_exists('base_url')) {
			return base_url();
		}
		
		// Manual determination of base URL
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
		
		// Remove any path components from the script name to get the directory
		$dir = isset($_SERVER['SCRIPT_NAME']) ? rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') : '';
		
		return $protocol . $host . $dir;
	}

	/**
	 * @return string Access token
	 */
	public function token()
	{
		$endpoint = ($this->config->env == 'live') 
			? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' 
			: 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

		$credentials = base64_encode($this->config->key.':'.$this->config->secret);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $endpoint);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials));
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$curl_response = curl_exec($curl);
		$result = json_decode($curl_response);

		return isset($result->access_token) ? $result->access_token : '';
	}
	
	/**
	 * @param callable $callback Defined function or closure to process data and return true/false
	 * 
	 * @return array
	 */
	public function validate($callback = null)
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!$data) {
			return array('ResultCode' => 1, 'ResultDesc' => 'No data received');
		}

		if(is_null($callback)){
			return array('ResultCode' => 0, 'ResultDesc' => 'Success');
		} else {
			return call_user_func_array($callback, array($data)) 
				? array('ResultCode' => 0, 'ResultDesc' => 'Success') 
				: array('ResultCode' => 1, 'ResultDesc' => 'Failed');
		}
	}
	
	/**
	 * @param callable $callback Defined function or closure to process data and return true/false
	 * 
	 * @return array
	 */
	public function confirm($callback = null)
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!$data) {
			return array('ResultCode' => 1, 'ResultDesc' => 'No data received');
		}

		if(is_null($callback)){
			return array('ResultCode' => 0, 'ResultDesc' => 'Success');
		} else {
			return call_user_func_array($callback, array($data)) 
				? array('ResultCode' => 0, 'ResultDesc' => 'Success') 
				: array('ResultCode' => 1, 'ResultDesc' => 'Failed');
		}
	}
	
	/**
	 * @param callable $callback Defined function or closure to process data and return true/false
	 * 
	 * @return array
	 */
	public function reconcile($callback = null)
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!$data) {
			return array('ResultCode' => 1, 'ResultDesc' => 'No data received');
		}

		if(is_null($callback)){
			return array('ResultCode' => 0, 'ResultDesc' => 'Success');
		} else {
			return call_user_func_array($callback, array($data)) 
				? array('ResultCode' => 0, 'ResultDesc' => 'Success') 
				: array('ResultCode' => 1, 'ResultDesc' => 'Failed');
		}
	}
	
	/**
	 * @param callable $callback Defined function or closure to process data and return true/false
	 * 
	 * @return array
	 */
	public function register($callback = null)
	{
		$token      = $this->token();
		$endpoint   = ($this->config->env == 'live') 
			? 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl'
			: 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';

		$curl       = curl_init();
		curl_setopt($curl, CURLOPT_URL, $endpoint);
		curl_setopt(
			$curl, 
			CURLOPT_HTTPHEADER, 
			array(
				'Content-Type:application/json',
				'Authorization:Bearer '.$token
			)
		);

		$curl_post_data = array(
			'ShortCode' 		=> $this->config->shortcode,
			'ResultType' 		=> 'Cancelled',
			'ConfirmationURL' 	=> $this->config->confirmation_url,
			'ValidationURL' 	=> $this->config->validation_url
		);
		$data_string = json_encode($curl_post_data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$response   = curl_exec($curl);
		$content    = json_decode($response, true);

		if(is_null($callback)){
			if ($response) {
				if(isset($content['ResultDescription'])){
					$status = $content['ResultDescription'];
				} elseif(isset($content['errorMessage'])){
					$status = $content['errorMessage'];
				} else {
					$status = 'Sorry could not connect to Daraja. Check your connection/configuration and try again.';
				}
			}
			
			return array('Registration status' => $status);
		} else {
			return \call_user_func_array($callback, $content);
		}
	}

    /**
     * @param $phone The MSISDN sending the funds.
     * @param $amount The amount to be transacted.
     * @param $reference Used with M-Pesa PayBills.
     * 
     * @return array Response
     */
	public function simulate($phone, $amount = 10, $reference = 'TRX')
	{
		$token = $this->token();
		$phone = (substr($phone, 0,1) == '+') ? str_replace('+', '', $phone) : $phone;
		$phone = (substr($phone, 0,1) == '0') ? preg_replace('/^0/', '254', $phone) : $phone;

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
				'Authorization:Bearer '.$token
			)
		);
		$curl_post_data     = array(
			'ShortCode'     => $this->config->shortcode,
			'CommandID'     => ($this->config->type == 4) ? 'CustomerPayBillOnline' : 'CustomerBuyGoodsOnline',
			'Amount'        => round($amount),
			'Msisdn'        => $phone,
			'BillRefNumber' => $reference
		);
		$data_string        = json_encode($curl_post_data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$curl_response = curl_exec($curl);
		$response = curl_exec($curl);

		return json_decode($response, true);
	}

    /**
     * @param $phone The MSISDN sending the funds.
     * @param $amount The amount to be transacted.
     * @param $reference Used with M-Pesa PayBills.
     * @param $description A description of the transaction.
     * @param $remark Remarks
     * 
     * @return array Response
     */
    public function request($phone, $amount, $reference = 'ACCOUNT', $description = 'Transaction Description', $remark = 'Remark')
    {
        $token      = $this->token();
        
		$phone      = (substr($phone, 0,1) == '+') ? str_replace('+', '', $phone) : $phone;
		$phone      = (substr($phone, 0,1) == '0') ? preg_replace('/^0/', '254', $phone) : $phone;
		$timestamp  = date('YmdHis');
        $password   = base64_encode($this->config->shortcode.$this->config->passkey.$timestamp);
        
		$endpoint   = ($this->config->env == 'live')
            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' 
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt(
            $curl, 
            CURLOPT_HTTPHEADER, 
            array(
                'Content-Type:application/json', 
                'Authorization:Bearer '.$token
            )
        );
        $curl_post_data = array(
            'BusinessShortCode' => $this->config->headoffice,
            'Password' 			=> $password,
            'Timestamp' 		=> $timestamp,
            'TransactionType' 	=> ($this->config->type == 4) ? 'CustomerPayBillOnline' : 'CustomerBuyGoodsOnline',
            'Amount' 			=> round($amount),
            'PartyA' 			=> $phone,
            'PartyB' 			=> $this->config->shortcode,
            'PhoneNumber' 		=> $phone,
            'CallBackURL' 		=> $this->config->callback_url,
            'AccountReference' 	=> $reference,
            'TransactionDesc' 	=> $description,
            'Remark'			=> $remark
        );
        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $response = curl_exec($curl);
		
		return json_decode($response, true);
    }
}
