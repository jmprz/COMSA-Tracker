<?php
session_start();
// Regenerate session ID after login to prevent fixation attacks
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}
// Check if user is logged in and is admin
if (!isset($_SESSION['student_number']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | COMSA - TRACKER</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons (for a cleaner look) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="styles.css">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        /* 1. Remove unnecessary top padding and set background */
        body {
            padding-top: 0;
            padding-bottom: 20px;
            background-color: #f8f9fa;
        }

        /* 2. Main wrapper uses Flexbox for side-by-side layout */
        #wrapper {
            display: flex;
            width: 100%;
        }

        /* 3. Sidebar Styles: fixed, full height, dark background */
        #sidebar-wrapper {
            min-height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1030;
            background-color: white; /* Darker than the top nav */
            transition: all 0.3s;
        }
        
        /* Style for the logo/brand area within the sidebar */
        .sidebar-heading {
            padding: 1.5rem 1rem;
            font-size: 1.2rem;
            color: #f8f9fa;
            font-weight: bold;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Style for the navigation links in the sidebar */
        .sidebar-links .list-group-item {
            border: none;
            padding: 0.8rem 1.5rem;
            background-color: transparent;
            color: #000000ff; /* Light grey text */
            transition: background-color 0.2s;
        }

        .sidebar-links .list-group-item:hover,
        .sidebar-links .list-group-item.active {
            color: #ffffff;
            background-color: #09b003; /* Slightly lighter dark on hover/active */
        }
        
        .sidebar-links .list-group-item.active {
            border-left: 20px solid #007a00; /* Highlight active link */
        }

        /* 4. Content Area Styles */
        #page-content-wrapper {
            flex-grow: 1;
            width: 100%;
            /* Offset content to make space for the desktop sidebar */
            padding-top: 70px; /* Padding for the fixed top header */
        }

        .top-header {
            width: 100%;
            padding-left: 250px;
            z-index: 1000;
        }
        
        /* 5. Responsive / Mobile Toggling */
        @media (max-width: 991.98px) { /* Adjusting for lg breakpoint */
            /* Hide the sidebar completely on smaller screens by default */
            #sidebar-wrapper {
                margin-left: -250px;
            }
            /* Content takes full width on mobile */
            #page-content-wrapper,
            .top-header {
                padding-left: 0;
            }
            /* When the toggled class is present, slide the sidebar in */
            #wrapper.toggled #sidebar-wrapper {
                margin-left: 0;
            }
            /* Push the content over when the sidebar is open */
            #wrapper.toggled #page-content-wrapper {
                margin-left: 250px;
            }
        }
        
        /* Adjust for overall responsiveness */
        .container-fluid {
            max-width: 100%; /* Use full width in the dashboard layout */
        }
    </style>
</head>
<body>
    
    <!-- Overall Layout Wrapper -->
    <div id="wrapper">
        <!-- Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm fixed-top">
  <div class="container-xxl d-flex align-items-center justify-content-between">

    <!-- Left: Logo -->
    <a class="navbar-brand fs-2 fw-bold d-flex align-items-center gap-2" href="#">
      <img src="img/logo.png" alt="" class="img-fluid" style="height:60px;">
      <span class="d-lg-inline">COMSA-TRACKER</span>
    </a>



      <!-- Right: Icon buttons -->
    <div class="d-flex align-items-center gap-3 d-none d-lg-flex">

      <a href="admin_dashboard.php" class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
         style="width:50px; height:50px;">
        <i class="ri-dashboard-line fs-4"></i>
      </a>

      <a href="events.php"
         class="btn btn-active rounded-3 d-flex align-items-center justify-content-center"
         style="width:50px; height:50px;">
        <i class="ri-calendar-schedule-line fs-4"></i>
      </a>

       <a href="tasks.php"
         class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
         style="width:50px; height:50px;">
        <i class="ri-list-check-2 fs-4"></i>
      </a>

       <a href="users.php"
         class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
         style="width:50px; height:50px;">
        <i class="ri-user-3-line fs-4"></i>
      </a>

      <a href="settings.php"
         class="btn btn-light rounded-3 d-flex align-items-center justify-content-center"
         style="width:50px; height:50px;">
        <i class="ri-settings-line fs-4"></i>
      </a>

  </div>
</nav>

