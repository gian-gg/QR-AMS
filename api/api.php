<?php

include '../config/db_conn.php';

date_default_timezone_set('Asia/Manila');

// Your API key
$apiKey = '9ccb6fe7';

// Check if the provided API key matches the expected key
if (!isset($_GET['api_key']) || $_GET['api_key'] !== $apiKey) {
    echo json_encode(array('error' => 'Invalid API key'));
    exit;
}
// gets the data from a student using their respective LRNs (ID)
function getDataById($conn, $id)
{
    $selectSQL = "SELECT * FROM registeredStudents WHERE id=?";
    $stmt = $conn->prepare($selectSQL);

    $stmt->bindParam(1, $id);
    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    return $data;
}

// checks if ID is in the inputted table name
// used to check if LRN is valid and for inputting the Time 1
function isInTable($conn, $tableName, $id)
{
    $selectSQL = "SELECT * FROM $tableName WHERE id=?";
    $stmt = $conn->prepare($selectSQL);

    $stmt->bindParam(1, $id);
    $stmt->execute();

    $rowCount = $stmt->rowCount();

    if ($rowCount > 0) {
        return true;
    } else {
        return false;
    }
}

// check if time 2 and time 3 is filled in

function timeChecker($conn, $columnName, $id)
{
    $sql = "SELECT $columnName FROM attendance WHERE id=?";
    $stmt = $conn->prepare($sql);

    $stmt->bindParam(1, $id);
    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if any data was retrieved and if the column value is not "00:00:00"
    if ($data !== false && isset($data[$columnName]) && $data[$columnName] != "00:00:00") {
        return true;
    } else {
        return false;
    }
}



// logic of the system
function addAttendance($conn, $id, $grade_level, $section, $currentTime)
{
    try {
        $currentTimeField = '';
        $nextTimeField = '';
        $case = 0;
        $timePlaceHolder = '00:00:00';

        if (timeChecker($conn, 'time_four', $id)) {
            return "case 5"; // successful recording of time four
        } elseif (timeChecker($conn, 'time_three', $id)) {
            $currentTimeField = 'time_four';
            $nextTimeField = 'time_four';
            $case = 4;
        } elseif (timeChecker($conn, 'time_two', $id)) {
            $currentTimeField = 'time_three';
            $nextTimeField = 'time_four';
            $case = 3;
        } elseif (timeChecker($conn, 'time_one', $id)) {
            $currentTimeField = 'time_two';
            $nextTimeField = 'time_three';
            $case = 2;
        } else {
            $currentTimeField = 'time_one';
            $nextTimeField = 'time_two';
            $case = 1;
        }

        // Update the attendance record
        $SQL = "UPDATE attendance SET gradeLevel=?, section=?, $currentTimeField=? WHERE id=?";
        $stmt = $conn->prepare($SQL);
        $stmt->execute([$grade_level, $section, $currentTime, $id]);

        // If it's the first time, also update the next time field
        if ($case == 1) {
            $SQL = "INSERT INTO attendance(id, gradeLevel, section, time_one, time_two, time_three, time_four) VALUES(?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($SQL);

            $stmt->execute([$id, $grade_level, $section, $currentTime, $timePlaceHolder, $timePlaceHolder, $timePlaceHolder]);
        }

        return "case $case"; // successful recording
    } catch (Exception $e) {
        return $e->getMessage(); // Return the error message
    }
}



// run function
function attendance($conn, $id)
{
    if (isInTable($conn, 'registeredStudents', $id)) {

        $studentData = getDataById($conn, $id);

        $phone_number = '+63' . $studentData["phoneNumber"];
        $full_name = $studentData["full_name"];
        $grade_level = $studentData["grade_level"];
        $section = $studentData["section"];

        $full_name = $studentData["full_name"];

        $currentTime = date("H:i:s");

        $addAttendance = addAttendance($conn, $id, $grade_level, $section, $currentTime);

        // return array("msg" => $addAttendance);

        if ($addAttendance === 'case 1') { // time one
            return array('case' => "1", 'message' => "Time One has been Recorded.", 'currentTime' => $currentTime, 'full_name' => $full_name, 'grade_level' => $grade_level, 'section' => $section, 'phone_number' => $phone_number, "SMS" => "
            Status: Student Arrival
            Student: " . $full_name . "
            Time: " . $currentTime);
        } elseif ($addAttendance === 'case 2') { // time two
            return array('case' => "1", 'message' => "Time Two has been Recorded.", 'currentTime' => $currentTime, 'full_name' => $full_name, 'grade_level' => $grade_level, 'section' => $section, 'phone_number' => $phone_number, "SMS" => "
            Status: Student Departure
            Student: " . $full_name . "
            Time: " . $currentTime);
        } elseif ($addAttendance === 'case 3') { // time three
            return array('case' => "1", 'message' => "Time Three has been Recorded.", 'currentTime' => $currentTime, 'full_name' => $full_name, 'grade_level' => $grade_level, 'section' => $section, 'phone_number' => $phone_number, "SMS" => "
            Status: Student Arrival
            Student: " . $full_name . "
            Time: " . $currentTime);
        } elseif ($addAttendance === 'case 4') { // time four
            return array('case' => "1", 'message' => "Time Four has been Recorded.", 'currentTime' => $currentTime, 'full_name' => $full_name, 'grade_level' => $grade_level, 'section' => $section, 'phone_number' => $phone_number, "SMS" => "
            Status: Student Departure
            Student: " . $full_name . "
            Time: " . $currentTime);
        } elseif ($addAttendance === 'case 5') { // all time slots have been filled in.
            $sql = "SELECT time_one, time_two, time_three, time_four FROM attendance WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            return array('case' => "2", 'message' => "Time One, Time Two, Time Three, and Time Four has already been Recorded.", 'time_one' => $data['time_one'], 'time_two' => $data['time_two'], 'time_three' => $data['time_three'], 'time_four' => $data['time_four']);
        } else { // error
            return array('case' => "3", 'message' => "Error: " . $addAttendance, 'id' => $id);
        }
    } else {
        return array('message' => "Unable to retrieve student data", 'id' => $id);
    }
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'add':
            // Retrieve data by ID
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $data = attendance($connect, $id);

                // Output the data as JSON
                header('Content-Type: application/json');
                echo json_encode($data);
            } else {
                // Handle the case when no ID is provided
                echo json_encode(array('error' => 'No ID provided'));
            }
            break;
    }
} else {
    // Handle the case when no action is provided
    echo json_encode(array('error' => 'No action provided'));
}
