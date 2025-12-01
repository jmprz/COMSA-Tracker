<?php
session_start();
require_once "../functions/config.php"; // DB connection

// Admin check
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// --- SORTING LOGIC ---
$allowed_sort_fields = [
    'id' => 'id',
    'name' => 'name',
    'email' => 'email',
    'student_number' => 'student_number',
    'role' => 'role'
];

// Determine the current sort field and direction from the URL parameters
$sort_field_param = $_GET['sort'] ?? 'id'; // The parameter used in the form/URL
$sort_dir = $_GET['dir'] ?? 'ASC';        // Default direction is 'ASC' (lowest to highest/A-Z)

// Validate and sanitize the inputs for the SQL query
$sort_field = 'id'; // Default DB column
if (array_key_exists($sort_field_param, $allowed_sort_fields)) {
    $sort_field = $allowed_sort_fields[$sort_field_param];
}

if (!in_array(strtoupper($sort_dir), ['ASC', 'DESC'])) {
    $sort_dir = 'ASC';
}
// --- END SORTING LOGIC ---

// 1. DYNAMICALLY FETCH ENUM VALUES FOR THE 'role' FIELD (For Modals)
$enum_query = "
    SELECT COLUMN_TYPE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'role'
";
$enum_result = $conn->query($enum_query);
$role_options = [];

if ($enum_result && $enum_row = $enum_result->fetch_assoc()) {
    $enum_list = $enum_row['COLUMN_TYPE'];
    preg_match("/^enum\(\'(.*)\'\)$/", $enum_list, $matches);
    if (isset($matches[1])) {
        $role_options = explode("','", $matches[1]);
    }
}
// Fallback/Default roles if fetching fails
if (empty($role_options)) {
    $role_options = ['executive', 'representative', 'committee_head', 'committee_member'];
}

// Fetch all users with dynamic sorting
$sql_query = "
    SELECT id, name, email, student_number, role, type
    FROM users
    ORDER BY {$sort_field} {$sort_dir}
