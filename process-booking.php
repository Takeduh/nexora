<?php
session_start();
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: fleet.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    $next = '../booking-summary.php?' . ($_POST['return_query'] ?? '');
    header('Location: Login/login.php?' . http_build_query(['next' => $next]));
    exit;
}

$variantId = filter_var($_POST['car_variant_id'] ?? null, FILTER_VALIDATE_INT);
$location = trim($_POST['pickup_location'] ?? '');
$pickup = $_POST['pickup_date'] ?? '';
$returnDate = $_POST['return_date'] ?? '';
$special = trim($_POST['special_requests'] ?? '');

$start = DateTime::createFromFormat('Y-m-d', $pickup);
$end = DateTime::createFromFormat('Y-m-d', $returnDate);

if (!$variantId || !$location || !$start || !$end || $start->format('Y-m-d') !== $pickup || $end->format('Y-m-d') !== $returnDate || $end <= $start) {
    exit('Invalid booking details. Please return to the fleet and try again.');
}

try {
    $pdo->beginTransaction();

    // Lock this variant while availability is checked and the booking is inserted.
    $variantStmt = $pdo->prepare("SELECT
            v.id AS variant_id,
            v.transmission,
            v.daily_rate,
            v.quantity,
            v.status AS variant_status,
            c.id AS car_id,
            c.brand,
            c.model,
            c.status AS car_status
        FROM car_variants v
        INNER JOIN cars c ON c.id = v.car_id
        WHERE v.id = ?
        LIMIT 1
        FOR UPDATE");
    $variantStmt->execute([$variantId]);
    $variant = $variantStmt->fetch();

    if (!$variant || $variant['car_status'] !== 'active' || $variant['variant_status'] !== 'available' || (int)$variant['quantity'] < 1) {
        throw new RuntimeException('This vehicle variant is currently unavailable.');
    }

    // Two bookings overlap when existing pickup < requested return
    // AND existing return > requested pickup.
    $availabilityStmt = $pdo->prepare("SELECT COUNT(*)
        FROM bookings
        WHERE car_variant_id = ?
          AND status IN ('pending', 'confirmed', 'active')
          AND pickup_date < ?
          AND return_date > ?");
    $availabilityStmt->execute([$variantId, $returnDate, $pickup]);
    $alreadyBooked = (int)$availabilityStmt->fetchColumn();

    if ($alreadyBooked >= (int)$variant['quantity']) {
        throw new RuntimeException('That transmission is already fully booked for the selected dates. Please choose another variant or different dates.');
    }

    $totalDays = (int)$start->diff($end)->days;
    $dailyRate = (float)$variant['daily_rate'];
    $totalAmount = $totalDays * $dailyRate;
    $vehicleName = trim($variant['brand'] . ' ' . $variant['model']);
    $vehicleCode = 'car-' . (int)$variant['car_id'] . '-variant-' . (int)$variant['variant_id'];
    $transmission = strtolower($variant['transmission']);

    $insert = $pdo->prepare("INSERT INTO bookings (
            user_id,
            car_variant_id,
            vehicle_code,
            vehicle_name,
            transmission,
            pickup_location,
            dropoff_location,
            pickup_date,
            return_date,
            total_days,
            daily_rate,
            total_amount,
            special_requests
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $insert->execute([
        (int)$_SESSION['user_id'],
        $variantId,
        $vehicleCode,
        $vehicleName,
        $transmission,
        $location,
        $location,
        $pickup,
        $returnDate,
        $totalDays,
        $dailyRate,
        $totalAmount,
        $special !== '' ? $special : null,
    ]);

    $bookingId = (int)$pdo->lastInsertId();
    $pdo->commit();

    header('Location: payment.php?booking=' . $bookingId);
    exit;
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    $message = $error instanceof RuntimeException
        ? $error->getMessage()
        : 'We could not create the booking. Please try again.';

    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
}
