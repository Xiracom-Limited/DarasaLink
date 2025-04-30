<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mpesa_controller extends CI_Controller {

    private $consumer_key = 'wh11KbBRa7SfTBosfKEMwEOPUGO61AUA5wxgZV74A8Xy2sEJ';
    private $consumer_secret = 'SbA8RTxBmwVyYD7QG5anNENMDsWKPpiHXDnRa5eWOJLywGDBnT7nGSPkPGiIM7ah';
    private $shortcode = '174379';
    private $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'; 
  
    public function __construct() {
        parent::__construct();
        $this->load->model('Mpesa_payment_model'); 
        $this->load->library('session','database');
        $autoload['helper'] = array('url', 'form');
    }

    // Process the M-Pesa STK Push
    public function process() {
    log_message('debug', 'Process Method Called with POST: ' . print_r($this->input->post(), true));
    if ($this->input->post()) {
        $phone = $this->input->post('phone');
        $amount = $this->input->post('amount');
        $student_id = $this->input->post('student_id');
        $collected_date = $this->input->post('collected_date');
        $note = $this->input->post('fee_gupcollected_note');
        $row_counters = $this->input->post('row_counter');

        // Validate inputs
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (!preg_match('/^254[0-9]{9}$/', $phone)) {
            log_message('error', 'Invalid phone number format: ' . $phone);
            $this->session->set_flashdata('error', 'Phone number must be in the format 254XXXXXXXXX.');
            redirect('fees/collection'); // Replace with your actual route
        }
        if (empty($amount) || $amount < 1) {
            log_message('error', 'Invalid amount: ' . $amount);
            $this->session->set_flashdata('error', 'Amount must be at least KES 1.');
            redirect('fees/collection');
        }

        // Generate access token
        $access_token = $this->get_access_token();
        if (!$access_token) {
            log_message('error', 'Failed to get access token');
            $this->session->set_flashdata('error', 'Failed to authenticate with M-Pesa.');
            redirect('fees/collection');
        }

        // Prepare STK Push request
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        $transaction_desc = 'School Fee Payment';
        $account_reference = 'FEE_' . $student_id . '_' . time();

        $stk_push_data = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callback_url,
            'AccountReference' => $account_reference,
            'TransactionDesc' => $transaction_desc
        ];

        // Send STK Push request
        $response = $this->send_stk_push($access_token, $stk_push_data);

        if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
            $checkout_request_id = $response['CheckoutRequestID'];

            // Save transaction to database
            $transaction_data = [
                'student_id' => $student_id,
                'phone' => $phone,
                'amount' => $amount,
                'request_id' => $checkout_request_id, // Store CheckoutRequestID
                'account_reference' => $account_reference,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'collected_date' => $collected_date,
                'note' => $note
            ];

            $transaction_id = $this->Mpesa_payment_model->create($transaction_data);

            // Save fee details (if needed, assuming a separate table)
            $fee_details = [];
            foreach ($row_counters as $row) {
                $fee_details[] = [
                    'student_fees_master_id' => $this->input->post('student_fees_master_id_' . $row),
                    'fee_groups_feetype_id' => $this->input->post('fee_groups_feetype_id_' . $row),
                    'fee_groups_feetype_fine_amount' => $this->input->post('fee_groups_feetype_fine_amount_' . $row),
                    'fee_amount' => $this->input->post('fee_amount_' . $row),
                    'fee_category' => $this->input->post('fee_category_' . $row),
                    'trans_fee_id' => $this->input->post('trans_fee_id_' . $row),
                    'mpesa_payment_id' => $transaction_id // Link to mpesa_payments
                ];
            }

            // Save fee details to a separate table (if applicable)
            if (!empty($fee_details)) {
                $this->db->insert_batch('mpesa_fee_details', $fee_details); // Adjust table name as needed
            }

            $this->session->set_flashdata('success', 'Payment request sent to your phone. Please enter your M-Pesa PIN.');
            redirect('fees/collection');
        } else {
            $error = isset($response['errorMessage']) ? $response['errorMessage'] : 'Unknown error occurred.';
            if (isset($response['errorCode'])) {
                $error .= ' (Error Code: ' . $response['errorCode'] . ')';
            }
            log_message('error', 'STK Push Failed: ' . $error);
            $this->session->set_flashdata('error', $error);
            redirect('fees/collection');
        }
    } else {
        log_message('error', 'No POST data received in process method.');
        $this->session->set_flashdata('error', 'No payment data submitted.');
        redirect('fees/collection');
    }
}

    // Get OAuth access token
    private function get_access_token() {
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'; // Use sandbox for testing
        // $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'; // Use production for live

        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ]);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            return $result['access_token'];
        }
        return false;
    }

    // Send STK Push request
    private function send_stk_push($access_token, $data) {
        $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'; // Use sandbox for testing
        // $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'; // Use production for live

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}