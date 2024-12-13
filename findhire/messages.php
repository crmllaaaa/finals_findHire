<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    header("Location: login.php");
    exit();
}
require_once 'core/dbConfig.php';

// Fetch messages where the HR is either the sender or recipient
$stmt = $pdo->prepare("
    SELECT m.id, m.from_user_id, m.to_user_id, m.message, m.created_at, 
                              u.username AS sender_username, u2.username AS recipient_username 
                        FROM messages m
                        JOIN users u ON m.from_user_id = u.id
                        LEFT JOIN users u2 ON m.to_user_id = u2.id
                        WHERE m.from_user_id = ? OR m.to_user_id = ? 
                        ORDER BY m.created_at DESC");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$messages = $stmt->fetchAll();

// Handle the reply form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $messageContent = $_POST['message'];
    $applicantId = $_POST['applicant_id'];  // Applicant who sent the original message

    // Insert the reply into the messages table
    $query = "INSERT INTO messages (from_user_id, to_user_id, message, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $applicantId, $messageContent]);

    // Redirect back to the messages page after sending the reply
    header("Location: messages.php");
    exit();
}

// Handle the new message form submission (sending message to applicants)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $messageContent = $_POST['message'];
    $applicantId = $_POST['applicant_id'];  // Applicant user to send the message to

    // Insert the new message into the messages table
    $query = "INSERT INTO messages (from_user_id, to_user_id, message, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $applicantId, $messageContent]);

    // Redirect back to the messages page after sending the new message
    header("Location: messages.php");
    exit();
}

// Fetch all applicants to populate the dropdown for sending messages
function getApplicants() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'applicant'");
    $stmt->execute();
    return $stmt->fetchAll();
}

$applicants = getApplicants();  // Get list of applicants
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="styles/messages.css">
</head>
<body>

<header>
    <h1>HR Dashboard</h1>
    <nav>
        <a href="hrDash.php">Dashboard</a> | 
        <a href="applyJob.php">Create Job Post</a> | 
        <a href="viewApplications.php">View Applications</a> | 
        <a href="core/handleForms.php?logoutAUser=1">Logout</a>
    </nav>
</header>

<div class="container">
    <h2>Send Message to Applicant</h2>
    <!-- Form for sending a new message -->
    <form method="POST" action="messages.php">
        <div class="form-group">
            <label for="applicant_id">Select Applicant:</label>
            <select name="applicant_id" id="applicant_id" required>
                <?php
                // Populate dropdown with applicant users
                foreach ($applicants as $applicant) {
                    echo "<option value='" . $applicant['id'] . "'>" . htmlspecialchars($applicant['username']) . "</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="message">Your Message:</label>
            <textarea name="message" id="message" rows="4" required></textarea>
        </div>
        
        <div class="form-group">
            <button type="submit" name="send_message">Send Message</button>
        </div>
    </form>

    <div class="message-list">
        <h2>Message History</h2>
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $message): ?>
                <div class="message-item">
                    <?php
                    // Display sent or received message
                    if ($message['from_user_id'] == $_SESSION['user_id']) {
                        // Sent message
                        echo "<strong>To: " . htmlspecialchars($message['recipient_username']) . "</strong>";
                    } else {
                        // Received message
                        echo "<strong>From: " . htmlspecialchars($message['sender_username']) . "</strong>";
                    }
                    ?>
                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                    <p><small>Sent/Received on: <?php echo htmlspecialchars($message['created_at']); ?></small></p>

                    <!-- Form to reply to the message -->
                    <?php if ($message['from_user_id'] != $_SESSION['user_id']): // Show reply form for received messages ?>
                        <form method="POST" action="messages.php">
                            <textarea name="message" rows="4" required placeholder="Your reply..."></textarea><br>
                            <input type="hidden" name="applicant_id" value="<?php echo $message['from_user_id']; ?>">
                            <button type="submit" name="reply_message" class="reply-btn">Send Reply</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No messages available.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>