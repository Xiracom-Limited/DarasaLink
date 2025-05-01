<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Mpesa_model');
        $this->load->helper('url');
        $this->load->library('session');

        // M-PESA Configuration (you can move this to config/mpesa.php)
        $this->config->set_item('consumer_key', 'wh11KbBRa7SfTBosfKEMwEOPUGO61AUA5wxgZV74A8Xy2sEJ');
        $this->config->set_item('consumer_secret', 'SbA8RTxBmwVyYD7QG5anNENMDsWKPpiHXDnRa5eWOJLywGDBnT7nGSPkPGiIM7ah');
        $this->config->set_item('shortcode', '174379'); 
        $this->config->set_item('passkey', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
        $this->config->set_item('callback_url', base_url('https://5e9d-197-248-28-81.ngrok-free.app'));
    }

    public function index($student_fees_master_id = null) {
        if (!$student_fees_master_id) {
            show_error('Student fee record not specified.');
        }

        // Fetch fee details (adjust based on your database schema)
        $this->db->select('sfm.id as student_fees_master_id, sfm.student_session_id, sfm.amount');
        $this->db->from('student_fees_master sfm');
        $this->db->where('sfm.id', $student_fees_master_id);
        $query = $this->db->get();
        $fee_record = $query->row();

        if (!$fee_record) {
            show_error('Student fee record not found.');
        }

        // Fetch fee array (adjust this based on how $feearray is generated in your system)
        $feearray = $this->get_fee_array($student_fees_master_id);

        $invoice_id = 'INV-' . time();

        $data = [
            'student_fees_master_id' => $fee_record->student_fees_master_id,
            'student_session_id' => $fee_record->student_session_id,
            'amount' => $fee_record->amount,
            'invoice_id' => $invoice_id,
            'feearray' => $feearray
        ];

        $this->load->view('getcollectfee', $data);
    }

    // Placeholder method for fetching fee array (adjust based on your system)
    private function get_fee_array($student_fees_master_id) {
        // This should return the $feearray as expected by getcollectfee.php
        // Example placeholder (replace with actual logic)
        $this->db->select('sfm.id, sfm.amount, sfm.student_fees_master_amount, sfm.amount_detail, sfm.due_date, sfm.fine_amount, sfm.is_system, ft.name, ft.type, ft.code, "fees" as fee_category');
        $this->db->from('student_fees_master sfm');
        $this->db->join('fee_groups_feetype fgf', 'fgf.id = sfm.fee_groups_feetype_id');
        $this->db->join('feetype ft', 'ft.id = fgf.feetype_id');
        $this->db->where('sfm.id', $student_fees_master_id);
        $query = $this->db->get();
        return $query->result();
    }

    public function process() {
        $payment_mode = $this->input->post('payment_mode_fee');
        $amount = $this->input->post('amount');
        $mpesa_amount = $this->input->post('mpesa_amount'); // User-entered amount for M-PESA
        $invoice_id = $this->input->post('invoice_id');
        $phone_number = $this->input->post('phone_number');
        $student_fees_master_id = $this->input->post('student_fees_master_id');
        $student_session_id = $this->input->post('student_session_id');

        if ($payment_mode === 'M-PESA') {
            if (empty($phone_number) || !preg_match('/^2547\d{8}$/', $phone_number)) {
                $this->session->set_flashdata('error', 'Please enter a valid phone number in the format 2547XXXXXXXX');
                redirect('payment/index/' . $student_fees_master_id);
            }
            if (empty($mpesa_amount) || $mpesa_amount <= 0) {
                $this->session->set_flashdata('error', 'Please enter a valid amount to pay via M-PESA');
                redirect('payment/index/' . $student_fees_master_id);
            }

            // Use $mpesa_amount for M-PESA transactions
            $transaction_id = $this->Mpesa_model->insert_mpesa_transaction($student_fees_master_id, $student_session_id, $mpesa_amount, $phone_number, $invoice_id);

            // Get OAuth token
            $token = $this->Mpesa_model->generate_access_token();

            if (!$token) {
                $this->session->set_flashdata('error', 'Failed to authenticate with M-PESA API');
                redirect('payment/index/' . $student_fees_master_id);
            }

            // Initiate STK Push
            $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            $timestamp = date('YmdHis');
            $password = base64_encode($this->config->item('shortcode') . $this->config->item('passkey') . $timestamp);
            $payload = [
                'BusinessShortCode' => $this->config->item('shortcode'),
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $mpesa_amount,
                'PartyA' => $phone_number,
                'PartyB' => $this->config->item('shortcode'),
                'PhoneNumber' => $phone_number,
                'CallBackURL' => $this->config->item('callback_url'),
                'AccountReference' => $invoice_id,
                'TransactionDesc' => 'School Fees Payment'
            ];

            $headers = [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code == 200) {
                $response_data = json_decode($response, true);
                if (isset($response_data['ResponseCode']) && $response_data['ResponseCode'] == '0') {
                    $this->session->set_flashdata('success', 'M-PESA payment initiated. Please check your phone to complete the transaction.');
                } else {
                    $this->session->set_flashdata('error', 'Failed to initiate M-PESA payment: ' . ($response_data['errorMessage'] ?? 'Unknown error'));
                }
            } else {
                $this->session->set_flashdata('error', 'Failed to connect to M-PESA API');
            }

            redirect('payment/index/' . $student_fees_master_id);
        } else {
            // Handle other payment modes (placeholder)
            $this->session->set_flashdata('success', 'Payment processed via ' . htmlspecialchars($payment_mode));
            redirect('payment/index/' . $student_fees_master_id ?? 1);
        }
    }

    public function callback() {
        // Log the callback data
        $callback_data = file_get_contents('php://input');
        log_message('info', 'M-PESA Callback: ' . $callback_data);

        $data = json_decode($callback_data, true);

        if (isset($data['Body']['stkCallback']['ResultCode']) && $data['Body']['stkCallback']['ResultCode'] == 0) {
            $transaction_data = $data['Body']['stkCallback']['CallbackMetadata']['Item'];
            $transaction = [];

            foreach ($transaction_data as $item) {
                $transaction[$item['Name']] = $item['Value'];
            }

            $mpesa_receipt_number = $transaction['MpesaReceiptNumber'] ?? null;
            $phone_number = $transaction['PhoneNumber'] ?? null;
            $amount = $transaction['Amount'] ?? null;
            $transaction_date = $transaction['TransactionDate'] ?? null;

            // Update the transaction in the database
            $this->Mpesa_model->update_mpesa_transaction($mpesa_receipt_number, $amount, $phone_number, $transaction_date);
        } else {
            $result_desc = $data['Body']['stkCallback']['ResultDesc'] ?? 'Unknown error';
            log_message('error', 'M-PESA Callback Failed: ' . $result_desc);
        }

        // Respond to M-PESA
        header('Content-Type: application/json');
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback received']);
    }
}