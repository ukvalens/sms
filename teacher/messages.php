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
        $message = 'Message sent successfully!';
    }
    
    // Redirect to prevent duplicate on reload
    header('Location: messages.php?sent=1');
    exit;
}

// Check for success message
if(isset($_GET['sent'])) {
    $message = 'Message sent successfully!';
}

// Get received messages
$db->query("SELECT m.*, u.username as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = :user_id ORDER BY m.sent_at DESC");
$db->bind(':user_id', $_SESSION['user_id']);
$received = $db->resultset();

// Get sent messages
$db->query("SELECT m.*, u.username as receiver_name FROM messages m JOIN users u ON m.receiver_id = u.id WHERE m.sender_id = :user_id ORDER BY m.sent_at DESC");
$db->bind(':user_id', $_SESSION['user_id']);
$sent = $db->resultset();

// Get users for compose
$db->query("SELECT id, username, role FROM users WHERE id != :user_id ORDER BY role, username");
$db->bind(':user_id', $_SESSION['user_id']);
$users = $db->resultset();

$content = '
<div class="page-header">
    <h2>Messages</h2>
    <button class="btn" onclick="showComposeForm()">Compose Message</button>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '

<div id="composeModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Compose Message</h3>
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

<div class="message-tabs">
    <button class="tab-btn active" onclick="showTab(\'received\')">Inbox (' . count($received) . ')</button>
    <button class="tab-btn" onclick="showTab(\'sent\')">Sent (' . count($sent) . ')</button>
</div>

<div id="received" class="tab-content">
    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>From</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

foreach($received as $msg) {
    $content .= '
                <tr>
                    <td>' . $msg['sender_name'] . '</td>
                    <td>' . $msg['subject'] . '</td>
                    <td>' . date('M d, Y H:i', strtotime($msg['sent_at'])) . '</td>
                    <td>' . ($msg['is_read'] ? 'Read' : '<strong>Unread</strong>') . '</td>
                    <td>
                        <button class="btn-small btn-view" onclick="viewMessage(' . $msg['id'] . ')">View</button>
                    </td>
                </tr>';
}

$content .= '
            </tbody>
        </table>
    </div>
</div>

<div id="sent" class="tab-content" style="display:none;">
    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

foreach($sent as $msg) {
    $content .= '
                <tr>
                    <td>' . $msg['receiver_name'] . '</td>
                    <td>' . $msg['subject'] . '</td>
                    <td>' . date('M d, Y H:i', strtotime($msg['sent_at'])) . '</td>
                    <td>' . ($msg['is_read'] ? 'Read' : 'Unread') . '</td>
                </tr>';
}

$content .= '
            </tbody>
        </table>
    </div>
</div>

<!-- Message View Modal -->
<div id="messageModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeMessageModal()">&times;</span>
        <div id="messageContent">
            <!-- Message content will be loaded here -->
        </div>
    </div>
</div>

<script>
function showComposeForm() {
    document.getElementById("composeModal").style.display = "block";
}

function closeModal() {
    document.getElementById("composeModal").style.display = "none";
}

function showTab(tab) {
    document.querySelectorAll(".tab-content").forEach(el => el.style.display = "none");
    document.querySelectorAll(".tab-btn").forEach(el => el.classList.remove("active"));
    
    document.getElementById(tab).style.display = "block";
    event.target.classList.add("active");
}

function viewMessage(id) {
    alert("Loading message " + id);
}

function closeMessageModal() {
    document.getElementById("messageModal").style.display = "none";
}
</script>';

echo renderTeacherLayout('Messages', $content);
?>

<script>
function viewMessage(id) {
    fetch('view_message.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                var content = '<h3>' + data.message.subject + '</h3>' +
                    '<div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #E6F2F1;">' +
                        '<strong>From:</strong> ' + data.message.sender_name + '<br>' +
                        '<strong>Date:</strong> ' + data.message.sent_at +
                    '</div>' +
                    '<div style="line-height: 1.6;">' +
                        data.message.message.replace(/\n/g, '<br>') +
                    '</div>';
                document.getElementById('messageContent').innerHTML = content;
                document.getElementById('messageModal').style.display = 'block';
            } else {
                alert('Error loading message');
            }
        })
        .catch(error => {
            alert('Error loading message');
        });
}
</script>