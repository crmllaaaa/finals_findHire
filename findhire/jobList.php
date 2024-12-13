<?php
session_start();
require_once 'core/models.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'applicant') {
    header("Location: login.php");
    exit();
}

// Fetch all job posts (you can filter if needed)
$jobPosts = getAllJobPosts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings</title>
    <link rel="stylesheet" href="styles/jobList.css">
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <a href="applicantDash.php">Dashboard</a>
        <a href="myApplications.php">My Applications</a>
        <a href="applicantMsg.php">Messages</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h1>Available Job Listings</h1>

        <?php
        if (empty($jobPosts)) {
            echo "<p>No job listings available at the moment.</p>";
        } else {
            foreach ($jobPosts as $job) {
                echo "<div class='job-post'>";
                echo "<h2>" . htmlspecialchars($job['title']) . "</h2>";
                echo "<p>" . htmlspecialchars($job['description']) . "</p>";

                echo "<form action='core/handleForms.php' method='POST' enctype='multipart/form-data' class='apply-form'>";
                echo "<input type='hidden' name='job_post_id' value='" . $job['id'] . "'>";
                echo "<textarea name='cover_message' placeholder='Why are you the best candidate for this job?' required></textarea>";
                echo "<input type='file' name='resume' accept='.pdf' required>";
                echo "<button type='submit' name='applyJobBtn'>Apply</button>";
                echo "</form>";
                echo "</div>";
            }
        }
        ?>
    </div>
</body>
</html>