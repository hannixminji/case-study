<?php

require_once __DIR__ . '/../database/database.php'            ;

require_once __DIR__ . '/../departments/DepartmentService.php';
require_once __DIR__ . '/../job-titles/JobTitleService.php'   ;
require_once __DIR__ . '/../employees/EmployeeService.php'    ;

$departmentDao        = new DepartmentDao       ($pdo                                  );
$departmentRepository = new DepartmentRepository($departmentDao              );
$departmentService    = new DepartmentService   ($departmentRepository);

$departmentTableColumns = [
    'id'  ,
    'name'
];

$departmentFilterCriteria = [
    [
        'column'   => 'department.status',
        'operator' => '='                ,
        'value'    => 'Active'
    ]
];

$departmentSortCriteria = [
    [
        'column'    => 'department.name',
        'direction' => 'ASC'
    ]
];

$departments = $departmentService->fetchAllDepartments(
    columns       : $departmentTableColumns  ,
    filterCriteria: $departmentFilterCriteria,
    sortCriteria  : $departmentSortCriteria
);

if ($departments !== ActionResult::FAILURE) {
    $departments = $departments['result_set'];
}

$jobTitleDao        = new JobTitleDao       ($pdo                              );
$jobTitleRepository = new JobTitleRepository($jobTitleDao              );
$jobTitleService    = new JobTitleService   ($jobTitleRepository);

$jobTitleTableColumns = [
    'id'   ,
    'title'
];

$jobTitleFilterCriteria = [
    [
        'column'   => 'job_title.status',
        'operator' => '='                ,
        'value'    => 'Active'
    ]
];

$jobTitleSortCriteria = [
    [
        'column'    => 'job_title.title',
        'direction' => 'ASC'
    ]
];

$jobTitles = $jobTitleService->fetchAllJobTitles(
    columns       : $jobTitleTableColumns  ,
    filterCriteria: $jobTitleFilterCriteria,
    sortCriteria  : $jobTitleSortCriteria
);

if ($jobTitles !== ActionResult::FAILURE) {
    $jobTitles = $jobTitles['result_set'];
}

$employeeDao        = new EmployeeDao       ($pdo                              );
$employeeRepository = new EmployeeRepository($employeeDao              );
$employeeService    = new EmployeeService   ($employeeRepository);

$employeeTableColumns = [
    'id'  ,
    'name',
];

$employeeFilterCriteria = [
    [
        'column'   => 'employee.deleted_at',
        'operator' => 'IS NULL'
    ]
];

$employeeSortCriteria = [
    [
        'column'    => 'employee.name',
        'direction' => 'ASC'
    ]
];

$employees = $employeeService->fetchAllEmployees(
    columns       : $employeeTableColumns  ,
    filterCriteria: $employeeFilterCriteria,
    sortCriteria  : $employeeSortCriteria
);

if ($employees !== ActionResult::FAILURE) {
    $employees = $employees['result_set'];
}

?>

<!DOCTYPE html>
<html>
<head>
  <style>
    table {
        overflow-x: auto;
        white-space: nowrap;
        padding: 3px;
        width: 100% !important;
    }

    .department-section {
        background-color: #f4f6f9;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .dropdown-menu {
        background-color: #ffffff;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-Apply {
        background-color: #64dd68;
        border: black solid 1.5px;
        border-radius: 5px;
        height: 32px;
        width: 70px;
    }

    .transparent {
        background-color: rgba(255, 255, 255, 0) !important;
    }

    .content {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
    }

    .controls {
        margin-bottom: 20px;
        text-align: left;
        display: flex;
        align-items: center;
        justify-content: left;
    }

    .pagination {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }

    .dropdown-item.selected {
        font-weight: bold;
        color: #4CAF50;
    }

    .line {
        border-bottom: black 1px solid;
    }
  </style>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

  <!-- Icons -->
  <link rel="stylesheet" href="assets/vendor/fonts/boxicons.css" />

  <!-- Core CSS -->
  <link rel="stylesheet" href="assets/vendor/css/core.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="assets/css/demo.css" />

  <!-- Vendors CSS -->
  <link rel="stylesheet" href="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="assets/vendor/libs/apex-charts/apex-charts.css" />

  <!-- Page CSS -->
  <link rel="stylesheet" href="lib/datatables/dataTables.css" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" />

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>

