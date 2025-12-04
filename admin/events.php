// events.php

<?php
session_start();
require_once "../functions/config.php"; // DB connection

// Admin check
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Fetch events with printed/signed
$result = $conn->query("
    SELECT e.*,
           ep.sas_f6 AS p_sas, ep.transmittal AS p_trans, ep.invitation AS p_inv, ep.endorsement AS p_end,
           es.sas_f6 AS s_sas, es.transmittal AS s_trans, es.invitation AS s_inv, es.endorsement AS s_end
    FROM events e
    LEFT JOIN events_printed ep ON e.id = ep.event_id
    LEFT JOIN events_signed es ON e.id = es.event_id
    ORDER BY e.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | COMSA - TRACKER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../styles.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
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
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }

        #page-content-wrapper.page-loaded {
            /* Final Visible State */
            opacity: 1;
            transform: translateY(0);
        }
        

        /* 1. Remove unnecessary top padding and set background */

        /* 2. Main wrapper uses Flexbox for side-by-side layout */
        #wrapper {
            display: flex;
            width: 100%;
        }

        /* 4. Content Area Styles */
        #page-content-wrapper {
            flex-grow: 1;
            width: 100%;
            /* Offset content to make space for the desktop sidebar */
            padding-top: 70px;
            /* Padding for the fixed top header */
        }

       body.modal-open #page-content-wrapper {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
        }
        
        .top-header {
            width: 100%;
            padding-left: 250px;
            z-index: 1000;
        }

        /* Adjust for overall responsiveness */
        .container-fluid {
            max-width: 100%;
            /* Use full width in the dashboard layout */
        }


        /* Modern Table Styles */
        .table {
            border-radius: 8px;
            /* Slight rounding for the whole table container */
            overflow: hidden;
            /* Ensures rounded corners apply correctly */
            margin-top: 15px;
            border-collapse: separate;
            /* Required for border-spacing if needed */
        }

        /* Header styling */
        .table-light th {
            background-color: #e9ecef;
            /* Light grey header background */
            color: #495057;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border-bottom: 2px solid #dee2e6;
        }

        /* Row hover effect for better interactivity */
        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
            transition: background-color 0.2s;
        }

        /* Badge styling for status */
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 600;
        }

        /* Custom button width adjustment for columns */
        .table-hover td:nth-child(5),
        /* Printed column */
        .table-hover td:nth-child(6) {
            /* Signed column */
            width: 120px;
            /* Give the buttons fixed space */
        }

        /* New column width for Participants */
        .table-hover td:nth-child(7) {
            width: 130px;
        }
    </style>
</head>

