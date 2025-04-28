<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mpesa_controller extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Load the M-Pesa library
        $mpesa_config = array(
            'env'               => 'sandbox', // Use 'live' for production
            'type'              => 4, // 4 for PayBill, 2 for Till Number
            'shortcode'         => '174379', // Your PayBill/Till Number
            'key'               => 'wh11KbBRa7SfTBosfKEMwEOPUGO61AUA5wxgZV74A8Xy2sEJ', // API Key
            'secret'            => 'SbA8RTxBmwVyYD7QG5anNENMDsWKPpiHXDnRa5eWOJLywGDBnT7nGSPkPGiIM7ah', // API Secret
            'passkey'           => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919', // Passkey
            'validation_url'    => site_url('mpesa/validate'),
            'confirmation_url'  => site_url('mpesa/confirm'),
            'callback_url'      => site_url('mpesa/reconcile'),
            'timeout_url'       => site_url('mpesa/timeout'),
            'results_url'       => site_url('mpesa/results'),
        );
        
        $this->load->library('mpesa_lib', $mpesa_config);
        
        // Load the payment model
        $this->load->model('Mpesa_payment_model', 'payment');
        
        // Additional models for related data
        $this->load->model('Student_model', 'student');
        $this->load->model('Fee_model', 'fee');
        
        // Load helpers
        $this->load->helper('url');
        $this->load->helper('form');
    }
    
    /**
     * Display payment form
     */
    public function index() {
        $data['title'] = 'M-Pesa Payment';
        $this->load->view('header', $data);
        $this->load->view('mpesa/payment_form', $data);
        $this->load->view('footer');
    }
    
    /**
     * Process payment form and initiate STK Push
     */
    public function process() {
        // Form validation
        $this->load->library('form_validation');
        $this->form_validation->set_rules('student_id', 'Student', 'required|numeric');
        $this->form_validation->set_rules('amount', 'Amount', 'required|numeric');
        $this->form_validation->set_rules('phone', 'Phone Number', 'required|min_length[10]');
        $this->form_validation->set_rules('payment_purpose', 'Payment Purpose', 'required');
        
        if ($this->form_validation->run() == FALSE) {
            // If validation fails, return to form with errors
            $this->index();
            return;
        }
        
        // Get form data
        $student_id = $this->input->post('student_id');
        $amount = $this->input->post('amount');
        $phone = $this->input->post('phone');
        $purpose = $this->input->post('payment_purpose');
        $fee_groups_feetype_id = $this->input->post('fee_groups_feetype_id');
        $feetype_id = $this->input->post('feetype_id');
        $academic_year = $this->input->post('academic_year');
        $term = $this->input->post('term');
        $session_id = $this->input->post('session_id');
        
        // Generate a unique order ID
        $order_id = 'ORD-' . date('Ymd') . '-' . sprintf('%03d', rand(1, 999));
        
        // Check if order already exists (idempotency)
        if ($this->payment->get_by_order_id($order_id)) {
            // Regenerate order ID if duplicate
            $order_id = 'ORD-' . date('Ymd') . '-' . sprintf('%03d', rand(1000, 1999));
        }
        
        // Initiate STK Push request
        $response = $this->mpesa_lib->request(
            $phone, 
            $amount, 
            $order_id, 
            "School Fee Payment", 
            "Fee Payment for Student ID: " . $student_id
        );
        
        // Check if request was successful
        if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
            // Create payment record in database
            $payment_data = array(
                'order_id' => $order_id,
                'student_id' => $student_id,
                'fee_groups_feetype_id' => $fee_groups_feetype_id,
                'feetype_id' => $feetype_id,
                'phone_number' => $phone,
                'amount' => $amount,
                'payment_purpose' => $purpose,
                'request_id' => $response['CheckoutRequestID'],
                'session_id' => $session_id,
                'academic_year' => $academic_year,
                'term' => $term,
                'notes' => 'Payment initiated via STK Push'
            );
            
            $payment_id = $this->payment->create($payment_data);
            
            // Redirect to status page
            redirect('mpesa/status/' . $payment_id);
        } else {
            // Handle error
            $error_message = isset($response['errorMessage']) ? $response['errorMessage'] : 'Unknown error occurred';
            
        }
    }
    
    /**
     * Display payment status page
     */
    public function status($payment_id) {
        $data['title'] = 'Payment Status';
        $data['payment'] = $this->payment->get_by_id($payment_id);
        
        if (!$data['payment']) {
            show_404();
        }
        
        $this->load->view('header', $data);
        $this->load->view('mpesa/payment_status', $data);
        $this->load->view('footer');
    }
    
    /**
     * M-Pesa validation callback
     */
    public function validate() {
        $response = $this->mpesa_lib->validate(function($data) {
            // Log validation data
            log_message('info', 'M-Pesa validation data: ' . json_encode($data));
            
            // Always return true to accept all transactions
            return true;
        });
        
        echo json_encode($response);
    }
    
    /**
     * M-Pesa confirmation callback
     */
    public function confirm() {
        $response = $this->mpesa_lib->confirm(function($data) {
            // Log confirmation data
            log_message('info', 'M-Pesa confirmation data: ' . json_encode($data));
            
            // Process the confirmation
            if (isset($data['BillRefNumber'])) {
                $order_id = $data['BillRefNumber'];
                $payment = $this->payment->get_by_order_id($order_id);
                
                if ($payment) {
                    // Update payment status
                    $mpesa_reference = isset($data['TransID']) ? $data['TransID'] : null;
                    $this->payment->mark_as_success($payment->id, $mpesa_reference);
                    
                    // Additional processing can be done here
                    // e.g., update student fee records, send notifications, etc.
                    
                    return true;
                }
            }
            
            return false;
        });
        
        echo json_encode($response);
    }
    
    /**
     * M-Pesa reconciliation callback (for STK Push)
     */
    public function reconcile() {
        // Get the STK callback response
        $data = json_decode(file_get_contents('php://input'), true);
        log_message('info', 'M-Pesa STK callback: ' . json_encode($data));
        
        // Extract the callback metadata
        if (isset($data['Body']['stkCallback'])) {
            $callback = $data['Body']['stkCallback'];
            $request_id = $callback['CheckoutRequestID'];
            $result_code = $callback['ResultCode'];
            
            // Find the payment by request ID
            $payment = $this->payment->get_by_request_id($request_id);
            
            if ($payment) {
                if ($result_code == 0) {
                    // Payment was successful
                    // Extract transaction details
                    $items = $callback['CallbackMetadata']['Item'];
                    $mpesa_reference = null;
                    
                    // Extract the M-Pesa Transaction ID from the items
                    foreach ($items as $item) {
                        if ($item['Name'] == 'MpesaReceiptNumber') {
                            $mpesa_reference = $item['Value'];
                            break;
                        }
                    }
                    
                    // Update payment record
                    $this->payment->mark_as_success($payment->id, $mpesa_reference);
                    
                    // Additional processing
                    // e.g., update student fee records, send confirmation email/SMS, etc.
                    
                } else {
                    // Payment failed
                    $reason = isset($callback['ResultDesc']) ? $callback['ResultDesc'] : 'Unknown error';
                    $this->payment->mark_as_failed($payment->id, $reason);
                }
                
                // Return success response
                echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
                return;
            }
        }
        
        // If we reach here, something went wrong
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Failed to process callback']);
    }
    
    /**
     * M-Pesa timeout callback
     */
    public function timeout() {
        log_message('info', 'M-Pesa timeout: ' . file_get_contents('php://input'));
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }
    
    /**
     * M-Pesa results callback
     */
    public function results() {
        log_message('info', 'M-Pesa results: ' . file_get_contents('php://input'));
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }
    
    /**
     * Admin dashboard showing payment summary
     */
    public function dashboard() {
        // Check admin authentication (implement as needed)
        
        $data['title'] = 'M-Pesa Payments Dashboard';
        
        // Get payment statistics
        $data['pending_payments'] = $this->payment->get_pending_payments();
        $data['successful_payments'] = $this->payment->get_successful_payments();
        $data['failed_payments'] = $this->payment->get_failed_payments();
        $data['payments_for_reconciliation'] = $this->payment->get_payments_for_reconciliation();
        
        $this->load->view('admin/header', $data);
        $this->load->view('mpesa/dashboard', $data);
        $this->load->view('admin/footer');
    }
    
    /**
     * Mark a payment as reconciled
     */
    public function mark_reconciled($payment_id) {
        // Check admin authentication (implement as needed)
        
        $payment = $this->payment->get_by_id($payment_id);
        
        if (!$payment) {
            show_404();
        }
        
        $this->payment->mark_as_reconciled($payment_id);
        $this->session->set_flashdata('success', 'Payment marked as reconciled');
        redirect('mpesa/dashboard');
    }
    
    /**
     * View payment details
     */
    public function view($payment_id) {
        // Check admin authentication (implement as needed)
        
        $data['title'] = 'Payment Details';
        $data['payment'] = $this->payment->get_by_id($payment_id);
        
        if (!$data['payment']) {
            show_404();
        }
        
        // Get student details
        $data['student'] = $this->student->get_by_id($data['payment']->student_id);
        
        $this->load->view('admin/header', $data);
        $this->load->view('mpesa/payment_details', $data);
        $this->load->view('admin/footer');
    }
}