<?php
// delete_participant.php
session_start();
require_once "config.php"; 

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

    if (!$id || !$event_id) {
        $response['message'] = 'Missing or invalid participant ID or event ID.';
    } else {
        // Start transaction for safety, as deleting a participant may affect related checklist records
        $conn->begin_transaction();
        $success = true;

        try {
            // 1. Delete associated checklist entry (assuming foreign key constraint)
            $sql_checklist = "DELETE FROM participant_checklist WHERE participant_id = ?";
            $stmt_checklist = $conn->prepare($sql_checklist);
            $stmt_checklist->bind_param("i", $id);
            if (!$stmt_checklist->execute()) {
                throw new Exception("Checklist deletion failed: " . $stmt_checklist->error);
            }
            $stmt_checklist->close();

            // 2. Delete the participant
            $sql_participant = "DELETE FROM participants WHERE id = ? AND event_id = ?";
            $stmt_participant = $conn->prepare($sql_participant);
            $stmt_participant->bind_param("ii", $id, $event_id);
            if (!$stmt_participant->execute()) {
                throw new Exception("Participant deletion failed: " . $stmt_participant->error);
            }
            $stmt_participant->close();

            // Commit transaction if both succeeded
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Participant and associated checklist deleted successfully.';

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $response['message'] = 'Deletion failed: ' . $e->getMessage();
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();
echo json_encode($response);
?>