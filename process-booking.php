<?php
session_start();
require_once __DIR__ . '/config/database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: fleet.php'); exit; }
if (empty($_SESSION['user_id'])) {
    $query = http_build_query(['next' => '../booking-summary.php?' . ($_POST['return_query'] ?? '')]);
    header('Location: Login/login.php?' . $query); exit;
}
$vehicleCode = trim($_POST['vehicle_code'] ?? '');
$vehicleName = trim($_POST['vehicle_name'] ?? '');
$location = trim($_POST['pickup_location'] ?? '');
$pickup = $_POST['pickup_date'] ?? '';
$return = $_POST['return_date'] ?? '';
$dailyRate = filter_var($_POST['daily_rate'] ?? null, FILTER_VALIDATE_FLOAT);
$special = trim($_POST['special_requests'] ?? '');

$start = DateTime::createFromFormat('Y-m-d', $pickup);
$end = DateTime::createFromFormat('Y-m-d', $return);
if (!$vehicleCode || !$vehicleName || !$location || !$start || !$end || $end <= $start || $dailyRate === false || $dailyRate <= 0) {
    exit('Invalid booking details. Please return to the fleet and try again.');
}
$totalDays = (int)$start->diff($end)->days;
$totalAmount = $totalDays * (float)$dailyRate;
$stmt = $pdo->prepare('INSERT INTO bookings (user_id,vehicle_code,vehicle_name,pickup_location,dropoff_location,pickup_date,return_date,total_days,daily_rate,total_amount,special_requests) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
$stmt->execute([(int)$_SESSION['user_id'],$vehicleCode,$vehicleName,$location,$location,$pickup,$return,$totalDays,$dailyRate,$totalAmount,$special !== '' ? $special : null]);
$bookingId = (int)$pdo->lastInsertId();
header('Location: payment.php?booking=' . $bookingId); exit;