<!-- /Navbar -->
        <!-- 🌟 SIDEBAR/NAVIGATION 🌟 
        <div id="sidebar-wrapper" class="shadow-lg border-right">
            <!-- Sidebar Heading now includes the close button for mobile 
            <div class="sidebar-heading d-flex justify-content-between align-items-center">
                
                <!-- Close button (visible only on mobile) 
                <button class="btn text-black d-lg-none p-0" id="sidebarClose" aria-label="Close menu">
                    <i class="bi bi-x-lg fs-4"></i>
                </button>
            </div>
            
            <div class="list-group list-group-flush sidebar-links">
                <!-- Navigation Items 
                <a href="admin_dashboard.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a href="events.php" class="list-group-item list-group-item-action active">
                    <i class="bi bi-calendar-week me-2"></i> Events
                </a>
                <a href="tasks.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-table me-2"></i> Tasks
                </a>
                <a href="users.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-person me-2"></i> Users
                </a>
                <a href="logout.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
         🌟 END SIDEBAR 🌟  -->

        <!-- Page Content Wrapper -->
        <div id="page-content-wrapper">
            
            <!-- TOP HEADER (for Brand and Mobile Toggle) -->
            <nav class="navbar navbar-expand-lg fixed-top top-header">
                <div class="container-fluid">
                    <!-- Hamburger Toggle Button (HIDES ON LARGE SCREENS AND UP) -->
                    <button class="btn btn-comsa d-lg-none" id="sidebarToggle" aria-label="Open menu">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
            </nav>

          <!-- Main Content Area -->
<main class="container-fluid py-5">
 <div class="row g-4 justify-content-center">
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0">Events</h2>
        <!-- Trigger Modal -->
        <button class="add-btn" data-bs-toggle="modal" data-bs-target="#addEventModal">
  <i class="ri-add-line"></i> Add Activity
