// update_participant_checklist.php

<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $participant_id = $_POST['participant_id'];
    
    // Printed Status Checkboxes
    $p_studid = isset($_POST['p_studid']) ? 1 : 0;
    $p_parentid = isset($_POST['p_parentid']) ? 1 : 0;
    $p_waiver = isset($_POST['p_waiver']) ? 1 : 0;
    $p_cor = isset($_POST['p_cor']) ? 1 : 0;

    // Signed Status Checkboxes
    $s_studid = isset($_POST['s_studid']) ? 1 : 0;
    $s_parentid = isset($_POST['s_parentid']) ? 1 : 0;
    $s_waiver = isset($_POST['s_waiver']) ? 1 : 0;
    $s_cor = isset($_POST['s_cor']) ? 1 : 0;

    // Use Prepared Statement for security
    $stmt = $conn->prepare("
        UPDATE participant_checklist 
        SET p_studid=?, p_parentid=?, p_waiver=?, p_cor=?, 
            s_studid=?, s_parentid=?, s_waiver=?, s_cor=? 
        WHERE participant_id=?
    ");

    // Bind 9 parameters (8 integers for status + 1 integer for ID)
    $stmt->bind_param("iiiiiiiii", 
        $p_studid, $p_parentid, $p_waiver, $p_cor, 
        $s_studid, $s_parentid, $s_waiver, $s_cor, 
        $participant_id
    );

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ../admin/events.php"); 
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>