<?php
// Include required files
require_once 'dbConfig.php';  // PDO connection setup
require_once 'models.php';  // Access model functions like insertNewUser

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle user registration
if (isset($_POST['registerUserBtn'])) {
    // Sanitize input values
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = trim($_POST['role']); // 'applicant' or 'hr'

    if (!empty($username) && !empty($password) && !empty($role)) {
        // Insert new user
        $result = insertNewUser($pdo, $username, $password, $role);
        
        // Set message and redirect
        $_SESSION['message'] = $result['message'];
        header("Location: ../login.php");
        exit();
    } else {
        $_SESSION['message'] = "Please fill in all fields";
        header("Location: ../register.php");
        exit();
    }
}

// Handle job posting
if (isset($_POST['createJobPostBtn'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $created_by = $_SESSION['user_id'];

    if (!empty($title) && !empty($description)) {
        $result = createJobPost($pdo, $title, $description, $created_by);
        $_SESSION['message'] = $result['message'];
        header("Location: ../hrDash.php");
        exit();
    } else {
        $_SESSION['message'] = "Please fill in all fields";
        header("Location: ../hrDash.php");
        exit();
    }
}

// Handle job application
if (isset($_POST['applyJobBtn'])) {
    $user_id = $_SESSION['user_id'];
    $job_id = $_POST['job_post_id'];
    $cover_message = trim($_POST['cover_message']);
    $resume = $_FILES['resume'];

    // Validate resume file
    if ($resume['type'] !== 'application/pdf' || $resume['size'] > 5 * 1024 * 1024) { // 5MB limit
        $_SESSION['message'] = "Invalid resume file. Please upload a PDF under 5MB.";
        header("Location: ../applicantDash.php");
        exit();
    }

    // Check for upload errors
    if ($resume['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "Error uploading the resume.";
        header("Location: ../applicantDash.php");
        exit();
    }

    // Save resume to server
    $upload_dir = '../upload';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);  // Create directory if it doesn't exist
    }

    $resume_path = $upload_dir . basename($resume['name']);
    
    // Move the file to the upload folder
    if (move_uploaded_file($resume['tmp_name'], $resume_path)) {
        // Insert application into database
        $query = "INSERT INTO applications (user_id, job_post_id, cover_message, resume) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id, $job_id, $cover_message, $resume_path]);

        $_SESSION['message'] = "Application submitted successfully!";
    } else {
        $_SESSION['message'] = "There was an error uploading your resume.";
    }

    header("Location: ../applicantDash.php");
    exit();
}

// Handle messaging
if (isset($_POST['sendMessageBtn'])) {
    $from_user_id = $_SESSION['user_id'];
    $to_user_id = $_POST['hr_id'];
    $message = trim($_POST['message']);

    $query = "INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$from_user_id, $to_user_id, $message]);

    $_SESSION['message'] = "Message sent successfully!";
    header("Location: ../applicantDash.php");
    exit();
}

// Handle user login
if (isset($_POST['loginUserBtn'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $user = getUserByUsername($pdo, $username);

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'hr') {
                header("Location: ../hrDash.php");
            } elseif ($user['role'] === 'applicant') {
                header("Location: ../applicantDash.php");
            } else {
                $_SESSION['message'] = "Invalid role.";
                header("Location: ../login.php");
            }
            exit();
        } else {
            $_SESSION['message'] = "Invalid username or password.";
            header("Location: ../login.php");
            exit();
        }
    } else {
        $_SESSION['message'] = "Please fill in all fields.";
        header("Location: ../login.php");
        exit();
    }
}

// Reject application
if (isset($_POST['rejectApplicationBtn'])) {
    $application_id = $_POST['application_id'];
    $status = 'rejected';

    // Update status to rejected
    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $application_id]);

    // Optionally: Send rejection message
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch();

    $message = "Your application has been rejected.";
    $stmt = $pdo->prepare("INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $application['user_id'], $message]);

    // Remove application
    $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);

    $_SESSION['message'] = "Application rejected!";
    header("Location: ../hrDash.php");
    exit();
}

// Accept application
if (isset($_POST['acceptApplicationBtn'])) {
    $application_id = $_POST['application_id'];
    $status = 'accepted';

    // Update status to accepted
    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $application_id]);

    // Optionally: Send acceptance message
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch();

    $message = "Congratulations! Your application has been accepted.";
    $stmt = $pdo->prepare("INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $application['user_id'], $message]);

    $_SESSION['message'] = "Application accepted!";
    header("Location: ../hrDash.php");
    exit();
}

// Handle user logout
if (isset($_GET['logoutAUser'])) {
    session_unset();  // Clear session
    session_destroy(); // Destroy session

    // Redirect to login page
    header("Location: ../login.php");
    exit();
}
?>