<body>
  <!-- Layout wrapper -->
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

      <div class="layout-page">
        <div class="container-fluid">
          <nav class="layout-navbar container-xxl navbar navbar-expand-xl align-items-center transparent" id="layout-navbar">
            <div class="navbar-nav-left layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-lg"></i>
              </a>
            </div>
          </nav>
        </div>

        <div class="container-xxl">
          <div class="container department-section">
            <div class="controls">
              <!-- Department Dropdown -->
              <div class="dropdown space">
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                  Department <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                  <li><a class="dropdown-item selected" href="#" data-group="group1" data-value="ALL">ALL</a></li>

                  <?php foreach ($departments as $department): ?>
                    <li><a class="dropdown-item" href="#" data-group="group1" data-value="<?= htmlspecialchars($department['name']); ?>"><?= htmlspecialchars($department['name']); ?></a></li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <!-- Job Titles Dropdown (Updated) -->
              <div class="dropdown space">
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                  Job Titles <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                  <li><a class="dropdown-item selected" href="#" data-group="group1" data-value="ALL">ALL</a></li>

                  <?php foreach ($jobTitles as $jobTitle): ?>
                    <li><a class="dropdown-item" href="#" data-group="group1" data-value="<?= htmlspecialchars($jobTitle['title']); ?>"><?= htmlspecialchars($jobTitle['title']); ?></a></li>
                  <?php endforeach; ?>

                  <li><h5 class="dropdown-header line"></h5></li>
                  <li><a class="dropdown-item" href="#" data-group="group2" data-value="Inactive">Inactive</a></li>
                  <li><a class="dropdown-item" href="#" data-group="group2" data-value="Archived">Archived</a></li>
                </ul>
              </div>

              <!-- Employees Dropdown -->
              <div class="dropdown space">
                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                  Employees <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                  <li><a class="dropdown-item selected" href="#" data-group="group1" data-value="ALL">ALL</a></li>

                  <?php foreach ($employees as $employee): ?>
                    <li><a class="dropdown-item" href="#" data-group="group1" data-value="<?= htmlspecialchars($employee['full_name']); ?>"><?= htmlspecialchars($employee['full_name']); ?></a></li>
                  <?php endforeach; ?>

                  <li><h5 class="dropdown-header line"></h5></li>
                  <li><a class="dropdown-item" href="#" data-group="group2" data-value="Inactive">Inactive</a></li>
                  <li><a class="dropdown-item" href="#" data-group="group2" data-value="Archived">Archived</a></li>
                </ul>
              </div>


              <!-- Apply Button -->
              <div>
                <button class="btn-Apply"> Apply</button>
              </div>
            </div>

            <!-- Table Section -->
            <div class="content">
              <table id="dynamicTable" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Day Type</th>
                    <th>Regular Hour</th>
                    <th>Overtime</th>
                    <th>Night Differential</th>
                    <th>Night Differential Overtime</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <th>Regular</th>
                    <th><input type="number" placeholder="0.00"></th>
                    <th><input type="number" placeholder="1.350"></th>
                    <th><input type="number" placeholder="0.100"></th>
                    <th><input type="number" placeholder="1.375"></th>
                  </tr>
                  <tr>
                    <th>Special Holiday</th>
                    <th><input type="number" placeholder="0.300"></th>
                    <th><input type="number" placeholder="1.690"></th>
                    <th><input type="number" placeholder="0.430"></th>
                    <th><input type="number" placeholder="1.859"></th>
                </tr>
                <tr>
                    <th>Regular Holiday</th>
                    <th><input type="number" placeholder="1.000"></th>
                    <th><input type="number" placeholder="2.600"></th>
                    <th><input type="number" placeholder="1.200"></th>
                    <th><input type="number" placeholder="2.860"></th>
                </tr>
                <tr>
                    <th>Double Holiday</th>
                    <th><input type="number" placeholder="1.600"></th>
                    <th><input type="number" placeholder="3.380"></th>
                    <th><input type="number" placeholder="1.860"></th>
                    <th><input type="number" placeholder="3.718"></th>
                </tr>
                <tr>
                    <th>Rest Day</th>
                    <th><input type="number" placeholder="1.300"></th>
                    <th><input type="number" placeholder="1.690"></th>
                    <th><input type="number" placeholder="1.430"></th>
                    <th><input type="number" placeholder="1.859"></th>
                </tr>
                <tr>
                    <th>Rest Day Special Holiday</th>
                    <th><input type="number" placeholder="1.500"></th>
                    <th><input type="number" placeholder="1.950"></th>
                    <th><input type="number" placeholder="1.650"></th>
                    <th><input type="number" placeholder="2.145"></th>
                </tr>
                <tr>
                    <th>Rest Day Regular Holiday</th>
                    <th><input type="number" placeholder="2.600"></th>
                    <th><input type="number" placeholder="3.380"></th>
                    <th><input type="number" placeholder="2.860"></th>
                    <th><input type="number" placeholder="3.719"></th>
                </tr>
                <tr>
                    <th>Rest Day Double Holiday</th>
                    <th><input type="number" placeholder="3.000"></th>
                    <th><input type="number" placeholder="3.900"></th>
                    <th><input type="number" placeholder="3.300"></th>
                    <th><input type="number" placeholder="4.290"></th>
                </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <!-- Footer -->
      <footer class="content-footer footer bg-footer-theme">
        <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
          <!-- Add footer content if needed -->
        </div>
      </footer>
      <!-- / Footer -->
    </div>
  </div>
  <script src="assets/vendor/libs/jquery/jquery.js"></script>
        <script src="assets/vendor/libs/popper/popper.js"></script>
        <script src="assets/vendor/js/bootstrap.js"></script>
        <script src="assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

        <script src="assets/vendor/js/menu.js"></script>
        <!-- endbuild -->

        <!-- Vendors JS -->
        <script src="assets/vendor/libs/apex-charts/apexcharts.js"></script>

        <!-- Main JS -->
        <script src="assets/js/main.js"></script>

        <!-- Page JS -->
        <script src="assets/js/dashboards-analytics.js"></script>

        <!-- Place this tag in your head or just before your close body tag. -->
        <script async defer src="https://buttons.github.io/buttons.js"></script>


        <!--jQuery-->
              <script src="lib/jquery/jquery-3.7.1.js"></script>
              <!--DataTables-->
              <script src="lib/datatables/dataTables.js"></script>
              <script>
                  //Dropdown Selection Highlight
                  const dropdownItems = document.querySelectorAll('.dropdown-item');
                  const dropdownButton = document.getElementById('dropdownMenuButton');

                  const selectedOptions = {
                  group1: null,
                  group2: null
                  };
                  dropdownItems.forEach(item => {
                  item.addEventListener('click', (e) => {
                  e.preventDefault();

                  const group = item.getAttribute('data-group');
                  const value = item.getAttribute('data-value');

                // Deselect previously selected option in the same group
                  dropdownItems.forEach(option => {
                  if (option.getAttribute('data-group') === group) {
                  option.classList.remove('selected');
                  }
                });

                // Select the clicked option
                  item.classList.add('selected');
                  selectedOptions[group] = value;

                // Update dropdown button text
                  const selectedText = Object.values(selectedOptions)
                  .filter(val => val)
                  .map(val => val.replace('option', 'Option '))
                  .join(', ');

                 });
                });
    // Initial render
    renderPage();
            $(document).ready(function() {
                $('#myTable').DataTable({

                    });
                });



              </script>
</body>

</html>
