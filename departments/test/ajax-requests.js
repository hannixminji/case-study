function fetchAllDepartments() {
    $.ajax({
        url: 'apiTest.php',
        method: 'POST',
        data: {
            action: 'fetchAll'
        },
        dataType: 'html',
        success(response) {
            $('#departments').html(response);
        },
        error(xhr, status, error) {
            console.error("Error fetching departments:", error);
        }
    });
}

function createDepartment() {
    const departmentName = document.getElementById('departmentName').value;
    const departmentHeadId = document.getElementById('departmentHeadId').value;

    const departmentData = {
        name: departmentName,
        departmentHeadId
    };

    $.ajax({
        url: 'apiTest.php',
        method: 'POST',
        data: {
            action: 'create',
            department: departmentData
        },
        success(response) {
            fetchAllDepartments();
            document.getElementById('createDepartmentForm').reset();
        },
        error(xhr, status, error) {
            console.error("Error creating department:", error);
        }
    });
}

function updateDepartment(departmentData) {
    $.ajax({
        url: 'apiTest.php',
        method: 'POST',
        data: {
            action: 'update',
            department: departmentData
        },
        success(response) {
            fetchAllDepartments();
        },
        error(xhr, status, error) {
            console.error("Error updating department:", error);
        }
    });
}

function deleteDepartment(departmentId) {
    $.ajax({
        url: 'apiTest.php',
        method: 'POST',
        data: {
            action: 'delete',
            department_id: departmentId
        },
        success(response) {
            fetchAllDepartments();
        },
        error(xhr, status, error) {
            console.error("Error deleting department:", error);
        }
    });
}
