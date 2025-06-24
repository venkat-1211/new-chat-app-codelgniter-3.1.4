<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartHR Chat</title>
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('public/css/main.css') ?>">

    <style>


    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-2 sidebar">
            <?php if ($this->phpsession->get('role_id') == 1): ?>
                <h4 class="text-primary mb-4 text-center">Admin</h4>
            <?php endif; ?>
            <?php if ($this->phpsession->get('role_id') == 2): ?>
                <h4 class="text-primary mb-4 text-center">Normal User (<?php echo ($this->phpsession->get('user_id')); ?>)</h4>
            <?php endif; ?>
                <ul class="nav flex-column">
                    <?php if ($this->phpsession->get('role_id') == 1): ?>
                        <li class="nav-item bg-light">
                            <a class="nav-link" href="#"><i class="fas fa-comment"></i> Chat</a>
                        </li>
                    <?php endif; ?>
                        <li class="nav-item bg-light">
                            <a class="nav-link" href="<?php echo base_url('auth/logout'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                </ul>
            </div>

            <?php if ($this->phpsession->get('role_id') == 1): ?>
            <!-- Chat List -->
            <div class="chat-list shadow-sm mr-3">
                <div class="chat-list-header">Chats</div>
                <div class="chat-search">
                    <input type="text" class="form-control searchUserInput" placeholder="Search For Contacts or Messages" aria-label="Search contacts or messages" />
                </div>

                <div class="chat-list-body">
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <a href="#" class="chat-list-item selectedUser" tabindex="0" data-user-id="<?= $user->id ?>">
                                <div class="avatar">
                                    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ea3f4c61-ad8c-4bd7-ad08-5ce7275771a5.png" alt="Avatar <?= htmlspecialchars($user->username) ?>">
                                    <span class="<?= $user->is_online == 1 ? 'status-dot bg-success' : 'status-dot bg-secondary' ?>" title="<?= $user->is_online == 1 ? 'Online' : 'Offline' ?>"></span>
                                </div>

                                <div class="chat-info">
                                    <p class="name mb-0"><?= htmlspecialchars($user->username) ?></p>
                                    <p class="last-message mb-0">
                                        <?php if ($user->is_typing == 1): ?>
                                            <div class="typing-indicator-inline d-flex align-items-center">
                                                <div class="typing-bubble shadow-sm">
                                                    <span class="dot dot1"></span>
                                                    <span class="dot dot2"></span>
                                                    <span class="dot dot3"></span>
                                                </div>
                                                <small class="ml-2 text-muted font-italic">Typing...</small>
                                            </div>
                                        <?php else: ?>
                                            <?= htmlspecialchars($user->latest_message) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div class="text-right d-flex flex-column align-items-end">
                                    <div class="chat-time"><?= htmlspecialchars($user->formatted_time) ?></div>
                                    <?php if ((int)$user->unread_count > 0): ?>
                                        <span class="unread-badge"><?= $user->unread_count ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        <!-- View More Button -->
                        <div class="text-center mt-4 mb-3">
                            <button class="btn userListViewMoreButton px-4 py-2 shadow" id="userListViewMoreButton" type="button" style="
                                background: linear-gradient(135deg, #007bff, #6610f2);
                                color: white;
                                font-weight: 600;
                                font-size: 16px;
                                border: none;
                                border-radius: 50px;
                                transition: all 0.3s ease-in-out;
                                box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
                            ">
                                <i class="fas fa-chevron-circle-down mr-2"></i> View More
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="no-users-found text-center py-4" style="color: #666;">
                            <i class="fas fa-users-slash fa-2x mb-2 text-muted"></i>
                            <div style="font-size: 18px; font-weight: 500;">No users found</div>
                            <div style="font-size: 14px;">Try refreshing or adjusting your filters</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="col chat-area" id="chat-area"> <!-- Changed from col-7 to col for full width -->
                <div class="chat-header" id="chatHeader">
                    <!-- <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ea3f4c61-ad8c-4bd7-ad08-5ce7275771a5.png" class="rounded-circle mr-2" alt="Anthony Lewis">
                    <div>
                        <strong class="selectedUserName"></strong><br>
                        <small>Online</small>
                    </div> -->
                    <strong style="
                        font-size: 20px;
                        background: linear-gradient(to right, #007bff, #6610f2);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        font-weight: 600;
                    ">
                        Select a user to start chatting
                    </strong>
                </div>
                <div class="chat-body" id="chatBody">
                </div>
    
                    <!-- 👇 Scroll to Bottom Button -->
                    <button id="scrollToBottomBtnAdmin" style="
                    display: none;
                    position: absolute;
                    bottom: 70px;
                    right: 20px;
                    z-index: 999;
                    background-color: #007bff;
                    color: white;
                    border: none;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                " title="Scroll to bottom">
                    <i class="fas fa-arrow-down"></i>
                </button>

                <!-- 👇 Image Preview Will Appear Here -->
                <div id="imagePreview" class="mt-2 imagePreview">
                </div>

                <!-- Get AI Response -->
                <div class="ai-response alert alert-light border shadow-sm d-none" id="aiResponse" style="
                    border-radius: 12px;
                    padding: 16px 20px;
                    margin-top: 15px;
                    background: #f8f9fa;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 15px;
                    animation: fadeIn 0.3s ease-in-out;
                ">
                    <div class="d-flex align-items-start gap-2" style="flex-grow: 1;">
                        <i class="fas fa-robot text-primary mt-1"></i>
                        <span class="ai-response-text text-dark" style="font-size: 15px; font-weight: 500;">
                            <!-- AI response goes here -->
                        </span>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary use-ai-response" title="Insert into message input">
                            <i class="fas fa-check me-1"></i> Use This
                        </button>
                        <button class="btn btn-outline-secondary dismiss-ai-response" title="Dismiss suggestion">
                            <i class="fas fa-times me-1"></i> Dismiss
                        </button>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div class="typing-indicator-wrapper-admin d-none mb-2">
                    <div class="d-flex align-items-center shadow-sm p-2 rounded bg-light" style="max-width: 220px;">
                        <img class="rounded-circle mr-2" width="36" height="36" alt="User" id="normalUser">
                        <div class="typing-indicator-bubble px-3 py-1 rounded bg-white d-flex align-items-center" style="border-radius: 20px; position: relative;">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        </div>
                    </div>
                </div>


                <div class="chat-input">
                    <div class="input-group d-none" id="messageInputGroup">
                        <textarea class="form-control messageInput" rows="1" placeholder="Type your message..." style="resize: none;"></textarea>


                        <div class="input-group-append d-flex align-items-center">
                            <!-- Get AI Response Button -->
                            <button class="btn btn-outline-info me-1 getAiResponse" type="button" title="Get AI Suggested Response">
                                🤖
                            </button>
                            <!-- File Upload Button -->
                            <label class="btn btn-outline-secondary btn-file-label mb-0" for="chatFile">
                                <i class="fas fa-paperclip"></i>
                            </label>
                            <input type="file" id="chatFile" class="fileInput chatFile" accept="image/*" />

                            <!-- Send Button -->
                            <button class="btn btn-primary sendMessage" type="button">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Session Expired -->
                <div class="text-center py-3 d-none sessionExpiredMessageAdminChat">
                    <span class="session-expired-blink btn btn-sm btn-danger px-4 py-2 rounded-pill shadow-sm" style="
                        font-weight: 600;
                        font-size: 15px;
                        animation: blinkSession 2s ease-in-out infinite;
                        transition: background-color 0.3s ease;
                        cursor: pointer;
                    ">
                        🔒 Session is closed, click to Restart
                    </span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Icon -->
    <?php if ($this->phpsession->get('role_id') == 2): ?>
    <div class="chat-icon" id="chatIcon">
        <i class="fas fa-comment"></i>
    </div>
    <?php endif; ?>

    <!-- Chat Tab -->
    <div class="chat-tab" id="chatTab">
        <div class="chat-tab-header">
            <span>Quick Chat</span>
            <i class="fas fa-times" id="closeChatTab"></i>
        </div>
        <div class="chat-tab-body" id="chatTabBody">
            <div class="chat-message received">
                Welcome to Quick Chat!
                <br><small>Just now</small>
            </div>
        </div>
        <div id="chatTabFooter">
        </div>
        
        <!-- 👇 Scroll to Bottom Button -->
        <button id="scrollToBottomBtn" style="
            display: none;
            position: absolute;
            bottom: 70px;
            right: 20px;
            z-index: 999;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        " title="Scroll to bottom">
            <i class="fas fa-arrow-down"></i>
        </button>

        <!-- 👇 Image Preview Will Appear Here -->
        <div id="imagePreview" class="mt-2 imagePreview"></div>

        <!-- Typing Indicator -->
        <div class="typing-indicator-wrapper d-none mb-2">
            <div class="d-flex align-items-center shadow-sm p-2 rounded bg-light" style="max-width: 220px;">
                <img src="https://ui-avatars.com/api/?name=Admin" class="rounded-circle mr-2" width="36" height="36" alt="User">
                <div class="typing-indicator-bubble px-3 py-1 rounded bg-white d-flex align-items-center" style="border-radius: 20px; position: relative;">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
                </div>
            </div>
        </div>


        <div class="chat-input">
            <div class="input-group" id="messageInputGroup">
                <!-- Text Message Input -->
                <textarea class="form-control messageInput" rows="1" placeholder="Type your message..." style="resize: none;"></textarea>

                <div class="input-group-append d-flex align-items-center">

                    <!-- File Upload Button -->
                    <label class="btn btn-outline-secondary mb-0" for="chatFile" style="cursor: pointer;">
                        <i class="fas fa-paperclip"></i>
                    </label>
                    <!-- Hidden File Input -->
                    <input type="file" id="chatFile" class="fileInput d-none chatFile" accept="image/*" />

                    <!-- Send Button -->
                    <button class="btn btn-primary sendMessage" type="button" id="sendMessage">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Session Expired -->
        <div class="text-center py-3 d-none sessionExpiredMessageNormalUserChat">
            <span class="session-expired-blink btn btn-sm btn-danger px-4 py-2 rounded-pill shadow-sm" style="
                font-weight: 600;
                font-size: 15px;
                animation: blinkSession 2s ease-in-out infinite;
                transition: background-color 0.3s ease;
                cursor: pointer;
            ">
                🔒 Session is closed, click to Restart
            </span>
        </div>


    </div>

    <!-- Bootstrap 4 JS and Dependencies -->
    <!-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


<!-- Default Variables -->
<script>

    // Get logged-in user ID from PHP session
    const loggedInUserId = <?php echo $this->phpsession->get('user_id'); ?>;
    let selectedUserId = null;

    let scrollUpCountNormalChatBox = 1;

    let scrollUpCountAdminChatBox = 1;

    // Static Variables
    let user_id = null;
    let messageInterval = null;
    let selectedFile = null;

    let typingTimer;

    const ChatConfig = {
        pollingInterval: 3000,        // Default interval in ms
        sessionTimeout: 600000,       // 10 minutes  => 600000
        typingTimeout : 3000,
        typingSessionTimeout : 3000
    };

</script>

<!-- Local Storage -->
<script>
    // Check if scrollUpCountAdminChatBox exists in localStorage
    if (localStorage.getItem('scrollUpCountAdminChatBox')) {
        scrollUpCountAdminChatBox = parseInt(localStorage.getItem('scrollUpCountAdminChatBox'));
    }

    // Check if scrollUpCountAdminChatBox exists in localStorage
    if (localStorage.getItem('scrollUpCountNormalChatBox')) {
        scrollUpCountNormalChatBox = parseInt(localStorage.getItem('scrollUpCountNormalChatBox'));
    }
</script>

<!-- Interval -->
<script>
    // Interval Manager
    const intervalManager = {
        normalChat: null,
        normalTimeout: null,
        adminChat: null,
        adminTimeout: null,
        userList: null,
        userListTimeout: null,
        typingNormal: null,   // Typing indicator for normal user chat box inside
        typingNormalTimeout: null,
        typingAdmin: null,     // Typing indicator for admin chat box inside
        typingAdminTimeout: null,
        clearAll() {
            if (this.normalChat) clearInterval(this.normalChat);
            if (this.normalTimeout) clearTimeout(this.normalTimeout);
            if (this.adminChat) clearInterval(this.adminChat);
            if (this.adminTimeout) clearTimeout(this.adminTimeout);
            if (this.userList) clearInterval(this.userList);
            if (this.userListTimeout) clearTimeout(this.userListTimeout);

            if (this.typingNormal) clearInterval(this.typingNormal);
            if (this.typingNormalTimeout) clearTimeout(this.typingNormalTimeout);

            if (this.typingAdmin) clearInterval(this.typingAdmin);
            if (this.typingAdminTimeout) clearTimeout(this.typingAdminTimeout);

            this.normalChat = null;
            this.normalTimeout = null;
            this.adminChat = null;
            this.adminTimeout = null;
            this.userList = null;
            this.userListTimeout = null;
            this.typingNormal = null;
            this.typingNormalTimeout = null;
            this.typingAdmin = null;
            this.typingAdminTimeout = null;
        }
    };

    // Normal User Polling Start

    const chatTab = document.getElementById('chatTab');
    let chatTabOpened = false;

    const observer = new MutationObserver(() => {
        if (chatTab.classList.contains('active') && !chatTabOpened) {
            chatTabOpened = true;

            // Session Expired Purpose code
            $('.sessionExpiredMessageNormalUserChat').addClass('d-none');
            $('.chat-input').removeClass('d-none');

            loadMessages(<?php echo $this->phpsession->get('user_id') ?>, scrollUpCountNormalChatBox);

            intervalManager.normalChat = setInterval(() => {
                loadMessages(<?php echo $this->phpsession->get('user_id') ?>, scrollUpCountNormalChatBox);
            }, ChatConfig.pollingInterval);

            intervalManager.normalTimeout = setTimeout(() => {
                clearInterval(intervalManager.normalChat);
                intervalManager.normalChat = null;
                console.log("Auto-stopped normal chat after 10 minutes");
                $('.chat-input').addClass('d-none');
                $('#chatTab').append(`
                    <div class="text-center py-3 sessionExpiredMessageNormalUserChat">
                        <span id="sessionExpiredMessage" class="session-expired-blink btn btn-sm btn-danger px-4 py-2 rounded-pill shadow-sm" style="
                            font-weight: 600;
                            font-size: 15px;
                            animation: blinkSession 2s ease-in-out infinite;
                            cursor: pointer;
                            transition: background-color 0.3s ease;
                        ">
                            🔒 Session is closed, click to Restart
                        </span>
                    </div>
                `);
            }, ChatConfig.sessionTimeout);

            intervalManager.typingNormal = setInterval(() => {
                if (selectedUserId) checkTypingStatus(selectedUserId);
            }, ChatConfig.pollingInterval);

            intervalManager.typingNormalTimeout = setTimeout(() => {
                clearInterval(intervalManager.typingNormal);
                intervalManager.typingNormal = null;
            }, ChatConfig.sessionTimeout);

    
        } else if (!chatTab.classList.contains('active')) {
            chatTabOpened = false;

            clearInterval(intervalManager.normalChat);
            clearTimeout(intervalManager.normalTimeout);

            clearInterval(intervalManager.typingNormal);
            clearTimeout(intervalManager.typingNormalTimeout);

            intervalManager.normalChat = null;
            intervalManager.normalTimeout = null;

            intervalManager.typingNormal = null;
            intervalManager.typingNormalTimeout = null;

            console.log("Normal chat tab closed");
        }
    });
    observer.observe(chatTab, { attributes: true, attributeFilter: ['class'] });


    // Session Expired Purpose code For normal chat
    $(document).on('click', '.sessionExpiredMessageNormalUserChat', function () {

        // Session Expired Purpose code
        $('.sessionExpiredMessageNormalUserChat').addClass('d-none');
        $('.chat-input').removeClass('d-none');

        intervalManager.normalChat = setInterval(() => {
            loadMessages(<?php echo $this->phpsession->get('user_id') ?>, scrollUpCountNormalChatBox);
        }, ChatConfig.pollingInterval);

        intervalManager.normalTimeout = setTimeout(() => {
            clearInterval(intervalManager.normalChat);
            intervalManager.normalChat = null;
            console.log("Auto-stopped normal chat after 10 minutes");
            $('.chat-input').addClass('d-none');
            $('#chatTab').append(`
                <div class="text-center py-3 sessionExpiredMessageNormalUserChat">
                    <span id="sessionExpiredMessage" class="session-expired-blink btn btn-sm btn-danger px-4 py-2 rounded-pill shadow-sm" style="
                        font-weight: 600;
                        font-size: 15px;
                        animation: blinkSession 2s ease-in-out infinite;
                        cursor: pointer;
                        transition: background-color 0.3s ease;
                    ">
                        🔒 Session is closed, click to Restart
                    </span>
                </div>
            `);
        }, ChatConfig.sessionTimeout);

        // Normal User Typing to Admin
        intervalManager.typingNormal = setInterval(() => {
            if (selectedUserId) checkTypingStatus(selectedUserId);
        }, ChatConfig.pollingInterval);

        intervalManager.typingNormalTimeout = setTimeout(() => {
            clearInterval(intervalManager.typingNormal);
            intervalManager.typingNormal = null;
        }, ChatConfig.sessionTimeout);
    });

    // Normal User Polling End

    // Admin User Polling Start

    $(document).on('click', '.selectedUser', function () {
        const userId = $(this).data('user-id');

        // Store the selected user ID in localStorage => use case : active user set
        localStorage.setItem('selectedChatUserId', userId);

        // Get AI Response Remove
        $('#aiResponse').addClass('d-none');

        //  Session Expired Purpose code
        $('.chat-input').removeClass('d-none');
        $('.sessionExpiredMessageAdminChat').addClass('d-none');

        setTimeout(() => {
            scrollToBottom('chatTabBody');
            scrollToBottom();
        }, 300);

        $('#messageInputGroup').removeClass('d-none');

        scrollUpCountAdminChatBox = 1;
        localStorage.setItem('scrollUpCountAdminChatBox', scrollUpCountAdminChatBox);
        markAsRead(userId);

        // Clear existing admin chat polling
        clearInterval(intervalManager.adminChat);
        clearTimeout(intervalManager.adminTimeout);

        // Clear previous
        clearInterval(intervalManager.userList);
        clearTimeout(intervalManager.userListTimeout);

        clearInterval(intervalManager.typingAdmin);
        clearTimeout(intervalManager.typingAdminTimeout);

        loadMessages(userId, scrollUpCountAdminChatBox);

        intervalManager.adminChat = setInterval(() => {
            loadMessages(userId, scrollUpCountAdminChatBox);
            loadChatUsers($('.searchUserInput').val(), getCurrentUserListLimit(), getActiveUserId());
        }, ChatConfig.pollingInterval);

        intervalManager.adminTimeout = setTimeout(() => {
            clearInterval(intervalManager.adminChat);
            intervalManager.adminChat = null;
            console.log("Auto-stopped admin chat after 10 minutes");
            $('.chat-input').addClass('d-none');
            $('.sessionExpiredMessageAdminChat').removeClass('d-none');
        }, ChatConfig.sessionTimeout);

        // Normal User Typing to Admin
        intervalManager.typingAdmin = setInterval(() => {
            checkTypingStatus(getActiveUserId());
        }, ChatConfig.pollingInterval);

        intervalManager.typingAdminTimeout = setTimeout(() => {
            clearInterval(intervalManager.typingAdmin);
            intervalManager.typingAdmin = null;
        }, ChatConfig.sessionTimeout);
    });

    $(document).on('click', '.sessionExpiredMessageAdminChat', function () {
        $('.chat-input').removeClass('d-none');
        $('.sessionExpiredMessageAdminChat').addClass('d-none');

        const userId = localStorage.getItem('selectedChatUserId');
        intervalManager.adminChat = setInterval(() => {
            loadMessages(userId, scrollUpCountAdminChatBox);
            loadChatUsers($('.searchUserInput').val(), getCurrentUserListLimit(), getActiveUserId());
        }, ChatConfig.pollingInterval);

        intervalManager.adminTimeout = setTimeout(() => {
            clearInterval(intervalManager.adminChat);
            intervalManager.adminChat = null;
            console.log("Auto-stopped admin chat after 10 minutes");
            $('.chat-input').addClass('d-none');
            $('.sessionExpiredMessageAdminChat').removeClass('d-none');
        }, ChatConfig.sessionTimeout);

        // Normal User Typing to Admin
        intervalManager.typingAdmin = setInterval(() => {
            checkTypingStatus(getActiveUserId());
        }, ChatConfig.pollingInterval);

        intervalManager.typingAdminTimeout = setTimeout(() => {
            clearInterval(intervalManager.typingAdmin);
            intervalManager.typingAdmin = null;
        }, ChatConfig.sessionTimeout);
    });

    $(document).on('click', '.sessionExpiredMessageNotSelectUser', function () {
        $('.sessionExpiredMessageNotSelectUser').addClass('d-none');

        const userId = localStorage.getItem('selectedChatUserId');
        intervalManager.userList = setInterval(() => {
            loadChatUsers($('.searchUserInput').val(), getCurrentUserListLimit(), getActiveUserId());
        }, ChatConfig.pollingInterval);

        intervalManager.userListTimeout = setTimeout(() => {
            clearInterval(intervalManager.userList);
            intervalManager.userList = null;
            console.log("Auto-stopped admin chat after 10 minutes");
            $('.sessionExpiredMessageNotSelectUser').removeClass('d-none');
        }, ChatConfig.sessionTimeout);

        // Normal User Typing to Admin
        intervalManager.typingAdmin = setInterval(() => {
            checkTypingStatus(getActiveUserId());
        }, ChatConfig.pollingInterval);

        intervalManager.typingAdminTimeout = setTimeout(() => {
            clearInterval(intervalManager.typingAdmin);
            intervalManager.typingAdmin = null;
        }, ChatConfig.sessionTimeout);
    });


    $(document).ready(function () {

        // Initialize selected user ID on first load
        localStorage.setItem('selectedChatUserId', null);

        // Initialize View More counter on first load
        let userListViewMoreButtonCount = 1;
        localStorage.setItem('userListViewMoreButtonCount', userListViewMoreButtonCount);

        // Clear previous
        clearInterval(intervalManager.userList);

        intervalManager.userList = setInterval(() => {
            const count = parseInt(localStorage.getItem('userListViewMoreButtonCount')) || 1;
            const limit = count * 10;
            loadChatUsers($('.searchUserInput').val(), limit, getActiveUserId());
        }, ChatConfig.pollingInterval);

        intervalManager.userListTimeout = setTimeout(() => {
                clearInterval(intervalManager.userList);
                intervalManager.userList = null;
                console.log("Auto-stopped user list after 10 minutes");
                $('#chatBody').append(`
                    <div class="text-center py-3 sessionExpiredMessageNotSelectUser">
                        <span id="sessionExpiredMessage" class="session-expired-blink btn btn-sm btn-danger px-4 py-2 rounded-pill shadow-sm" style="
                            font-weight: 600;
                            font-size: 15px;
                            animation: blinkSession 2s ease-in-out infinite;
                            cursor: pointer;
                            transition: background-color 0.3s ease;
                        ">
                            🔒 Session is closed, click to Restart
                        </span>
                    </div>
                `);
        }, ChatConfig.sessionTimeout);


        //  User List View More Button
        let userListViewMoreButton = 1;
        localStorage.setItem('userListViewMoreButton', userListViewMoreButton);

        // Normal User Typing to Admin
        intervalManager.typingAdmin = setInterval(() => {
            checkTypingStatus(getActiveUserId());
        }, ChatConfig.pollingInterval);

        intervalManager.typingAdminTimeout = setTimeout(() => {
            clearInterval(intervalManager.typingAdmin);
            intervalManager.typingAdmin = null;
        }, ChatConfig.sessionTimeout);

    });

    // Admin User Polling End


</script>

<!-- Helpers -->
<script>

    // Mark As Read
    function markAsRead(auth_id) {
        $.ajax({
            url: "<?php echo site_url('chat/mark_as_read'); ?>",
            type: 'POST',
            data: {
                auth_id: auth_id  // Normal User Id 
            },
            dataType: 'json',
            success: function (response) {
                // After Success Load Chat Users List
                loadChatUsers($('.searchUserInput').val(), getCurrentUserListLimit(), getActiveUserId());
            },
            error: function (xhr, status, error) {
                console.error('Error marking message as read:', error);
            }
        });
    }

    // Function to load messages
    function loadMessages(userId, scrollCount = 1) {
        selectedUserId = userId;

        if (!selectedUserId) {
            $('#chat-area').html('<div class="text-muted text-center mt-5">Select a chat to start messaging</div>');
            return;
        }

        if (selectedUserId != loggedInUserId) {
            markAsRead(selectedUserId);
        }

        $.ajax({
            url: "<?php echo site_url('chat/messages'); ?>",
            type: 'POST',
            data: {
                logged_in_user_id: loggedInUserId,
                selected_user_id: selectedUserId,
                scroll_count: scrollCount
            },
            dataType: 'json',
            success: function (response) {
                if (response.role_id == 1) {
                    console.log(response.count);
                    $('.messageInput').data('auth-id', response.selectedUserId); 
                    $('.sendMessage').data('auth-id', response.selectedUserId);
                    $('.selectedUserName').text(response.selectedUserName);
                    
                    var Headerhtml = '';

                    Headerhtml += '<img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ea3f4c61-ad8c-4bd7-ad08-5ce7275771a5.png" class="rounded-circle mr-2" alt="' + response.selectedUserName + '">' +
                                '<div>' +
                                    '<strong>' + response.selectedUserName + '</strong><br>' +
                                    '<small style="color:' + (response.selectedUserOnlineStatus == 1 ? 'green' : 'grey') + ';">' +
                                        (response.selectedUserOnlineStatus == 1 ? 'Online' : 'Offline') +
                                    '</small>' +
                                '</div>';

                    $('#chatHeader').html(Headerhtml);
                    $('#chatBody').html(response.html); // server sends HTML only
                    $('.messageInput').data('auth-id', response.selectedUserId);
                    $('.sendMessage').data('auth-id', response.selectedUserId);
                } else {
                    console.log(response.count);
                    $('#chatTabBody').html(response.html);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading chat:', error);
                $('#chat-area').html('<div class="text-danger text-center">Failed to load messages. Please try again.</div>');
            }
        });
    }

    // Chat Users List
    function loadChatUsers(searchUsers = null, limit = 10, getActiveUserId = null) {
        $.ajax({
            url: "<?php echo site_url('chat/users'); ?>",
            type: 'POST',
            dataType: 'json',
            data: {
                searchUsers: searchUsers,
                limit: limit,
                ActiveUserId : getActiveUserId
            },
            success: function (response) {
                $('.chat-list-body').html(response.html);
                if (response.count < limit) {
                    $('.userListViewMoreButton').hide();
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading chat users:', error);
            }
        });
    }

    function showMessageLoader(status = true) {
        const loaderHTML = `
            <div class="chat-loader text-center py-2" id="chatLoader">
                <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
                    <span class="sr-only">Sending...</span>
                </div>
            </div>
        `;

        if (status) {
            // ✅ Remove old one just in case
            $('#chatLoader').remove();

            // ✅ Append to bottom of chatBody
            if ($('#chatBody').length) {
                $('#chatBody').append(loaderHTML);
            } else if ($('#chatTabBody').length) {
                $('#chatTabBody').append(loaderHTML);
            }
        } else {
            // ✅ Remove loader
            $('#chatLoader').remove();
        }
    }

    // User Limit
    function getCurrentUserListLimit() {
        return (parseInt(localStorage.getItem('userListViewMoreButtonCount')) || 1) * 10;
    }

    // Active User
    function getActiveUserId() {
        return parseInt(localStorage.getItem('selectedChatUserId')) || null;
    }

    // Send Message
    function sendMessage(auth_id) {
        // ✅ Pause everything
        intervalManager.clearAll();

        const message = $('.messageInput').val().trim();
        const fileInput = document.querySelector('.fileInput');
        const file = fileInput.files[0];
        var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        if (!message && !file) {
            alert("Please enter a message or choose a file.");
            return;
        }
        const user_id = <?php echo $this->phpsession->get('user_id') ?>;
        // if ((!message && !selectedFile || !user_id)) return;
        // if ((!message || !user_id)) return;

        const formData = new FormData();
        formData.append('user_id', user_id);
        formData.append('message', message);
        formData.append('auth_id', auth_id);
        formData.append('timezone', timezone);
        if (file) {
            formData.append('image', file);
        }

        $('.sendMessage').prop('disabled', true);
        showMessageLoader(true);
        $.ajax({
            url: '<?php echo site_url("chat/send"); ?>', // CI-safe
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Message sent successfully');
                $('.sendMessage').prop('disabled', false);
                showMessageLoader(false);

                setTimeout(() => {
                    loadMessages(selectedUserId, scrollUpCountAdminChatBox);
                    scrollToBottom('chatTabBody');
                    scrollToBottom();
                    if (auth_id) {
                        loadChatUsers($('.searchUserInput').val(), getCurrentUserListLimit(), getActiveUserId());
                    }
                }, 100); // Delay gives time for DOM to render messages

                intervalManager.adminChat = setInterval(() => {
                    loadMessages(selectedUserId, scrollUpCountAdminChatBox);
                    if (auth_id) {
                        loadChatUsers($('.searchUserInput').val(), getCurrentUserListLimit(), getActiveUserId());
                    }
                }, ChatConfig.pollingInterval);

                intervalManager.adminTimeout = setTimeout(() => {
                    clearInterval(intervalManager.adminChat);
                    intervalManager.adminChat = null;
                    console.log("Auto-stopped after 10 minutes");
                    if (auth_id) {
                        $('.chat-input').addClass('d-none');
                        $('.sessionExpiredMessageAdminChat').removeClass('d-none');
                    } else {
                        $('.chat-input').addClass('d-none');
                        $('.sessionExpiredMessageNormalUserChat').removeClass('d-none');
                    }
                }, ChatConfig.sessionTimeout);   // 10 minutes => 600000

                // Normal User Typing to Admin
                intervalManager.typingAdmin = setInterval(() => {
                    checkTypingStatus(getActiveUserId());
                }, ChatConfig.pollingInterval);

                intervalManager.typingAdminTimeout = setTimeout(() => {
                    clearInterval(intervalManager.typingAdmin);
                    intervalManager.typingAdmin = null;
                }, ChatConfig.sessionTimeout);

                // Normal User Typing to Admin
                intervalManager.typingNormal = setInterval(() => {
                    if (selectedUserId) checkTypingStatus(selectedUserId);
                }, ChatConfig.pollingInterval);

                intervalManager.typingNormalTimeout = setTimeout(() => {
                    clearInterval(intervalManager.typingNormal);
                    intervalManager.typingNormal = null;
                }, ChatConfig.sessionTimeout);


                
            },
            error: function() {
                alert('Failed to send message');
            }
        });
    }

    // AI Response
    function getAiResponse(selectedUserId = null) {
        $.post("<?= site_url('chat/get_ai_suggestion') ?>", {
            selectedUserId: selectedUserId,
        }, function (response) {
            if (response.success) {
                console.log(response.response);
                $('.ai-response-text').text(response.response || "I 'm not sure how to respond right now. Could you please rephrase or add more details?");
                $('#aiResponse').removeClass('d-none');
            }
        }, 'json');
    }

    // Scroll to bottom
    function scrollToBottom(containerId = 'chatBody') {
        const chatBody = document.getElementById(containerId);
        if (chatBody) {
            chatBody.scrollTo({
                top: chatBody.scrollHeight,
                behavior: 'smooth'
            });
        }
    }

    // Typing Indicator
    function sendTypingStatus(authId, isTyping) {
        $.post("<?= site_url('chat/typing_status') ?>", {
            auth_id: authId,
            is_typing: isTyping || 0
        });
    }

    function checkTypingStatus(authId) {
        $.post('<?= site_url('chat/check_typing_status') ?>', { auth_id: authId }, function (res) {
            if (res.is_typing == 2) {
                $('.typing-indicator-wrapper').removeClass('d-none');
            } else {
                $('.typing-indicator-wrapper').addClass('d-none');
            }

            if (res.is_typing == 1) {
                $('.typing-indicator-wrapper-admin').removeClass('d-none');
                $('#normalUser').attr('src', 'https://ui-avatars.com/api/?name=' + res.noramlUserName);

            } else {
                $('.typing-indicator-wrapper-admin').addClass('d-none');
            }
        }, 'json');
    }


</script>

<!-- Events -->
<script>
    // Toggle Chat Tab
    $('#chatIcon').on('click', function () {
        setTimeout(function () {
            scrollToBottom('chatTabBody');
        }, 100);
        $('#chatTab').toggleClass('active');
        // Reset count on new chat
        scrollUpCountNormalChatBox = 1;
        localStorage.setItem('scrollUpCountNormalChatBox', scrollUpCountNormalChatBox);
    });

    // Close Chat Tab
    $('#closeChatTab').on('click', function () {
        $('#chatTab').removeClass('active');
    });


    // Chat Send
    // Send message
    // $('.sendMessage').click(function() {
    $(document).on('click', '.sendMessage', function () {
        let authId = $(this).data('auth-id') || '';
        sendMessage(authId);
        // Clear Input
        $('.messageInput').val('').css('height', 'auto');
        $('.fileInput').val(''); 
        $('.imagePreview').empty();
        // Scroll Down Code
        scrollToBottom('chatTabBody');
        scrollToBottom();
    });

    // enter to submit + shift+enter to prevent submit Codes
    $(document).on('keydown', '.messageInput', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault(); // Prevent default Enter behavior

            let authId = $(this).data('auth-id') || '';  // Normal User Id
            sendMessage(authId);

            // Clear input and file
            $('.messageInput').val('').css('height', 'auto');
            $('.fileInput').val('');
            $('.imagePreview').empty();

            // Scroll to bottom
            scrollToBottom('chatTabBody');
            scrollToBottom();
        }
    });

    // If you want the textarea to grow automatically as the user types:
    $(document).on('input', '.messageInput', function () {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';

        // Typing Indicator
        let authId = $(this).data('auth-id');

        // Notify typing
        sendTypingStatus(authId, 2);  // Normal User to Admin Typing

        if (authId == undefined) {
            const loginUserId = <?php echo $this->phpsession->get('user_id') ?>;
            sendTypingStatus(loginUserId, 1);  // Admin to Normal User Typing
        }

        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            if (authId) sendTypingStatus(authId, 0);

            if (authId == undefined) {
                const loginUserId = <?php echo $this->phpsession->get('user_id') ?>;
                sendTypingStatus(loginUserId, 0);  // Admin to Normal User Typing
            }
        }, ChatConfig.typingTimeout);
    });

    // If you want the textarea to grow automatically as the user types:
    $(document).on('input', '.edited-message-input', function () {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });

    // GEt AI Response
    $(document).on('click', '.getAiResponse', function () {
        let selectedUserId = getActiveUserId();
        getAiResponse(selectedUserId);
    });

    $(document).on('click', '.use-ai-response', function () {
        const aiText = $('#aiResponse .ai-response-text').text();
        $('.messageInput').val(aiText).focus();
        $('#aiResponse').addClass('d-none');
    });

    $(document).on('click', '.dismiss-ai-response', function () {
        $('#aiResponse').addClass('d-none');
    });

    // Admin Chat Box Scroll Upto 10 10 records Show
    $('#chatBody').on('scroll', function () {
        const $chatBox = $(this);
        const scrollTop = $chatBox.scrollTop();

        if (scrollTop === 0 && selectedUserId) {
            scrollUpCountAdminChatBox++;
            localStorage.setItem('scrollUpCountAdminChatBox', scrollUpCountAdminChatBox);

            const previousHeight = $chatBox[0].scrollHeight;

            loadMessages(selectedUserId, scrollUpCountAdminChatBox);

            let attemptCount = 0;
            const interval = setInterval(() => {
                const newHeight = $chatBox[0].scrollHeight;

                if (newHeight > previousHeight || attemptCount > 10) {
                    const nudge = Math.max(1, newHeight - previousHeight); // minimal scroll
                    $chatBox.scrollTop(nudge); // very small scroll down
                    clearInterval(interval);
                }

                attemptCount++;
            }, 50);
        }
    });

    // Normal User Chat Box Scroll Upto 10 10 records Show
    $('#chatTabBody').on('scroll', function () {  // Full scroll to down little bit
        const $chatBox = $(this);
        const scrollTop = $chatBox.scrollTop();

        if (scrollTop === 0 && selectedUserId) {
            scrollUpCountNormalChatBox++;
            localStorage.setItem('scrollUpCountNormalChatBox', scrollUpCountNormalChatBox);

            const previousHeight = $chatBox[0].scrollHeight;

            loadMessages(selectedUserId, scrollUpCountNormalChatBox);

            let attemptCount = 0;
            const interval = setInterval(() => {
                const newHeight = $chatBox[0].scrollHeight;

                if (newHeight > previousHeight || attemptCount > 10) {
                    const nudge = Math.max(1, newHeight - previousHeight); // minimal scroll
                    $chatBox.scrollTop(nudge); // very small scroll down
                    clearInterval(interval);
                }

                attemptCount++;
            }, 50);
        }
    });

    // Search User
    $(document).on('input', '.searchUserInput', function () {
        loadChatUsers($(this).val(), getCurrentUserListLimit(), getActiveUserId());
    });

    // Message Edit Delete Codes
    $(document).on('click', '.edit-message-btn', function () {
        // ✅ Pause everything
        intervalManager.clearAll();
        const parent = $(this).closest('.chat-message');
        parent.find('.message-content').hide();
        parent.find('.edit-message-form').removeClass('d-none');
    });

    $(document).on('click', '.cancel-edit-message', function () {
        const parent = $(this).closest('.chat-message');
        parent.find('.edit-message-form').addClass('d-none');
        parent.find('.message-content').show();
        intervalManager.adminChat = setInterval(() => {
            loadMessages(selectedUserId, scrollUpCountAdminChatBox);
            loadChatUsers($('.searchUserInput').val(), getCurrentUserListLimit(), getActiveUserId());
        }, ChatConfig.pollingInterval);

        intervalManager.adminTimeout = setTimeout(() => {
            clearInterval(intervalManager.adminChat);
            intervalManager.adminChat = null;
            console.log("Auto-stopped after 10 minutes");
            $('.chat-input').addClass('d-none');
            $('.sessionExpiredMessageAdminChat').removeClass('d-none');
        }, ChatConfig.sessionTimeout);   // 10 minutes => 600000

        // Normal User Typing to Admin
        intervalManager.typingAdmin = setInterval(() => {
            checkTypingStatus(getActiveUserId());
        }, ChatConfig.pollingInterval);

        intervalManager.typingAdminTimeout = setTimeout(() => {
            clearInterval(intervalManager.typingAdmin);
            intervalManager.typingAdmin = null;
        }, ChatConfig.sessionTimeout);
    });

    $(document).on('click', '.save-edited-message', function () {
        const $btn = $(this);
        const parent = $btn.closest('.chat-message');
        const messageId = parent.data('msg-id');
        const newMessage = parent.find('.edited-message-input').val().trim();

        if (newMessage === '') {
            alert("Message cannot be empty.");
            return;
        }

        // Show loader inside button
        $btn.prop('disabled', true);
        $btn.find('.spinner-border').removeClass('d-none');
        $btn.find('.btn-label').addClass('d-none');

        $.post("<?= site_url('chat/update_message') ?>", {
            id: messageId,
            message: newMessage
        }, function (response) {
            $btn.prop('disabled', false);
            $btn.find('.spinner-border').addClass('d-none');
            $btn.find('.btn-label').removeClass('d-none');

            if (response.success) {
                parent.find('.message-content').text(newMessage).show();
                parent.find('.edit-message-form').addClass('d-none');

                // Restart polling
                intervalManager.adminChat = setInterval(() => {
                    loadMessages(selectedUserId, scrollUpCountAdminChatBox);
                    loadChatUsers($('.searchUserInput').val(), getCurrentUserListLimit(), getActiveUserId());
                }, ChatConfig.pollingInterval);

                intervalManager.adminTimeout = setTimeout(() => {
                    clearInterval(intervalManager.adminChat);
                    intervalManager.adminChat = null;
                    console.log("Auto-stopped after 10 minutes");
                    $('.chat-input').addClass('d-none');
                    $('.sessionExpiredMessageAdminChat').removeClass('d-none');
                }, ChatConfig.sessionTimeout);

                // Normal User Typing to Admin
                intervalManager.typingAdmin = setInterval(() => {
                    checkTypingStatus(getActiveUserId());
                }, ChatConfig.pollingInterval);

                intervalManager.typingAdminTimeout = setTimeout(() => {
                    clearInterval(intervalManager.typingAdmin);
                    intervalManager.typingAdmin = null;
                }, ChatConfig.sessionTimeout);
            } else {
                alert("Failed to update message.");
            }
        }, 'json');
    });

    
    $(document).on('click', '.delete-message-btn', function () {
        const $btn = $(this);
        const parent = $btn.closest('.chat-message');
        const messageId = parent.data('msg-id');
        $.post("<?= site_url('chat/delete_message') ?>", {
            id: messageId,
        }, function (response) {
            
            if (response.success) {
                // parent.remove();
            } else {
                alert("Failed to update message.");
            }
        }, 'json');
    });
    // Message Edit Delete Codes End

    // User List More Button Click
    $(document).on('click', '.userListViewMoreButton', function () {
        let currentCount = parseInt(localStorage.getItem('userListViewMoreButtonCount')) || 1;
        currentCount++; // Increase by 1 (for next 10)
        localStorage.setItem('userListViewMoreButtonCount', currentCount);

        const newLimit = currentCount * 10;
        loadChatUsers($('.searchUserInput').val(), newLimit, getActiveUserId());
    });

    // Image Preview
    $('.chatFile').on('change', function () {
        const MAX_FILE_SIZE_MB = 5;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        const file = this.files[0];
        const previewContainer = $('.imagePreview');
        previewContainer.empty(); // Clear previous preview

        let typeWarning = '';
        if (!allowedTypes.includes(file.type)) {
            typeWarning = `
                <div class="text-danger small mt-1">Only GIF, JPG, PNG, JPEG image types are allowed.</div>
            `;
        }

        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const fileSizeMB = file.size / (1024 * 1024);

                let sizeWarning = '';
                if (fileSizeMB > MAX_FILE_SIZE_MB) {
                    sizeWarning = `
                        <div class="text-danger small mt-1">File exceeds 5MB limit</div>
                    `;
                }
                previewContainer.append(`
                    <div class="position-relative d-inline-block mr-2 mb-2 text-center">
                        <img src="${e.target.result}" class="rounded border" style="max-height: 100px;" />
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 remove-preview" title="Remove">&times;</button>
                        ${sizeWarning}
                        ${typeWarning}
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove image preview and reset file input
    $(document).on('click', '.remove-preview', function () {
        $('.chatFile').val('');
        $('.imagePreview').empty();
    });

    // ✅ jQuery Scroll Down Arrow Button for Chat Tab (Normal User)
    const $chatTabBody = $('#chatTabBody');
    const $scrollBtn = $('#scrollToBottomBtn');

    // Show/hide scroll button based on scroll position
    $chatTabBody.on('scroll', function () {
        const nearBottom = $chatTabBody[0].scrollHeight - $chatTabBody.scrollTop() <= $chatTabBody.outerHeight() + 50;

        if (!nearBottom) {
            $scrollBtn.fadeIn();
        } else {
            $scrollBtn.fadeOut();
        }
    });

    // Scroll to bottom on arrow click
    $scrollBtn.on('click', function () {
        $chatTabBody.animate({
            scrollTop: $chatTabBody[0].scrollHeight
        }, 500); // 500ms smooth scroll
    });


    // ✅ jQuery Scroll Down Arrow Button for Admin Chat Tab
    const $chatTabBodyAdmin = $('#chatBody');
    const $scrollBtnAdmin = $('#scrollToBottomBtnAdmin');

    // Show/hide scroll button based on scroll position
    $chatTabBodyAdmin.on('scroll', function () {
        const nearBottom = $chatTabBodyAdmin[0].scrollHeight - $chatTabBodyAdmin.scrollTop() <= $chatTabBodyAdmin.outerHeight() + 50;

        if (!nearBottom) {
            $scrollBtnAdmin.fadeIn();
        } else {
            $scrollBtnAdmin.fadeOut();
        }
    });

    // Scroll to bottom on arrow click
    $scrollBtnAdmin.on('click', function () {
        $chatTabBodyAdmin.animate({
            scrollTop: $chatTabBodyAdmin[0].scrollHeight
        }, 500); // Smooth scroll in 500ms
    });

</script>


</body>
</html>