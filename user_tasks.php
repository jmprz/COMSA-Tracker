<?php
session_start();
// The include line is left as is, assuming the path fix was for the relative directory structure.
require_once "./functions/config.php";

// User Check (non-admin access)
if (!isset($_SESSION['student_number'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$type = $_SESSION['type'];

// Define the three main status columns
$status_columns = [
    'not_started' => 'Not Started',
    'in_progress' => 'In Progress',
    'completed' => 'Completed'
];

// Query to fetch tasks for the logged-in user, organized by status
$sql_tasks = "
    SELECT 
        id, 
        task_name, 
        description, 
        notes, 
        DATE_FORMAT(due_date, '%b %d, %Y') AS formatted_due_date, 
        status, 
        link
    FROM tasks
    WHERE assigned_to_id = ?
    ORDER BY due_date ASC
";

$stmt = $conn->prepare($sql_tasks);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks_result = $stmt->get_result();

// Organize tasks into the status columns
$tasks_by_status = [
    'not_started' => [],
    'in_progress' => [],
    'completed' => []
];

if ($tasks_result && $tasks_result->num_rows > 0) {
    while ($task = $tasks_result->fetch_assoc()) {
        if (isset($tasks_by_status[$task['status']])) {
            $tasks_by_status[$task['status']][] = $task;
        }
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | COMSA - TRACKER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">

    <style>
        /* --- MODERN CARD STYLES --- */

        /* Mobile-first styles (default: stacked columns) */
        .task-board {
            display: block;
            padding: 20px 0;
        }

        .status-column {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            min-height: 10px;
            margin-bottom: 20px;
            flex: unset;
            width: 100%;
        }

        /* Desktop/Tablet styles (Kanban board view) */
        @media (min-width: 768px) {
            .task-board {
                display: flex;
                gap: 20px;
                overflow-x: auto;
                padding: 20px 0;
            }

            .status-column {
                flex: 0 0 350px;
                min-height: 500px;
                margin-bottom: 0;
            }
        }

        /* Card Styles */
        .task-card {
            /* Use shadow for lift, remove left border for a cleaner look */
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            /* Softer, larger radius */
            margin-bottom: 18px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
            /* Ensures border-radius applies cleanly */
        }

        .task-card:hover {
            transform: translateY(-3px);
            /* More noticeable lift on hover */
            /* Deeper, more noticeable shadow on hover */
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        /* Status Indicator Bar at the top of the card */
        .task-card::before {
            content: '';
            display: block;
            height: 6px;
            /* A thin indicator line */
            width: 100%;
            /* Default light color */
            background-color: #e9ecef;
        }

        /* Border color mapping to the new indicator line */
        .task-card.not_started::before {
            background-color: #ffc107;
        }

        /* Warning/Yellow */
        .task-card.in_progress::before {
            background-color: #0d6efd;
        }

        /* Primary/Blue */
        .task-card.completed::before {
            background-color: #198754;
        }

        /* Success/Green */

        /* Typography refinements */
        .task-title-modern {
            font-size: 1.2rem;
            font-weight: 700;
            /* Bolder title */
            color: #212529;
            /* Dark color for title in light mode */
        }

        .task-due-date-text {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .task-description-text {
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- RESPONSIVE HEADER STYLES --- */
        /* Custom CSS for Responsive Header Text Size */
        .comsa-header {
            font-size: 1.5rem;
            /* Default size for small screens (e.g., 24px) */
        }

        /* Reduce text size further on extra small screens */
        @media (max-width: 576px) {
            .comsa-header {
                /* Hide the full text and show only 'COMSA' */
                display: none !important;
            }

            .comsa-header-sm {
                /* Show a smaller version of the name */
                font-size: 1.25rem;
                /* Adjust this size as needed */
            }
        }

        /* Desktop size override */
        @media (min-width: 768px) {
            .comsa-header {
                font-size: 2rem;
                /* The original large size */
            }

            .comsa-header-sm {
                display: none !important;
            }
        }

        /* --- DARK MODE OVERRIDES (Aesthetic Charcoal) --- */

        body.dark-mode {
            background-color: #1a1a1a !important;
            color: #ffffff !important;
        }

        .dark-mode .navbar,
        .dark-mode .bg-white {
            background-color: #2c2c2c !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
            border-bottom: 1px solid #444;
        }

        .dark-mode .card,
        .dark-mode .status-column,
        .dark-mode .modal-content {
            background-color: #2c2c2c !important;
            color: #ffffff !important;
            border: 1px solid #444;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .task-card:hover {
            background-color: #3a3a3a !important;
        }

        /* Dark mode typography and links */
        .dark-mode .task-title-modern {
            color: #ffffff !important;
        }

        .dark-mode .task-due-date-text,
        .dark-mode .text-muted,
        .dark-mode .task-description-text {
            color: #b0b0b0 !important;
        }

        .dark-mode a {
            color: #84e184;
            /* Light Green Accent */
        }

        /* Form elements in dark mode */
        .dark-mode .form-control,
        .dark-mode .form-select {
            background-color: #1a1a1a;
            color: #ffffff;
            border-color: #444;
        }

        .dark-mode .form-control:focus,
        .dark-mode .form-select:focus {
            background-color: #2c2c2c;
            border-color: #84e184;
            box-shadow: 0 0 0 0.25rem rgba(132, 225, 132, 0.3);
        }

        .dark-mode .btn-comsa {
            /* Example using a hardcoded lightened green for contrast */
            background-color: #4CAF50 !important;
            color: white !important;
            border-color: #388E3C !important;
        }

        /* Dark Mode Task Status Overrides */
        .dark-mode .task-card.completed::before {
            background-color: #84e184;
        }

        .dark-mode .task-card.not_started::before {
            background-color: #ffb74d;
        }

        .dark-mode .task-card.in_progress::before {
            background-color: #64b5f6;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-light bg-white shadow-sm fixed-top">
        <div class="container-xxl d-flex align-items-center justify-content-between">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <img src="./img/tracker-logo.png" alt="COMSA-TRACKER Logo" class="img-fluid" style="height:60px;">
                <span class="fw-bold d-none d-sm-inline fs-2 fs-md-2 comsa-header">COMSA-TRACKER</span>
                <span class="fw-bold d-inline d-sm-none fs-3 comsa-header-sm">COMSA-TRACKER</span>
            </a>

            <div class="d-flex align-items-center gap-2">
                <span class="navbar-text me-1 fw-medium d-none d-sm-inline">
                   <?= htmlspecialchars($user_name) ?> | <?= htmlspecialchars($type) ?>
                </span>
                <a href="logout.php" class="btn btn-light">
                    <i class="ri-logout-box-r-line fs-5"></i>
                </a>
            </div>
        </div>
    </nav>

    <div id="wrapper">
        <div id="page-content-wrapper">
            <main class="container pt-5 mt-5">
                <span class="navbar-text me-1 fw-medium d-sm-none d-lg-none d-sm-inline">
                     <?= htmlspecialchars($user_name) ?> | <?= htmlspecialchars($type) ?>
                </span>
                <h1 class="mt-4 mb-4 fw-bold text-dark fs-3">My Assigned Tasks</h1>
                <hr>

                <div class="task-board justify-content-center">
                    <?php foreach ($status_columns as $status_key => $status_label): ?>
                        <div class="status-column">
                            <h4 class="fw-bold mb-3 text-uppercase text-dark text-center fs-5"><?= $status_label ?></h4>
                            <div class="task-list">
                                <?php if (!empty($tasks_by_status[$status_key])): ?>
                                    <?php foreach ($tasks_by_status[$status_key] as $task): ?>
                                        <div class="card shadow-sm task-card <?= $status_key ?>" data-bs-toggle="modal"
                                            data-bs-target="#taskUpdateModal" data-id="<?= $task['id'] ?>"
                                            data-status="<?= $task['status'] ?>"
                                            data-task-name="<?= htmlspecialchars($task['task_name']) ?>"
                                            data-due-date="<?= $task['formatted_due_date'] ?>"
                                            data-description="<?= htmlspecialchars($task['description']) ?>"
                                            data-notes="<?= htmlspecialchars($task['notes']) ?>"
                                            data-link="<?= htmlspecialchars($task['link']) ?>">

                                            <div class="card-body p-4">
                                                <h5 class="card-title text-truncate mb-2 text-dark task-title-modern">
                                                    <?= htmlspecialchars($task['task_name']) ?>
                                                </h5>

                                                <div class="d-flex align-items-center gap-1 mb-2">
                                                    <?php $date_icon = ($status_key === 'completed') ? 'ri-checkbox-circle-line text-success' : 'ri-time-line text-muted'; ?>
                                                    <i class="<?= $date_icon ?> fs-6"></i>
                                                    <p class="card-text mb-0 text-muted task-due-date-text">
                                                        Due: <?= $task['formatted_due_date'] ?>
                                                    </p>
                                                </div>

                                                <p class="card-text text-truncate mb-2 text-dark-emphasis task-description-text">
                                                    <?= htmlspecialchars($task['description']) ?>
                                                </p>

                                                <?php if (!empty($task['link'])): ?>
                                                    <a href="<?= htmlspecialchars($task['link']) ?>" target="_blank"
                                                        class="btn btn-sm btn-outline-secondary mt-2">
                                                        <i class="ri-attachment-line me-1"></i> View Link
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-light text-center" role="alert">
                                        No <?= $status_label ?> found.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </main>
        </div>
    </div>

    <div class="modal fade" id="taskUpdateModal" tabindex="-1" aria-labelledby="taskUpdateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="./functions/user_update_task.php">
                    <input type="hidden" name="id" id="update-task-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="taskUpdateModalLabel">Update Task: <span
                                id="modal-task-name"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Due Date:</label>
                            <p id="modal-due-date" class="form-control-static"></p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Description:</label>
                            <p id="modal-description" class="form-control-static"></p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Admin Notes:</label>
                            <p id="modal-notes" class="form-control-static text-muted"></p>
                        </div>

                        <hr>

                        <h6 class="fw-bold">Update Progress</h6>

                        <div class="mb-3">
                            <label for="update-status" class="form-label">Change Status</label>
                            <select class="form-select" id="update-status" name="status" required>
                                <option value="not_started">Not Started</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="update-link" class="form-label">Link/Submission URL</label>
                            <input type="url" class="form-control" id="update-link" name="link"
                                placeholder="Paste your submission link here">
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-comsa"><i class="ri-save-line me-1"></i> Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const updateModal = document.getElementById('taskUpdateModal');
            updateModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                const taskId = button.getAttribute('data-id');
                const taskName = button.getAttribute('data-task-name');
                const dueDate = button.getAttribute('data-due-date');
                const description = button.getAttribute('data-description');
                const notes = button.getAttribute('data-notes');
                const currentStatus = button.getAttribute('data-status');
                const currentLink = button.getAttribute('data-link');

                document.getElementById('update-task-id').value = taskId;
                document.getElementById('modal-task-name').textContent = taskName;
                document.getElementById('modal-due-date').textContent = dueDate;
                document.getElementById('modal-description').textContent = description;

                // Handle notes, providing a default if empty
                const notesElement = document.getElementById('modal-notes');
                notesElement.textContent = (notes && notes !== 'null') ? notes : 'No notes provided by Admin.';

                document.getElementById('update-status').value = currentStatus;

                const linkInput = document.getElementById('update-link');
                linkInput.value = (currentLink && currentLink !== 'null') ? currentLink : '';
            });
        });
    </script>
</body>

</html>