<?php
session_start();
include 'config/db_conn.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    // Function to get students from database by gender
    function getStudentsByGender($connect, $gradeLevel, $section, $gender)
    {
        $stmt = $connect->prepare("SELECT * FROM registeredStudents WHERE grade_level=? AND section=? AND gender=? ORDER BY full_name ASC");
        $stmt->execute([$gradeLevel, $section, $gender]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getGreeting()
    {
        date_default_timezone_set("Asia/Manila");
        $today = date("F j, Y | g:i A");
        $hour = date('H');

        if ($hour >= 17 || $hour < 5) {
            $dayTerm = "Evening";
        } elseif ($hour >= 12) {
            $dayTerm = "Afternoon";
        } else {
            $dayTerm = "Morning";
        }

        $name = $_SESSION['user_full_name'];

        return "<h1>Good $dayTerm, $name</h1><p>$today</p>";
    }


    // Function to render a student table
    function renderStudentTable($students, $connect)
    {
        $html = '<table>';
        $html .= '<tr><th>Name</th><th>Time 1</th><th>Time 2</th><th>Time 3</th><th>Time 4</th></tr>';
        foreach ($students as $student) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($student['full_name']) . '</td>';

            $id = $student['id'];

            $stmt2 = $connect->prepare("SELECT * FROM attendance WHERE id=?");
            $stmt2->execute([$id]);
            $attendances = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($attendances)) {

                foreach ($attendances as $attendance) {
                    $time_one = $attendance['time_one'];
                    $time_two = $attendance['time_two'];
                    $time_three = $attendance['time_three'];
                    $time_four = $attendance['time_four'];

                    $html .= '<td class="attendanceData">' . htmlspecialchars($time_one) . '</td>';
                    $html .= '<td class="attendanceData">' . htmlspecialchars($time_two) . '</td>';
                    $html .= '<td class="attendanceData">' . htmlspecialchars($time_three) . '</td>';
                    $html .= '<td class="attendanceData">' . htmlspecialchars($time_four) . '</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    // Generate the greeting
    $greeting = getGreeting();

    $gradeLevel = $_SESSION['user_gradeLevel'];
    $section = $_SESSION['user_section'];

    // Get male and female students
    $maleStudents = getStudentsByGender($connect, $gradeLevel, $section, 'male');
    $femaleStudents = getStudentsByGender($connect, $gradeLevel, $section, 'female');

    if (isset($_POST['sf2'])) {
        // File to download
        $fileName = '[' . $gradeLevel . '] - ' . ucfirst($section);
        $file = 'SF2/' . $fileName . '.xls';

        include 'scripts/sf2.php';
        // Check if the file exists
        if (file_exists($file)) {

            // Set headers to force file download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        } else {
            echo "NO DATA FOUND.";
        }
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR-AMS | <?= $_SESSION['user_gradeLevel'] ?> <?= strtoupper($_SESSION['user_section']) ?></title>

        <link rel="icon" type="image/x-icon" href="assets/images/icon.png">

        <link rel="stylesheet" href="assets/css/index.css">
        <link rel="stylesheet" href="assets/css/footer.css">
        <link rel="stylesheet" href="assets/css/nav.css">
        <link rel="stylesheet" href="assets/css/glass.css">
        <link rel="stylesheet" href="assets/css/template.css">

    </head>

    <body>
        <nav class="navbar">
            <div class="navbar-left">
                <h1><?= $_SESSION['user_gradeLevel'] ?> - <?= strtoupper($_SESSION['user_section']) ?> ATTENDANCE</h1>
            </div>
            <div class="navbar-right">
                <ul>
                    <li><a href="scripts/logout.php">LOGOUT</a></li>
                </ul>
            </div>
        </nav>
        <div class="main-container glass">
            <div class="container1">
                <div class="greetings">
                    <?= $greeting ?>
                </div>
                <div class="buttons">
                    <ul>
                        <form method="post">
                            <button type="submit" name="sf2">SF2</button>
                        </form>

                        <li><button onclick="refreshPage()"><img src="assets/images/refresh.svg" alt=""></button></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="container2">

                <?php

                $registeresSQL = "SELECT * FROM registeredStudents WHERE section=?";
                $attenanceSQL = "SELECT * FROM attendance  WHERE section=?";

                $section = $_SESSION['user_section'];

                $Rstmt = $connect->prepare($registeresSQL);
                $Astmt = $connect->prepare($attenanceSQL);

                $Rstmt->execute([$section]);
                $Astmt->execute([$section]);

                $RrowCount = $Rstmt->rowCount();
                $ArowCount = $Astmt->rowCount();

                echo '

                <p id="alert"> Total Present: ' . $ArowCount . ' | Total Students: ' . $RrowCount . '</p>

                ';
                ?>


                <div class="male-table">
                    <?= renderStudentTable($maleStudents, $connect) ?>
                </div>
                <div class="female-table">
                    <?= renderStudentTable($femaleStudents, $connect) ?>
                </div>
            </div>
            <hr>
        </div>
        <div class="footer">
            <img src="assets/images/gg.png" alt="gian.gg logo">
            <hr id="vertical-hr">
            <a href="https://github.com/spookyexe" target="_blank">© GIAN EPANTO, 2024</a>

        </div>

        <p id="disclaimer">In Partial fulfillment Of the requirements for the Strand Science, Technology, Engineering, Mathematics. <a href="pages/about.php">Learn more</a></p>
        <script src="./assets/js/refresh_page.js"></script>
    </body>

    </html>
<?php
} else {
    header("Location: pages/login.php");
}
?>