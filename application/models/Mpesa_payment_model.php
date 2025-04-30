<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mpesa_payment_model extends CI_Model {
    
    protected $table = 'mpesa_payments';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get all payments
     */
    public function get_all() {
        return $this->db->get($this->table)->result();
    }
    
    /**
     * Get payment by ID
     */
    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }
    
    /**
     * Get payment by order ID (idempotency check)
     */
    public function get_by_order_id($order_id) {
        return $this->db->get_where($this->table, ['order_id' => $order_id])->row();
    }
    
    /**
     * Get payment by request ID
     */
    public function get_by_request_id($request_id) {
        return $this->db->get_where($this->table, ['request_id' => $request_id])->row();
    }
    
    /**
     * Get payment by M-Pesa reference
     */
    public function get_by_mpesa_reference($mpesa_reference) {
        return $this->db->get_where($this->table, ['mpesa_reference' => $mpesa_reference])->row();
    }
    
    /**
     * Create new payment record
     * Uses transaction data and student info to create a new payment record
     */
    public function create($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    /**
     * Update payment record
     */
    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }
    
    /**
     * Update payment by order ID
     */
    public function update_by_order_id($order_id, $data) {
        $this->db->where('order_id', $order_id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Update payment by request ID
     */
    public function update_by_request_id($request_id, $data) {
        $this->db->where('request_id', $request_id);
        return $this->db->update($this->table, $data);
    }
    
    /**
     * Get payments by status
     */
    public function get_by_status($status) {
        $this->db->where('status', $status);
        $this->db->order_by('created_at', 'desc');
        return $this->db->get($this->table)->result();
    }
    
    /**
     * Get pending payments
     */
    public function get_pending_payments() {
        return $this->get_by_status('pending');
    }
    
    /**
     * Get successful payments
     */
    public function get_successful_payments() {
        return $this->get_by_status('success');
    }
    
    /**
     * Get failed payments
     */
    public function get_failed_payments() {
        return $this->get_by_status('failed');
    }
    
    /**
     * Get payments for reconciliation (successful but not reconciled)
     */
    public function get_payments_for_reconciliation() {
        $this->db->where('status', 'success');
        $this->db->where('reconciled', 0);
        $this->db->order_by('created_at', 'asc');
        return $this->db->get($this->table)->result();
    }
    
    /**
     * Mark payment as successful
     */
    public function mark_as_success($id, $mpesa_reference = null) {
        $data = [
            'status' => 'success',
            'callback_received_at' => date('Y-m-d H:i:s')
        ];
        
        if ($mpesa_reference) {
            $data['mpesa_reference'] = $mpesa_reference;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Mark payment as failed
     */
    public function mark_as_failed($id, $reason = null) {
        $data = [
            'status' => 'failed',
            'callback_received_at' => date('Y-m-d H:i:s')
        ];
        
        if ($reason) {
            $data['notes'] = $reason;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Mark payment as reconciled
     */
    public function mark_as_reconciled($id) {
        return $this->update($id, ['reconciled' => 1]);
    }
}