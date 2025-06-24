<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['User_model', 'ChatSession_model']);
        $this->CI =& get_instance();
    }

    public function register() {
        if ($this->input->post()) {
            $data = [
                'username' => trim($this->input->post('username')),
                'email' => trim($this->input->post('email')),
                'password' => trim($this->input->post('password')),
            ];

            if ($this->User_model->is_email_exists($data['email'])) {
                $this->session->set_flashdata('error', 'Email already exists!');
                redirect('auth/register');
            }

            $this->User_model->create_user($data);
            $this->session->set_flashdata('success', 'Registration successful!');
            redirect('auth/login');
        }

        $this->load->view('auth/register');
    }

    public function login() {
        if ($this->input->post()) {
            $email = trim($this->input->post('email'));
            $password = trim($this->input->post('password'));

            $user = $this->User_model->get_by_email($email);
            if ($user && password_verify($password, $user->password_hash)) {
                $this->CI->phpsession->set('user_id', $user->id);
                $this->CI->phpsession->set('username', $user->username);
                $this->CI->phpsession->set('role_id', $user->role_id);
                redirect('dashboard');
            } else {
                $this->session->set_flashdata('error', 'Invalid email or password');
                redirect('auth/login');
            }
        }

        $this->load->view('auth/login');
    }

    public function logout() {
        $this->CI->phpsession->destroy();
        redirect('auth/login');
    }

    public function dashboard() {
        if (!$this->phpsession->get('user_id')) {
            redirect('auth/login');
        }

        $data['users'] = $this->ChatSession_model->getAllChatUsers();
        $this->load->view('dashboard', $data);
    }

    public function userDashboard() {
        $this->load->view('user/dashboard');
    }
}
