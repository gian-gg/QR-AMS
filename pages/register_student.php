<?php
session_start();
include '../config/db_conn.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {

    function isDuplicate($conn, $value, $columnName)
    {
        $sql = "SELECT $columnName FROM registeredStudents";
        $stmt = $conn->query($sql);

        if ($stmt !== false) {
            // Fetching data and checking for duplicates
            $found = false;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row[$columnName] == $value) {
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

    function isValidPhoneNumber($phoneNumber)
    {
        // Remove non-numeric characters
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        // Check if the number is of valid length and starts with a valid country code
        if (strlen($phoneNumber) >= 10 && preg_match('/^\+?\d{10,}$/', $phoneNumber)) {
            return true;
        } else {
            return false;
        }
    }

    function registerStudent($conn, $phoneNumber, $gradeLevel, $section, $lrn, $full_name, $gender, $email, $password)
    {
        if (isDuplicate($conn, $lrn, 'id')) {
            echo "There is a Student already assigned to " . $lrn . ".";
        } else {
            if (strlen($lrn) === 12) {
                if (isValidPhoneNumber($phoneNumber)) {
                    if (isDuplicate($conn, $phoneNumber, 'phoneNumber')) {
                        echo "There is already a Student assigned to Phone Number: +63" . $phoneNumber . ".";
                    } else {
                        // Hash the password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                        // Prepare the SQL statement with placeholders
                        $registerSQL = "INSERT INTO registeredStudents (phoneNumber, grade_level, section, id, full_name, gender, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                        try {
                            $conn->beginTransaction();

                            // Prepare the statement
                            $stmt = $conn->prepare($registerSQL);

                            // Bind parameters and execute the statement
                            $stmt->execute([$phoneNumber, $gradeLevel, $section, $lrn, $full_name, $gender, $email, $hashedPassword]);

                            // Commit the transaction
                            $conn->commit();

                            echo "Student registered successfully";
                        } catch (PDOException $e) {
                            $conn->rollBack();
                            echo "Error: " . $e->getMessage();
                        }
                    }
                } else {
                    echo "Invalid Phone Number";
                }
            } else {
                echo "Invalid LRN.";
            }
        }
    }


?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR-AMS | ADMIN > REGISTER STUDENT</title>

        <link rel="icon" type="image/x-icon" href="../assets/images/icon.png">

        <link rel="stylesheet" href="../assets/css/register.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/nav.css">
        <link rel="stylesheet" href="../assets/css/glass.css">
        <link rel="stylesheet" href="../assets/css/template.css">
    </head>

    <body>
        <nav class="navbar">
            <div class="navbar-left">
                <h1>ADMIN INTERFACE > REGISTER STUDENT</h1>
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

            <div class="container container1">
                <h1 class="container-h1">REGISTER STUDENT</h1>

                <p id="error">

                    <?php

                    if (isset($_POST['registerButton'])) {
                        $phoneNumber = $_POST['phoneNumberInput'];
                        $gradeLevel = $_POST['gradeLevelInput'];
                        $section = $_POST['sectionInput'];
                        $lrn = $_POST['lrnInput'];
                        $full_name = $_POST['full_nameInput'];
                        $gender = $_POST['genderInput'];
                        $email = $_POST['emailInput'];
                        $password = $_POST['passwordInput'];

                        registerStudent($connect, $phoneNumber, $gradeLevel, $section, $lrn, $full_name, $gender, $email, $password);
                    }

                    ?>

                </p>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                    <div class="gradeLevel-section">
                        <select class="register_student" required name="genderInput" id="gender">
                            <option disabled selected hidden value="">Sex: </option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>

                        <select class="register_student" required name="gradeLevelInput" id="gradeLevel">
                            <option disabled selected hidden value="">Grade:</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>

                        <select class="register_student" required name="sectionInput" id="section">
                            <option disabled selected hidden value="">Section:</option>
                            <optgroup label="Grade 7">
                                <option value="hydrogen">Hydrogen</option>
                                <option value="carbon">Carbon</option>
                                <option value="oxygen">Oxygen</option>
                            </optgroup>
                            <optgroup label="Grade 8">
                                <option value="mollusca">Mollusca</option>
                                <option value="chordata">Chordata</option>
                                <option value="arthropoda">Arthropoda</option>
                                <option value="porifera">Porifera</option>
                            </optgroup>
                            <optgroup label="Grade 9">
                                <option value="dalton">Dalton</option>
                                <option value="mendeleev">Mendeleev</option>
                                <option value="thomson">Thomson</option>
                            </optgroup>
                            <optgroup label="Grade 10">
                                <option value="photon">Photon</option>
                                <option value="graviton">Graviton</option>
                                <option value="neutron">Neutron</option>
                            </optgroup>
                            <optgroup label="Grade 11">
                                <option value="mars">Mars</option>
                                <option value="venus">Venus</option>
                                <option value="mercury">Mercury</option>
                                <option value="earth">Earth</option>
                            </optgroup>
                            <optgroup label="Grade 12">
                                <option value="gemini">Gemini</option>
                                <option value="aries">Aries</option>
                                <option value="scorpio">Scorpio</option>
                                <option value="capricorn">Capricorn</option>
                                <option value="pisces">Pisces</option>
                            </optgroup>
                        </select>

                    </div>


                    <input required type="number" name="phoneNumberInput" id="" placeholder="Phone Number: 10 digits... ">
                    <input required type="number" name="lrnInput" id="" placeholder="LRN: ">
                    <input required type="text" name="full_nameInput" id="" placeholder="Full Name: ">
                    <input required type="email" name="emailInput" id="" placeholder="Email: ">

                    <input required type="password" name="passwordInput" id="passwordInput" placeholder="Password: ">

                    <div id="show_password">
                        <input type="checkbox" onclick="myFunction()">
                        <p>Show Password</p>
                    </div>


                    <button type="submit" name="registerButton">REGISTER</button>


                </form>
            </div>
            <div class="container container2">

                <div class="advisers-list">
                    <h1>REGISTERED STUDENTS</h1>

                    <table>
                        <tr>
                            <th>Phone Number</th>
                            <th>Grade Level</th>
                            <th>Section</th>
                            <th>LRN</th>
                            <th>Full Name</th>
                            <th>Sex</th>
                            <th></th>
                        </tr>
                        <?php
                        $sql = "SELECT * FROM registeredStudents  ORDER BY grade_level ASC, full_name ASC";
                        $stmt = $connect->prepare($sql);
                        $stmt->execute();
                        $advisers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($advisers) {
                            foreach ($advisers as $row) {
                                // Check if the ID is equal to the one you want to exclude
                                if ($row['id'] != 98765432100) {
                                    echo '
                                        <tr>
                                            <td>' . $row['phoneNumber'] . '</td>
                                            <td>' . $row['grade_level'] . '</td>
                                            <td>' . $row['section'] . '</td>
                                            <td>' . $row['id'] . '</td>
                                            <td>' . $row['full_name'] . '</td>
                                            <td>' . $row['gender'] . '</td>
                                            <td>
                                                <a href="edit_student.php?id=' . $row['id'] . '"><img id="edit_icon" src="../assets/images/edit.png" alt=""></a>
                                            </td>
                                        </tr>
                                    ';
                                }
                            }
                        }

                        echo '

                        <p id="error">

                        No. of Students:

                        ' . count($advisers) . '
                        
                        
                        </p>

                        
                        ';
                        ?>

                    </table>
                </div>




            </div>

        </div>

        <div class="footer">
            <img src="../assets/images/gg.png" alt="gian.gg logo">
            <hr id="vertical-hr">
            <a href="https://github.com/spookyexe" target="_blank">© GIAN EPANTO, 2024</a>

        </div>

        <p id="disclaimer">In Partial fulfillment Of the requirements for the Strand Science, Technology, Engineering, Mathematics. <a href="about.php">Learn more</a></p>

        <script src="../assets/js/show_password.js"></script>
    </body>

    </html>
<?php
} else {
    header("Location: login.php");
}
?>