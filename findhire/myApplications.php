<?php
session_start();
require_once 'core/models.php';

// Check if the user is logged in and has the 'applicant' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'applicant') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the applicant's applications
$applications = getApplicationsByApplicant($user_id); // Function to be defined in models.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>
    <link rel="stylesheet" href="styles/myApplications.css">
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <a href="jobList.php">Job Listings</a>
        <a href="applicantDash.php">Dashboard</a>
        <a href="core/handleForms.php?logoutAUser=1">Logout</a>
    </div>

    <div class="container">
        <h1>My Applications</h1>

        <h2>Your Job Applications</h2>
        <?php
        if (!empty($applications)) {
            echo "<ul class='applications-list'>";
            foreach ($applications as $application) {
                echo "<li class='application-item'>";
                echo "<h3>" . htmlspecialchars($application['title']) . "</h3>";
                echo "<p><strong>Status:</strong> " . htmlspecialchars($application['status']) . "</p>";
                echo "<p><strong>Cover Message:</strong> " . htmlspecialchars($application['cover_message']) . "</p>";
                echo "<p><strong>Resume:</strong> <a href='" . htmlspecialchars($application['resume']) . "' target='_blank'>View Resume</a></p>";
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='no-applications'>You haven't applied to any jobs yet.</p>";
        }
        ?>
    </div>
</body>
</html>