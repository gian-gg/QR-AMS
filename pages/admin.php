<?php
session_start();
include '../config/db_conn.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {

    function isTableEmpty($conn)
    {
        $selectSQL = "SELECT COUNT(*) AS count FROM attendance";
        $stmt = $conn->prepare($selectSQL);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if count is zero
        if ($result['count'] == 0) {
            return true; // Table is empty
        } else {
            return false; // Table is not empty
        }
    }


    function getFullName($conn, $id)
    {
        $sql = "SELECT full_name FROM registeredStudents WHERE id=?";

        $stmt = $conn->prepare($sql);

        $stmt->execute([$id]);

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Print the full_name if it exists
        if ($result) {
            return $result['full_name'];
        } else {
            return false;
        }
    }


    function archiveAttendance($conn)
    {

        $isEmpty = isTableEmpty($conn);
        if ($isEmpty) {
            echo "There are no data to archive.";
        } else {

            $selectSQL = "SELECT * FROM attendance";

            $stmt = $conn->prepare($selectSQL);

            // Execute the query
            $stmt->execute();

            // Fetch the result in a loop
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Get values from the fetched row

                $id = $row['id'];
                $gradeLevel = $row['gradeLevel'];
                $section = $row['section'];
                $time_one = $row['time_one'];
                $time_two = $row['time_two'];
                $time_three = $row['time_three'];
                $time_four = $row['time_four'];

                $currentDate = date('Y-m-d');
                $currentTime = date('H:i:s');

                // Insert fetched values into archive table
                $insertSQL = "INSERT INTO archive(archive_date, archive_time, id, gradeLevel, section, time_one, time_two, time_three, time_four) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt2 = $conn->prepare($insertSQL);

                $stmt2->execute([$currentDate, $currentTime, $id, $gradeLevel, $section, $time_one, $time_two, $time_three, $time_four]);

                $deleteSQL = "DELETE FROM attendance";

                $stmt3 = $conn->prepare($deleteSQL);

                $stmt3->execute();
            }

            echo "Successfully Archived Data.";
        }
    }


    function searchAttendance($conn, $dateInput, $searchInput)
    {

        if ($searchInput) {
            $currentDate = date('Y-m-d');

            $searchSQL = "SELECT id, gradeLevel, section, time_one, time_two, time_three, time_four FROM ";

            if ($dateInput === $currentDate) {
                $searchSQL .= "attendance";
                $searchSQL .= " WHERE id = ? OR section LIKE ? OR gradeLevel = ?";
            } else {
                $searchSQL .= "archive WHERE archive_date = ?";
                $searchSQL .= " AND (id = ? OR section LIKE ? OR gradeLevel = ?)";
            }

            $stmt = $conn->prepare($searchSQL);

            if ($dateInput !== $currentDate) {
                $stmt->execute([$dateInput, $searchInput, $searchInput, $searchInput]);
            } else {
                $stmt->execute([$searchInput, $searchInput, $searchInput]);
            }

            // Fetch all rows as an associative array
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($data) > 0) {
                foreach ($data as $row) {
                    $id = $row['id'];
                    $full_name = getFullName($conn, $id);

                    $time_one = $row["time_one"];
                    $time_two = $row["time_two"];
                    $time_three = $row["time_three"];
                    $time_four = $row["time_four"];

                    echo "
                        <tr>
                            <td>" . $id . "</td>
                            <td>" . $row['gradeLevel'] . "</td>
                            <td>" . $row['section'] . "</td>
                            <td>" . $full_name . "</td>
                            <td>" . $time_one . "</td>
                            <td>" . $time_two . "</td>
                            <td>" . $time_three . "</td>
                            <td>" . $time_four . "</td>
                        </tr>
                    ";
                }

                echo '
                    <p id="alert"> ' . count($data) . ' Student/s Found.</p>
                ';
            } else {
                echo '
                        <p id="alert">No data found.</p>
                    ';
            }
        } else {
            echo '
                    <p id="alert">Search Field Empty.</p>
                ';
        }
    }

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR-AMS | ADMIN</title>

        <link rel="icon" type="image/x-icon" href="../assets/images/icon.png">

        <link rel="stylesheet" href="../assets/css/admin.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/nav.css">
        <link rel="stylesheet" href="../assets/css/glass.css">
        <link rel="stylesheet" href="../assets/css/template.css">
    </head>

    <body>
        <nav class="navbar">
            <div class="navbar-left">
                <h1>ADMIN INTERFACE</h1>
            </div>
            <div class="navbar-right">
                <ul>
                    <li><a href="admin.php">DATA</a></li>
                    <li><a href="register_adviser.php">ADVISER</a></li>
                    <li><a href="register_student.php">STUDENT</a></li>
                    <li><a href="../scripts/logout.php">LOGOUT</a></li>
                </ul>
            </div>
        </nav>
        <div class="main-container glass">

            <div class="container1">

                <form id="archiveForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <button type="submit" name="archiveButton">ARCHIVE</button>
                </form>

                <p id="alert">

                    <?php
                    if (isset($_POST['archiveButton'])) {
                        archiveAttendance($connect);
                    }
                    ?>


                </p>


            </div>

            <div class="container2">

                <div class="search-container">

                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="date" name="dateInput" id="dateInput" value="<?php echo isset($_POST['dateInput']) ? htmlspecialchars($_POST['dateInput']) : date('Y-m-d'); ?>">
                        <input type="text" name="searchInput" id="searchInput" placeholder="Search by LRN, Grade Level, or Section..." value="<?php echo isset($_POST['searchInput']) ? htmlspecialchars($_POST['searchInput']) : ''; ?>">
                        <button type="submit" name="searchButton">SEARCH</button>
                    </form>


                    <hr>

                    <div class="table-container">
                        <table>
                            <tr>
                                <th>LRN</th>
                                <th>Grade Level</th>
                                <th>Section</th>
                                <th>Full Name</th>
                                <th>Time 1</th>
                                <th>Time 2</th>
                                <th>Time 3</th>
                                <th>Time 4</th>
                            </tr>

                            <?php
                            if (isset($_POST['searchButton'])) {
                                $dateInput = $_POST['dateInput'];
                                $searchInput = $_POST['searchInput'];

                                searchAttendance($connect, $dateInput, $searchInput);
                            }
                            ?>

                        </table>
                    </div>


                </div>

            </div>
        </div>
        </div>

        <div class="footer">
            <img src="../assets/images/gg.png" alt="gian.gg logo">
            <hr id="vertical-hr">
            <a href="https://github.com/spookyexe" target="_blank">© GIAN EPANTO, 2024</a>

        </div>
        <p id="disclaimer">In Partial fulfillment Of the requirements for the Strand Science, Technology, Engineering, Mathematics. <a href="about.php">Learn more</a></p>
    </body>

    </html>
<?php
} else {
    header("Location: login.php");
}
?>