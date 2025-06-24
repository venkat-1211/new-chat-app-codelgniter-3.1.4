<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ChatSession_model extends CI_Model
{
    public function getByUserId($user_id)
    {
        return $this->db
        ->select('chat_sessions.*, users.username') // select required fields
        ->from('chat_sessions')
        ->join('users', 'users.id = chat_sessions.participant_id') // join condition
        ->where('chat_sessions.participant_id', $user_id)
        ->get()
        ->row();
    }

    public function getTypingStatus($auth_id) {
        // return $this->db->select('is_typing')->from('chat_sessions')->where('participant_id', $auth_id)->get()->row();
        return $this->db
            ->select('chat_sessions.is_typing, n.username as normalUserName')
            ->from('chat_sessions')
            ->join('users n', 'n.id = chat_sessions.participant_id')
            ->where('chat_sessions.participant_id', $auth_id)
            ->get()
            ->row();
    }

    public function getAllChatUsers()
    {

        $searchUserInput = $this->input->post('searchUsers');
        $loggedInUserId = $this->phpsession->get('user_id');
        $limit = $this->input->post('limit') ?? 10;

        return $this->db->select("
                users.id,
                users.username,
                chat_sessions.is_typing,
                chat_sessions.is_online,   
                CASE 
                    WHEN m.message_type = 2 THEN 
                        CASE 
                            WHEN m.sender_id = {$loggedInUserId} THEN 'You: image'
                            ELSE 'image'
                        END
                    WHEN m.sender_id = {$loggedInUserId} THEN CONCAT('You: ', m.message_translate)
                    ELSE m.message_translate
                END AS latest_message,
                m.message_content,
                m.created_at,
                IFNULL(uc.unread_count, 0) AS unread_count,

                 CASE
                    WHEN DATE(CONVERT_TZ(m.created_at, '+00:00', '+05:30')) = CURDATE() THEN 
                        DATE_FORMAT(CONVERT_TZ(m.created_at, '+00:00', '+05:30'), '%h:%i %p') -- Today
                    WHEN DATE(CONVERT_TZ(m.created_at, '+00:00', '+05:30')) = CURDATE() - INTERVAL 1 DAY THEN 
                        'Yesterday' -- Yesterday
                    WHEN DATE(CONVERT_TZ(m.created_at, '+00:00', '+05:30')) >= CURDATE() - INTERVAL 6 DAY THEN 
                        DAYNAME(CONVERT_TZ(m.created_at, '+00:00', '+05:30')) -- Mon/Tue/etc
                    ELSE 
                        DATE_FORMAT(CONVERT_TZ(m.created_at, '+00:00', '+05:30'), '%d %b %Y') -- 14 Jun 2025
                END AS formatted_time
            ")
                // CASE
                //     WHEN DATE(m.created_at) = CURDATE() THEN DATE_FORMAT(m.created_at, '%h:%i %p') -- Today
                //     WHEN DATE(m.created_at) = CURDATE() - INTERVAL 1 DAY THEN 'Yesterday'         -- Yesterday
                //     WHEN DATE(m.created_at) >= CURDATE() - INTERVAL 6 DAY THEN DAYNAME(m.created_at) -- Mon/Tue/etc
                //     ELSE DATE_FORMAT(m.created_at, '%d %b %Y') -- 14 Jun 2025
                // END AS formatted_time
            ->from('chat_sessions')
            ->join('users', 'users.id = chat_sessions.participant_id')
            ->join('(SELECT auth_id, MAX(created_at) AS latest_time FROM chat_messages WHERE status = 1 GROUP BY auth_id) AS lm',    // new word add for status = 1
                'lm.auth_id = users.id', false)
            ->join('chat_messages AS m',
                'm.auth_id = lm.auth_id AND m.created_at = lm.latest_time',
                'left')
            ->join('(
                SELECT auth_id, COUNT(*) AS unread_count 
                FROM chat_messages 
                WHERE is_read = 0 AND sender_id = 0 
                GROUP BY auth_id
            ) AS uc', 'uc.auth_id = users.id', 'left')   // Unread count get Only For Normal User Messages, So sender_id = 0
            ->like('users.username', $searchUserInput, 'both')
            ->where('chat_sessions.chat_status', 1)
            ->order_by('m.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    
    }

    public function create($data)
    {
        return $this->db->insert('chat_sessions', $data);
    }

    public function update($id, $data)
    {
        return $this->db->where('participant_id', $id)->update('chat_sessions', $data);
    }
}