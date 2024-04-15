<?php
session_start();
include '../config/db_conn.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {

    function isSectionDuplicate($conn, $section)
    {
        $columnName = 'section';

        $sql = "SELECT $columnName FROM advisers";
        $stmt = $conn->query($sql);

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

    function isStrongPassword($password)
    {
        // Minimum length requirement
        $min_length = 8;

        // Check for presence of uppercase letters, lowercase letters, numbers, and special characters
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $special   = preg_match('/[^A-Za-z0-9]/', $password);

        // Check if all criteria are met
        if (!$uppercase || !$lowercase || !$number || !$special || strlen($password) < $min_length) {
            return false;
        }

        return true;
    }

    function generateID()
    {
        // Get current timestamp
        $timestamp = time();

        // Generate a random number with 6 digits
        $random_number = mt_rand(100000, 999999);

        // Concatenate timestamp and random number
        $id = $timestamp . $random_number;

        // If the generated ID is longer than 12 digits, trim it to 12 digits
        if (strlen($id) > 12) {
            $id = substr($id, 0, 12);
        }

        return $id;
    }

    function registerAdviser($conn, $gradeLevel, $section, $full_name, $email, $password)
    {

        if (isSectionDuplicate($conn, $section)) {
            echo "There is an Adviser already assigned to " . $section . ".";
        } else {

            // Validate password strength
            if (!isStrongPassword($password)) {
                echo "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
                return;
            }

            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $id = generateID();

            // Prepare the SQL statement with placeholders
            $registerSQL = "INSERT INTO advisers (id, gradeLevel, section, full_name, email, password) VALUES (?, ?, ?, ?, ?, ?)";

            try {
                $conn->beginTransaction();

                // Prepare the statement
                $stmt = $conn->prepare($registerSQL);

                // Bind parameters and execute the statement
                $stmt->execute([$id, $gradeLevel, $section, $full_name, $email, $hashed_password]);

                // Commit the transaction
                $conn->commit();

                echo "Adviser registered successfully";
            } catch (PDOException $e) {
                $conn->rollBack();
                echo "Error: " . $e->getMessage();
            }
        }
    }

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR-AMS | ADMIN > REGISTER ADVISER</title>

        <link rel="icon" type="image/x-icon" href="../assets/images/icon.png">

        <!-- <link rel="stylesheet" href="../assets/css/register_adviser.css"> -->
        <link rel="stylesheet" href="../assets/css/register.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/nav.css">
        <link rel="stylesheet" href="../assets/css/glass.css">
        <link rel="stylesheet" href="../assets/css/template.css">
    </head>

    <body>
        <nav class="navbar">
            <div class="navbar-left">
                <h1>ADMIN INTERFACE > REGISTER ADVISER</h1>
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
                <h1 class="container-h1">REGISTER ADVISER</h1>

                <p id="error">

                    <?php

                    if (isset($_POST['registerButton'])) {
                        $gradeLevel = $_POST['gradeLevelInput'];
                        $section = $_POST['sectionInput'];
                        $full_name = $_POST['full_nameInput'];
                        $email = $_POST['emailInput'];
                        $password = $_POST['passwordInput'];

                        registerAdviser($connect, $gradeLevel, $section, $full_name, $email, $password);
                    }

                    ?>

                </p>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                    <div class="gradeLevel-section">
                        <select class="register_adviser" required name="gradeLevelInput" id="gradeLevel">
                            <option disabled selected hidden value="">Grade:</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>

                        <select class="register_adviser" required name="sectionInput" id="section">
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
                                <option value="dalon">Dalton</option>
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
                    <h1>REGISTERED ADVISERS</h1>

                    <table>
                        <tr>
                            <th>Grade Level</th>
                            <th>Section</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th></th>
                        </tr>
                        <?php
                        $sql = "SELECT * FROM advisers  ORDER BY gradeLevel ASC";
                        $stmt = $connect->prepare($sql);
                        $stmt->execute();
                        $advisers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($advisers) {
                            foreach ($advisers as $row) {
                                // Check if the ID is equal to the one you want to exclude
                                if ($row['id'] != 98765432100) {
                                    echo '
                                        <tr>
                                            <td>' . $row['gradeLevel'] . '</td>
                                            <td>' . $row['section'] . '</td>
                                            <td>' . $row['full_name'] . '</td>
                                            <td>' . $row['email'] . '</td>
                                            <td>
                                                <a href="edit_adviser.php?id=' . $row['id'] . '"><img id="edit_icon" src="../assets/images/edit.png" alt=""></a>
                                            </td>
                                        </tr>
                                    ';
                                }
                            }

                            // Minus 1 because of Admin Account
                            $adviserCount = count($advisers) - 1;

                            echo '

                            <p id="error">
    
                            No. of Advisers:
    
                            ' . $adviserCount . '
                            
                            
                            </p>
    
                            
                            ';
                        }
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