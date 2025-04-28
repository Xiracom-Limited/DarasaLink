<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Student_model extends CI_Model {
    
    protected $table = 'students';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get all students
     */
    public function get_all() {
        return $this->db->get($this->table)->result();
    }
    
    /**
     * Get student by ID
     */
    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }
    
    /**
     * Get student by admission number
     */
    public function get_by_admission_number($admission_number) {
        return $this->db->get_where($this->table, ['admission_number' => $admission_number])->row();
    }
    
    /**
     * Create new student record
     */
    public function create($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }
    
    /**
     * Update student record
     */
    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }
    
    /**
     * Delete student record
     */
    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }
    
    /**
     * Search students by name or admission number
     */
    public function search($query) {
        $this->db->like('first_name', $query);
        $this->db->or_like('last_name', $query);
        $this->db->or_like('admission_number', $query);
        return $this->db->get($this->table)->result();
    }
    
    /**
     * Get students by class/grade
     */
    public function get_by_class($class_id) {
        $this->db->where('class_id', $class_id);
        return $this->db->get($this->table)->result();
    }
    
    /**
     * Get active students
     */
    public function get_active_students() {
        $this->db->where('status', 'active');
        return $this->db->get($this->table)->result();
    }
    
    /**
     * Get student fees information
     */
    public function get_student_fees($student_id) {
        $this->db->select('f.*, p.amount_paid, p.payment_date');
        $this->db->from('fees f');
        $this->db->join('payments p', 'p.fee_id = f.id', 'left');
        $this->db->where('f.student_id', $student_id);
        return $this->db->get()->result();
    }
    
    /**
     * Check if admission number exists
     */
    public function admission_number_exists($admission_number, $exclude_id = null) {
        $this->db->where('admission_number', $admission_number);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        $query = $this->db->get($this->table);
        return $query->num_rows() > 0;
    }
}