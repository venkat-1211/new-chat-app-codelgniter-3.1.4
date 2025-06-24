<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['ChatSession_model', 'ChatMessage_model']);
        $this->load->library('upload');
        $this->load->helper(['file', 'url']);
        $this->load->library('OpenAIService'); // Assuming you've registered this service
    }

    public function sendMessage()
    {
        $user_id = $this->input->post('user_id');        // Logged in user
        $auth_id = $this->input->post('auth_id');        // If present, user they are talking to (Normal User)
        $message = $this->input->post('message');
        $timezone = $this->input->post('timezone');

        if (!$user_id || !$this->phpsession->get('user_id') || $user_id != $this->phpsession->get('user_id')) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['error' => 'Unauthorized user']));
        }

        $user = $this->ChatSession_model->getByUserId($auth_id ?: $user_id); // For translation & session
        $latestMessage = $this->ChatMessage_model->getLatestMessage($auth_id ?: $user_id);

        // Detect language for normal user
        if (empty($auth_id)) { // Normal user (no auth_id)
            if (empty($user) && !empty($message)) {
                $detectedLang = $this->openaiservice->detect($message);
                $this->ChatSession_model->create([
                    'participant_id' => $user_id,
                    'chat_language' => $detectedLang,
                    'timezone' => $timezone
                ]);
                $user = (object)[
                    'chat_language' => $detectedLang,
                ];
            } elseif (!empty($user) && !empty($message)) {
                $detectedLang = $this->openaiservice->detect($message);
                $this->ChatSession_model->update($user_id, [
                    'chat_language' => $detectedLang,
                    'timezone' => $timezone
                ]);
                $user->chat_language = $detectedLang;
            }
        }

        // Translation
        $translatedMessage = null;
        if (!empty($message) && isset($user->chat_language)) {

            $chat_language = $user->chat_language;
            $detectedLang = $this->openaiservice->detect($message);
            if ($detectedLang === $chat_language) {
                $translatedMessage = $message;
            } else {
                $context = $this->openaiservice->detectContext($latestMessage->message_translate);
                $translatedMessage = $this->openaiservice->translate($message, $chat_language, $context);
            }
        } else {
            $translatedMessage = $this->openaiservice->translate($message, 'English');  // No this function inside not come
        }

        // Admin Purpose 
        $translatedMessageFinalDetech = $this->openaiservice->detect($translatedMessage);
        if ($translatedMessageFinalDetech != 'English') {
            $context = $this->openaiservice->detectContext($latestMessage->message_translate);
            $translatedMessageFinal = $this->openaiservice->translate($translatedMessage, 'English', $context);
        } else {
            $translatedMessageFinal = $translatedMessage;
        }

        if (empty($auth_id)) {
            // Normal User sends to Admin (auth_id is empty)
            $sender_id = 0;
            $from_id = $user_id;

        } else {
            // Admin sends to Normal User
            $sender_id = $user_id;
            $from_id = $auth_id;
        }

        // Optional: image handling
        $imagePath = null;
        $imageBase64 = null;

        if (!empty($_FILES['image']['name'])) {
            $config['upload_path']   = './uploads/chat_images/';
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            $config['max_size']      = 5120;
            $config['file_name']     = time() . '_' . $_FILES['image']['name'];

            $this->upload->initialize($config);

            if ($this->upload->do_upload('image')) {
                $uploadData = $this->upload->data();
                $imagePath = 'uploads/chat_images/' . $uploadData['file_name'];
                $imageBase64 = base64_encode(file_get_contents($uploadData['full_path']));
            } else {
                return $this->output
                            ->set_content_type('application/json')
                            ->set_output(json_encode(['error' => $this->upload->display_errors()]));
            }
        }

        $roleId = $this->phpsession->get('role_id');

        $utcNow = new DateTime('now', new DateTimeZone('UTC'));
        $utcNow = $utcNow->format('Y-m-d H:i:s');

        // Save to database
        $chatData = [
            'auth_id' => $from_id,
            'sender_id' => $sender_id,
            'message_content' => $imagePath ? $imagePath : $translatedMessage,
            // 'message_translate' => $imagePath ? '' : $translatedMessageFinal,
            'message_translate' => $imagePath ? '' : ($roleId == 1 ? $message : $translatedMessageFinal),
            'message_type' => $imagePath ? 2 : 1,
            'is_ai_generated' => 0,
            'is_read' => 0,
            'status' => 1,
            'created_at' => $utcNow,
            'updated_at' => $utcNow
        ];

        $this->ChatMessage_model->create($chatData);

        // (Optional) Handle AI Auto-response here if required

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'message' => 'Message sent successfully',
                'image_url' => $imagePath ? base_url($imagePath) : null
            ]));
    }

    public function convertToTimezone($utcDatetime, $targetTimezone = 'Asia/Kolkata') {
        $date = new DateTime($utcDatetime, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($targetTimezone));
        return $date->format('h:i A');
    }
    


    public function getMessages()
    {
        $roleId = $this->phpsession->get('role_id');
        $loggedInUserId = $this->input->post('logged_in_user_id');
        $selectedUserId = $this->input->post('selected_user_id');
        $scrollCount = $this->input->post('scroll_count') ?? 1;
        $limit = 10;
        $totalRecords = $scrollCount * $limit;
    
        // Get chat session (optional validation)
        $userSelected = $this->ChatSession_model->getByUserId($selectedUserId);
        if (!$userSelected) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode(['html' => '<div class="text-danger text-center">Chat session not found.</div>']));
        }
    
        // Get chat messages
        $messages = $this->ChatMessage_model->getMessagesBySelectedUser($selectedUserId, $totalRecords);

        $html = '';
        $previousDate = null;
        $count = 0;
        // foreach ($messages as $msg) {
        //     $count++;
        //     $msgDate = date('Y-m-d', strtotime($msg->created_at));
            
        //     // Determine label (Today, Yesterday, or actual date)
        //     $label = '';
        //     $today = date('Y-m-d');
        //     $yesterday = date('Y-m-d', strtotime('-1 day'));

        //     if ($msgDate === $today) {
        //         $label = 'Today';
        //     } elseif ($msgDate === $yesterday) {
        //         $label = 'Yesterday';
        //     } else {
        //         $label = date('d M Y', strtotime($msg->created_at)); // Example: 14 May 2025
        //     }

        //     // Group messages by date with label
        //     if (!isset($previousDate) || $msgDate !== $previousDate) {
        //         $html .= '<div class="chat-date-label text-center my-2">';
        //         $html .= '<span class="badge badge-secondary p-2">' . $label . '</span>';
        //         $html .= '</div>';
        //         $previousDate = $msgDate;
        //     }

        //     // Determine if sent or received
        //     $messageClass = ($roleId == 1)
        //         // ? ($msg->sender_id == $loggedInUserId ? 'sent' : 'received')
        //         ? ($msg->sender_id == $loggedInUserId || $msg->sender_id != 0 ? 'sent' : 'received')
        //         : ($msg->auth_id == $loggedInUserId && $msg->sender_id == 0 ? 'sent' : 'received');

        //     $messageTime = date('h:i A', strtotime($msg->created_at));

        //     $html .= '<div class="chat-message ' . $messageClass . '">';

        //     // Show image if message_type == 2
        //     if ((int)$msg->message_type === 2 && !empty($msg->message_content)) {
        //         $imagePath = base_url($msg->message_content); // Ensure base_url() is loaded
        //         $html .= '<a href="' . $imagePath . '" target="_blank">';
        //         $html .= '<img src="' . $imagePath . '" alt="Image" style="max-width: 200px; border-radius: 6px;">';
        //         $html .= '</a><br>';
        //     } else {
        //         // Text or translated message
        //         $messageText = ($roleId == 1)
        //             ? htmlspecialchars($msg->message_translate)
        //             : (!empty($msg->translated_content)
        //                 ? htmlspecialchars($msg->translated_content)
        //                 : htmlspecialchars($msg->message_content));

        //         $html .= $messageText . '<br>';
        //     }

        //     $html .= '<small style="color: #6c757d; font-size: 12px;">' . $messageTime . '</small>';
        //     $html .= '</div>';
        // }
        foreach ($messages as $msg) {
            $count++;
            // $msgDate = date('Y-m-d', strtotime($msg->created_at));
            
            // // Determine label (Today, Yesterday, or actual date)
            // $label = '';
            // $today = date('Y-m-d');
            // $yesterday = date('Y-m-d', strtotime('-1 day'));

            // if ($msgDate === $today) {
            //     $label = 'Today';
            // } elseif ($msgDate === $yesterday) {
            //     $label = 'Yesterday';
            // } else {
            //     $label = date('d M Y', strtotime($msg->created_at)); // Example: 14 May 2025
            // }

            
            // Determine which timezone to use
            if ($roleId == 1) {
                $timezone = 'Asia/Kolkata';
            } else {
                $timezone = $userSelected->timezone ?? 'UTC'; // Fallback to UTC
            }

            // Convert UTC datetime to user timezone
            $utcDate = new DateTime($msg->created_at, new DateTimeZone('UTC'));
            $utcDate->setTimezone(new DateTimeZone($timezone));

            // Format in Y-m-d to compare for Today/Yesterday
            $msgDate = $utcDate->format('Y-m-d');

            // Get current date/time in same timezone
            $now = new DateTime('now', new DateTimeZone($timezone));
            $today = $now->format('Y-m-d');
            $yesterday = $now->modify('-1 day')->format('Y-m-d');

            // Decide label
            if ($msgDate === $today) {
                $label = 'Today';
            } elseif ($msgDate === $yesterday) {
                $label = 'Yesterday';
            } else {
                $label = $utcDate->format('d M Y'); // Ex: 14 May 2025
            }

            // Group messages by date with label
            if (!isset($previousDate) || $msgDate !== $previousDate) {
                $html .= '<div class="chat-date-label text-center my-2">';
                $html .= '<span class="badge badge-secondary p-2">' . $label . '</span>';
                $html .= '</div>';
                $previousDate = $msgDate;
            }

            // Determine if sent or received
            $messageClass = ($roleId == 1)
                // ? ($msg->sender_id == $loggedInUserId ? 'sent' : 'received')
                ? ($msg->sender_id == $loggedInUserId || $msg->sender_id != 0 ? 'sent' : 'received')
                : ($msg->auth_id == $loggedInUserId && $msg->sender_id == 0 ? 'sent' : 'received');


            // $messageTime = date('h:i A', strtotime($msg->created_at));
            $messageTime = $this->convertToTimezone($msg->created_at, $userSelected->timezone ?? 'UTC');
            if ($roleId == 1) {
                $messageTime = $this->convertToTimezone($msg->created_at);
            }

            $html .= '<div class="chat-message ' . $messageClass . '" data-msg-id="' . $msg->id . '">';

            if ($roleId == 1 && strtotime($messageTime) >= strtotime('-10 minutes')) {
                if ($messageClass === 'sent') {
                    $html .= '<div class="message-actions d-none">';
                
                    if (!empty($msg->message_translate)) {
                        $html .= '
                            <button class="btn btn-sm btn-edit edit-message-btn" title="Edit Message" aria-label="Edit Message">
                                <i class="fas fa-pen"></i>
                            </button>
                        ';
                    }
                
                    $html .= '
                        <button class="btn btn-sm btn-delete delete-message-btn" title="Delete Message" aria-label="Delete Message">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    ';
                
                    $html .= '</div>';
                }
                
            }
            


            // Show image if message_type == 2
            if ((int)$msg->message_type === 2 && !empty($msg->message_content)) {
                $imagePath = base_url($msg->message_content); // Ensure base_url() is loaded
                $html .= '<a href="' . $imagePath . '" target="_blank">';
                $html .= '<img src="' . $imagePath . '" alt="Image" style="max-width: 200px; border-radius: 6px;">';
                $html .= '</a><br>';
            } else {
                // Text or translated message
                $messageText = ($roleId == 1)
                    ? htmlspecialchars($msg->message_translate)
                    : (!empty($msg->translated_content)
                        ? htmlspecialchars($msg->translated_content)
                        : htmlspecialchars($msg->message_content));

                $html .= '<div class="message-content">' . nl2br($messageText) . '</div>';

                if ($messageClass === 'sent') {
                    $html .= '
                        <div class="edit-message-form d-none mt-1">
                            <textarea class="form-control form-control-sm edited-message-input" rows="1" placeholder="Edit your message..." style="resize: none;">' . htmlspecialchars($msg->message_translate) . '</textarea>
                            <div class="mt-3 d-flex justify-content-end gap-3">
                                <button class="btn btn-sm btn-success save-edited-message shadow-sm" style="border-radius: 8px; padding: 8px 16px; transition: all 0.2s;">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                    <span class="btn-label"><span class="me-1">✅</span> Save</span>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary cancel-edit-message shadow-sm" style="border-radius: 8px; padding: 8px 16px; transition: all 0.2s;">
                                    <span class="me-1">❌</span> Cancel
                                </button>
                            </div>
                        </div>
                    ';
                }
            }

            $html .= '<small style="color: #6c757d; font-size: 12px;">' . $messageTime . '</small>';
            $html .= '</div>';
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['html' => $html, 'role_id' => $roleId, 'selectedUserId' => $selectedUserId, 'selectedUserName' => $userSelected->username, 'selectedUserOnlineStatus' => $userSelected->is_online, 'count' => $count]));
    }

    public function chatUsersList() {
        $users = $this->ChatSession_model->getAllChatUsers();

        $ActiveUserId = $this->input->post('ActiveUserId') ?? null;

        // Render view to string
        $html = $this->load->view('chat/chat_user_list', ['users' => $users, 'ActiveUserId' => $ActiveUserId], true);
    
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'html' => $html,
                'count' => count($users)
            ]));
    }

    public function markAsRead() {
        $auth_id = $this->input->post('auth_id');
        $this->ChatMessage_model->markAsRead($auth_id);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
            ]));
    }

    public function updateMessage() {
        $message_id = $this->input->post('id');
        $message_content = $this->input->post('message');
        $message_translate = $message_content;  // Save same message for Admin, like message_translate column
        $message_row = $this->ChatMessage_model->getMessageById($message_id);
        $old_language_find = $this->openaiservice->detect($message_row->message_content);  // Tamil or any other language
        $message_content = $this->openaiservice->translate($message_content, $old_language_find);
        $utcNow = new DateTime('now', new DateTimeZone('UTC'));
        $utcNow = $utcNow->format('Y-m-d H:i:s');
        $this->ChatMessage_model->updateMessage($message_id, $message_content, $message_translate, $utcNow);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
            ]));
    }

    public function deleteMessage() {
        $message_id = $this->input->post('id');
        $this->ChatMessage_model->deleteMessage($message_id);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
            ]));
    }

    public function getAiSuggestion() {
        $selectedUserId = $this->input->post('selectedUserId');
        $messageHistory = $this->ChatMessage_model->getMessagesBySelectedUser($selectedUserId);
        $response = $this->openaiservice->generateSuggestedResponse($messageHistory);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'response' => $response
            ]));
    }

    public function typingStatus() {
        $auth_id = $this->input->post('auth_id');
        $is_typing = $this->input->post('is_typing');
        $this->ChatSession_model->update($auth_id, [
            'is_typing' => $is_typing
        ]);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
            ]));
    }

    public function checkTypingStatus() {
        $auth_id = $this->input->post('auth_id');
        $is_typing = $this->ChatSession_model->getTypingStatus($auth_id);
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'is_typing' => $is_typing ? (int)$is_typing->is_typing : 0,
                'noramlUserName' => $is_typing->normalUserName
            ]));
    }
    
}