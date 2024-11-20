<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expandable Table Row</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse; /* Ensures cells share borders */
            border-spacing: 0; /* Removes extra spacing between cells */
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f4f4f4;
        }
        .hidden-row table {
            margin: 0; /* Removes gaps caused by margins on nested tables */
        }
        .hidden-row {
            display: none; /* Initially hidden */
        }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Age</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>John Doe</td>
                <td>30</td>
                <td><button class="expand-btn">Expand</button></td>
            </tr>
            <tr class="hidden-row">
                <td colspan="3">
                    <table>
                        <thead>
                            <tr>
                                <th>Sub Item</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Detail 1</td>
                                <td>More info about John</td>
                            </tr>
                            <tr>
                                <td>Detail 2</td>
                                <td>Additional data</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <script>
        document.querySelectorAll('.expand-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const hiddenRow = btn.closest('tr').nextElementSibling;

                // Toggle visibility
                if (hiddenRow.style.display === 'none' || hiddenRow.style.display === '') {
                    hiddenRow.style.display = 'table-row';
                    btn.textContent = 'Collapse'; // Update button text
                } else {
                    hiddenRow.style.display = 'none';
                    btn.textContent = 'Expand'; // Update button text
                }
            });
        });
    </script>
</body>
</html>
