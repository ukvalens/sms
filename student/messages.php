<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$message = '';

// Handle message sending
if($_POST && isset($_POST['action']) && $_POST['action'] == 'send') {
    $db->query("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (:sender_id, :receiver_id, :subject, :message)");
    $db->bind(':sender_id', $_SESSION['user_id']);
    $db->bind(':receiver_id', $_POST['receiver_id']);
    $db->bind(':subject', $_POST['subject']);
    $db->bind(':message', $_POST['message']);
    
    if($db->execute()) {
        header('Location: messages.php?sent=1');
        exit;
    }
}

if(isset($_GET['sent'])) {
    $message = 'Message sent successfully!';
}

// Get received messages
$db->query("SELECT m.*, u.username as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = :user_id ORDER BY m.sent_at DESC");
$db->bind(':user_id', $_SESSION['user_id']);
$received = $db->resultset();

// Get teachers and admin for compose
$db->query("SELECT id, username, role FROM users WHERE role IN ('teacher', 'admin') ORDER BY role, username");
$users = $db->resultset();

$content = '
<div class="page-header">
    <h2>Messages</h2>
    <button class="btn" onclick="showComposeForm()">Send Message</button>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '

<div id="composeModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Send Message</h3>
        <form method="POST">
            <input type="hidden" name="action" value="send">
            <div class="form-group">
                <label>To:</label>
                <select name="receiver_id" required class="form-control">';

foreach($users as $user) {
    $content .= '<option value="' . $user['id'] . '">' . $user['username'] . ' (' . ucfirst($user['role']) . ')</option>';
}

$content .= '</select>
            </div>
            <div class="form-group">
                <label>Subject:</label>
                <input type="text" name="subject" required class="form-control">
            </div>
            <div class="form-group">
                <label>Message:</label>
                <textarea name="message" required class="form-control" rows="5"></textarea>
            </div>
            <button type="submit" class="btn">Send Message</button>
        </form>
    </div>
</div>

<div class="messages-container">
    <h3>Inbox (' . count($received) . ' messages)</h3>
    
    <div class="messages-list">';

if(empty($received)) {
    $content .= '<div class="no-messages">No messages received yet.</div>';
} else {
    foreach($received as $msg) {
        $readClass = $msg['is_read'] ? 'read' : 'unread';
        $content .= '
        <div class="message-item ' . $readClass . '" onclick="viewMessage(' . $msg['id'] . ')">
            <div class="message-header">
                <span class="sender">' . $msg['sender_name'] . '</span>
                <span class="date">' . date('M d, Y H:i', strtotime($msg['sent_at'])) . '</span>
            </div>
            <div class="message-subject">' . $msg['subject'] . '</div>
            <div class="message-preview">' . substr($msg['message'], 0, 100) . '...</div>
        </div>';
    }
}

$content .= '
    </div>
</div>

<script>
function showComposeForm() {
    document.getElementById("composeModal").style.display = "block";
}

function closeModal() {
    document.getElementById("composeModal").style.display = "none";
}

function viewMessage(id) {
    alert("Message view will be implemented for ID: " + id);
}
</script>';

echo renderStudentLayout('Messages', $content);
?>