";
$result = $conn->query($sql_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | COMSA - TRACKER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../styles.css"> 
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        /* CSS from events.php for consistent layout */
        body {
            padding-top: 0;
            padding-bottom: 20px;
            background-color: #f8f9fa;
        }

        #wrapper {
            display: flex;
            width: 100%;
        }

        #page-content-wrapper {
            flex-grow: 1;
            width: 100%;
            padding-top: 70px;
        }

        .top-header {
            width: 100%;
            /* Assuming sidebar is 250px on desktop */
            padding-left: 250px; 
            z-index: 1000;
        }
        
        @media (max-width: 991.98px) {
            .top-header {
                padding-left: 0;
            }
        }

        .container-fluid {
            max-width: 100%;
        }

        /* Modern Table Styles - Adjusted for User columns */
        .table {
            border-radius: 8px;
            overflow: hidden;
            margin-top: 15px;
            border-collapse: separate;
        }

        .table-light th {
            background-color: #e9ecef;
            color: #495057;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border-bottom: 2px solid #dee2e6;
        }

        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
            transition: background-color 0.2s;
        }

        .badge {
            padding: 0.5em 0.8em;
            font-weight: 600;
        }

        /* Highlight the active menu item */
        .btn-active {
            background-color: #e3f2fd !important; /* A light blue shade for active state */
            color: white !important; /* Blue text color */
        }
        
        .material-table th, .material-table td {
            vertical-align: middle;
        }
        .material-header-bottom th {
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div id="wrapper">
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
                    <a href="events.php"
                        class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
                        style="width:50px; height:50px;">
                        <i class="ri-calendar-schedule-line fs-4"></i>
                    </a>
                    <a href="tasks.php" class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
                        style="width:50px; height:50px;">
                        <i class="ri-list-check-2 fs-4"></i>
                    </a>
                    <a href="users.php" class="btn btn-active rounded-3 d-flex align-items-center justify-content-center"
                        style="width:50px; height:50px;"> <i class="ri-user-3-line fs-4"></i>
                    </a>
                    <a href="settings.php"
                        class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
                        style="width:50px; height:50px;">
                        <i class="ri-settings-line fs-4"></i>
                    </a>
                </div>
            </div>
        </nav>

        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg fixed-top top-header">
                <div class="container-fluid">
                    <button class="btn btn-comsa d-lg-none" id="sidebarToggle" aria-label="Open menu">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </nav>

            <main class="container-md py-5">
                <?php
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
                } elseif (isset($_SESSION['info_message'])) {
                    $alert_type = 'info';
                    $alert_message = $_SESSION['info_message'];
                    unset($_SESSION['info_message']);
                }

                if (!empty($alert_message)) {
                    echo '
                        <div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">
                            ' . $alert_message . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    ';
                }
                ?>
                <div class="row g-4 justify-content-center">
                    <div class="card shadow-md border-0">
                        <div class="card-body">
                            <div
                                class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom material-header-top">
                                <h2 class="fw-bold mb-0 text-dark"><i class="ri-user-settings-line me-2"></i> User Management</h2>
                                <button class="btn btn-comsa fw-bold" data-bs-toggle="modal"
                                    data-bs-target="#addUserModal">
                                    <i class="ri-user-add-line me-1"></i> Add User
                                </button>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-md-5 mb-2">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                                        <input type="text" id="userFilterInput" class="form-control" placeholder="Search by name, email, or student number...">
                                    </div>
                                </div>
                                <div class="col-md-5 mb-2">
                                    <form id="sortForm" class="d-flex" method="GET" action="users.php">
                                        <select name="sort" class="form-select me-2">
                                            <option value="id" <?= $sort_field_param == 'id' ? 'selected' : '' ?>>Sort by ID</option>
                                            <option value="name" <?= $sort_field_param == 'name' ? 'selected' : '' ?>>Sort by Name</option>
                                            <option value="email" <?= $sort_field_param == 'email' ? 'selected' : '' ?>>Sort by Email</option>
                                            <option value="student_number" <?= $sort_field_param == 'student_number' ? 'selected' : '' ?>>Sort by Student No.</option>
                                            <option value="role" <?= $sort_field_param == 'role' ? 'selected' : '' ?>>Sort by Role</option>
                                        </select>
                                        <select name="dir" class="form-select me-2" style="max-width: 100px;">
                                            <option value="ASC" <?= $sort_dir == 'ASC' ? 'selected' : '' ?>>ASC</option>
                                            <option value="DESC" <?= $sort_dir == 'DESC' ? 'selected' : '' ?>>DESC</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary"><i class="ri-sort-asc"></i> Apply</button>
                                    </form>
                                </div>
                                <div class="col-md-2 mb-2 text-md-end">
                                    <a href="users.php" class="btn btn-outline-secondary w-100"><i class="ri-close-line"></i> Reset</a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-borderless table-hover align-middle material-table">
                                    <thead class="material-header-bottom">
                                        <tr>
                                            <th scope="col" style="width: 5%;">ID</th>
                                            <th scope="col" style="width: 25%;">NAME</th>
                                            <th scope="col" style="width: 20%;">EMAIL</th>
                                            <th scope="col" style="width: 15%;">STUDENT NO.</th>
                                            <th scope="col" style="width: 10%;">ROLE</th>
                                            <th scope="col" style="width: 10%;">TYPE</th>
                                            <th scope="col" class="text-center" style="width: 15%;">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody id="userTableBody">
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()):
                                                // Role badge logic (using actual roles for classes)
                                                $role_class = match($row['role']) {
                                                    'executive' => 'text-danger bg-danger-subtle',
                                                    'representative' => 'text-warning bg-warning-subtle',
                                                    'committee_head' => 'text-success bg-success-subtle',
                                                    default => 'text-primary bg-primary-subtle', // committee_member or other
                                                };
                                                // Type badge logic (Assuming 'Regular' or similar is the default type if not 'None')
                                                $type_text = empty($row['type']) ? 'N/A' : $row['type'];
                                                $type_class = empty($row['type']) ? 'text-secondary bg-light-subtle' : 'text-info bg-info-subtle';
                                            ?>
                                                <tr class="align-middle user-row">
                                                    <td><?= $row['id'] ?></td>
                                                    <td class="fw-medium text-dark user-name"><?= $row['name'] ?></td>
                                                    <td class="user-email"><?= $row['email'] ?></td>
                                                    <td class="user-student_number"><?= $row['student_number'] ?></td>
                                                    
                                                    <td>
                                                        <span class="badge rounded-pill fw-medium <?= $role_class ?>">
                                                            <?= ucwords(str_replace('_', ' ', $row['role'])) ?>
                                                        </span>
                                                    </td>
                                                    
                                                    <td>
                                                        <span class="badge rounded-pill fw-medium <?= $type_class ?>">
                                                            <?= $type_text ?>
                                                        </span>
                                                    </td>
                                                    
                                                    <td class="text-center">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button class="btn btn-icon edit-user-btn text-primary"
                                                                data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                                title="Edit" data-id="<?= $row['id'] ?>"
                                                                data-name="<?= $row['name'] ?>"
                                                                data-email="<?= $row['email'] ?>"
                                                                data-studentnumber="<?= $row['student_number'] ?>"
                                                                data-role="<?= $row['role'] ?>"
                                                                data-type="<?= $row['type'] ?>">
                                                                <i class="ri-edit-line"></i>
                                                            </button>
                                                            <button class="btn btn-icon delete-user-btn text-danger"
                                                                data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                                title="Delete" data-id="<?= $row['id'] ?>">
                                                                <i class="ri-delete-bin-line"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">No users found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="../functions/save_user.php">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="addUserModalLabel"><i class="ri-user-add-line me-2"></i> Register New User</h5>
                                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Student Number</label>
                                    <input type="text" name="student_number" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role" required>
                                        <option value="">Select Role</option>
                                        <?php foreach ($role_options as $role): ?>
                                            <option value="<?= $role ?>">
                                                <?= ucwords(str_replace('_', ' ', $role)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Type (e.g., Year/Section, Department, etc.)</label>
                                    <input type="text" name="type" class="form-control" placeholder="Optional">
                                </div>
                                
                                <input type="hidden" name="is_admin_register" value="1">
                                
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-comsa"><i class="ri-user-add-line me-1"></i> Register User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="../functions/update_user.php">
                            <input type="hidden" name="id" id="edit-user-id">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="editUserModalLabel"><i class="ri-edit-line me-2"></i> Edit User Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" id="edit-user-name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit-user-email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Student Number</label>
                                    <input type="text" class="form-control" id="edit-user-student_number" name="student_number" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password (Leave blank to keep old password)</label>
                                    <input type="password" class="form-control" name="password" placeholder="Enter new password">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" id="edit-user-role" name="role" required>
                                        <?php foreach ($role_options as $role): ?>
                                            <option value="<?= $role ?>">
                                                <?= ucwords(str_replace('_', ' ', $role)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Type</label>
                                    <input type="text" class="form-control" id="edit-user-type" name="type" placeholder="Optional">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-comsa"><i class="ri-save-line me-1"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <form method="GET" action="../functions/delete_user.php">
                            <input type="hidden" name="id" id="delete-user-id">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="deleteUserModalLabel"><i class="ri-alert-line me-2"></i> Confirm Delete</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to permanently delete this user? This action cannot be undone.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger"><i class="ri-delete-bin-line me-1"></i> Delete User</button>
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
        // --- MODAL JS (Retained) ---
        // Fill Edit User Modal
        document.querySelectorAll('.edit-user-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit-user-id').value = btn.dataset.id;
                document.getElementById('edit-user-name').value = btn.dataset.name;
                document.getElementById('edit-user-email').value = btn.dataset.email;
                document.getElementById('edit-user-student_number').value = btn.dataset.studentnumber;
                
                // Select the correct option in the Role dropdown using the data-role attribute
                document.getElementById('edit-user-role').value = btn.dataset.role;
                
                // Set the Type field
                // Note: The 'null' check is useful if the column is nullable and returns 'null' as a string
                document.getElementById('edit-user-type').value = btn.dataset.type === 'null' ? '' : btn.dataset.type; 
            });
        });

        // Fill Delete User Modal
        document.querySelectorAll('.delete-user-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('delete-user-id').value = btn.dataset.id;
            });
        });

        // --- FILTER/SEARCH JS (Added) ---
        document.getElementById('userFilterInput').addEventListener('keyup', function() {
            const filterValue = this.value.toLowerCase();
            // Select all rows that contain the class 'user-row'
            const rows = document.querySelectorAll('.user-row');

            rows.forEach(row => {
                // Get the text content from the searchable columns
                const name = row.querySelector('.user-name')?.textContent.toLowerCase() || '';
                const email = row.querySelector('.user-email')?.textContent.toLowerCase() || '';
                const studentNumber = row.querySelector('.user-student_number')?.textContent.toLowerCase() || '';

                // Check if the filter value is present in any of the searchable columns
                if (name.includes(filterValue) || email.includes(filterValue) || studentNumber.includes(filterValue)) {
                    row.style.display = ''; // Show the row
                } else {
                    row.style.display = 'none'; // Hide the row
                }
            });
        });

        // Add JavaScript for the mobile sidebar toggle (if needed)
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            // Your logic to show/hide the sidebar on mobile (if applicable)
        });
    </script>
</body>

</html>