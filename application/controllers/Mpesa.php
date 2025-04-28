<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mpesa extends CI_Controller {
    
    private $mpesa;
    
    public function __construct() {
        parent::__construct();
        $this->load->library('Mpesa_lib');
        $this->load->model('Fee_model');
        $this->load->model('Student_model');
        $this->mpesa = $this->mpesa_lib;
    }
    
    public function index() {
        $data['transactions'] = $this->db->order_by('created_at', 'desc')
                                       ->limit(20)
                                       ->get('mpesa_transactions')
                                       ->result();
        
        $data['config'] = $this->mpesa->config;
        $data['title'] = 'M-Pesa Integration';
        
        $this->load->view('mpesa/index', $data);
    }
    
    public function save_config() {
        $config = array(
            'env' => $this->input->post('env'),
            'type' => $this->input->post('type'),
            'shortcode' => $this->input->post('shortcode'),
            'key' => $this->input->post('key'),
            'secret' => $this->input->post('secret'),
            'passkey' => $this->input->post('passkey')
        );
        
        // Save config to session or database
        $this->session->set_userdata('mpesa_config', $config);
        
        $this->session->set_flashdata('success', 'M-Pesa configuration saved successfully');
        redirect('mpesa');
    }
    
    public function register_urls() {
        $response = $this->mpesa->register();
        
        if(isset($response['Registration status'])) {
            $status = $response['Registration status'];
            
            if(strpos(strtolower($status), 'success') !== false) {
                $this->session->set_flashdata('success', 'URLs registered successfully: ' . $status);
            } else {
                $this->session->set_flashdata('error', 'URL registration failed: ' . $status);
            }
        } else {
            $this->session->set_flashdata('error', 'URL registration failed. Check your configuration.');
        }
        
        redirect('mpesa');
    }
    
    public function test_payment() {
        $phone = $this->input->post('phone');
        $amount = $this->input->post('amount');
        $reference = $this->input->post('reference');
        
        $response = $this->mpesa->request($phone, $amount, $reference, 'Test Payment', 'Test');
        
        if(isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
            // Success
            $this->session->set_flashdata('success', 'Payment request sent successfully. Check your phone to complete.');
            
            // Store request in database
            $data = array(
                'request_id' => $response['CheckoutRequestID'],
                'transaction_type' => 'stk_push',
                'trans_amount' => $amount,
                'msisdn' => $phone,
                'bill_ref_number' => $reference,
                'status' => 'pending'
            );
            
            $this->db->insert('mpesa_transactions', $data);
            
        } else {
            // Error
            $error = isset($response['errorMessage']) ? $response['errorMessage'] : 'Payment request failed';
            $this->session->set_flashdata('error', $error);
        }
        
        redirect('mpesa');
    }
    
    public function pay_fee($fee_id) {
        $fee = $this->Fee_model->get_by_id($fee_id);
        $student = $this->Student_model->get_by_id($fee->student_id);
        
        if(!$fee || !$student) {
            $this->session->set_flashdata('error', 'Fee or student not found');
            redirect('fees');
        }
        
        $balance = $fee->amount - $fee->amount_paid;
        
        $data = array(
            'fee' => $fee,
            'student' => $student,
            'balance' => $balance,
            'title' => 'Pay Fee via M-Pesa'
        );
        
        $this->load->view('mpesa/pay_fee', $data);
    }
    
    public function process_fee_payment() {
        $fee_id = $this->input->post('fee_id');
        $phone = $this->input->post('phone');
        $amount = $this->input->post('amount');
        $student_id = $this->input->post('student_id');
        
        $fee = $this->Fee_model->get_by_id($fee_id);
        $student = $this->Student_model->get_by_id($student_id);
        
        if(!$fee || !$student) {
            $this->session->set_flashdata('error', 'Fee or student not found');
            redirect('fees');
        }
        
        $reference = $student->admission_number;
        $description = "Fee payment for {$student->first_name} {$student->last_name}";
        
        $response = $this->mpesa->request($phone, $amount, $reference, $description, 'Fee Payment');
        
        if(isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
            // Success
            $this->session->set_flashdata('success', 'Payment request sent successfully. Check your phone to complete.');
            
            // Store request in database
            $data = array(
                'request_id' => $response['CheckoutRequestID'],
                'transaction_type' => 'stk_push',
                'trans_amount' => $amount,
                'msisdn' => $phone,
                'bill_ref_number' => $reference,
                'status' => 'pending'
            );
            
            $this->db->insert('mpesa_transactions', $data);
            
            // Redirect to fee details
            redirect("fees/view/{$fee_id}");
            
        } else {
            // Error
            $error = isset($response['errorMessage']) ? $response['errorMessage'] : 'Payment request failed';
            $this->session->set_flashdata('error', $error);
            redirect("mpesa/pay_fee/{$fee_id}");
        }
    }
    
    public function validate() {
        // This endpoint will be called by M-Pesa during payment validation
        $response = $this->mpesa->validate(function($data) {
            // Validate payment here
            // For example, check if account/reference exists
            $reference = isset($data['BillRefNumber']) ? $data['BillRefNumber'] : null;
            
            // Check if student with this admission number exists
            $student = $this->Student_model->get_by_admission_number($reference);
            
            return ($student != null);
        });
        
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    public function confirm() {
        // This endpoint will be called by M-Pesa to confirm payment
        $response = $this->mpesa->confirm(function($data) {
            // Process payment confirmation
            log_message('debug', 'M-Pesa confirmation: ' . json_encode($data));
            
            // Store transaction
            $transaction = array(
                'transaction_type' => isset($data['TransactionType']) ? $data['TransactionType'] : null,
                'trans_id' => isset($data['TransID']) ? $data['TransID'] : null,
                'trans_time' => isset($data['TransTime']) ? $data['TransTime'] : null,
                'trans_amount' => isset($data['TransAmount']) ? $data['TransAmount'] : null,
                'business_short_code' => isset($data['BusinessShortCode']) ? $data['BusinessShortCode'] : null,
                'bill_ref_number' => isset($data['BillRefNumber']) ? $data['BillRefNumber'] : null,
                'invoice_number' => isset($data['InvoiceNumber']) ? $data['InvoiceNumber'] : null,
                'org_account_balance' => isset($data['OrgAccountBalance']) ? $data['OrgAccountBalance'] : null,
                'third_party_trans_id' => isset($data['ThirdPartyTransID']) ? $data['ThirdPartyTransID'] : null,
                'msisdn' => isset($data['MSISDN']) ? $data['MSISDN'] : null,
                'first_name' => isset($data['FirstName']) ? $data['FirstName'] : null,
                'middle_name' => isset($data['MiddleName']) ? $data['MiddleName'] : null,
                'last_name' => isset($data['LastName']) ? $data['LastName'] : null,
                'status' => 'completed'
            );
            
            $this->db->insert('mpesa_transactions', $transaction);
            
            // Find student by reference
            $reference = isset($data['BillRefNumber']) ? $data['BillRefNumber'] : null;
            $student = $this->Student_model->get_by_admission_number($reference);
            
            if($student) {
                // Find unpaid fees for this student
                $fees = $this->Fee_model->get_by_student_id($student->id);
                $amount = isset($data['TransAmount']) ? $data['TransAmount'] : 0;
                
                foreach($fees as $fee) {
                    if($fee->status !== 'paid') {
                        $balance = $fee->amount - $fee->amount_paid;
                        
                        if($balance > 0) {
                            // Apply payment to this fee
                            $payment_amount = min($amount, $balance);
                            $amount -= $payment_amount;
                            
                            // Record payment
                            $payment_data = array(
                                'fee_id' => $fee->id,
                                'student_id' => $student->id,
                                'amount' => $payment_amount,
                                'payment_method' => 'M-Pesa',
                                'payment_reference' => isset($data['TransID']) ? $data['TransID'] : null,
                                'payment_date' => date('Y-m-d'),
                                'mpesa_reference' => isset($data['TransID']) ? $data['TransID'] : null,
                                'notes' => 'Payment via M-Pesa'
                            );
                            
                            $this->db->insert('payments', $payment_data);
                            $payment_id = $this->db->insert_id();
                            
                            // Update the fee with payment
                            $this->Fee_model->mark_as_partial($fee->id, ($fee->amount_paid + $payment_amount), $payment_id);
                            
                            // Update the mpesa transaction with payment id
                            if(isset($data['TransID'])) {
                                $this->db->where('trans_id', $data['TransID']);
                                $this->db->update('mpesa_transactions', array('payment_id' => $payment_id));
                            }
                            
                            // If no more amount to allocate, break
                            if($amount <= 0) {
                                break;
                            }
                        }
                    }
                }
            }
            
            return true;
        });
        
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    public function reconcile() {
        // This endpoint will be called by M-Pesa for STK push callback
        $response = $this->mpesa->reconcile(function($data) {
            log_message('debug', 'M-Pesa reconcile: ' . json_encode($data));
            
            $result = isset($data['Body']['stkCallback']['ResultCode']) ? $data['Body']['stkCallback']['ResultCode'] : 1;
            
            if($result == 0) {
                // Success
                $metadata = isset($data['Body']['stkCallback']['CallbackMetadata']['Item']) ? $data['Body']['stkCallback']['CallbackMetadata']['Item'] : array();
                $amount = $phone = $mpesa_receipt = $date = null;
                
                foreach($metadata as $item) {
                    if(isset($item['Name'])) {
                        if($item['Name'] == 'Amount') $amount = $item['Value'];
                        if($item['Name'] == 'PhoneNumber') $phone = $item['Value'];
                        if($item['Name'] == 'MpesaReceiptNumber') $mpesa_receipt = $item['Value'];
                        if($item['Name'] == 'TransactionDate') $date = $item['Value'];
                    }
                }
                
                // Update the transaction
                $checkout_id = isset($data['Body']['stkCallback']['CheckoutRequestID']) ? $data['Body']['stkCallback']['CheckoutRequestID'] : null;
                
                if($checkout_id) {
                    $this->db->where('request_id', $checkout_id);
                    $transaction = $this->db->get('mpesa_transactions')->row();
                    
                    if($transaction) {
                        $this->db->where('id', $transaction->id);
                        $this->db->update('mpesa_transactions', array(
                            'trans_id' => $mpesa_receipt,
                            'trans_amount' => $amount,
                            'msisdn' => $phone,
                            'status' => 'completed',
                            'result_code' => $result,
                            'result_desc' => 'Success'
                        ));
                        
                        // Find student by reference
                        $reference = $transaction->bill_ref_number;
                        $student = $this->Student_model->get_by_admission_number($reference);
                        
                        if($student) {
                            // Find unpaid fees for this student
                            $fees = $this->Fee_model->get_by_student_id($student->id);
                            $remaining = $amount;
                            
                            foreach($fees as $fee) {
                                if($fee->status !== 'paid') {
                                    $balance = $fee->amount - $fee->amount_paid;
                                    
                                    if($balance > 0) {
                                        // Apply payment to this fee
                                        $payment_amount = min($remaining, $balance);
                                        $remaining -= $payment_amount;
                                        
                                        // Record payment
                                        $payment_data = array(
                                            'fee_id' => $fee->id,
                                            'student_id' => $student->id,
                                            'amount' => $payment_amount,
                                            'payment_method' => 'M-Pesa',
                                            'payment_reference' => $mpesa_receipt,
                                            'payment_date' => date('Y-m-d'),
                                            'mpesa_reference' => $mpesa_receipt,
                                            'notes' => 'Payment via M-Pesa STK Push'
                                        );
                                        
                                        $this->db->insert('payments', $payment_data);
                                        $payment_id = $this->db->insert_id();
                                        
                                        // Update the fee with payment
                                        $this->Fee_model->mark_as_partial($fee->id, ($fee->amount_paid + $payment_amount), $payment_id);
                                        
                                        // Update the mpesa transaction with payment id
                                        $this->db->where('id', $transaction->id);
                                        $this->db->update('mpesa_transactions', array('payment_id' => $payment_id));
                                        
                                        // If no more amount to allocate, break
                                        if($remaining <= 0) {
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                // Failed
                $checkout_id = isset($data['Body']['stkCallback']['CheckoutRequestID']) ? $data['Body']['stkCallback']['CheckoutRequestID'] : null;
                $result_desc = isset($data['Body']['stkCallback']['ResultDesc']) ? $data['Body']['stkCallback']['ResultDesc'] : 'Failed';
                
                if($checkout_id) {
                    $this->db->where('request_id', $checkout_id);
                    $this->db->update('mpesa_transactions', array(
                        'status' => 'failed',
                        'result_code' => $result,
                        'result_desc' => $result_desc
                    ));
                }
            }
            
            return true;
        });
        
        header('Content-Type: application/json');
        echo json_encode($response);
    }
}