<?php
// add_participant.php
include 'config.php'; 

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Get and sanitize inputs
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $student_number = filter_input(INPUT_POST, 'student_number', FILTER_SANITIZE_STRING);
    $section = filter_input(INPUT_POST, 'section', FILTER_SANITIZE_STRING);

    if (!$event_id || !$name || !$student_number || !$section) {
        $response['message'] = 'Missing required fields.';
    } else {
        // 2. Insert into participants table
        $stmt = $conn->prepare("INSERT INTO participants (event_id, name, student_number, section) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $event_id, $name, $student_number, $section);
        
        if ($stmt->execute()) {
            // Optional: Get the last inserted ID to create a checklist entry
            $participant_id = $conn->insert_id;
            
            // Insert into participant_checklist with all initial values at 0 (assuming this table is needed)
            $stmt_check = $conn->prepare("INSERT INTO participant_checklist (participant_id) VALUES (?)");
            $stmt_check->bind_param("i", $participant_id);
            $stmt_check->execute();
            $stmt_check->close();

            $response['success'] = true;
            $response['message'] = 'Participant added successfully.';
        } else {
            $response['message'] = 'Database insertion failed: ' . $stmt->error;
        }

        $stmt->close();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();

// 3. Return the JSON response
echo json_encode($response);
?>