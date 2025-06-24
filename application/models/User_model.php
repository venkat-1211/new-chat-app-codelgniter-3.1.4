<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    private $table = 'users';

    public function get_by_email($email) {
        return $this->db->get_where($this->table, ['email' => $email, 'status' => 1])->row();
    }

    public function is_email_exists($email) {
        return $this->db->get_where($this->table, ['email' => $email])->num_rows() > 0;
    }

    public function create_user($data) {
        $insert = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
        ];
        return $this->db->insert($this->table, $insert);
    }
}
