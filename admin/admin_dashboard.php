<?php
session_start();
require_once "../functions/config.php";

// Admin check
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Fetch dashboard statistics
$stats = [
    'total_events' => 0,
    'pending' => 0,
    'ongoing' => 0,
    'completed' => 0,
    'total_printed' => 0,
    'total_signed' => 0,
    'upcoming_deadlines' => 0
];

// Get event counts by status
$status_query = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM events 
    GROUP BY status
");
while ($row = $status_query->fetch_assoc()) {
    $stats['total_events'] += $row['count'];
    $stats[strtolower($row['status'])] = $row['count'];
}

// Get printed/signed totals
$docs_query = $conn->query("
    SELECT 
        SUM(ep.sas_f6 + ep.transmittal + ep.invitation + ep.endorsement) as printed_total,
        SUM(es.sas_f6 + es.transmittal + es.invitation + es.endorsement) as signed_total
    FROM events e
    LEFT JOIN events_printed ep ON e.id = ep.event_id
    LEFT JOIN events_signed es ON e.id = es.event_id
");
$docs = $docs_query->fetch_assoc();
$stats['total_printed'] = $docs['printed_total'] ?? 0;
$stats['total_signed'] = $docs['signed_total'] ?? 0;

// Get upcoming deadlines (next 7 days)
$upcoming_query = $conn->query("
    SELECT COUNT(*) as count 
    FROM events 
    WHERE due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND status != 'Completed'
");
$stats['upcoming_deadlines'] = $upcoming_query->fetch_assoc()['count'];

// Fetch recent events (last 5)
$recent_events = $conn->query("
    SELECT e.*, 
           ep.sas_f6 + ep.transmittal + ep.invitation + ep.endorsement as printed_count,
           es.sas_f6 + es.transmittal + es.invitation + es.endorsement as signed_count
    FROM events e
    LEFT JOIN events_printed ep ON e.id = ep.event_id
    LEFT JOIN events_signed es ON e.id = es.event_id
    ORDER BY e.id DESC
    LIMIT 5
");

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../styles.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
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
            padding-left: 250px;
            z-index: 1000;
        }

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

        /* Color schemes for different stats */
        .stat-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-success {
            background: linear-gradient(135deg, #09b003 0%, #007a00 100%);
        }

        .stat-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .stat-secondary {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
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

    <div id="wrapper">
        <!-- Navbar -->
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
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h2 class="fw-bold mb-1">Dashboard Overview</h2>
                        <p class="text-muted">Welcome back! Here's what's happening with your events.</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Total Events</div>
                                    <div class="stat-value"><?= $stats['total_events'] ?></div>
                                    <small class="text-muted">All time</small>
                                </div>
                                <div class="stat-icon stat-primary text-white">
                                    <i class="ri-calendar-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Ongoing Events</div>
                                    <div class="stat-value text-info"><?= $stats['ongoing'] ?></div>
                                    <small class="text-muted">In progress</small>
                                </div>
                                <div class="stat-icon stat-info text-white">
                                    <i class="ri-time-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Completed</div>
                                    <div class="stat-value text-success"><?= $stats['completed'] ?></div>
                                    <small class="text-muted">Finished events</small>
                                </div>
                                <div class="stat-icon stat-success text-white">
                                    <i class="ri-checkbox-circle-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Pending</div>
                                    <div class="stat-value text-warning"><?= $stats['pending'] ?></div>
                                    <small class="text-muted">Not started</small>
                                </div>
                                <div class="stat-icon stat-warning text-white">
                                    <i class="ri-hourglass-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Documents Printed</div>
                                    <div class="stat-value text-secondary"><?= $stats['total_printed'] ?></div>
                                    <small class="text-muted">Out of <?= $stats['total_events'] * 4 ?> total</small>
                                </div>
                                <div class="stat-icon stat-secondary text-white">
                                    <i class="ri-printer-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card shadow-sm">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Upcoming Deadlines</div>
                                    <div class="stat-value text-danger"><?= $stats['upcoming_deadlines'] ?></div>
                                    <small class="text-muted">Next 7 days</small>
                                </div>
                                <div class="stat-icon stat-danger text-white">
                                    <i class="ri-alarm-warning-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout for Tables -->
                <div class="row g-4">
                    <!-- Recent Events -->
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
                                                        <div class="event-title"><?= $event['title'] ?></div>
                                                        <small class="text-muted"><?= $event['type'] ?></small>
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

                    <!-- Upcoming Deadlines -->
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
</body>

</html>