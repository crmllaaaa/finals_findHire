<?php
session_start();
require_once 'core/dbConfig.php';

// Check if the user is logged in and has the 'applicant' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'applicant') {
    header("Location: login.php");
    exit();
}

// Retrieve messages where the applicant is the sender or recipient
$stmt = $pdo->prepare("SELECT m.id, m.from_user_id, m.to_user_id, m.message, m.created_at, u.username AS sender_username, u2.username AS recipient_username 
                        FROM messages m
                        JOIN users u ON m.from_user_id = u.id
                        LEFT JOIN users u2 ON m.to_user_id = u2.id
                        WHERE m.from_user_id = ? OR m.to_user_id = ?
                        ORDER BY m.created_at DESC");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$messages = $stmt->fetchAll();

// Handle the reply message form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $messageContent = $_POST['message'];
    $hrUserId = $_POST['hr_user_id'];  // HR user to reply to

    // Insert the reply into the messages table
    $query = "INSERT INTO messages (from_user_id, to_user_id, message, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $hrUserId, $messageContent]);

    // Redirect to the messages page after sending the reply
    header("Location: applicantMsg.php");
    exit();
}

// Handle the new message form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $messageContent = $_POST['message'];
    $hrUserId = $_POST['hr_user_id'];  // HR user to send the message to

    // Insert the new message into the messages table
    $query = "INSERT INTO messages (from_user_id, to_user_id, message, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $hrUserId, $messageContent]);

    // Redirect to the messages page after sending the new message
    header("Location: applicantMsg.php");
    exit();
}

// Fetch all HR users for the dropdown menu
function getHRUsers() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'hr'");
    $stmt->execute();
    return $stmt->fetchAll();
}

$hrUsers = getHRUsers();  // List of HR users
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Messages</title>
    <link rel="stylesheet" href="styles/applicantMsg.css">
</head>
<body>
    <div class="container">
        <h1>Messages</h1>
        <nav>
            <a href="applicantDash.php">Back to Dashboard</a> |
            <a href="core/handleForms.php?logoutAUser=1">Logout</a>
        </nav>

        <h2>Send Message to HR</h2>
        <form method="POST" action="applicantMsg.php">
            <label for="hr_user_id">Select HR User:</label>
            <select name="hr_user_id" id="hr_user_id" required>
                <?php
                // Populate dropdown with HR usernames
                foreach ($hrUsers as $hr) {
                    echo "<option value='" . $hr['id'] . "'>" . htmlspecialchars($hr['username']) . "</option>";
                }
                ?>
            </select>

            <label for="message">Your Message:</label>
            <textarea name="message" id="message" rows="4" required></textarea>

            <button type="submit" name="send_message">Send Message</button>
        </form>

        <h2>Message History</h2>
        <?php if (!empty($messages)): ?>
            <ul class="message-history">
                <?php foreach ($messages as $message): ?>
                    <li class="message-item">
                        <?php
                        // Display message sender/recipient based on the current user
                        if ($message['from_user_id'] == $_SESSION['user_id']) {
                            echo "<strong>To: " . htmlspecialchars($message['recipient_username']) . "</strong>";
                        } else {
                            echo "<strong>From: " . htmlspecialchars($message['sender_username']) . "</strong>";
                        }
                        ?>
                        <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                        <p><small>Sent/Received on: <?php echo htmlspecialchars($message['created_at']); ?></small></p>

                        <!-- Show reply form for received messages -->
                        <?php if ($message['from_user_id'] != $_SESSION['user_id']): ?>
                            <div class="reply-form">
                                <form method="POST" action="applicantMsg.php">
                                    <textarea name="message" rows="4" required placeholder="Your reply..."></textarea>
                                    <input type="hidden" name="hr_user_id" value="<?php echo $message['from_user_id']; ?>">
                                    <button type="submit" name="reply_message">Send Reply</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="no-messages">No messages available.</p>
        <?php endif; ?>
    </div>
</body>
</html>
