<?php
session_start();
require_once "../functions/config.php"; // DB connection
require_once "../functions/send_task_email.php"; // Email function

// Admin check
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// --- FETCHING DATA ---

// Define the allowed filter types
$allowed_types = ['all', 'executives', 'csit', 'creatives', 'sbdg', 'docpub'];
// Check GET parameter for filtering, sanitize, and default to 'all'
$current_filter = isset($_GET['type']) && in_array(strtolower($_GET['type']), $allowed_types) ? strtolower($_GET['type']) : 'all';

// 1. Fetch all users for the "Assigned To" dropdown in Add/Edit Modals
$users_query = "SELECT id, name, email, type FROM users ORDER BY name ASC";
$users_result = $conn->query($users_query);
$users_list = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];


// 2. Fetch all tasks with the assigned user's name and type
$where_clause = "";
if ($current_filter !== 'all') {
    // Construct WHERE clause to filter by the selected user type
    $where_clause = "WHERE u.type = '" . $conn->real_escape_string($current_filter) . "'";
}

$sql_query = "
    SELECT 
        t.id, 
        t.task_name, 
        t.description, 
        t.notes, 
        t.due_date, 
        t.status, 
        t.link,
        t.assigned_to_id,
        u.name AS assigned_to_name,
        u.email AS assigned_to_email,
        u.type AS assigned_to_type /* Fetch the user type */
    FROM tasks t
    JOIN users u ON t.assigned_to_id = u.id
    $where_clause  /* Insert the filtering clause here */
    ORDER BY t.due_date ASC, t.status ASC
";
$tasks_result = $conn->query($sql_query);


// 3. Dynamically Fetch ENUM values for the 'status' field (For Modals)
$enum_query = "
    SELECT COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tasks'
    AND COLUMN_NAME = 'status'
";
$enum_result = $conn->query($enum_query);
$status_options = [];

if ($enum_result && $enum_row = $enum_result->fetch_assoc()) {
    $enum_list = $enum_row['COLUMN_TYPE'];
    preg_match("/^enum\(\'(.*)\'\)$/", $enum_list, $matches);
    if (isset($matches[1])) {
        $status_options = explode("','", $matches[1]);
    }
}
// Fallback/Default status if fetching fails
if (empty($status_options)) {
    $status_options = ['not_started', 'in_progress', 'completed'];
}

