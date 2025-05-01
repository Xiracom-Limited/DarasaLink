<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mpesa_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function generate_access_token() {
        $consumer_key = $this->config->item('consumer_key');
        $consumer_secret = $this->config->item('consumer_secret');
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
        $headers = [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }

        log_message('error', 'Failed to generate M-PESA access token: ' . $response);
        return null;
    }

    public function insert_mpesa_transaction($student_fees_master_id, $student_session_id, $amount, $phone_number, $invoice_id) {
        $data = [
            'student_fees_master_id' => $student_fees_master_id,
            'student_session_id' => $student_session_id,
            'amount' => $amount,
            'phone_number' => $phone_number,
            'invoice_id' => $invoice_id,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('mpesa_transactions', $data);
        return $this->db->insert_id();
    }

    public function update_mpesa_transaction($mpesa_receipt_number, $amount, $phone_number, $transaction_date) {
        $this->db->where('phone_number', $phone_number);
        $this->db->where('amount', $amount);
        $this->db->where('status', 'pending');
        $this->db->update('mpesa_transactions', [
            'mpesa_receipt_number' => $mpesa_receipt_number,
            'status' => 'completed',
            'transaction_date' => date('Y-m-d H:i:s', strtotime($transaction_date)),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $this->db->affected_rows();
    }
}