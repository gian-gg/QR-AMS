<?php
session_start();
include '../config/db_conn.php';

if (isset($_POST['email']) && isset($_POST['password'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email)) {
        header("Location: ../pages/login.php?error=Email is Required.");
    } else if (empty($password)) {
        header("Location: ../pages/login.php?error=Password is Required.&email=$email");
    } else {
        $adviserSTMT = $connect->prepare("SELECT * FROM advisers WHERE email=?");
        $adviserSTMT->execute([$email]);

        $studentSTMT = $connect->prepare("SELECT * FROM registeredStudents WHERE email=?");
        $studentSTMT->execute([$email]);

        if ($adviserSTMT->rowCount() === 1) {
            $user = $adviserSTMT->fetch();

            $user_id = $user['id'];
            $user_email = $user['email'];
            $user_password = $user['password'];
            $user_full_name = $user['full_name'];
            $user_gradeLevel = $user['gradeLevel'];
            $user_section = $user['section'];

            if ($email === $user_email && password_verify($password, $user_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $user_email;
                $_SESSION['user_full_name'] = $user_full_name;
                $_SESSION['user_gradeLevel'] = $user_gradeLevel;
                $_SESSION['user_section'] = $user_section;

                if ($email === 'admin@qrams.com') {
                    header("Location: ../pages/admin.php");
                } else {
                    header("Location: ../index.php");
                }
            } else {
                header("Location: ../pages/login.php?error=Incorrect Email or Password.&email=$email");
            }
        } else if ($studentSTMT->rowCount() === 1) {
            $user = $studentSTMT->fetch();

            $user_id = $user['id'];
            $user_email = $user['email'];
            $user_password = $user['password'];
            $user_full_name = $user['full_name'];
            $user_gradeLevel = $user['grade_level'];
            $user_section = $user['section'];

            if ($email === $user_email && password_verify($password, $user_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $user_email;
                $_SESSION['user_full_name'] = $user_full_name;
                $_SESSION['user_gradeLevel'] = $user_gradeLevel;
                $_SESSION['user_section'] = $user_section;

                header("Location: ../pages/student.php");
            } else {
                header("Location: ../pages/login.php?error=Incorrect Email or Password.&email=$email");
            }
        } else {
            header("Location: ../pages/login.php?error=Incorrect Email or Password.&email=$email");
        }
    }
}
