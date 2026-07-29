<?php
session_start();
require_once __DIR__ . '/../config.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recycling staff') {
    header("Location: ../login.php");
    exit();
}

$current_staff_id = $_SESSION['user_id'];

// 2. Get current staff info
$stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $current_staff_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();

// 3. Fetch other staff/admin contacts from users table
$contacts_query = "SELECT user_id, name, role FROM users WHERE user_id != ? AND (role = 'recycling staff' OR role = 'admin')";
$stmt = $conn->prepare($contacts_query);
$stmt->bind_param("i", $current_staff_id);
$stmt->execute();
$contacts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Selected recipient (default to first contact)
$active_recipient_id = $_GET['receiver_id'] ?? ($contacts[0]['user_id'] ?? 0);
$active_recipient = null;

if ($active_recipient_id) {
    $stmt = $conn->prepare("SELECT user_id, name, role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $active_recipient_id);
    $stmt->execute();
    $active_recipient = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Chat | RecycleHub</title>
    <link rel="stylesheet" href="staffchat.css">
    <link rel="stylesheet" href="recyclingstaff.css">
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="logo">Recycle<span>Hub</span></div>
            <nav class="side-nav">
                <a href="recyclingstaff.php">Overview</a>
                <a href="managerequest.php">Manage Requests</a>
                <a href="inventorylog.php">Inventory Log</a>
                <a href="staffscan.php">Scan QR</a>
                <a href="factory.php">Factory Recs</a>
                <a href="staffchat.php" class="active">Staff Chat</a>
                <a href="staffprofile.php">Profile</a>
                <div class="nav-divider"></div>
                <a href="../logout.php" class="logout">Logout</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="dash-header">
                <div>
                    <h2>Internal Communication</h2>
                    <p>Direct message colleagues at your recycling hub.</p>
                </div>
                <div class="user-badge" style="background: var(--primary-dark); color: white; padding: 8px 16px; border-radius: 8px;">
                    Logged in as: <?= htmlspecialchars($current_user['name'] ?? 'Staff') ?>
                </div>
            </header>

            <div class="chat-wrapper activity-section" style="display: flex; height: 72vh; padding: 0; overflow: hidden; background: white; border-radius: 12px;">
                
                <!-- Contacts List -->
                <aside class="chat-list" style="width: 300px; border-right: 1px solid var(--border, #eee); overflow-y: auto;">
                    <div style="padding: 1.2rem; border-bottom: 1px solid var(--border, #eee);">
                        <input type="text" id="searchContacts" placeholder="Search staff..." class="filter-btn" style="width: 100%; text-align: left; padding: 8px;">
                    </div>
                    
                    <div id="contactsContainer">
                        <?php if (empty($contacts)): ?>
                            <p style="padding: 1.5rem; color: #888; font-size: 0.85rem;">No other staff members found.</p>
                        <?php else: ?>
                            <?php foreach ($contacts as $contact): ?>
                                <?php $isActive = ($contact['user_id'] == $active_recipient_id); ?>
                                <a href="staffchat.php?receiver_id=<?= $contact['user_id'] ?>" style="text-decoration: none; color: inherit;">
                                    <div class="contact <?= $isActive ? 'active' : '' ?>" 
                                         style="padding: 1rem 1.5rem; cursor: pointer; border-bottom: 1px solid #f9f9f9; <?= $isActive ? 'background: #e8f5e9; border-left: 4px solid #2d5a27;' : '' ?>">
                                        <h4 style="font-size: 0.95rem; margin: 0 0 4px 0;">
                                            <?= htmlspecialchars($contact['name'] ?? 'Staff Member') ?>
                                        </h4>
                                        <p style="font-size: 0.78rem; color: #666; margin: 0;">
                                            <?= htmlspecialchars($contact['role'] ?? 'Staff Member') ?>
                                        </p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </aside>

                <!-- Active Chat Area -->
                <section class="chat-main" style="flex: 1; display: flex; flex-direction: column;">
                    <?php if ($active_recipient): ?>
                        <div class="chat-main-header" style="padding: 1rem 1.5rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="font-size: 1.1rem; margin: 0;"><?= htmlspecialchars($active_recipient['name'] ?? 'User') ?></h3>
                                <span style="font-size: 0.75rem; color: #666;"><?= htmlspecialchars($active_recipient['role'] ?? 'Staff Member') ?></span>
                            </div>
                        </div>

                        <!-- Messages Window -->
                        <div id="chatMessages" class="chat-messages" style="flex: 1; padding: 1.5rem; overflow-y: auto; background: #fafafa; display: flex; flex-direction: column; gap: 0.8rem;">
                            <!-- Messages loaded dynamically via JS -->
                        </div>

                        <!-- Send Message Input -->
                        <div class="chat-input-area" style="padding: 1.2rem 1.5rem; border-top: 1px solid #eee;">
                            <form id="sendMessageForm" style="display: flex; gap: 1rem;">
                                <input type="hidden" id="receiverId" value="<?= $active_recipient['user_id'] ?>">
                                <input type="text" id="messageInput" placeholder="Type your message..." style="flex: 1; text-align: left; padding: 0.8rem 1.2rem; border: 1px solid #ccc; border-radius: 8px;" required autocomplete="off">
                                <button type="submit" class="btn-primary" style="background: #2d5a27; color: white; border: none; padding: 0 20px; border-radius: 8px; cursor: pointer;">Send</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: #888;">
                            <p>Select a staff member from the list to start messaging.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <!-- Live Messaging Script -->
    <script>
        const activeReceiverId = document.getElementById('receiverId') ? document.getElementById('receiverId').value : null;
        const chatMessages = document.getElementById('chatMessages');

        function loadMessages() {
            if (!activeReceiverId) return;

            fetch(`chat_handler.php?action=fetch&receiver_id=${activeReceiverId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        chatMessages.innerHTML = '';
                        data.messages.forEach(msg => {
                            const isOutgoing = msg.sender_id == '<?= $current_staff_id ?>';
                            const msgDiv = document.createElement('div');
                            msgDiv.className = `msg ${isOutgoing ? 'outgoing' : 'incoming'}`;
                            
                            msgDiv.style.maxWidth = '65%';
                            msgDiv.style.padding = '0.8rem 1rem';
                            msgDiv.style.borderRadius = '12px';
                            msgDiv.style.alignSelf = isOutgoing ? 'flex-end' : 'flex-start';
                            msgDiv.style.background = isOutgoing ? '#2d5a27' : 'white';
                            msgDiv.style.color = isOutgoing ? 'white' : '#333';
                            if(!isOutgoing) msgDiv.style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';

                            msgDiv.innerHTML = `
                                <p style="font-size: 0.9rem; margin-bottom: 3px;">${msg.message}</p>
                                <span style="font-size: 0.68rem; color: ${isOutgoing ? '#aec6ab' : '#888'}; display: block; text-align: right;">
                                    ${msg.formatted_time}
                                </span>
                            `;
                            chatMessages.appendChild(msgDiv);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                });
        }

        const sendForm = document.getElementById('sendMessageForm');
        if (sendForm) {
            sendForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const input = document.getElementById('messageInput');
                const message = input.value.trim();

                if (!message) return;

                const formData = new FormData();
                formData.append('action', 'send');
                formData.append('receiver_id', activeReceiverId);
                formData.append('message', message);

                fetch('chat_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        input.value = '';
                        loadMessages();
                    }
                });
            });
        }

        if (activeReceiverId) {
            loadMessages();
            setInterval(loadMessages, 3000);
        }
    </script>
</body>
</html>