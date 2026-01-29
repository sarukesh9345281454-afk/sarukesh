<?php
session_start();
require_once 'config.php';

// --- HARDCODED ADMIN CREDENTIALS ---
$admin_users = [
    'csc'      => ['pass' => 'csc123',    'name' => 'Computer Science'],
    'bca'      => ['pass' => 'bca123',    'name' => 'Computer Science and Appliction '],
    'mca'      => ['pass' => 'mca123',    'name' => 'Master of Computer Science appliction'],
    'it'      => ['pass' => 'it123',    'name' => 'Information Technology'],
    'maths'   => ['pass' => 'math123',  'name' => 'Mathematics'],
    'physics' => ['pass' => 'phy123',   'name' => 'Physics'],
    'chem'    => ['pass' => 'chem123',  'name' => 'Chemistry'],
    'eng'     => ['pass' => 'eng123',   'name' => 'English'],
    'tamil'   => ['pass' => 'tam123',   'name' => 'Tamil'],
    'com'     => ['pass' => 'com123',   'name' => 'Commerce'],
    'eco'    => ['pass' => 'econ123',  'name' => 'Economics'],
    'his'     => ['pass' => 'his123',   'name' => 'History'],
    'bba'      => ['pass' => 'bba123',    'name' => 'Business Admin'],
    'phyedu'  => ['pass' => 'ped123',    'name' => 'Physical Education'],
    'rds'     => ['pass' => 'rds123',   'name' => 'Rural Development Science'],
    'fst'    => ['pass' => 'fst123',  'name' => 'Food Science and Technology']
];

// Admin Login Logic
if (isset($_POST['admin_login_btn'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    if (isset($admin_users[$user]) && $admin_users[$user]['pass'] === $pass) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user;
        $_SESSION['admin_dept'] = $admin_users[$user]['name'];
        header("Location: admin.php");
        exit();
    } else {
        $error = "❌ Invalid Department ID or Password";
    }
}

// Staff Login Logic (For index.php)
if (isset($_POST['staff_login_btn'])) {
    $_SESSION['staff_name'] = htmlspecialchars($_POST['staff_name']);
    $_SESSION['staff_dept'] = htmlspecialchars($_POST['staff_dept']);
    $_SESSION['staff_subject'] = htmlspecialchars($_POST['staff_subject']);
    header("Location: index.php");
    exit();
}

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit();
}
?>