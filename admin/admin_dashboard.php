<?php
session_start();
require_once "../functions/config.php";

// Admin check
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Initialize stats
$stats = [
    'total_users' => 0,
    'total_tasks' => 0,
    'task_pending' => 0,
    'task_ongoing' => 0,
    'task_completed' => 0,
    'total_events' => 0,
    'event_pending' => 0,
    'event_ongoing' => 0,
    'event_completed' => 0,
    'upcoming_deadlines' => 0
];

// 1. Fetch Total Users
$user_query = $conn->query("SELECT COUNT(*) as total_users FROM users");
$stats['total_users'] = $user_query->fetch_assoc()['total_users'] ?? 0;

// 2. Fetch Task Counts by Status
$task_status_query = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM tasks 
    GROUP BY status
");
while ($row = $task_status_query->fetch_assoc()) {
    $stats['total_tasks'] += $row['count'];
    $stats['task_' . strtolower($row['status'])] = $row['count'];
}

// 3. Fetch Event Counts by Status
$event_status_query = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM events 
    GROUP BY status
");
while ($row = $event_status_query->fetch_assoc()) {
    $stats['total_events'] += $row['count'];
    $stats['event_' . strtolower($row['status'])] = $row['count'];
}


// 4. Get upcoming deadlines (next 7 days) - Using events as source
$upcoming_query = $conn->query("
    SELECT COUNT(*) as count 
    FROM events 
    WHERE due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND status != 'Completed'
");
$stats['upcoming_deadlines'] = $upcoming_query->fetch_assoc()['count'] ?? 0;


// Fetch recent events (last 5)
$recent_events_query = "
    SELECT e.*, 
            COALESCE(ep.sas_f6, 0) + COALESCE(ep.transmittal, 0) + COALESCE(ep.invitation, 0) + COALESCE(ep.endorsement, 0) as printed_count,
            COALESCE(es.sas_f6, 0) + COALESCE(es.transmittal, 0) + COALESCE(es.invitation, 0) + COALESCE(es.endorsement, 0) as signed_count
    FROM events e
    LEFT JOIN events_printed ep ON e.id = ep.event_id
    LEFT JOIN events_signed es ON e.id = es.event_id
    ORDER BY e.id DESC
    LIMIT 5
";
$recent_events = $conn->query($recent_events_query);

// Fetch upcoming deadlines
$deadline_events = $conn->query("
    SELECT *, DATEDIFF(due_date, CURDATE()) as days_remaining 
    FROM events 
    WHERE due_date >= CURDATE() 
    AND status != 'Completed'
    ORDER BY due_date ASC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | COMSA - TRACKER</title>
    <link rel="apple-touch-icon" sizes="180x180" href="../img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../img/favicon/favicon-16x16.png">
    <link rel="manifest" href="../img/favicon/site.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../styles.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            padding-top: 0;
            padding-bottom: 20px;
            background-color: #f8f9fa;
            /* Hide vertical overflow initially for transition effect */
            overflow-y: hidden;
        }

        #wrapper {
            display: flex;
            width: 100%;
        }

        #page-content-wrapper {
            flex-grow: 1;
            width: 100%;
            padding-top: 70px;
            /* Initial state for page transition */
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }

        /* Final state for page transition after load */
        .page-loaded #page-content-wrapper {
            opacity: 1;
            transform: translateY(0);
        }

        .top-header {
            width: 100%;
            padding-left: 0;
            /* Adjusted for full screen view */
            z-index: 1000;
        }

        /* --- LOADING SCREEN STYLES (NEW) --- */
        #loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #ffffff;
            /* White background */
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease-out;
        }

        #loading-screen.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

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

        /* --- END LOADING SCREEN STYLES --- */

        /* Stats Cards */
        .stat-card {
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0 0.25rem 0;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* --- UPDATED COLOR SCHEMES FOR ICONS --- */

        /* Total Users - Purple */
        .stat-purple {
            background: linear-gradient(135deg, #7F00FF 0%, #E100FF 100%);
        }

        .stat-purple-text {
            color: #6f42c1;
        }

        /* Total Tasks & Events - Orange */
        .stat-orange {
            background: linear-gradient(135deg, #FF9933 0%, #FF6600 100%);
        }

        .stat-orange-text {
            color: #fd7e14;
        }

        /* Completed/Success - Green */
        .stat-success {
            background: linear-gradient(135deg, #09b003 0%, #007a00 100%);
        }

        /* Ongoing/In Progress - Blue */
        .stat-progress {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        /* Pending/Awaiting Start - YELLOW GRADIENT (MODIFIED) */
        .stat-pending {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            /* Gold to Orange */
        }

        /* Deadlines/Urgent - Red */
        .stat-urgent {
            background: linear-gradient(135deg, #FF4B2B 0%, #FF416C 100%);
        }


        /* Table styling */
        .dashboard-table {
            border-radius: 8px;
            overflow: hidden;
        }

        .dashboard-table thead {
            background-color: #e9ecef;
        }

        .dashboard-table th {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            padding: 1rem;
        }

        .dashboard-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .progress-thin {
            height: 6px;
        }

        .event-title {
            font-weight: 500;
            color: #212529;
        }

        .mini-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
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
                        class="btn btn-active rounded-3 d-flex align-items-center justify-content-center"
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
                <div class="row mb-4">
                    <div class="col-12">
                        <h2 class="fw-bold mb-1">Dashboard Overview</h2>
                        <p class="text-muted">Welcome back! Here's what's happening with your accounts, tasks, and
                            events.</p>
                    </div>
                </div>

                <div class="row g-4 mb-4">

                    <div class="col-lg-12 mb-2">
                        <h4 class="text-secondary fw-bold border-bottom pb-2">Personnel & Event Overview</h4>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Total Users</div>
                                    <div class="stat-value stat-purple-text"><?= $stats['total_users'] ?></div>
                                    <small class="text-muted">Registered accounts</small>
                                </div>
                                <div class="stat-icon stat-purple text-white">
                                    <i class="ri-user-3-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Total Events</div>
                                    <div class="stat-value stat-orange-text"><?= $stats['total_events'] ?></div>
                                    <small class="text-muted">Active and archived</small>
                                </div>
                                <div class="stat-icon stat-orange text-white">
                                    <i class="ri-calendar-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Upcoming Deadlines</div>
                                    <div class="stat-value text-danger"><?= $stats['upcoming_deadlines'] ?></div>
                                    <small class="text-muted">Events in next 7 days</small>
                                </div>
                                <div class="stat-icon stat-urgent text-white">
                                    <i class="ri-alarm-warning-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Completed Events</div>
                                    <div class="stat-value text-success"><?= $stats['event_completed'] ?></div>
                                    <small class="text-muted">Total finished events</small>
                                </div>
                                <div class="stat-icon stat-success text-white">
                                    <i class="ri-calendar-check-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 mt-4 mb-2">
                        <h4 class="text-secondary fw-bold border-bottom pb-2">Task Management Status</h4>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Total Tasks</div>
                                    <div class="stat-value stat-orange-text"><?= $stats['total_tasks'] ?></div>
                                    <small class="text-muted">Total tasks created</small>
                                </div>
                                <div class="stat-icon stat-orange text-white">
                                    <i class="ri-clipboard-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Pending Tasks</div>
                                    <div class="stat-value text-warning"><?= $stats['task_pending'] ?></div>
                                    <small class="text-muted">Awaiting start</small>
                                </div>
                                <div class="stat-icon stat-pending text-white">
                                    <i class="ri-timer-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Ongoing Tasks</div>
                                    <div class="stat-value text-info"><?= $stats['task_ongoing'] ?></div>
                                    <small class="text-muted">Currently in progress</small>
                                </div>
                                <div class="stat-icon stat-progress text-white">
                                    <i class="ri-loader-4-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Tasks Completed</div>
                                    <div class="stat-value text-success"><?= $stats['task_completed'] ?></div>
                                    <small class="text-muted">Finished and reviewed</small>
                                </div>
                                <div class="stat-icon stat-success text-white">
                                    <i class="ri-check-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                                    <h5 class="fw-bold mb-0">Recent Events</h5>
                                    <a href="events.php" class="btn btn-sm btn-outline-secondary">View All</a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-borderless dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>Event</th>
                                                <th>Status</th>
                                                <th>Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($event = $recent_events->fetch_assoc()):
                                                $progress = (($event['printed_count'] ?? 0) + ($event['signed_count'] ?? 0)) / 8 * 100;
                                                $status_class = match ($event['status']) {
                                                    'Pending' => 'bg-warning-subtle text-warning',
                                                    'Ongoing' => 'bg-info-subtle text-info',
                                                    'Completed' => 'bg-success-subtle text-success',
                                                    default => 'bg-light text-secondary'
                                                };
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="event-title"><?= htmlspecialchars($event['title']) ?>
                                                        </div>
                                                        <small
                                                            class="text-muted"><?= htmlspecialchars($event['type']) ?></small>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge mini-badge <?= $status_class ?>"><?= $event['status'] ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="progress progress-thin">
                                                            <div class="progress-bar bg-success"
                                                                style="width: <?= $progress ?>%"></div>
                                                        </div>
                                                        <small class="text-muted"><?= round($progress) ?>%</small>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                                    <h5 class="fw-bold mb-0">Upcoming Deadlines</h5>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php
                                    if ($deadline_events->num_rows > 0) {
                                        while ($deadline = $deadline_events->fetch_assoc()):
                                            $days_left = $deadline['days_remaining'];
                                            $urgency = $days_left <= 2 ? 'danger' : ($days_left <= 5 ? 'warning' : 'info');
                                            ?>
                                            <div class="list-group-item border-0 px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($deadline['title']) ?></h6>
                                                        <small
                                                            class="text-muted"><?= date('M j, Y', strtotime($deadline['due_date'])) ?></small>
                                                    </div>
                                                    <span class="badge bg-<?= $urgency ?>-subtle text-<?= $urgency ?>">
                                                        <?= $days_left ?> day<?= $days_left != 1 ? 's' : '' ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php
                                        endwhile;
                                    } else {
                                        echo '<p class="text-muted text-center py-3">No upcoming deadlines</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript for Loading Screen and Page Animation
        document.addEventListener('DOMContentLoaded', (event) => {
            const loadingScreen = document.getElementById('loading-screen');
            const body = document.body;

            // Wait for the entire page (including images, resources) to load
            window.onload = function () {
                // 1. Apply page-loaded class for the main content transition
                body.classList.add('page-loaded');

                // 2. Hide the loading screen with a fade-out effect
                loadingScreen.classList.add('hidden');

                // 3. Re-enable vertical scrolling after the animation is finished
                setTimeout(() => {
                    body.style.overflowY = 'auto';
                }, 500); // Match the CSS transition duration (0.5s)
            };
        });
    </script>
</body>

</html>