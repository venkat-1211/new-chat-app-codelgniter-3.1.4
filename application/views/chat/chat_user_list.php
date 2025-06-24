<?php foreach ($users as $user): ?>
    <!-- <a href="#" class="chat-list-item selectedUser" tabindex="0" data-user-id="<?= $user->id ?>">
        <div class="avatar">
            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ea3f4c61-ad8c-4bd7-ad08-5ce7275771a5.png" alt="Avatar <?= htmlspecialchars($user->username) ?>">
            <span class="status-dot" title="Online"></span>
        </div>

        <div class="chat-info">
            <p class="name mb-0"><?= htmlspecialchars($user->username) ?></p>
            <p class="last-message mb-0"><?= htmlspecialchars($user->latest_message) ?></p>
        </div>

        <div class="text-right d-flex flex-column align-items-end">
            <div class="chat-time"><?= date('h:i A', strtotime($user->formatted_time)) ?></div>
            <?php if ((int)$user->unread_count > 0): ?>
                <span class="unread-badge"><?= $user->unread_count ?></span>
            <?php endif; ?>
        </div>
    </a> -->
<?php endforeach; ?>

<?php if (!empty($users)): ?>
    <?php foreach ($users as $user): ?>
        <!-- <a href="#" class="chat-list-item selectedUser ($ActiveUserId == $user->id ? 'active-user' : '')" tabindex="0" data-user-id="<?= $user->id ?>"> -->
        <a href="#" class="chat-list-item selectedUser <?= $user->id == $ActiveUserId ? 'active-user' : '' ?>" tabindex="0" data-user-id="<?= $user->id ?>">
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

