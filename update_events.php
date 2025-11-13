<?php
require_once "config.php";
session_start();

if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $title = $_POST['title'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    $sas_f6 = isset($_POST['sas_f6']) ? 1 : 0;
    $transmittal = isset($_POST['transmittal']) ? 1 : 0;
    $invitation = isset($_POST['invitation']) ? 1 : 0;
    $endorsement = isset($_POST['endorsement']) ? 1 : 0;
    $printed = isset($_POST['printed']) ? 1 : 0;
    $signed = isset($_POST['signed']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE events 
        SET type=?, title=?, sas_f6=?, transmittal=?, invitation=?, endorsement=?, printed=?, signed=?, due_date=?, status=?
        WHERE id=?");
    $stmt->bind_param(
        "ssiiiiiiisi",
        $type, $title, $sas_f6, $transmittal, $invitation, $endorsement,
        $printed, $signed, $due_date, $status, $id
    );

    if ($stmt->execute()) {
        header("Location: events.php?updated=1");
        exit();
    } else {
        echo "Error updating event: " . $stmt->error;
    }
}
?>
