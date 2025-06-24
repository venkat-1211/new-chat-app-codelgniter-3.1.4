<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ChatMessage_model extends CI_Model
{

    public function getMessagesBySelectedUser($selected_user_id, $limit = 10) {
        $subQuery = $this->db->select('users.id as user_id, users.username, chat_messages.*')
        ->from('chat_messages')
        ->join('users', 'users.id = chat_messages.auth_id')
        ->where('chat_messages.auth_id', $selected_user_id)
        ->where('chat_messages.status', 1)
        ->order_by('chat_messages.created_at', 'DESC')
        ->limit($limit)
        ->get_compiled_select();

        return $this->db->query("SELECT * FROM ($subQuery) AS sub ORDER BY sub.created_at ASC")->result();
    }

    public function getLatestMessage($auth_id) {
        return $this->db->select('message_content, message_translate, created_at')
        ->from('chat_messages')
        ->where('auth_id', $auth_id)
        ->where('status', 1)
        ->order_by('created_at', 'DESC')
        ->limit(1)
        ->get()
        ->row();
    }

    
    public function create($data)
    {
        return $this->db->insert('chat_messages', $data);
    }

    public function markAsRead($auth_id) {
        $this->db->where('auth_id', $auth_id);
        $this->db->where('sender_id', '0');  // Users Send Message Only Update is_read
        $this->db->update('chat_messages', ['is_read' => 1]);
    }

    public function getMessageById($id) {
        return $this->db->get_where('chat_messages', ['id' => $id])->row();
    }

    public function updateMessage($id, $message_content, $message_translate, $utcNow) {
        return $this->db->where('id', $id)->update('chat_messages', ['message_content' => $message_content, 'message_translate' => $message_translate, 'updated_at' => $utcNow]);
    }

    public function deleteMessage($id) {
        return $this->db->where('id', $id)->update('chat_messages',['status' => 0]);
    }
}