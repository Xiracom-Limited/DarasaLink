<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fee_model extends CI_Model {
    
    protected $table = 'fees';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get all fees
     */
    public function get_all() {
        $this->db->select('f.*, s.first_name, s.last_name, s.admission_number, ft.name as fee_type_name');
        $this->db->from($this->table . ' f');
        $this->db->join('students s', 's.id = f.student_id', 'left');
        $this->db->join('fee_types ft', 'ft.id = f.fee_type_id', 'left');
        $this->db->order_by('f.due_date', 'desc');
        return $this->db->get()->result();
    }
    
    /**
     * Get fee by ID
     */
    public function get_by_id($id) {
        $this->db->select('f.*, s.first_name, s.last_name, s.admission_number, ft.name as fee_type_name');
        $this->db->from($this->table . ' f');
        $this->db->join('students s', 's.id = f.student_id', 'left');
        $this->db->join('fee_types ft', 'ft.id = f.fee_type_id', 'left');
        $this->db->where('f.id', $id);
        return $this->db->get()->row();
    }
    
    /**
     * Get fees by student ID
     */
    public function get_by_student_id($student_id) {
        $this->db->select('f.*, ft.name as fee_type_name');
        $this->db->from($this->table . ' f');
        $this->db->join('fee_types ft', 'ft.id = f.fee_type_id', 'left');
        $this->db->where('f.student_id', $student_id);
        $this->db->order_by('f.due_date', 'desc');
        return $this->db->get()->result();
    }
    
    /**
     * Get fees by fee type
     */
    public function get_by_fee_type($fee_type_id) {
        $this->db->select('f.*, s.first_name, s.last_name, s.admission_number');
        $this->db->from($this->table . ' f');
        $this->db->join('students s', 's.id = f.student_id', 'left');
        $this->db->where('f.fee_type_id', $fee_type_id);
        $this->db->order_by('f.due_date', 'desc');
        return $this->db->get()->result();
    }
    
    /**
     * Get all fee types
     */
    public function get_fee_types() {
        return $this->db->get('fee_types')->result();
    }
    
    /**
     * Create new fee record
     */
    public function create($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    /**
     * Update fee record
     */
    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }
    
    /**
     * Delete fee record
     */
    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }
    
    /**
     * Create fee type
     */
    public function create_fee_type($data) {
        $this->db->insert('fee_types', $data);
        return $this->db->insert_id();
    }
    
    /**
     * Update fee type
     */
    public function update_fee_type($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('fee_types', $data);
    }
    
    /**
     * Delete fee type
     */
    public function delete_fee_type($id) {
        $this->db->where('id', $id);
        return $this->db->delete('fee_types');
    }
    
    /**
     * Get overdue fees
     */
    public function get_overdue_fees() {
        $today = date('Y-m-d');
        $this->db->select('f.*, s.first_name, s.last_name, s.admission_number, ft.name as fee_type_name');
        $this->db->from($this->table . ' f');
        $this->db->join('students s', 's.id = f.student_id', 'left');
        $this->db->join('fee_types ft', 'ft.id = f.fee_type_id', 'left');
        $this->db->where('f.due_date <', $today);
        $this->db->where('f.status', 'unpaid');
        $this->db->order_by('f.due_date', 'asc');
        return $this->db->get()->result();
    }
    
    /**
     * Get upcoming fees
     */
    public function get_upcoming_fees($days = 30) {
        $today = date('Y-m-d');
        $future = date('Y-m-d', strtotime("+{$days} days"));
        
        $this->db->select('f.*, s.first_name, s.last_name, s.admission_number, ft.name as fee_type_name');
        $this->db->from($this->table . ' f');
        $this->db->join('students s', 's.id = f.student_id', 'left');
        $this->db->join('fee_types ft', 'ft.id = f.fee_type_id', 'left');
        $this->db->where('f.due_date >=', $today);
        $this->db->where('f.due_date <=', $future);
        $this->db->where('f.status', 'unpaid');
        $this->db->order_by('f.due_date', 'asc');
        return $this->db->get()->result();
    }
    
    /**
     * Mark fee as paid
     */
    public function mark_as_paid($id, $payment_id = null) {
        $data = [
            'status' => 'paid',
            'payment_date' => date('Y-m-d'),
        ];
        
        if ($payment_id) {
            $data['payment_id'] = $payment_id;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Mark fee as partially paid
     */
    public function mark_as_partial($id, $amount_paid, $payment_id = null) {
        $fee = $this->get_by_id($id);
        
        if (!$fee) {
            return false;
        }
        
        $data = [
            'amount_paid' => $amount_paid,
            'status' => ($amount_paid >= $fee->amount) ? 'paid' : 'partial',
            'payment_date' => date('Y-m-d'),
        ];
        
        if ($payment_id) {
            $data['payment_id'] = $payment_id;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Get fee payment history
     */
    public function get_payment_history($fee_id) {
        $this->db->select('p.*, u.username as collected_by');
        $this->db->from('payments p');
        $this->db->join('users u', 'u.id = p.user_id', 'left');
        $this->db->where('p.fee_id', $fee_id);
        $this->db->order_by('p.payment_date', 'desc');
        return $this->db->get()->result();
    }
    
    /**
     * Get student fee summary
     */
    public function get_student_fee_summary($student_id) {
        $this->db->select('
            SUM(CASE WHEN status = "unpaid" OR status = "partial" THEN amount - COALESCE(amount_paid, 0) ELSE 0 END) as total_outstanding,
            SUM(CASE WHEN status = "paid" OR status = "partial" THEN COALESCE(amount_paid, 0) ELSE 0 END) as total_paid,
            COUNT(*) as total_fee_records,
            SUM(CASE WHEN status = "unpaid" THEN 1 ELSE 0 END) as unpaid_count,
            SUM(CASE WHEN status = "partial" THEN 1 ELSE 0 END) as partial_count,
            SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count
        ');
        $this->db->from($this->table);
        $this->db->where('student_id', $student_id);
        return $this->db->get()->row();
    }
    
    /**
     * Get monthly fee collection report
     */
    public function get_monthly_collection($year = null, $month = null) {
        if (!$year) $year = date('Y');
        if (!$month) $month = date('m');
        
        $start_date = "{$year}-{$month}-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $this->db->select('
            DATE(p.payment_date) as collection_date,
            SUM(p.amount) as total_amount,
            COUNT(*) as payment_count
        ');
        $this->db->from('payments p');
        $this->db->where('p.payment_date >=', $start_date);
        $this->db->where('p.payment_date <=', $end_date);
        $this->db->group_by('DATE(p.payment_date)');
        $this->db->order_by('p.payment_date', 'asc');
        return $this->db->get()->result();
    }
    
    /**
     * Get annual fee collection report
     */
    public function get_annual_collection($year = null) {
        if (!$year) $year = date('Y');
        
        $start_date = "{$year}-01-01";
        $end_date = "{$year}-12-31";
        
        $this->db->select('
            MONTH(p.payment_date) as month,
            SUM(p.amount) as total_amount,
            COUNT(*) as payment_count
        ');
        $this->db->from('payments p');
        $this->db->where('p.payment_date >=', $start_date);
        $this->db->where('p.payment_date <=', $end_date);
        $this->db->group_by('MONTH(p.payment_date)');
        $this->db->order_by('MONTH(p.payment_date)', 'asc');
        return $this->db->get()->result();
    }
    
    /**
     * Calculate remaining balance for a fee
     */
    public function calculate_balance($id) {
        $fee = $this->get_by_id($id);
        
        if (!$fee) {
            return 0;
        }
        
        $amount_paid = $fee->amount_paid ? $fee->amount_paid : 0;
        return $fee->amount - $amount_paid;
    }
    
    /**
     * Bulk fee assignment for a class
     */
    public function assign_class_fee($class_id, $fee_data) {
        $this->load->model('Student_model');
        $students = $this->Student_model->get_by_class($class_id);
        
        $count = 0;
        foreach ($students as $student) {
            $fee = $fee_data;
            $fee['student_id'] = $student->id;
            
            if ($this->create($fee)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Get fee statistics for dashboard
     */
    public function get_fee_statistics() {
        $this->db->select('
            SUM(CASE WHEN status = "unpaid" OR status = "partial" THEN amount - COALESCE(amount_paid, 0) ELSE 0 END) as total_outstanding,
            SUM(CASE WHEN status = "paid" OR status = "partial" THEN COALESCE(amount_paid, 0) ELSE 0 END) as total_collected,
            SUM(amount) as total_invoiced,
            COUNT(*) as total_invoices,
            SUM(CASE WHEN status = "unpaid" THEN 1 ELSE 0 END) as unpaid_count,
            SUM(CASE WHEN status = "partial" THEN 1 ELSE 0 END) as partial_count,
            SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count
        ');
        $this->db->from($this->table);
        return $this->db->get()->row();
    }
}