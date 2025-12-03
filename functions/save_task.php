<?php
// functions/save_task.php
session_start();
require_once "config.php";
require_once "send_task_email.php"; // Include the email function

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $assigned_to_id = filter_input(INPUT_POST, 'assigned_to_id', FILTER_VALIDATE_INT);
    $task_name      = trim($_POST['task_name']);
    $description    = trim($_POST['description']);
    $notes          = trim($_POST['notes']);
    $due_date       = $_POST['due_date'];
    $status         = $_POST['status'];
    $link           = filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL);

    if ($assigned_to_id && !empty($task_name) && !empty($due_date) && !empty($status)) {
        
        // 1. Insert the task into the database
        $stmt = $conn->prepare("INSERT INTO tasks (assigned_to_id, task_name, description, notes, due_date, status, link) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $assigned_to_id, $task_name, $description, $notes, $due_date, $status, $link);
        
        if ($stmt->execute()) {
            
            // 2. Fetch user details for email notification
            $user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $assigned_to_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_row = $user_result->fetch_assoc()) {
                $recipient_name = $user_row['name'];
                $recipient_email = $user_row['email'];

                // 3. Send email notification
                if (sendTaskAssignmentEmail($recipient_email, $recipient_name, $task_name, $due_date, $description)) {
                    $_SESSION['success_message'] = "Task '{$task_name}' assigned to {$recipient_name}. Email notification sent.";
                } else {
                    $_SESSION['info_message'] = "Task '{$task_name}' saved, but the email notification failed to send.";
                }
            } else {
                $_SESSION['success_message'] = "Task '{$task_name}' created successfully (assigned user not found for email).";
            }
            $user_stmt->close();

        } else {
            $_SESSION['error_message'] = "Error creating task: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Invalid or missing task data.";
    }

    $conn->close();
    header("Location: ../admin/tasks.php");
    exit();
} else {
    header("Location: ../admin/tasks.php");
    exit();
}
?>