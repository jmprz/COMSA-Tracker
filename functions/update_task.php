<?php
// functions/update_task.php
session_start();
require_once "config.php";
// Note: We won't re-send the email on every edit, only on creation/assignment change.
// For a complete system, you'd add logic here to check if assigned_to_id changed.

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $id             = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $assigned_to_id = filter_input(INPUT_POST, 'assigned_to_id', FILTER_VALIDATE_INT);
    $task_name      = trim($_POST['task_name']);
    $description    = trim($_POST['description']);
    $notes          = trim($_POST['notes']);
    $due_date       = $_POST['due_date'];
    $status         = $_POST['status'];
    $link           = filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL);

    if ($id && $assigned_to_id && !empty($task_name) && !empty($due_date) && !empty($status)) {
        
        $stmt = $conn->prepare("UPDATE tasks SET assigned_to_id = ?, task_name = ?, description = ?, notes = ?, due_date = ?, status = ?, link = ? WHERE id = ?");
        $stmt->bind_param("issssssi", $assigned_to_id, $task_name, $description, $notes, $due_date, $status, $link, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Task '{$task_name}' updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating task: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Invalid or missing task data for update.";
    }

    $conn->close();
    header("Location: ../admin/tasks.php");
    exit();
} else {
    header("Location: ../admin/tasks.php");
    exit();
}
?>