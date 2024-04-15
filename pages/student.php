<?php
session_start();
include '../config/db_conn.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {

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

        return "<h1>$name</h1><p>$today</p>";
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR-AMS | <?= strtoupper($_SESSION['user_full_name']) ?></title>

        <link rel="icon" type="image/x-icon" href="../assets/images/icon.png">

        <link rel="stylesheet" href="../assets/css/student.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/nav.css">
        <link rel="stylesheet" href="../assets/css/glass.css">
        <link rel="stylesheet" href="../assets/css/template.css">
    </head>

    <body>
        <nav class="navbar">
            <div class="navbar-left">
                <h1><?= $_SESSION['user_gradeLevel'] ?> - <?= strtoupper($_SESSION['user_section']) ?></h1>
            </div>
            <div class="navbar-right">
                <ul>
                    <li><a href="../scripts/logout.php">LOGOUT</a></li>
                </ul>
            </div>
        </nav>
        <div class="main-container glass">
            <div class="container1">

                <?= getGreeting() ?>
                <p>
                    <?= $_SESSION['user_id'] ?>
                </p>
                <hr>
            </div>
            <div class="container2">

                <table>
                    <tr>
                        <th>Date</th>
                        <th>Time One</th>
                        <th>Time Two</th>
                        <th>Time Three</th>
                        <th>Time Four</th>
                    </tr>
                    <?php

                    $id = $_SESSION['user_id'];

                    $sql = "SELECT * FROM archive WHERE id=?";
                    $stmt = $connect->prepare($sql);

                    $stmt->execute([$id]);
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($data) {
                        foreach ($data as $row) {
                            echo '
                                    <tr>
                                        <td>' . $row['archive_date'] . '</td>
                                        <td>' . $row['time_one'] . '</td>
                                        <td>' . $row['time_two'] . '</td>
                                        <td>' . $row['time_three'] . '</td>
                                        <td>' . $row['time_four'] . '</td>
                                    </tr>
                                ';
                        }
                    }



                    ?>


                </table>



            </div>
        </div>
        <div class="footer">
            <img src="../assets/images/gg.png" alt="gian.gg logo">
            <hr id="vertical-hr">
            <a href="https://github.com/spookyexe" target="_blank">© GIAN EPANTO, 2024</a>

        </div>

        <p id="disclaimer">In Partial fulfillment Of the requirements for the Strand Science, Technology, Engineering, Mathematics. <a href="../pages/about.php">Learn more</a></p>
    </body>

    </html>
<?php
} else {
    header("Location: pages/login.php");
}
?>