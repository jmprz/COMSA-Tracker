<?php
include 'config.php'; 

// Set content type to JSON
header('Content-Type: application/json'); 

if (!isset($_GET['event_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: No event ID provided.']);
    exit;
}

$event_id = $_GET['event_id'];
$participants = []; // Initialize the array to hold data

$sql = "
    SELECT p.id, p.name, p.student_number, p.section,
           pc.p_studid, pc.p_parentid, pc.p_waiver, pc.p_cor,
           pc.s_studid, pc.s_parentid, pc.s_waiver, pc.s_cor,
           (pc.p_studid + pc.p_parentid + pc.p_waiver + pc.p_cor + 
            pc.s_studid + pc.s_parentid + pc.s_waiver + pc.s_cor) AS completed_count
    FROM participants p
    LEFT JOIN participant_checklist pc ON p.id = pc.participant_id
    WHERE p.event_id = ?
    ORDER BY p.name ASC
";

$stmt = $conn->prepare($sql); 

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Query Setup Failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $event_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    
    while ($p = $result->fetch_assoc()) {
        // Add the participant data to the array
        $participants[] = $p;
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch data: ' . $stmt->error]);
    exit;
}

$stmt->close();
$conn->close();

// Output the final JSON data
echo json_encode($participants);
?>