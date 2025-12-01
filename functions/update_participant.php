<?php
// update_participant.php
session_start();
require_once "config.php"; 

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Input Sanitization and Validation
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    // Use FILTER_UNSAFE_RAW for student_number/section if they contain chars that might be removed by FULL_SPECIAL_CHARS
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $student_number = filter_input(INPUT_POST, 'student_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $section = filter_input(INPUT_POST, 'section', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Also, check for empty strings for the non-integer values
    if (!$id || !$event_id || empty($name) || empty($student_number) || empty($section)) {
        // The filter_input for strings might return NULL if missing, or FALSE if filtering failed.
        // It returns the string if successful. Check if integers are invalid/missing OR if strings are empty.
        $response['message'] = 'Missing or invalid required fields.';
    } else {
        $sql = "UPDATE participants SET name = ?, student_number = ?, section = ? WHERE id = ? AND event_id = ?";
        
        $stmt = $conn->prepare($sql);
        
        // 2. FIX: Changed "sssi" to "sssii" to include the event_id integer
        $stmt->bind_param("sssii", $name, $student_number, $section, $id, $event_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Participant updated successfully.';
            } else {
                // This means the update succeeded but no data was changed (e.g., they submitted the same data)
                $response['success'] = true; 
                $response['message'] = 'No changes made or participant not found.';
            }
        } else {
            $response['message'] = 'Database error: ' . $stmt->error;
        }

        $stmt->close();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();
echo json_encode($response);
?>