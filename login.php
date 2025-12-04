<?php
include('functions/config.php');
session_start();

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Input Validation
    if (empty($_POST['student_number']) || empty($_POST['password'])) {
        $error_message = "Please enter both student number and password.";
    } else {
        $student_number = htmlspecialchars(trim($_POST['student_number']));
        $password = $_POST['password'];

        // 2. Fetch user data
        $stmt = $conn->prepare("SELECT id, student_number, name, email, role, type, is_admin, password FROM users WHERE student_number = ?");

        if ($stmt === false) {
            // Database preparation error (rare, but good to handle)
            $error_message = "A system error occurred. Please try again later. (SQL Prepare)";
        } else {
            $stmt->bind_param("s", $student_number);

            if ($stmt->execute()) {
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();

                    // 3. Password Verification
                    if (password_verify($password, $user['password'])) {
                        // Success: Start session and redirect
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['student_number'] = $user['student_number'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['type'] = $user['type'];
                        $_SESSION['is_admin'] = $user['is_admin'];
                        $_SESSION['initiated'] = true;

                        if ($user['is_admin']) {
                            header("Location: admin/admin_dashboard.php");
                        } else {
                            header("Location: user_tasks.php");
                        }
                        exit();
                    }
                }

                // 4. Failed Login (Handle incorrect credentials securely)
                $error_message = "Invalid student number or password.";

            } else {
                // Database execution error
                $error_message = "A system error occurred. Please try again later. (SQL Execute)";
            }
            $stmt->close();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | COMSA - TRACKER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<style>
    /* 1. Base State: Hide vertical scroll initially for a clean transition */
    body {
        overflow-y: hidden;
    }

    /* 2. Loading Screen: Fixed, full coverage, high Z-index */
    #loading-screen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #ffffff;
        /* Or your preferred background color */
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: opacity 0.5s ease-out;
        /* Fade-out transition */
    }

    #loading-screen.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    /* 3. Spinner Styles (Optional, customize colors if needed) */
    .loader {
        border: 5px solid #f3f3f3;
        border-top: 5px solid #09b003;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* 4. Main Content Transition (Crucial) */
    #page-content-wrapper {
        /* Initial Hidden State */
        opacity: 0;
        transform: translateY(20px);
        /* Starts slightly below the final position */
        /* Transition Properties */
        transition: opacity 0.8s ease-out, transform 0.8s ease-out;
    }

    #page-content-wrapper.page-loaded {
        /* Final Visible State */
        opacity: 1;
        transform: translateY(0);
    }
</style>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div id="loading-screen">
        <div class="loader"></div>
    </div>
    <div id="page-content-wrapper">
        <div class="card shadow-lg border-0 p-4" style="width: 100%; max-width: 400px; border-radius: 16px;">
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="img/tracker-logo2.png" alt="COMSA Logo" style="width: 200px;">
                </div>

                <?php
                // Display error message with enhanced layout
                if (!empty($error_message)) {
                    echo '
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="ri-alert-fill me-2 fs-5"></i> 
                    <div>
                        ' . htmlspecialchars($error_message) . '
                    </div>
                </div>
                ';
                }
                ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="student_number" class="form-label">Student Number</label>
                        <input type="text" class="form-control" id="student_number" name="student_number" required
                            placeholder="Enter your student number">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required
                            placeholder="Enter your password">
                    </div>

                    <button type="submit" class="btn btn-comsa w-100 py-2">Login</button>
                </form>

                <div class="text-center mt-3">
                    <p class="mb-0">Forgot your password? <a href="forgot_password.php"
                            class="text-decoration-none comsa-text">Click Here</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const loadingScreen = document.getElementById('loading-screen');
            const pageContent = document.getElementById('page-content-wrapper');
            const body = document.body;

            // This function runs once all external resources (images, CSS) are loaded
            window.onload = function () {
                // Step 1: Add the class to start the page content transition
                pageContent.classList.add('page-loaded');

                // Step 2: Fade out the loading screen
                loadingScreen.classList.add('hidden');

                // Step 3: Re-enable vertical scrolling after the loading screen fades
                setTimeout(() => {
                    body.style.overflowY = 'auto';
                }, 500); // 500ms delay matches the loading screen's opacity transition
            };
        });
    </script>

</body>

</html>