<body>
    <div id="loading-screen">
        <div class="loader"></div>
    </div>
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
                        class="btn btn-active rounded-3 d-flex align-items-center justify-content-center"
                        style="width:50px; height:50px;">
                        <i class="ri-calendar-schedule-line fs-4"></i>
                    </a>

                    <a href="tasks.php" class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
                        style="width:50px; height:50px;">
                        <i class="ri-list-check-2 fs-4"></i>
                    </a>

                    <a href="users.php" class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
                        style="width:50px; height:50px;">
                        <i class="ri-user-3-line fs-4"></i>
                    </a>

                    <a href="../logout.php"
                        class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
                        style="width:50px; height:50px;">
                        <i class="ri-logout-box-r-line fs-4"></i>
                    </a>

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
                <div class="row g-4 justify-content-center">
                    <div class="card shadow-md border-0">
                        <div class="card-body">
                            <div
                                class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom material-header-top">
                                <h2 class="fw-bold mb-0 text-dark">Events</h2>
                                <button class="btn btn-comsa fw-bold" data-bs-toggle="modal"
                                    data-bs-target="#addEventModal">
                                    <i class="ri-add-line me-1"></i> Add Event
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-borderless table-hover align-middle material-table">
                                    <thead class="material-header-bottom">
                                        <tr>
                                            <th scope="col" style="width: 10%;">TYPE</th>
                                            <th scope="col">TITLE</th>
                                            <th scope="col" style="width: 12%;">DUE DATE</th>
                                            <th scope="col" style="width: 12%;">STATUS</th>
                                            <th scope="col" class="text-center" style="width: 10%;">PRINTED</th>
                                            <th scope="col" class="text-center" style="width: 10%;">SIGNED</th>
                                            <th scope="col" class="text-center" style="width: 12%;">PARTICIPANTS</th>
                                            <th scope="col" class="text-center" style="width: 10%;">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()):
                                            // ... Calculation Logic remains the same ...
                                            $printed_total = $row['p_sas'] + $row['p_trans'] + $row['p_inv'] + $row['p_end'];
                                            $signed_total = $row['s_sas'] + $row['s_trans'] + $row['s_inv'] + $row['s_end'];

                                            // Status Badge Logic (Green Shades for Monochromatic look)
                                            $status_class = match ($row['status']) {
                                                'Pending' => 'text-warning bg-warning-subtle',   // Light Accent Green/Yellow
                                                'Ongoing' => 'text-info bg-info-subtle',         // Dark Green (Info)
                                                'Completed' => 'text-success bg-success-subtle', // Bright Green (Success)
                                                default => 'text-secondary bg-light-subtle',
                                            };

                                            // Printed & Signed Button Logic (Dark Green for incomplete, Bright Green for complete)
                                            $printed_btn_class = $printed_total == 4 ? 'text-success' : 'text-info';
                                            $signed_btn_class = $signed_total == 4 ? 'text-success' : 'text-info';
                                            ?>
                                            <tr class="align-middle">
                                                <td><span
                                                        class="badge rounded-pill text-secondary bg-light-subtle border border-secondary-subtle"><?= $row['type'] ?></span>
                                                </td>

                                                <td class="fw-medium text-dark"><?= $row['title'] ?></td>

                                                <td><?= date('M j, Y', strtotime($row['due_date'])) ?></td>

                                                <td><span
                                                        class="badge rounded-pill fw-medium <?= $status_class ?>"><?= $row['status'] ?></span>
                                                </td>

                                                <td class="text-center">
                                                    <button
                                                        class="btn btn-sm btn-link <?= $printed_btn_class ?> printed-btn text-decoration-none"
                                                        data-bs-toggle="modal" data-bs-target="#printedModal"
                                                        data-id="<?= $row['id'] ?>" data-sas="<?= $row['p_sas'] ?>"
                                                        data-trans="<?= $row['p_trans'] ?>" data-inv="<?= $row['p_inv'] ?>"
                                                        data-end="<?= $row['p_end'] ?>" data-type="<?= $row['type'] ?>">
                                                        <?= $printed_total ?>/4
                                                    </button>
                                                </td>

                                                <td class="text-center">
                                                    <button
                                                        class="btn btn-sm btn-link <?= $signed_btn_class ?> signed-btn text-decoration-none"
                                                        data-bs-toggle="modal" data-bs-target="#signedModal"
                                                        data-id="<?= $row['id'] ?>" data-sas="<?= $row['s_sas'] ?>"
                                                        data-trans="<?= $row['s_trans'] ?>" data-inv="<?= $row['s_inv'] ?>"
                                                        data-end="<?= $row['s_end'] ?>" data-type="<?= $row['type'] ?>">
                                                        <?= $signed_total ?>/4
                                                    </button>
                                                </td>

                                                <td class="text-center">
                                                    <button
                                                        class="btn btn-sm btn-outline-success rounded-pill participants-btn text-decoration-none"
                                                        data-bs-toggle="modal" data-bs-target="#participantsModal"
                                                        data-id="<?= $row['id'] ?>" data-title="<?= $row['title'] ?>">
                                                        <i class="ri-team-line me-1"></i> Manage
                                                    </button>
                                                </td>


                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button class="btn btn-icon edit-btn text-primary"
                                                            data-bs-toggle="modal" data-bs-target="#editEventModal"
                                                            title="Edit" data-id="<?= $row['id'] ?>"
                                                            data-type="<?= $row['type'] ?>"
                                                            data-title="<?= $row['title'] ?>"
                                                            data-due="<?= $row['due_date'] ?>"
                                                            data-status="<?= $row['status'] ?>">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                        <button class="btn btn-icon delete-btn text-danger"
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                            title="Delete" data-id="<?= $row['id'] ?>">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="../functions/save_events.php">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="addEventModalLabel"><i class="ri-add-line me-2"></i> Add New
                                    Event</h5>
                                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="type" required>
                                        <option value="Off-Campus">Off-Campus</option>
                                        <option value="On-Campus">On-Campus</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Due Date</label>
                                    <input type="date" name="due_date" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" required>
                                        <option value="Pending">Pending</option>
                                        <option value="Ongoing">Ongoing</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-comsa"><i class="ri-save-line me-1"></i> Save
                                    Event</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="../functions/update_events.php">
                            <input type="hidden" name="id" id="edit-id">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="editEventModalLabel"><i class="ri-edit-line me-2"></i> Edit
                                    Event Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3"><label class="form-label">Type</label><select class="form-select"
                                        id="edit-type" name="type" required>
                                        <option value="Off-Campus">Off-Campus</option>
                                        <option value="On-Campus">On-Campus</option>
                                    </select></div>
                                <div class="mb-3"><label class="form-label">Title</label><input type="text"
                                        class="form-control" id="edit-title" name="title" required></div>
                                <div class="mb-3"><label class="form-label">Due Date</label><input type="date"
                                        class="form-control" id="edit-due" name="due_date" required></div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" id="edit-status" name="status" required>
                                        <option value="Pending">Pending</option>
                                        <option value="Ongoing">Ongoing</option>
                                        <option value="Completed">Completed</option>
                                    </select>
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

            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <form method="GET" action="../functions/delete_events.php">
                            <input type="hidden" name="id" id="delete-id">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="deleteModalLabel"><i class="ri-alert-line me-2"></i> Confirm
                                    Delete</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to permanently delete this event? This action cannot be undone.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger"><i class="ri-delete-bin-line me-1"></i>
                                    Delete Event</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="printedModal" tabindex="-1" aria-labelledby="printedModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <form method="POST" action="../functions/update_printed.php">
                            <input type="hidden" name="id" id="printed-id">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="printedModalLabel"><i class="ri-printer-line me-2"></i>
                                    Printed Checklist</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small mb-3">Mark the documents that have been printed.</p>

                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="sas_f6" id="printed-sas"
                                            role="switch">
                                        <label class="form-check-label" for="printed-sas">SAS F6</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="transmittal"
                                            id="printed-trans" role="switch">
                                        <label class="form-check-label" for="printed-trans">Transmittal</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="invitation"
                                            id="printed-inv" role="switch">
                                        <label class="form-check-label" for="printed-inv">Invitation</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="endorsement"
                                            id="printed-end" role="switch">
                                        <label class="form-check-label" for="printed-end">Endorsement</label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-comsa"><i class="ri-save-line me-1"></i>
                                    Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="signedModal" tabindex="-1" aria-labelledby="signedModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <form method="POST" action="../functions/update_signed.php">
                            <input type="hidden" name="id" id="signed-id">
                            <div class="modal-header text-dark">
                                <h5 class="modal-title" id="signedModalLabel"><i class="ri-mark-pen-line me-2"></i>
                                    Signed Checklist</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small mb-3">Mark the documents that have been signed.</p>

                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="sas_f6" id="signed-sas"
                                            role="switch">
                                        <label class="form-check-label" for="signed-sas">SAS F6</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="transmittal"
                                            id="signed-trans" role="switch">
                                        <label class="form-check-label" for="signed-trans">Transmittal</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="invitation"
                                            id="signed-inv" role="switch">
                                        <label class="form-check-label" for="signed-inv">Invitation</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="endorsement"
                                            id="signed-end" role="switch">
                                        <label class="form-check-label" for="signed-end">Endorsement</label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-comsa"><i class="ri-save-line me-1"></i>
                                    Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="participantsModal" tabindex="-1" aria-labelledby="participantsModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="participantsModalLabel">
                                <i class="ri-team-line me-2"></i> Manage Participants for: <span
                                    id="event-title-display" class="fw-bold"></span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="participants-event-id">

                            <h6 class="border-bottom pb-1 mb-3">Add New Participant</h6>
                            <form id="addParticipantForm" class="row g-3" method="POST"
                                action="../functions/add_participant.php">
                                <input type="hidden" name="event_id" id="add-participant-event-id">
                                <div class="col-md-4">
                                    <input type="text" name="name" class="form-control"
                                        placeholder="Surname, First Name M.I." required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="student_number" class="form-control"
                                        placeholder="Student Number" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="section" class="form-control" placeholder="Year & Section"
                                        required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success w-100">Add</button>
                                </div>
                            </form>

                            <hr class="mt-4 mb-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%;">Name</th>
                                            <th style="width: 20%;">Student ID</th>
                                            <th style="width: 15%;">Year & Section</th>
                                            <th style="width: 25%;" class="text-center">Progress</th>
                                            <th style="width: 10%;" class="text-center">Checklist</th>
                                            <th style="width: 20%;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="participants-table-body">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
         <div class="modal fade" id="participantChecklistModal" tabindex="-1"
                aria-labelledby="participantChecklistModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <form method="POST" action="../functions/update_participant_checklist.php">
                            <input type="hidden" name="participant_id" id="checklist-p-id">
                            <div class="modal-header">
                                <h5 class="modal-title" id="participantChecklistModalLabel">
                                    Requirements for: <span id="checklist-p-name" class="fw-bold"></span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">

                                <h6 class="border-bottom pb-1"><i class="ri-printer-line me-1"></i>
                                    Check if Printed</h6>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="p_studid" id="p-studid"
                                            role="switch">
                                        <label class="form-check-label" for="p-studid">Student ID</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="p_parentid"
                                            id="p-parentid" role="switch">
                                        <label class="form-check-label" for="p-parentid">Parent ID</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="p_waiver" id="p-waiver"
                                            role="switch">
                                        <label class="form-check-label" for="p-waiver">Waiver</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="p_cor" id="p-cor"
                                            role="switch">
                                        <label class="form-check-label" for="p-cor">COR (Cert. of
                                            Reg.)</label>
                                    </div>
                                </div>

                                <h6 class="border-bottom pb-1 mt-4"><i class="ri-check-double-line me-1"></i>
                                    Check if Signed</h6>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="s_studid" id="s-studid"
                                            role="switch">
                                        <label class="form-check-label" for="s-studid">Student ID</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="s_parentid"
                                            id="s-parentid" role="switch">
                                        <label class="form-check-label" for="s-parentid">Parent ID</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="s_waiver" id="s-waiver"
                                            role="switch">
                                        <label class="form-check-label" for="s-waiver">Waiver</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="s_cor" id="s-cor"
                                            role="switch">
                                        <label class="form-check-label" for="s-cor">COR (Cert. of
                                            Reg.)</label>
                                    </div>
                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-comsa"><i class="ri-save-line me-1"></i> Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <div class="modal fade" id="editParticipantModal" tabindex="-1" aria-labelledby="editParticipantModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="editParticipantForm" method="POST" action="../functions/update_participant.php">
                        <input type="hidden" name="id" id="edit-participant-id">
                        <input type="hidden" name="event_id" id="edit-participant-event-id-hidden">
                        <div class="modal-header text-dark">
                            <h5 class="modal-title" id="editParticipantModalLabel"><i class="ri-edit-line me-2"></i>
                                Edit
                                Participant</h5>
                            <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" id="edit-participant-name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Student Number</label>
                                <input type="text" name="student_number" class="form-control"
                                    id="edit-participant-student_number" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Year & Section</label>
                                <input type="text" name="section" class="form-control" id="edit-participant-section"
                                    required>
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

        <div class="modal fade" id="deleteParticipantModal" tabindex="-1" aria-labelledby="deleteParticipantModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <form id="deleteParticipantForm" method="POST" action="../functions/delete_participant.php">
                        <input type="hidden" name="id" id="delete-participant-id">
                        <input type="hidden" name="event_id" id="delete-participant-event-id-hidden">
                        <div class="modal-header text-dark">
                            <h5 class="modal-title" id="deleteParticipantModalLabel"><i class="ri-alert-line me-2"></i>
                                Confirm
                                Delete</h5>
                            <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to permanently delete participant <strong
                                    id="delete-participant-name-display"></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger"><i class="ri-delete-bin-line me-1"></i>
                                Delete</button>
                        </div>
                    </form>
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
        window.addEventListener("load", () => {
            document.getElementById("page-content-wrapper").classList.add("page-loaded");
            document.body.style.overflowY = "auto";
        });

    </script>
    <script>
        // --- EXISTING MODAL FILLERS (No Change) ---

        /**
         * Function to dynamically change the "Invitation" label to "Request" 
         * for On-Campus events in the Printed and Signed Modals.
         * @param {string} eventType - The type of the event (e.g., 'On-Campus', 'Off-Campus').
         */
        function updateInvitationLabels(eventType) {
            // 1. Determine the new label text
            let newLabel = "Invitation";
            if (eventType === 'On-Campus') {
                newLabel = "Request";
            }

            // 2. Update the Printed Modal label
            const printedLabel = document.querySelector('#printedModal label[for="printed-inv"]');
            if (printedLabel) {
                printedLabel.textContent = newLabel;
            }

            // 3. Update the Signed Modal label
            const signedLabel = document.querySelector('#signedModal label[for="signed-inv"]');
            if (signedLabel) {
                signedLabel.textContent = newLabel;
            }
        }


        // Fill Delete Modal
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('delete-id').value = btn.dataset.id;
            });
        });

        // Fill Edit Modal
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit-id').value = btn.dataset.id;
                document.getElementById('edit-type').value = btn.dataset.type;
                document.getElementById('edit-title').value = btn.dataset.title;
                document.getElementById('edit-due').value = btn.dataset.due;
                document.getElementById('edit-status').value = btn.dataset.status;
            });
        });

        // Fill Printed Modal (MODIFIED)
        document.querySelectorAll('.printed-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // 1. Get Event Type from the button
                const eventType = btn.dataset.type;

                // 2. Call the function to update the label based on the type
                updateInvitationLabels(eventType); // <--- NEW STEP

                // 3. Fill the rest of the modal fields (Existing Code)
                document.getElementById('printed-id').value = btn.dataset.id;
                document.getElementById('printed-sas').checked = btn.dataset.sas == 1;
                document.getElementById('printed-trans').checked = btn.dataset.trans == 1;
                document.getElementById('printed-inv').checked = btn.dataset.inv == 1;
                document.getElementById('printed-end').checked = btn.dataset.end == 1;
            });
        });

        // Fill Signed Modal (MODIFIED)
        document.querySelectorAll('.signed-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // 1. Get Event Type from the button
                const eventType = btn.dataset.type;

                // 2. Call the function to update the label based on the type
                updateInvitationLabels(eventType); // <--- NEW STEP

                // 3. Fill the rest of the modal fields (Existing Code)
                document.getElementById('signed-id').value = btn.dataset.id;
                document.getElementById('signed-sas').checked = btn.dataset.sas == 1;
                document.getElementById('signed-trans').checked = btn.dataset.trans == 1;
                document.getElementById('signed-inv').checked = btn.dataset.inv == 1;
                document.getElementById('signed-end').checked = btn.dataset.end == 1;
            });
        });

        // --- 🆕 NEW PARTICIPANT FUNCTIONS ---

        // 1. Load participants into the table when the Participants Modal is opened
        document.querySelectorAll('.participants-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const eventId = btn.dataset.id;
                const eventTitle = btn.dataset.title;

                document.getElementById('participants-event-id').value = eventId;
                document.getElementById('add-participant-event-id').value = eventId; // For the Add Form
                document.getElementById('event-title-display').textContent = eventTitle;

                loadParticipants(eventId);
            });
        });

        function loadParticipants(eventId) {
            // ⚠️ IMPORTANT: Fix the ID typo and use your actual file name
            const participantsTableBody = document.getElementById('participants-table-body');
            participantsTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Loading participants...</td></tr>';

            // ⚠️ CRITICAL FIX: Use your file name: fetch_participants.php
            fetch('../functions/fetch_participants.php?event_id=' + eventId)
                .then(response => {
                    if (!response.ok) {
                        // If server returns non-200 status (like 500 or 400)
                        throw new Error('Network response was not ok, status: ' + response.status);
                    }
                    return response.json(); // <-- Now this will work because PHP outputs JSON!
                })
                .then(data => {
                    participantsTableBody.innerHTML = ''; // Clear loading message

                    if (data.length === 0) {
                        participantsTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No participants added yet.</td></tr>';
                        return;
                    }

                    data.forEach(participant => {

                        // Calculate progress and progress_class using data from the JSON
                        const completed_total = parseInt(participant.completed_count);
                        const requirements_total = 8;
                        const progress_percent = Math.round((completed_total / requirements_total) * 100);

                        let progress_class = 'bg-danger';
                        if (progress_percent > 30) progress_class = 'bg-warning';
                        if (progress_percent > 70) progress_class = 'bg-info';
                        if (progress_percent === 100) progress_class = 'bg-success';


                        // Build the HTML row dynamically using the JSON data
                        const rowHtml = `
                    <tr class="align-middle">
                        <td class="fw-medium">${participant.name}</td>
                        <td>${participant.student_number}</td>
                        <td>${participant.section}</td>
                        <td class='text-center'>
                            <div class='progress' style='height: 10px;'>
                                <div class='progress-bar ${progress_class}' role='progressbar' 
                                     style='width: ${progress_percent}%' aria-valuenow='${progress_percent}' 
                                     aria-valuemin='0' aria-valuemax='100'></div>
                            </div>
                            <small class='text-muted'>${completed_total}/${requirements_total} (${progress_percent}%)</small>
                        </td>
                        <td class='text-center'>
                            <button class='btn btn-sm btn-outline-success checklist-btn rounded-pill' 
                                data-bs-toggle='modal' data-bs-target='#participantChecklistModal'
                                data-p-id='${participant.id}'
                                data-p-name='${participant.name}'
                                data-p-studid='${participant.p_studid}' 
                                data-p-parentid='${participant.p_parentid}' 
                                data-p-waiver='${participant.p_waiver}' 
                                data-p-cor='${participant.p_cor}'
                                data-s-studid='${participant.s_studid}' 
                                data-s-parentid='${participant.s_parentid}' 
                                data-s-waiver='${participant.s_waiver}' 
                                data-s-cor='${participant.s_cor}'>
                                Checklist
                            </button>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-icon btn-sm text-primary edit-participant-btn"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editParticipantModal"
                                    data-id="${participant.id}"
                                    data-name="${participant.name}"
                                    data-student_number="${participant.student_number}"
                                    data-section="${participant.section}"
                                    title="Edit Participant">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button class="btn btn-icon btn-sm text-danger delete-participant-btn"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteParticipantModal"
                                    data-id="${participant.id}"
                                    data-name="${participant.name}"
                                    title="Delete Participant">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                        participantsTableBody.insertAdjacentHTML('beforeend', rowHtml);
                    });
                    // Re-attach listeners after loading new content
                    attachChecklistListeners();
                    attachParticipantActionListeners();
                })
                .catch(error => {
                    console.error('Error fetching participants:', error);
                    participantsTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load participants. Check console for error.</td></tr>';
                });
        }

        // Function to attach listeners for Edit/Delete Participant buttons
        function attachParticipantActionListeners() {
            const currentEventId = document.getElementById('add-participant-event-id').value;

            // --- EDIT Participant Listener ---
            document.querySelectorAll('.edit-participant-btn').forEach(button => {
                button.addEventListener('click', function () {
                    // Get data from the button's data attributes
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const student_number = this.getAttribute('data-student_number');
                    const section = this.getAttribute('data-section');
                    // Populate the Edit Participant modal fields
                    document.getElementById('edit-participant-id').value = id;
                    document.getElementById('edit-participant-event-id-hidden').value = currentEventId;
                    document.getElementById('edit-participant-name').value = name;
                    document.getElementById('edit-participant-student_number').value = student_number;
                    document.getElementById('edit-participant-section').value = section;
                });
            });

            // --- DELETE Participant Listener ---
            document.querySelectorAll('.delete-participant-btn').forEach(button => {
                button.addEventListener('click', function () {
                    // Get data from the button's data attributes
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');

                    // Populate the Delete Participant modal fields
                    document.getElementById('delete-participant-id').value = id;

                    // ✅ FIX: Set the hidden event_id field for the delete form
                    document.getElementById('delete-participant-event-id-hidden').value = currentEventId;

                    document.getElementById('delete-participant-name-display').textContent = name;
                });
            });
        }


        // --- AJAX Submission Handlers for Edit/Delete Participants ---

        document.addEventListener('DOMContentLoaded', function () {

            const participantsModalElement = document.getElementById('participantsModal');
            const participantsModal = bootstrap.Modal.getOrCreateInstance(participantsModalElement);

            // 1. AJAX handler for EDIT Participant Form
            const editParticipantForm = document.getElementById('editParticipantForm');
            const editParticipantModalElement = document.getElementById('editParticipantModal');
            const editParticipantModal = bootstrap.Modal.getOrCreateInstance(editParticipantModalElement);

            if (editParticipantForm) {
                editParticipantForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const form = e.target;
                    const formData = new FormData(form);
                    const currentEventId = document.getElementById('edit-participant-event-id-hidden').value;

                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                editParticipantModal.hide();
                                loadParticipants(currentEventId); // Reload the list
                            } else {
                                alert('Failed to update participant: ' + (data.message || 'Unknown error.'));
                            }
                        })
                        .catch(error => {
                            console.error('Edit Participant Error:', error);
                            alert('An unexpected error occurred during participant update.');
                        });
                });
            }

            // 2. AJAX handler for DELETE Participant Form
            const deleteParticipantForm = document.getElementById('deleteParticipantForm');
            const deleteParticipantModalElement = document.getElementById('deleteParticipantModal');
            const deleteParticipantModal = bootstrap.Modal.getOrCreateInstance(deleteParticipantModalElement);

            if (deleteParticipantForm) {
                deleteParticipantForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const form = e.target;
                    const formData = new FormData(form);
                    const currentEventId = document.getElementById('delete-participant-event-id-hidden').value;

                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                deleteParticipantModal.hide();
                                loadParticipants(currentEventId); // Reload the list
                            } else {
                                alert('Failed to delete participant: ' + (data.message || 'Unknown error.'));
                            }
                        })
                        .catch(error => {
                            console.error('Delete Participant Error:', error);
                            alert('An unexpected error occurred during participant deletion.');
                        });
                });
            }
        });

        // 2. Fill Participant Checklist Modal when its button is clicked
        function attachChecklistListeners() {
            document.querySelectorAll('.checklist-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    // Fill the form data
                    document.getElementById('checklist-p-id').value = btn.dataset.pId;
                    document.getElementById('checklist-p-name').textContent = btn.dataset.pName;

                    // Set Printed Status
                    document.getElementById('p-studid').checked = btn.dataset.pStudid == 1;
                    document.getElementById('p-parentid').checked = btn.dataset.pParentid == 1;
                    document.getElementById('p-waiver').checked = btn.dataset.pWaiver == 1;
                    document.getElementById('p-cor').checked = btn.dataset.pCor == 1;

                    // Set Signed Status
                    document.getElementById('s-studid').checked = btn.dataset.sStudid == 1;
                    document.getElementById('s-parentid').checked = btn.dataset.sParentid == 1;
                    document.getElementById('s-waiver').checked = btn.dataset.sWaiver == 1;
                    document.getElementById('s-cor').checked = btn.dataset.sCor == 1;
                });
            });
        }

        // Add this code block to your existing <script> tags

        document.addEventListener('DOMContentLoaded', function () {

            // Get the form element
            const addForm = document.getElementById('addParticipantForm');

            // Add event listener to handle the form submission
            addForm.addEventListener('submit', function (e) {

                // 🛑 CRITICAL STEP 1: Prevent the default browser submission (page reload)
                e.preventDefault();

                const form = e.target;
                const eventIdInput = document.getElementById('add-participant-event-id');
                const currentEventId = eventIdInput.value;

                // Get the form data
                const formData = new FormData(form);

                // Optional: Disable the button while processing
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;

                // 2. Use the Fetch API to send data to the PHP processing file
                fetch(form.action, {
                    method: form.method, // POST
                    body: formData
                })
                    // Assuming add_participant.php returns a simple JSON object like { "success": true }
                    .then(response => {
                        // Check if the network response was OK
                        if (!response.ok) {
                            throw new Error('Network response was not ok.');
                        }
                        // Try to parse the response as JSON (important for status messages)
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // 3. Data successfully inserted, now refresh the participant list
                            console.log('Participant added successfully! Reloading list...');

                            // Clear the input fields in the form for the next entry
                            form.reset();

                            // Reload the participants list function (defined earlier in your code)
                            loadParticipants(currentEventId);

                            // Modal remains open because there was no page refresh.
                        } else {
                            console.error('Participant addition failed:', data.message || 'Unknown error.');
                            // Optionally show an error alert to the user
                            alert('Failed to add participant. ' + (data.message || 'Check server logs.'));
                        }
                    })
                    .catch(error => {
                        console.error('Submission Error:', error);
                        alert('An unexpected error occurred during submission.');
                    })
                    .finally(() => {
                        // Re-enable the submit button
                        submitBtn.disabled = false;
                    });
            });
        });
    </script>

</body>

</html>