// --- ALERT MESSAGES ---
$alert_type = '';
$alert_message = '';
if (isset($_SESSION['success_message'])) {
    $alert_type = 'success';
    $alert_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $alert_type = 'danger';
    $alert_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management | COMSA - TRACKER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <style>
        /* 1. Base State: Hide vertical scroll initially for a clean transition */
        body {
            overflow-y: hidden;
        }

        /* 2. Loading Screen: Fixed, full coverage, high Z-index */
        #loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #ffffff;
            /* Or your preferred background color */
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease-out;
            /* Fade-out transition */
        }

        #loading-screen.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        /* 3. Spinner Styles (Optional, customize colors if needed) */
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #09b003;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* 4. Main Content Transition (Crucial) */
        #page-content-wrapper {
            /* Initial Hidden State */
            opacity: 0;
            transform: translateY(20px);
            /* Starts slightly below the final position */
            /* Transition Properties */
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
        }

        #page-content-wrapper.page-loaded {
            /* Final Visible State */
            opacity: 1;
            transform: translateY(0);
        }

        /* Add your custom styles or use the ones from user.php as a base */
        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-in_progress {
            background-color: #0d6efd;
            color: #fff;
        }

        .badge-completed {
            background-color: #198754;
            color: #fff;
        }

        .material-table th,
        .material-table td {
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div id="loading-screen">
        <div class="loader"></div>
    </div>
    <nav class="navbar navbar-light bg-white shadow-sm fixed-top">
        <div class="container-xxl d-flex align-items-center justify-content-between">
            <a class="navbar-brand fs-2 fw-bold d-flex align-items-center gap-2" href="#">
                <img src="../img/tracker-logo.png" alt="" class="img-fluid" style="height:60px;">
                <span class="d-lg-inline">COMSA-TRACKER</span>
            </a>
            <div class="d-flex align-items-center gap-3 d-none d-lg-flex">
                <a href="admin_dashboard.php"
                    class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
                    style="width:50px; height:50px;">
                    <i class="ri-dashboard-line fs-4"></i>
                </a>
                <a href="events.php" class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
                    style="width:50px; height:50px;">
                    <i class="ri-calendar-schedule-line fs-4"></i>
                </a>
                <a href="tasks.php" class="btn btn-active rounded-3 d-flex align-items-center justify-content-center"
                    style="width:50px; height:50px;">
                    <i class="ri-list-check-2 fs-4"></i>
                </a>
                <a href="users.php" class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
                    style="width:50px; height:50px;"> <i class="ri-user-3-line fs-4"></i>
                </a>
                <a href="../logout.php" class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
                    style="width:50px; height:50px;">
                    <i class="ri-logout-box-r-line fs-4"></i>
                </a>
            </div>
        </div>
    </nav>

    <div id="wrapper">
        <div id="page-content-wrapper">
            <main class="container-md py-5">
                <?php if (!empty($alert_message)): ?>
                    <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show" role="alert">
                        <?= $alert_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4 justify-content-center mt-5">
                    <div class="card shadow-md border-0">
                        <div class="card-body">
                            <div
                                class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom material-header-top">
                                <h2 class="fw-bold mb-0 text-dark">Task Management</h2>
                                <button class="btn btn-comsa fw-bold" data-bs-toggle="modal"
                                    data-bs-target="#addTaskModal">
                                    <i class="ri-add-line me-1"></i> Add Task
                                </button>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <?php
                                $filter_options = [
                                    'all' => 'All Teams',
                                    'executives' => 'Executives',
                                    'csit' => 'CSIT',
                                    'creatives' => 'Creatives',
                                    'sbdg' => 'SBDG',
                                    'docpub' => 'DocPub'
                                ];
                                foreach ($filter_options as $type => $label):
                                    // Highlight the currently selected filter
                                    $active_class = ($current_filter == $type) ? 'btn-comsa' : 'btn-outline-secondary';
                                    ?>
                                    <a href="tasks.php?type=<?= $type ?>" class="btn btn-sm <?= $active_class ?> fw-medium">
                                        <?= $label ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-borderless table-hover align-middle material-table">
                                    <thead class="material-header-bottom">
                                        <tr>
                                            <th scope="col" style="width: 15%;">ASSIGNED TO</th>
                                            <th scope="col" style="width: 20%;">TASK</th>
                                            <th scope="col" style="width: 25%;">DESCRIPTION</th>
                                            <th scope="col" style="width: 15%;">NOTES</th>
                                            <th scope="col" style="width: 10%;">DUE DATE</th>
                                            <th scope="col" style="width: 10%;">STATUS</th>
                                            <th scope="col" style="width: 15%;">LINK</th>
                                            <th scope="col" class="text-center" style="width: 10%;">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($tasks_result && $tasks_result->num_rows > 0): ?>
                                            <?php while ($row = $tasks_result->fetch_assoc()):
                                                // Status badge logic
                                                $status_class = match ($row['status']) {
                                                    'not_started' => 'text-warning bg-warning-subtle',
                                                    'in_progress' => 'text-info bg-info-subtle',
                                                    'completed' => 'text-success bg-success-subtle',
                                                    default => 'text-secondary bg-light-subtle',
                                                };
                                                ?>
                                                <tr class="align-middle task-row"
                                                    data-user-type="<?= htmlspecialchars($row['assigned_to_type']) ?>">
                                                    <td class="fw-medium text-dark assigned-to-name">
                                                        <?= htmlspecialchars($row['assigned_to_name']) ?>
                                                        <br><small
                                                            class="text-muted text-uppercase">(<?= htmlspecialchars($row['assigned_to_type']) ?>)</small>
                                                    </td>
                                                    <td class="task-name"><?= htmlspecialchars($row['task_name']) ?></td>
                                                    <td class="task-description text-truncate" style="max-width: 200px;">
                                                        <?= htmlspecialchars($row['description']) ?>
                                                    </td>
                                                    <td class="task-notes text-truncate" style="max-width: 150px;">
                                                        <?= htmlspecialchars($row['notes']) ?>
                                                    </td>
                                                    <td class="task-due-date"><?= date('M d, Y', strtotime($row['due_date'])) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge rounded-pill fw-medium <?= $status_class ?>">
                                                            <?= ucwords(str_replace('_', ' ', $row['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td class="task-link">
                                                        <?php if (!empty($row['link'])): ?>
                                                            <a href="<?= htmlspecialchars($row['link']) ?>" target="_blank"
                                                                class="btn btn-sm btn-outline-info" title="View Link">
                                                                <i class="ri-external-link-line"></i> View
                                                            </a>
                                                        <?php else: ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>

                                                    <td class="text-center">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button class="btn btn-icon edit-task-btn text-primary"
                                                                data-bs-toggle="modal" data-bs-target="#editTaskModal"
                                                                title="Edit" data-id="<?= $row['id'] ?>"
                                                                data-assigned-id="<?= $row['assigned_to_id'] ?>"
                                                                data-task-name="<?= htmlspecialchars($row['task_name']) ?>"
                                                                data-description="<?= htmlspecialchars($row['description']) ?>"
                                                                data-notes="<?= htmlspecialchars($row['notes']) ?>"
                                                                data-due-date="<?= $row['due_date'] ?>"
                                                                data-status="<?= $row['status'] ?>"
                                                                data-link="<?= htmlspecialchars($row['link']) ?>">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <button class="btn btn-icon delete-task-btn text-danger"
                                                                data-bs-toggle="modal" data-bs-target="#deleteTaskModal"
                                                                title="Delete" data-id="<?= $row['id'] ?>">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">No tasks found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="../functions/save_task.php">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="addTaskModalLabel"><i class="ri-add-line me-2"></i> Create
                                    New Task</h5>
                                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Task Name</label>
                                    <input type="text" name="task_name" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Assigned To</label>
                                    <select class="form-select" name="assigned_to_id" required>
                                        <option value="">Select User</option>
                                        <?php foreach ($users_list as $user): ?>
                                            <option value="<?= $user['id'] ?>"
                                                data-email="<?= htmlspecialchars($user['email']) ?>">
                                                <?= htmlspecialchars($user['name']) ?>
                                                (<?= htmlspecialchars($user['type']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Due Date</label>
                                    <input type="date" name="due_date" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" required>
                                        <?php foreach ($status_options as $status): ?>
                                            <option value="<?= $status ?>">
                                                <?= ucwords(str_replace('_', ' ', $status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Link (e.g., Google Drive, Canva etc.)</label>
                                    <input type="url" name="link" class="form-control" placeholder="Optional URL">
                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-comsa"><i class="ri-check-line me-1"></i>
                                    Save Task</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="../functions/update_task.php">
                            <input type="hidden" name="id" id="edit-task-id">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="editTaskModalLabel"><i class="ri-edit-line me-2"></i> Edit
                                    Task Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Task Name</label>
                                    <input type="text" class="form-control" id="edit-task-name" name="task_name"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Assigned To</label>
                                    <select class="form-select" id="edit-assigned-to-id" name="assigned_to_id" required>
                                        <option value="">Select User</option>
                                        <?php foreach ($users_list as $user): ?>
                                            <option value="<?= $user['id'] ?>"
                                                data-email="<?= htmlspecialchars($user['email']) ?>">
                                                <?= htmlspecialchars($user['name']) ?>
                                                (<?= htmlspecialchars($user['type']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" id="edit-task-description"
                                        rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" id="edit-task-notes"
                                        rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Due Date</label>
                                    <input type="date" name="due_date" class="form-control" id="edit-task-due-date"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" id="edit-task-status" name="status" required>
                                        <?php foreach ($status_options as $status): ?>
                                            <option value="<?= $status ?>">
                                                <?= ucwords(str_replace('_', ' ', $status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Link</label>
                                    <input type="url" name="link" class="form-control" id="edit-task-link"
                                        placeholder="Optional URL">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-comsa"><i class="ri-save-line me-1"></i> Save
                                    Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <form method="POST" action="../functions/delete_task.php">
                            <input type="hidden" name="id" id="delete-task-id">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="deleteTaskModalLabel"><i class="ri-alert-line me-2"></i>
                                    Confirm Delete</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to permanently delete this task? This action cannot be undone.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger"><i class="ri-delete-bin-line me-1"></i>
                                    Delete Task</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const loadingScreen = document.getElementById('loading-screen');
            const pageContent = document.getElementById('page-content-wrapper');
            const body = document.body;

            // This function runs once all external resources (images, CSS) are loaded
            window.onload = function () {
                // Step 1: Add the class to start the page content transition
                pageContent.classList.add('page-loaded');

                // Step 2: Fade out the loading screen
                loadingScreen.classList.add('hidden');

                // Step 3: Re-enable vertical scrolling after the loading screen fades
                setTimeout(() => {
                    body.style.overflowY = 'auto';
                }, 500); // 500ms delay matches the loading screen's opacity transition
            };
        });
    </script>

    <script>
        // Fill Edit Task Modal
        document.querySelectorAll('.edit-task-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit-task-id').value = btn.dataset.id;
                document.getElementById('edit-task-name').value = btn.dataset.taskName;
                document.getElementById('edit-assigned-to-id').value = btn.dataset.assignedId;
                document.getElementById('edit-task-description').value = btn.dataset.description;
                document.getElementById('edit-task-notes').value = btn.dataset.notes;
                document.getElementById('edit-task-due-date').value = btn.dataset.dueDate;
                document.getElementById('edit-task-status').value = btn.dataset.status;
                // Handle link being 'null' or empty string
                document.getElementById('edit-task-link').value = btn.dataset.link === 'null' ? '' : btn.dataset.link;
            });
        });

        // Fill Delete Task Modal
        document.querySelectorAll('.delete-task-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('delete-task-id').value = btn.dataset.id;
            });
        });

        // Add JavaScript for the mobile sidebar toggle (if needed)
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            // Your logic to show/hide the sidebar on mobile (if applicable)
        });
    </script>
</body>

</html>