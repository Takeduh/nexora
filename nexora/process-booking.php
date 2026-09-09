<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: fleet.php'); exit; }
if (empty($_SESSION['user_id'])) {
    $next = 'booking-summary.php?' . ($_POST['return_query'] ?? '');
    header('Location: login.php?' . http_build_query(['next' => $next]));
    exit;
}

$variantIdRaw = $_POST['car_variant_id'] ?? null;
$variantId = filter_var($variantIdRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$location = trim($_POST['pickup_location'] ?? '');
$pickup = $_POST['pickup_date'] ?? '';
$returnDate = $_POST['return_date'] ?? '';
$special = trim($_POST['special_requests'] ?? '');
$supportedLocationKeywords = ['cebu','dumaguete','bohol','bacolod','iloilo','manila','makati','pasay','taguig','quezon city','davao','cagayan de oro','cdo','general santos','gensan','puerto princesa','palawan','tagbilaran','panglao','tacloban','ormoc','lapu-lapu','lapu lapu','mandaue','siargao','surigao','boracay','aklan','negros','leyte'];
$normalizedLocation = strtolower($location);
$locationSupported = false;
foreach ($supportedLocationKeywords as $keyword) { if (str_contains($normalizedLocation, $keyword)) { $locationSupported = true; break; } }
$bookingErrors = validationErrors([
    validatePositiveId($variantIdRaw, 'Vehicle variant'),
    validateRequired($location, 'Pick-up location'),
    validateIsoDate($pickup, 'Pick-up date'),
    validateIsoDate($returnDate, 'Return date'),
]);
$start = DateTimeImmutable::createFromFormat('!Y-m-d', $pickup);
$end = DateTimeImmutable::createFromFormat('!Y-m-d', $returnDate);
if ($bookingErrors || !$start || !$end || $end <= $start || $start < new DateTimeImmutable('today')) exit('Invalid booking details. Please return to the fleet and try again.');
if (!$locationSupported) exit('Please enter a pick-up location containing a supported major area such as Cebu, Dumaguete, Bohol, Bacolod, Iloilo, or Manila.');

$stmt = $pdo->prepare("SELECT v.id, v.quantity, v.status AS variant_status, c.status AS car_status FROM car_variants v INNER JOIN cars c ON c.id=v.car_id WHERE v.id=? LIMIT 1");
$stmt->execute([$variantId]);
$variant = $stmt->fetch();
if (!$variant || $variant['car_status'] !== 'active' || $variant['variant_status'] !== 'available' || (int)$variant['quantity'] < 1) exit('This vehicle variant is currently unavailable.');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE car_variant_id=? AND status IN ('pending','confirmed','active') AND pickup_date < ? AND return_date > ?");
$stmt->execute([$variantId, $returnDate, $pickup]);
$reservedCount = (int)$stmt->fetchColumn();
$remaining = max(0, (int)$variant['quantity'] - $reservedCount);
if ($remaining < 1) exit('That transmission is fully booked for the selected dates. Please choose another variant or different dates.');

$_SESSION['booking_draft'] = [
    'car_variant_id' => (int)$variantId,
    'pickup_location' => $location,
    'pickup_date' => $pickup,
    'return_date' => $returnDate,
    'special_requests' => $special,
    'remaining_at_check' => $remaining,
];
header('Location: payment.php');
exit;
