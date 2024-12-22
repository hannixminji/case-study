<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <script src="js/sweetalert2.all.min.js"></script>
    <link href="js/fullcalendar/lib/main.css" rel="stylesheet" />
    <script src="js/fullcalendar/lib/main.js"></script>
</head>
<body>
    <div class="container">
        <div class="wrapper">
            <div id="calendar"></div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var calendarEl = document.getElementById("calendar");
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: "dayGridMonth",
                height: 650,
                holidays: "fetchHolidays.php",
            });
        });
    </script>
</body>
</html>
