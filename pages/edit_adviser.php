<?php
session_start();
include '../config/db_conn.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {

    function deleteAdviser($conn, $id)
    {
        try {
            // Prepare SQL statement
            $deleteSQL = "DELETE FROM advisers WHERE id = ?";
            $stmt = $conn->prepare($deleteSQL);

            // Bind parameters and execute the statement
            $stmt->execute([$id]);

            // Check if any rows were affected
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                echo "Adviser deleted successfully";
            } else {
                echo "No adviser found with the provided ID";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }


    function registerAdviser($conn, $gradeLevel, $section, $full_name, $email, $id)
    {

        if (isSectionDuplicate($conn, $section)) {
            echo "There is an Adviser already assigned to " . $section . ".";
        } else {

            // Prepare the SQL statement with placeholders
            $registerSQL = "UPDATE advisers SET gradeLevel=?, section=?, full_name=?, email=? WHERE id=?";

            try {
                $conn->beginTransaction();

                // Prepare the statement
                $stmt = $conn->prepare($registerSQL);

                // Bind parameters and execute the statement
                $stmt->execute([$gradeLevel, $section, $full_name, $email, $id]);

                // Commit the transaction
                $conn->commit();

                echo "Adviser updated successfully";
            } catch (PDOException $e) {
                $conn->rollBack();
                echo "Error: " . $e->getMessage();
            }
        }
    }

    function isSectionDuplicate($conn, $section)
    {
        $columnName = 'section';

        // Prepare the SQL query with a WHERE clause to exclude the current section
        $sql = "SELECT $columnName FROM advisers WHERE $columnName != :section";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':section', $section);
        $stmt->execute();

        if ($stmt !== false) {
            // Fetching data and checking for duplicates
            $found = false;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row[$columnName] == $section) {
                    $found = true;
                    break; // No need to continue once a duplicate is found
                }
            }
            return $found;
        } else {
            echo "Error executing query: " . $conn->errorInfo()[2];
            return false;
        }
    }


    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    } else {
        echo "ID parameter is missing";
        header('Location: register_adviser.php');
    }

    if (isset($_POST['registerButton'])) {
        $gradeLevel = $_POST['gradeLevelInput'];
        $section = $_POST['sectionInput'];
        $full_name = $_POST['full_nameInput'];
        $email = $_POST['emailInput'];


        registerAdviser($connect, $gradeLevel, $section, $full_name, $email, $id);
        header('Location: register_adviser.php');
    }

    if (isset($_POST['deleteButton'])) {
        deleteAdviser($connect, $id);
        header('Location: register_adviser.php');
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
                <h1>ADMIN INTERFACE > REGISTER ADVISER > EDIT</h1>
            </div>
            <div class="navbar-right">
                <ul>
                    <li><a href="register_adviser.php">BACK</a></li>
                    <li><a href="../scripts/logout.php">LOGOUT</a></li>
                </ul>
            </div>
        </nav>
        <div class="main-container glass">

            <?php
            $sql = "SELECT * FROM advisers WHERE id=?";
            $stmt = $connect->prepare($sql);
            $stmt->execute([$id]);
            $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($adviser) {
                $gradeLevel = $adviser['gradeLevel'];
                $section = $adviser['section'];
                $full_name = $adviser['full_name'];
                $email = $adviser['email'];
            }

            ?>


            <h1>EDITING ADVISER: <?php echo $full_name; ?></h1>


            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $id; ?>">

                <table>
                    <tr>
                        <th>Grade Level</th>
                        <th>Section</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                    </tr>

                    <tr>
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
                        <?php
                        echo '
                            <td><input required type="text" name="full_nameInput" value="' . $full_name . '"></td>
                            <td><input required type="email" name="emailInput" id="" value="' . $email . '"></td>
                            ';
                        ?>

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