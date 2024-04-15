<?php

// librarys or whatever they're called
include 'config/db_conn.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Writer\Xls;

// SPREADSHEAT STUFF
$inputFileName = 'SF2/SF2_template.xls';
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
$spreadsheet = $reader->load($inputFileName);
$activeWorksheet = $spreadsheet->getActiveSheet();

// adviser data
$grade_level = $_SESSION['user_gradeLevel'];
$adviser_name = $_SESSION['user_full_name'];
$section = $_SESSION['user_section'];
// principal
$principal = "Crestina Tayone Parot";
// dynamic dates
$halfdayDates = array();
$holidayDates = array(); // "2024-04-9", "2024-04-17"

//sign conventions
$absentSign = 'X';
$presentSign = ' ';
$halfdayMorningSign = '/';
$halfdayAfternoonSign = "\\";

// functions
function getSchoolYear($month, $year)
{
    if ($month >= 9) { // September (9) or later, the school year has started
        $startYear = $year;
    } else { // Before September, the school year has not started yet
        $startYear = $year - 1;
    }

    $endYear = $startYear + 1;

    $schoolYear = $startYear . ' - ' . $endYear;

    return $schoolYear;
}

function getWeekdaysInMonth($month, $year)
{
    $numDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $weekdays = array();

    for ($day = 1; $day <= $numDays; $day++) {
        $date = strtotime("$year-$month-$day");
        $dayOfWeek = date('N', $date); // N returns 1 for Monday, 7 for Sunday
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            $weekdays[] = date('Y-m-j', $date);
        }
    }

    return $weekdays;
}

function getWeekday($date)
{
    $timestamp = strtotime($date);
    $weekday_number = date('w', $timestamp);
    $weekdays = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

    return $weekdays[$weekday_number];
}

function getStudentNames($conn, $section)
{
    $sql = "SELECT * FROM registeredStudents WHERE section=? ORDER BY full_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$section]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $results;
}

// Function to determine if time is morning or afternoon
function checkTimeOfDay($timeInput)
{
    // Convert time input to timestamp
    $timestamp = strtotime($timeInput);

    // Get the hour from the timestamp
    $hour = date("G", $timestamp);

    // Get the minutes from the timestamp
    $minutes = date("i", $timestamp);

    // Check if the time is after 12:30
    if ($hour > 12 || ($hour == 12 && $minutes >= 30)) {
        return "Afternoon";
    } else {
        return "Morning";
    }
}



// date variables
$currentMonthN = date('n'); // n returns the current month without leading zeros (1 to 12)
$currentYear = date('Y'); // Y returns the current year in four digits (e.g., 2024)


// define Grade Level
$yearLevel = array(
    7 => "Year 1",
    8 => "Year 2",
    9 => "Year 3",
    10 => "Year 4",
    11 => "Year 5",
    12 => "Year 6",
);

$finalGradeLevel = 'Grade ' . $grade_level . ' (' . $yearLevel[$grade_level] . ')';



// Weekdays Data
$getWeekdaysInMonth = getWeekdaysInMonth($currentMonthN, $currentYear);
$dayCell = array();

// 1st row logic
$weekdays = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
$firstDayIndex = array_search(getWeekday($getWeekdaysInMonth[0]), $weekdays);
$dayCell = array_slice(["F", "H", "I", "J", "K", "L", "N", "O", "P", "Q", "R", "T", "U", "V", "X", "Z", "AB", "AC", "AD", "AE", "AF", "AG", "AI", "AJ", "AK"], $firstDayIndex);

// array that has dates => column
$newDayCell = array();
$x = 0;
foreach ($getWeekdaysInMonth as $day) {
    $newDayCell["$day"] = $dayCell[$x];
    $x++;
}

// Assign Holidays
$listHolidays = array();
foreach ($holidayDates as $dates) {
    $listHolidays[] = $newDayCell[$dates];
}

