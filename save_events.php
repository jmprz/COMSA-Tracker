<?php
session_start();
require_once "config.php"; // create this file if you don’t have one yet

// Ensure only logged-in admins can save
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $types = (array) $_POST['type'];
    $titles = (array) $_POST['title'];
    $sas_f6 = (array) ($_POST['sas_f6'] ?? []);
    $transmittal = (array) ($_POST['transmittal'] ?? []);
    $invitation = (array) ($_POST['invitation'] ?? []);
    $endorsement = (array) ($_POST['endorsement'] ?? []);
    $due_dates = (array) $_POST['due_date'];
    $statuses = (array) $_POST['status'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO events (type, title, sas_f6, transmittal, invitation, endorsement, due_date, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        for ($i = 0; $i < count($types); $i++) {
            $sasVal = isset($sas_f6[$i]) ? 1 : 0;
            $transVal = isset($transmittal[$i]) ? 1 : 0;
            $invVal = isset($invitation[$i]) ? 1 : 0;
            $endVal = isset($endorsement[$i]) ? 1 : 0;

            $stmt->bind_param(
                "ssiiiiss",
                $types[$i],
                $titles[$i],
                $sasVal,
                $transVal,
                $invVal,
                $endVal,
                $due_dates[$i],
                $statuses[$i]
            );
            $stmt->execute();
        }

        $conn->commit();
        header("Location: events.php?success=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error saving events: " . $e->getMessage());
    }
}

?>