</button>
      </div>
      <hr>

      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>TYPE</th>
              <th>TITLE</th>
              <th>SAS F6</th>
              <th>TRANSMITTAL</th>
              <th>INVITATION</th>
              <th>ENDORSEMENT</th>
              <th>DUE DATE</th>
              <th>STATUS</th>
              <th>PRINTED</th>
              <th>SIGNED</th>
              <th>ACTION</th>
            </tr>
          </thead>
          <tbody>
          <?php
          require_once "config.php";
          $result = $conn->query("SELECT * FROM events ORDER BY id DESC");
          while ($row = $result->fetch_assoc()) {
              echo "<tr>";
              echo "<td>{$row['type']}</td>";
              echo "<td>{$row['title']}</td>";
              echo "<td><input type='checkbox' class='update-checkbox' data-id='{$row['id']}' data-column='sas_f6' " . ($row['sas_f6'] ? "checked" : "") . "></td>";
              echo "<td><input type='checkbox' class='update-checkbox' data-id='{$row['id']}' data-column='transmittal' " . ($row['transmittal'] ? "checked" : "") . "></td>";
              echo "<td><input type='checkbox' class='update-checkbox' data-id='{$row['id']}' data-column='invitation' " . ($row['invitation'] ? "checked" : "") . "></td>";
              echo "<td><input type='checkbox' class='update-checkbox' data-id='{$row['id']}' data-column='endorsement' " . ($row['endorsement'] ? "checked" : "") . "></td>";
              echo "<td>{$row['due_date']}</td>";
              echo "<td>{$row['status']}</td>";
              echo "<td><input type='checkbox' class='update-checkbox' data-id='{$row['id']}' data-column='printed' " . ($row['printed'] ? "checked" : "") . "></td>";
              echo "<td><input type='checkbox' class='update-checkbox' data-id='{$row['id']}' data-column='signed' " . ($row['signed'] ? "checked" : "") . "></td>";
              echo "<td>";
                echo "<button class='btn btn-sm btn-primary edit-btn' 
                        data-id='{$row['id']}' 
                        data-type='{$row['type']}' 
                        data-title='{$row['title']}' 
                        data-due_date='{$row['due_date']}' 
                        data-status='{$row['status']}'
                        data-sas_f6='{$row['sas_f6']}'
                        data-transmittal='{$row['transmittal']}'
                        data-invitation='{$row['invitation']}'
                        data-endorsement='{$row['endorsement']}'
                        data-printed='{$row['printed']}'
                        data-signed='{$row['signed']}'
                        >
                        <i class='ri-edit-line'></i>
                    </button> ";
                echo "<a href='delete_events.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this event?\")'>
                        <i class='ri-delete-bin-line'></i>
                    </a>";
              echo "</td>";
              echo "</tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="save_events.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addEventModalLabel">Add New Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Type</label>
              <input type="text" name="type" class="form-control" placeholder="e.g. Task A" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Title</label>
              <input type="text" name="title" class="form-control" placeholder="Event title">
            </div>
          </div>
          <div class="row mb-3 text-center">
            <div class="col"><label>SAS F6</label><input type="checkbox" name="sas_f6" class="form-check-input ms-2"></div>
            <div class="col"><label>Transmittal</label><input type="checkbox" name="transmittal" class="form-check-input ms-2"></div>
            <div class="col"><label>Invitation</label><input type="checkbox" name="invitation" class="form-check-input ms-2"></div>
            <div class="col"><label>Endorsement</label><input type="checkbox" name="endorsement" class="form-check-input ms-2"></div>
            <div class="col"><label>Printed</label><input type="checkbox" name="printed" class="form-check-input ms-2"></div>
            <div class="col"><label>Signed</label><input type="checkbox" name="signed" class="form-check-input ms-2"></div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Due Date</label>
              <input type="date" name="due_date" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="Pending">Pending</option>
                <option value="Ongoing">Ongoing</option>
                <option value="Completed">Completed</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Event</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="update_events.php">
        <div class="modal-header">
          <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit-id">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Type</label>
              <input type="text" name="type" id="edit-type" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Title</label>
              <input type="text" name="title" id="edit-title" class="form-control">
            </div>
          </div>
          <div class="row mb-3 text-center">
            <div class="col"><label>SAS F6</label><input type="checkbox" id="edit-sas_f6" name="sas_f6" class="form-check-input ms-2"></div>
            <div class="col"><label>Transmittal</label><input type="checkbox" id="edit-transmittal" name="transmittal" class="form-check-input ms-2"></div>
            <div class="col"><label>Invitation</label><input type="checkbox" id="edit-invitation" name="invitation" class="form-check-input ms-2"></div>
            <div class="col"><label>Endorsement</label><input type="checkbox" id="edit-endorsement" name="endorsement" class="form-check-input ms-2"></div>
            <div class="col"><label>Printed</label><input type="checkbox" id="edit-printed" name="printed" class="form-check-input ms-2"></div>
            <div class="col"><label>Signed</label><input type="checkbox" id="edit-signed" name="signed" class="form-check-input ms-2"></div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Due Date</label>
              <input type="date" name="due_date" id="edit-due_date" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" id="edit-status" class="form-select">
                <option value="Pending">Pending</option>
                <option value="Ongoing">Ongoing</option>
                <option value="Completed">Completed</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
</main>

        </div>
        <!-- End Page Content Wrapper -->

    </div>
    <!-- End Wrapper -->

    <!-- 💡 Bootstrap JavaScript CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- Custom JS for Sidebar Toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var sidebarToggle = document.getElementById('sidebarToggle');
            var sidebarClose = document.getElementById('sidebarClose');
            var wrapper = document.getElementById('wrapper');

            // Function to toggle the sidebar (open/close)
            function toggleSidebar(e) {
                e.preventDefault();
                wrapper.classList.toggle('toggled');
            }

            // Toggle sidebar visibility on click for the hamburger button
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            
            // Toggle sidebar visibility on click for the close button
            if (sidebarClose) {
                sidebarClose.addEventListener('click', toggleSidebar);
            }
        });
    </script>

    <script>
  document.getElementById('addRow').addEventListener('click', function() {
    const tableBody = document.querySelector('#eventTable tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
      <td><input type="text" name="type[]" class="form-control" placeholder="e.g. Task"></td>
      <td><input type="text" name="title[]" class="form-control" placeholder="Enter title"></td>
      <td class="text-center"><input type="checkbox" name="sas_f6[]"></td>
      <td class="text-center"><input type="checkbox" name="transmittal[]"></td>
      <td class="text-center"><input type="checkbox" name="invitation[]"></td>
      <td class="text-center"><input type="checkbox" name="endorsement[]"></td>
      <td><input type="date" name="due_date[]" class="form-control"></td>
      <td>
        <select name="status[]" class="form-select">
          <option value="Pending">Pending</option>
          <option value="Ongoing">Ongoing</option>
          <option value="Completed">Completed</option>
        </select>
      </td>
    `;
    tableBody.appendChild(newRow);
  });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const checkboxes = document.querySelectorAll('.update-checkbox');

  checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      const id = this.dataset.id;
      const column = this.dataset.column;
      const value = this.checked;

      fetch('update_events_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&column=${column}&value=${value}`
      })
      .then(response => response.text())
      .then(data => {
        if (data.trim() === 'success') {
          console.log(`Updated ${column} for ID ${id} → ${value}`);
        } else {
          alert('Error updating: ' + data);
          this.checked = !value; // revert if failed
        }
      })
      .catch(err => {
        alert('Request failed');
        this.checked = !value; // revert if network failed
      });
    });
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const editButtons = document.querySelectorAll('.edit-btn');

  editButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      document.getElementById('edit-id').value = this.dataset.id;
      document.getElementById('edit-type').value = this.dataset.type;
      document.getElementById('edit-title').value = this.dataset.title;
      document.getElementById('edit-due_date').value = this.dataset.due_date;
      document.getElementById('edit-status').value = this.dataset.status;

      // Checkboxes
      document.getElementById('edit-sas_f6').checked = this.dataset.sas_f6 == 1;
      document.getElementById('edit-transmittal').checked = this.dataset.transmittal == 1;
      document.getElementById('edit-invitation').checked = this.dataset.invitation == 1;
      document.getElementById('edit-endorsement').checked = this.dataset.endorsement == 1;
      document.getElementById('edit-printed').checked = this.dataset.printed == 1;
      document.getElementById('edit-signed').checked = this.dataset.signed == 1;

      const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
      modal.show();
    });
  });
});
</script>


</body>
</html>