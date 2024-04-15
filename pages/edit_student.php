<?php
session_start();
include '../config/db_conn.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {

    function deleteStudent($conn, $id)
    {
        try {
            // Prepare SQL statement
            $deleteSQL = "DELETE FROM registeredStudents WHERE id = ?";
            $stmt = $conn->prepare($deleteSQL);

            // Bind parameters and execute the statement
            $stmt->execute([$id]);

            // Check if any rows were affected
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                echo "Student deleted successfully";
            } else {
                echo "No Student found with the provided ID";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    function updateStudent($conn, $phoneNumber, $gradeLevel, $section, $lrn, $full_name, $gender, $id)
    {

        // Prepare the SQL statement with placeholders
        $registerSQL = "UPDATE registeredStudents SET phoneNumber=?, grade_level=?, section=?, full_name=?, gender=?, id=? WHERE id=?";

        try {
            $conn->beginTransaction();

            // Prepare the statement
            $stmt = $conn->prepare($registerSQL);

            // Bind parameters and execute the statement
            $stmt->execute([$phoneNumber, $gradeLevel, $section, $full_name, $gender, $lrn, $id]);

            // Commit the transaction
            $conn->commit();

            echo "Student updated successfully";
        } catch (PDOException $e) {
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }
    }

    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    } else {
        header('Location: register_student.php');
    }

    if (isset($_POST['registerButton'])) {
        $phoneNumber = $_POST['phoneNumberInput'];
        $gradeLevel = $_POST['gradeLevelInput'];
        $section = $_POST['sectionInput'];
        $lrn = $_POST['lrnInput'];
        $full_name = $_POST['full_nameInput'];
        $gender = $_POST['genderInput'];


        updateStudent($connect, $phoneNumber, $gradeLevel, $section, $lrn, $full_name, $gender, $id);
        header('Location: register_student.php');
    }

    if (isset($_POST['deleteButton'])) {
        deleteStudent($connect, $id);
        header('Location: register_student.php');
    }

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR-AMS | EDIT ADVISER</title>

        <link rel="icon" type="image/x-icon" href="../assets/images/icon.png">

        <link rel="stylesheet" href="../assets/css/edit.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/nav.css">
        <link rel="stylesheet" href="../assets/css/glass.css">
        <link rel="stylesheet" href="../assets/css/template.css">
    </head>

    <body>
        <nav class="navbar">
            <div class="navbar-left">
                <h1>ADMIN INTERFACE > REGISTER STUDENT > EDIT</h1>
            </div>
            <div class="navbar-right">
                <ul>
                    <li><a href="register_student.php">BACK</a></li>
                    <li><a href="../scripts/logout.php">LOGOUT</a></li>
                </ul>
            </div>
        </nav>
        <div class="main-container glass">

            <?php
            $sql = "SELECT * FROM registeredStudents WHERE id=?";
            $stmt = $connect->prepare($sql);
            $stmt->execute([$id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student) {
                $phoneNumber = $student['phoneNumber'];
                $gradeLevel = $student['grade_level'];
                $section = $student['section'];
                $lrn = $student['id'];
                $full_name = $student['full_name'];
                $gender = $student['gender'];
            }

            ?>


            <h1>EDITING STUDENT: <?php echo $full_name; ?></h1>


            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $id; ?>">

                <table>
                    <tr>
                        <th>Phone Number</th>
                        <th>Grade Level</th>
                        <th>Section</th>
                        <th>LRN</th>
                        <th>Full Name</th>
                        <th>Sex</th>
                    </tr>

                    <tr>
                        <td><input required type="number" name="phoneNumberInput" value="<?php echo $phoneNumber; ?>"></td>

                        <td>
                            <select required name="gradeLevelInput" id="gradeLevel">
                                <option value="7" <?php if ($gradeLevel == '7') echo 'selected'; ?>>7</option>
                                <option value="8" <?php if ($gradeLevel == '8') echo 'selected'; ?>>8</option>
                                <option value="9" <?php if ($gradeLevel == '9') echo 'selected'; ?>>9</option>
                                <option value="10" <?php if ($gradeLevel == '10') echo 'selected'; ?>>10</option>
                                <option value="11" <?php if ($gradeLevel == '11') echo 'selected'; ?>>11</option>
                                <option value="12" <?php if ($gradeLevel == '12') echo 'selected'; ?>>12</option>
                            </select>
                        </td>
                        <td>
                            <select required name="sectionInput" id="section">
                                <?php
                                $grade_sections = [
                                    7 => ["hydrogen", "carbon", "oxygen"],
                                    8 => ["mollusca", "chordata", "arthropoda", "porifera"],
                                    9 => ["dalon", "mendeleev", "thomson"],
                                    10 => ["photon", "graviton", "neutron"],
                                    11 => ["mars", "venus", "mercury", "earth"],
                                    12 => ["gemini", "aries", "scorpio", "capricorn", "pisces"]
                                ];

                                foreach ($grade_sections as $grade => $sections) {
                                    echo '<optgroup label="Grade ' . $grade . '">';
                                    foreach ($sections as $section_value) {
                                        $selected = ($gradeLevel == $grade && $section == $section_value) ? 'selected' : '';
                                        echo '<option value="' . $section_value . '" ' . $selected . '>' . ucfirst($section_value) . '</option>';
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>

                        </td>

                        <td><input required type="number" name="lrnInput" value="<?php echo $id; ?>"></td>
                        <td><input required type="text" name="full_nameInput" id="" value="<?php echo $full_name; ?>"></td>

                        <td>
                            <select required name="genderInput" id="gender">
                                <option value="female" <?php if ($gender == 'female') echo 'selected'; ?>>Female</option>
                                <option value="male" <?php if ($gender == 'male') echo 'selected'; ?>>Male</option>
                            </select>
                        </td>

                    </tr>

                </table>

                <button type="submit" name="registerButton">UPDATE</button>
                <button id="deleteButton" type="submit" name="deleteButton">DELETE</button>

            </form>



        </div>

        <div class="footer">
            <img src="../assets/images/gg.png" alt="gian.gg logo">
            <hr id="vertical-hr">
            <a href="https://github.com/spookyexe" target="_blank">© GIAN EPANTO, 2023</a>

        </div>

        <p id="disclaimer">In Partial fulfillment Of the requirements for the Strand Science, Technology, Engineering, Mathematics. <a href="about.php">Learn more</a></p>
    </body>

    </html>
<?php
} else {
    header("Location: login.php");
}
?>