// SF2 MANIPULATION
$studentData = getStudentNames($connect, $section);
if ($studentData) {
    // Assign Row Number to Students
    $rowStudent = array();
    // list of students
    $currentCellMale = 8; // Remove single quotes to treat it as an integer
    $currentCellFemale = 29; // Remove single quotes to treat it as an integer
    // foreach loop for student data
    foreach ($studentData as $student) {
        // inputs the list of students alphabetically
        $gender = $student["gender"];
        $currentCell = ($gender === "male") ? $currentCellMale : $currentCellFemale;
        $activeWorksheet->setCellValue('C' . $currentCell, strtoupper($student["full_name"]));
        $rowStudent[$student["full_name"]] = $currentCell;

        // formulae
        // assign data coordinate
        $studentCoord = 'F' . $currentCell . ':AK' . $currentCell;
        $activeWorksheet->setCellValue('AM' . $currentCell, '=COUNTIF(' . $studentCoord . ', "X") + COUNTIF(' . $studentCoord . ', "/")*0.5 + COUNTIF(' . $studentCoord . ', "\")*0.5'); // total absent
        $activeWorksheet->setCellValue('AO' . $currentCell, '=COUNTIF(' . $studentCoord . ', " ") + COUNTIF(' . $studentCoord . ', "/")*0.5 + COUNTIF(' . $studentCoord . ', "\")*0.5'); // total absent

        if ($gender === "male") {
            $currentCellMale++;
        } else {
            $currentCellFemale++;
        }

        $lrn = $student['id'];

        // inputs the attendance data
        foreach ($getWeekdaysInMonth as $day) {

            $sql1 = "SELECT * FROM archive WHERE section=:section AND archive_date=:archive_date AND id=:id";
            $stmt = $connect->prepare($sql1);
            $stmt->bindParam(':section', $section);
            $stmt->bindParam(':archive_date', $day);
            $stmt->bindParam(':id', $lrn);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all rows for the day

            // determines if $day is a holiday
            $isHoliday = false;
            foreach ($holidayDates as $holiday) {
                if ($day === $holiday) {
                    $isHoliday = true;
                    break;
                }
            }
            // determines if $day is halfday
            $isHalfDay = false;
            foreach ($halfdayDates as $halfDay) {
                if ($day === $halfDay) {
                    $isHalfDay = true;
                    break;
                }
            }

            if ($results) { // if student attendance exists in the database
                $sign;
                foreach ($results as $result1) {
                    // time variables
                    $time_one = $result1["time_one"];
                    $time_two = $result1["time_two"];
                    $time_three = $result1["time_three"];
                    $time_four = $result1["time_four"];

                    if (!$isHoliday) { // if not holiday
                        if (checkTimeOfDay($time_one) === "Morning") { // checks time_one if its morning or afternoon
                            if (checkTimeOfDay($time_two) === "Morning") { //checks time_two if its morning or afternoon
                                // if time_two === Morning, that means Student left. Might come back or not.
                                if ($time_three === "00:00:00" && !$isHalfDay) { // student did not come back. (MORNING ONLY)
                                    $sign = $halfdayMorningSign;
                                } else { // student came back (PRESENT WHOLE DAY)
                                    $sign = $presentSign; // student is present
                                }
                            } else { // if time_two === Afternoon, thats afternoon departure. (PRESENT WHOLE DAY)
                                $sign = $presentSign; // student is present
                            }
                        } else { // time_one is afternoon, means that student did not come in the morning. (AFTERNOON ONLY)
                            if (!$isHalfDay) { // if not holiday
                                $sign = $halfdayAfternoonSign; // student is halfday (AFTERNOON ONLY)
                            } else { // if holiday
                                $sign = $presentSign; // student is present
                            }
                        }

                        $activeWorksheet->setCellValue($newDayCell[$day] . $rowStudent[$student["full_name"]], $sign); // ASSIGN SIGNAGE

                    } else { // if holiday
                        $activeWorksheet->setCellValue($newDayCell[$day] . $rowStudent[$student["full_name"]], ""); // BLANK
                    }
                }
            } else { // student attendance was not recorded. hence, absent.
                if (!$isHoliday) { // if not holiday
                    $activeWorksheet->setCellValue($newDayCell[$day] . $rowStudent[$student["full_name"]], $absentSign); // ABSENT
                } else { // holiday
                    $activeWorksheet->setCellValue($newDayCell[$day] . $rowStudent[$student["full_name"]], ""); // BLANK
                }
            }
        }
    }

    // basic info
    $currentMonthName = date('F');
    $activeWorksheet->setCellValue('M3', getSchoolYear($currentMonthN, $currentYear)); // School Year
    $activeWorksheet->setCellValue('AA3', strtoupper($currentMonthName)); // Month
    $activeWorksheet->setCellValue('AA4', $finalGradeLevel); // Grade Level
    $activeWorksheet->setCellValue('AM4', strtoupper($section)); // Section
    $activeWorksheet->setCellValue('AN87', strtoupper($adviser_name)); // Adviser Name
    $activeWorksheet->setCellValue('AN92', strtoupper($principal)); // Principal Name
    $activeWorksheet->setCellValue('AM61', "Month : " . strtoupper($currentMonthName)); // Principal Name

    $activeWorksheet->setCellValue('AP61', "No. of Days of Classes: " . count($getWeekdaysInMonth)); // No. of Days of Classes

    // 1st row dates
    $x = 0;
    foreach ($getWeekdaysInMonth as $value) {
        $day = explode("-", $value);

        $column = $dayCell[$x]; // assign which column to put
        $totalMale = $column . '8:' . $column . '27'; // cell coordinate format for total male
        $totalFemale = $column . '29:' . $column . '58'; // cell coordinate format for total female
        $combined = $column . '28,' . $column . '59'; // cell coordinate format for combined data for total male and female

        $date = $day[2];
        $activeWorksheet->setCellValue($column . '6', $date); // input 1st row dates

        if (count($holidayDates) < 0) { // checks if holidays exists

            foreach ($holidayDates as $holidays) { // excludes holidays
                $holiday = explode("-", $holidays);

                if ($date !== $holiday[2]) {
                    $activeWorksheet->setCellValue($column . '28', '=COUNTIF(' . $totalMale . '," ")+COUNTIF(' . $totalMale . ',"/")*0.5+COUNTIF(' . $totalMale . ',"\")*0.5');
                    $activeWorksheet->setCellValue($column . '59', '=COUNTIF(' . $totalFemale . '," ")+COUNTIF(' . $totalFemale . ',"/")*0.5+COUNTIF(' . $totalFemale . ',"\")*0.5');
                    $activeWorksheet->setCellValue($column . '60', '=SUM(' . $combined . ')');
                } else {
                    $activeWorksheet->setCellValue($column . '28', "");
                }
            }
        } else { // no holiday
            $activeWorksheet->setCellValue($column . '28', '=COUNTIF(' . $totalMale . '," ")+COUNTIF(' . $totalMale . ',"/")*0.5+COUNTIF(' . $totalMale . ',"\")*0.5');
            $activeWorksheet->setCellValue($column . '59', '=COUNTIF(' . $totalFemale . '," ")+COUNTIF(' . $totalFemale . ',"/")*0.5+COUNTIF(' . $totalFemale . ',"\")*0.5');
            $activeWorksheet->setCellValue($column . '60', '=SUM(' . $combined . ')');
        }

        $x++;
    }

    // holidays
    foreach ($listHolidays as $holiday) {
        $activeWorksheet->setCellValue($holiday . '9', "H");
        $activeWorksheet->setCellValue($holiday . '10', "O");
        $activeWorksheet->setCellValue($holiday . '11', "L");
        $activeWorksheet->setCellValue($holiday . '12', "I");
        $activeWorksheet->setCellValue($holiday . '13', "D");
        $activeWorksheet->setCellValue($holiday . '14', "A");
        $activeWorksheet->setCellValue($holiday . '15', "Y");
    }

    // save sf2
    $fileName = '[' . $grade_level . '] - ' . ucfirst($section);
    $writer = new Xls($spreadsheet);
    $writer->save('SF2/' . $fileName . '.xls');
} else {
    echo "oh no it didnt work 🫨";
}
