<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    header("Location: login.php");
    exit();
}
require_once 'core/dbConfig.php';

// Get all applications for HR, including applicant name, job title, resume path, and cover message
$stmt = $pdo->prepare("
    SELECT a.*, u.username AS applicant_name, j.title AS job_title, a.resume, a.cover_message
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN job_posts j ON a.job_post_id = j.id
    WHERE j.created_by = ? AND (a.status IS NULL OR a.status = 'pending')
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll();

if ($applications === null) {
    $applications = []; // Initialize empty array to prevent foreach() warning
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications</title>
    <link rel="stylesheet" href="styles/viewApplications.css">
</head>
<body>

    <div class="container">
        <table>
            <tr>
                <th>Applicant</th>
                <th>Job Title</th>
                <th>Status</th>
                <th>Cover Message</th>
                <th>Resume</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                    <td><?php echo htmlspecialchars($app['status'] ?: 'Pending'); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($app['cover_message'])); ?></td>
                    <td>
                    <?php if (!empty($app['resume']) && file_exists('upload' . basename($app['resume']))): ?>
    <a href="upload<?php echo basename($app['resume']); ?>" target="_blank" class="resume-link">View Resume</a>
<?php else: ?>
    No resume uploaded or file not found
<?php endif; ?>
                    </td>
                    <td>
                        <form action="core/handleForms.php" method="POST">
                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                            <button type="submit" name="rejectApplicationBtn">Reject</button>
                            <button type="submit" name="acceptApplicationBtn">Accept</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="back-link">
            <a href="hrDash.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>