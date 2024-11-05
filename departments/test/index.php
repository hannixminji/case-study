<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="ajax-requests.js"></script>
</head>
<body>

    <h1>Department Management</h1>
    <button onclick="fetchAllDepartments()">Fetch All Departments</button>
    <div id="departments"></div>

    <div style="font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4;">
        <h2 style="color: #333;">Create New Department</h2>
        <form id="createDepartmentForm" onsubmit="event.preventDefault(); createDepartment();">
            <div>
                <label for="departmentName" style="display: block; margin-bottom: 5px;">Department Name:</label>
                <input type="text" id="departmentName" name="departmentName" required style="padding: 10px; width: 100%; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label for="departmentHeadId" style="display: block; margin-bottom: 5px;">Department Head ID:</label>
                <input type="number" id="departmentHeadId" name="departmentHeadId" required style="padding: 10px; width: 100%; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <button type="submit" style="padding: 10px 15px; cursor: pointer; background-color: #007bff; color: white; border: none; border-radius: 4px;">Create Department</button>
        </form>

        <div id="createMessage" style="font-weight: bold;"></div>
    </div>

</body>
</html>
