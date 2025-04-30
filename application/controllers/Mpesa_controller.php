<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mpesa_controller extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        require_once __DIR__ . '/../../vendor/autoload.php';

        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
        
        // Load M-Pesa library
        $mpesa_config = array(
            'env' => $_ENV['MPESA_ENV'],
            'type' => (int) $_ENV['MPESA_TYPE'],
            'shortcode' => $_ENV['BUSINESS_SHORTCODE'],
            'key' => $_ENV['CONSUMER_KEY'],
            'secret' => $_ENV['CONSUMER_SECRET'],
            'passkey' => $_ENV['MPESA_PASSKEY'],
            'validation_url' => $_ENV['MPESA_VALIDATION_URL'],
            'confirmation_url' => $_ENV['MPESA_CONFIRMATION_URL'],
            'callback_url' => $_ENV['MPESA_CALLBACK_URL'],
            'timeout_url' => $_ENV['MPESA_TIMEOUT_URL'],
            'results_url' => $_ENV['MPESA_RESULTS_URL'],
        );

        $this->load->library('mpesa_lib', $mpesa_config);
        $this->load->model('Mpesa_payment_model', 'payment');
        $this->load->model('Studentfee_model', 'studentfee');
        $this->load->library('form_validation');
        $this->load->helper('url');
    }

    /**
     * Process M-Pesa payment from fee collection form
     */
    public function process()
    {
        // Set form validation rules
        $this->form_validation->set_rules('phone', 'Phone Number', 'required|regex_match[/\+?[0-9]{10,12}/]');
        $this->form_validation->set_rules('amount', 'Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('student_id', 'Student ID', 'required|numeric');
        $this->form_validation->set_rules('collected_date', 'Collected Date', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('admin/feecollection'); // Fee collection route
        }

        // Get form data
        $phone = $this->input->post('phone');
        $amount = $this->input->post('amount');
        $student_id = $this->input->post('student_id');
        $collected_date = $this->input->post('collected_date');
        $note = $this->input->post('fee_gupcollected_note');
        $row_counter = $this->input->post('row_counter');

        // Prepare fee details from hidden fields
        $fee_details = [];
        $total_due = 0;
        foreach ($row_counter as $index) {
            $fee_amount = $this->input->post("fee_amount_$index");
            $fine_amount = $this->input->post("fee_groups_feetype_fine_amount_$index");
            $fee_details[] = [
                'student_fees_master_id' => $this->input->post("student_fees_master_id_$index"),
                'fee_groups_feetype_id' => $this->input->post("fee_groups_feetype_id_$index"),
                'fine_amount' => $fine_amount,
                'amount' => $fee_amount,
                'fee_category' => $this->input->post("fee_category_$index"),
                'trans_fee_id' => $this->input->post("trans_fee_id_$index")
            ];
            $total_due += ($fee_amount + $fine_amount);
        }

        // Validate custom amount
        if ($amount > $total_due) {
            $this->session->set_flashdata('error', 'Entered amount exceeds total due amount.');
            redirect('admin/feecollection');
        }

        // Generate unique order ID
        $order_id = 'FEE-' . date('YmdHis') . '-' . $student_id;

        // Initiate STK Push
        $response = $this->mpesa_lib->request(
            $phone,
            $amount,
            $order_id,
            "School Fee Payment",
            "Fee Payment for Student ID: " . $student_id
        );

        if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
            // Store pending payment
            $payment_data = [
                'order_id' => $order_id,
                'student_id' => $student_id,
                'phone_number' => $phone,
                'amount' => $amount,
                'request_id' => $response['CheckoutRequestID'],
                'status' => 'pending',
                'description' => $note,
                'date' => $collected_date,
                'fee_details' => json_encode($fee_details)
            ];
            $this->payment->create($payment_data);

            $this->session->set_flashdata('success', 'STK Push initiated. Please complete the transaction on your phone.');
        } else {
            $error_message = isset($response['errorMessage']) ? $response['errorMessage'] : 'Unknown error';
            $this->session->set_flashdata('error', 'Failed to initiate M-Pesa payment: ' . $error_message);
        }

        redirect('admin/feecollection');
    }

    /**
     * Handle STK Push callback
     */
    public function reconcile()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        log_message('info', 'M-Pesa STK callback: ' . json_encode($data));

        if (isset($data['Body']['stkCallback'])) {
            $callback = $data['Body']['stkCallback'];
            $request_id = $callback['CheckoutRequestID'];
            $result_code = $callback['ResultCode'];

            $payment = $this->payment->get_by_request_id($request_id);

            if ($payment) {
                if ($result_code == 0) {
                    // Payment successful
                    $items = $callback['CallbackMetadata']['Item'];
                    $mpesa_reference = null;
                    foreach ($items as $item) {
                        if ($item['Name'] == 'MpesaReceiptNumber') {
                            $mpesa_reference = $item['Value'];
                            break;
                        }
                    }

                    // Update payment status
                    $this->payment->mark_as_success($payment->id, $mpesa_reference);

                    // Update student fee records
                    $fee_details = json_decode($payment->fee_details, true);
                    $remaining_amount = $payment->amount;

                    foreach ($fee_details as $fee) {
                        if ($remaining_amount <= 0) {
                            break;
                        }

                        $fee_amount = min($fee['amount'], $remaining_amount);
                        $fine_amount = ($fee_amount == $fee['amount']) ? $fee['fine_amount'] : 0;
                        $amount_to_record = $fee_amount + $fine_amount;

                        if ($amount_to_record > 0) {
                            $fee_data = [
                                'student_id' => $payment->student_id,
                                'date' => $payment->date,
                                'amount' => $fee_amount,
                                'amount_fine' => $fine_amount,
                                'amount_discount' => 0,
                                'description' => $payment->description,
                                'student_fees_master_id' => $fee['student_fees_master_id'],
                                'fee_groups_feetype_id' => $fee['fee_groups_feetype_id'],
                                'transport_fees_id' => $fee['trans_fee_id'],
                                'fee_category' => $fee['fee_category'],
                                'payment_mode' => 'M-Pesa',
                                'guardian_phone' => $payment->phone_number,
                                'guardian_email' => '',
                                'parent_app_key' => '',
                                'student_fees_discount_id' => 0
                            ];
                            $this->studentfee->add($fee_data);
                            $remaining_amount -= $amount_to_record;
                        }
                    }

                } else {
                    // Payment failed
                    $reason = isset($callback['ResultDesc']) ? $callback['ResultDesc'] : 'Unknown error';
                    $this->payment->mark_as_failed($payment->id, $reason);
                }

                echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
                return;
            }
        }

        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Failed to process callback']);
    }

    /**
     * M-Pesa validation callback
     */
    public function validate()
    {
        $response = $this->mpesa_lib->validate(function ($data) {
            log_message('info', 'M-Pesa validation data: ' . json_encode($data));
            return true; // Accept all transactions
        });

        echo json_encode($response);
    }

    /**
     * M-Pesa confirmation callback
     */
    public function confirm()
    {
        $response = $this->mpesa_lib->confirm(function ($data) {
            log_message('info', 'M-Pesa confirmation data: ' . json_encode($data));

            if (isset($data['BillRefNumber'])) {
                $order_id = $data['BillRefNumber'];
                $payment = $this->payment->get_by_order_id($order_id);

                if ($payment) {
                    $mpesa_reference = isset($data['TransID']) ? $data['TransID'] : null;
                    $this->payment->mark_as_success($payment->id, $mpesa_reference);
                    return true;
                }
            }

            return false;
        });

        echo json_encode($response);
    }

    /**
     * M-Pesa timeout callback
     */
    public function timeout()
    {
        $data = file_get_contents('php://input');
        log_message('info', 'M-Pesa timeout: ' . $data);

        $data = json_decode($data, true);
        if (isset($data['Body']['stkCallback']['CheckoutRequestID'])) {
            $request_id = $data['Body']['stkCallback']['CheckoutRequestID'];
            $payment = $this->payment->get_by_request_id($request_id);

            if ($payment) {
                $this->payment->mark_as_failed($payment->id, 'Transaction timed out');
            }
        }

        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    /**
     * M-Pesa results callback
     */
    public function results()
    {
        log_message('info', 'M-Pesa results: ' . file_get_contents('php://input'));
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    /**
     * Admin dashboard showing payment summary
     */
    public function dashboard()
    {
        // Check admin authentication
        if (!$this->session->userdata('admin_login')) {
            redirect('admin/login');
        }

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
    public function mark_reconciled($payment_id)
    {
        // Check admin authentication
        if (!$this->session->userdata('admin_login')) {
            redirect('admin/login');
        }

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
    public function view($payment_id)
    {
        // Check admin authentication
        if (!$this->session->userdata('admin_login')) {
            redirect('admin/login');
        }

        $data['title'] = 'Payment Details';
        $data['payment'] = $this->payment->get_by_id($payment_id);

        if (!$data['payment']) {
            show_404();
        }

        // Get student details
        $this->load->model('Student_model', 'student');
        $data['student'] = $this->student->get($data['payment']->student_id);

        $this->load->view('admin/header', $data);
        $this->load->view('mpesa/payment_details', $data);
        $this->load->view('admin/footer');
    }
}