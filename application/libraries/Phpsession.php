<?php
class Phpsession {
    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    public function set($key, $value) {
        $this->CI->session->set_userdata($key, $value);
    }

    public function get($key) {
        return $this->CI->session->userdata($key);
    }

    public function destroy() {
        $this->CI->session->sess_destroy();
    }
}
