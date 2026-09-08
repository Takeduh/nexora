<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/utils/validation.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?next=../admin/admin-dashboard.php');
    exit;
}

$adminId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    exit('Admin access required.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-dashboard.php');
    exit;
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    header('Location: admin-dashboard.php?crud_error=' . urlencode('Your session token expired. Refresh the page and try again.'));
    exit;
}

function clean(string $value): string
{
    return trim($value);
}

function redirectBack(string $message = '', string $error = ''): never
{
    $returnTo = $_POST['return_to'] ?? 'admin-dashboard.php';
    if (!preg_match('/^admin-(?:dashboard|manage)\.php(?:\?[A-Za-z0-9_=&%-]*)?(?:#[A-Za-z0-9_-]+)?$/', $returnTo)) {
        $returnTo = 'admin-dashboard.php';
    }

    $separator = str_contains($returnTo, '?') ? '&' : '?';
    $fragment = '';
    if (str_contains($returnTo, '#')) {
        [$returnTo, $fragment] = explode('#', $returnTo, 2);
        $separator = str_contains($returnTo, '?') ? '&' : '?';
        $fragment = '#' . $fragment;
    }

    if ($message !== '') {
        $returnTo .= $separator . 'crud_saved=' . urlencode($message);
    } elseif ($error !== '') {
        $returnTo .= $separator . 'crud_error=' . urlencode($error);
    }

    header('Location: ' . $returnTo . $fragment);
    exit;
}

function requirePositiveId(string $key): int
{
    $raw = $_POST[$key] ?? null;
    $error = validatePositiveId($raw, 'Record');
    if ($error !== null) {
        throw new RuntimeException($error);
    }
    return (int)$raw;
}

try {
    $action = $_POST['action'] ?? '';

    if ($action === 'car_create' || $action === 'car_update') {
        $brand = clean($_POST['brand'] ?? '');
        $model = clean($_POST['model'] ?? '');
        $yearRaw = clean($_POST['year'] ?? '');
        $category = clean($_POST['category'] ?? '');
        $seats = (int)($_POST['seats'] ?? 0);
        $fuelType = clean($_POST['fuel_type'] ?? '');
        $luggageRaw = clean($_POST['luggage_capacity'] ?? '');
        $description = clean($_POST['description'] ?? '');
        $image = clean($_POST['image'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if ($brand === '' || $model === '' || $category === '' || $fuelType === '' || $seats < 1) {
            throw new RuntimeException('Brand, model, category, seats, and fuel type are required.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new RuntimeException('Invalid car status.');
        }

        $year = $yearRaw === '' ? null : (int)$yearRaw;
        $luggage = $luggageRaw === '' ? null : max(0, (int)$luggageRaw);
        if ($year !== null && ($year < 1990 || $year > ((int)date('Y') + 2))) {
            throw new RuntimeException('Enter a valid vehicle year.');
        }

        if ($action === 'car_create') {
            $stmt = $pdo->prepare('INSERT INTO cars (brand, model, year, category, seats, fuel_type, luggage_capacity, description, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$brand, $model, $year, $category, $seats, $fuelType, $luggage, $description ?: null, $image ?: null, $status]);
            redirectBack('Car added.');
        }

        $carId = requirePositiveId('car_id');
        $stmt = $pdo->prepare('UPDATE cars SET brand = ?, model = ?, year = ?, category = ?, seats = ?, fuel_type = ?, luggage_capacity = ?, description = ?, image = ?, status = ? WHERE id = ?');
        $stmt->execute([$brand, $model, $year, $category, $seats, $fuelType, $luggage, $description ?: null, $image ?: null, $status, $carId]);
        redirectBack('Car updated.');
    }

    if ($action === 'car_delete') {
        $carId = requirePositiveId('car_id');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN car_variants v ON v.id = b.car_variant_id WHERE v.car_id = ? AND b.status IN ('pending','confirmed','active') AND b.return_date >= CURDATE()");
        $stmt->execute([$carId]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new RuntimeException('This car has an active or upcoming booking and cannot be deleted. Set it inactive instead.');
        }
        $stmt = $pdo->prepare('DELETE FROM cars WHERE id = ?');
        $stmt->execute([$carId]);
        redirectBack('Car deleted.');
    }

    if ($action === 'variant_create' || $action === 'variant_update') {
        $transmission = $_POST['transmission'] ?? '';
        $dailyRate = (float)($_POST['daily_rate'] ?? 0);
        $quantity = max(0, (int)($_POST['quantity'] ?? 0));
        $status = $_POST['status'] ?? 'available';

        if (!in_array($transmission, ['manual', 'automatic'], true)) {
            throw new RuntimeException('Invalid transmission.');
        }
        if (!in_array($status, ['available', 'unavailable', 'maintenance'], true)) {
            throw new RuntimeException('Invalid variant status.');
        }
        if ($dailyRate <= 0) {
            throw new RuntimeException('Daily rate must be greater than zero.');
        }

        if ($action === 'variant_create') {
            $carId = requirePositiveId('car_id');
            $stmt = $pdo->prepare('INSERT INTO car_variants (car_id, transmission, daily_rate, quantity, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$carId, $transmission, $dailyRate, $quantity, $status]);
            redirectBack('Variant added.');
        }

        $variantId = requirePositiveId('variant_id');
        $stmt = $pdo->prepare('UPDATE car_variants SET transmission = ?, daily_rate = ?, quantity = ?, status = ? WHERE id = ?');
        $stmt->execute([$transmission, $dailyRate, $quantity, $status, $variantId]);
        redirectBack('Variant updated.');
    }

    if ($action === 'variant_delete') {
        $variantId = requirePositiveId('variant_id');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE car_variant_id = ? AND status IN ('pending','confirmed','active') AND return_date >= CURDATE()");
        $stmt->execute([$variantId]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new RuntimeException('This variant has an active or upcoming booking and cannot be deleted. Mark it unavailable instead.');
        }
        $stmt = $pdo->prepare('DELETE FROM car_variants WHERE id = ?');
        $stmt->execute([$variantId]);
        redirectBack('Variant deleted.');
    }

    if ($action === 'user_create') {
        $firstName = clean($_POST['first_name'] ?? '');
        $lastName = clean($_POST['last_name'] ?? '');
        $email = strtolower(clean($_POST['email'] ?? ''));
        $phone = clean($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $password = (string)($_POST['password'] ?? '');

        if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid name and email address.');
        }
        if (!in_array($role, ['user', 'admin'], true)) {
            throw new RuntimeException('Invalid account role.');
        }
        if (strlen($password) < 8) {
            throw new RuntimeException('New accounts need a password of at least 8 characters.');
        }

        $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, phone, role, password) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$firstName, $lastName, $email, $phone ?: null, $role, password_hash($password, PASSWORD_DEFAULT)]);
        redirectBack('Account created.');
    }

    if ($action === 'user_update') {
        $userId = requirePositiveId('user_id');
        $firstName = clean($_POST['first_name'] ?? '');
        $lastName = clean($_POST['last_name'] ?? '');
        $email = strtolower(clean($_POST['email'] ?? ''));
        $phone = clean($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'user';

        if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid name and email address.');
        }
        if (!in_array($role, ['user', 'admin'], true)) {
            throw new RuntimeException('Invalid account role.');
        }
        if ($userId === $adminId && $role !== 'admin') {
            throw new RuntimeException('You cannot remove your own admin role while signed in.');
        }

        $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ? WHERE id = ?');
        $stmt->execute([$firstName, $lastName, $email, $phone ?: null, $role, $userId]);
        redirectBack('Account updated.');
    }

    if ($action === 'user_delete') {
        $userId = requirePositiveId('user_id');
        if ($userId === $adminId) {
            throw new RuntimeException('You cannot delete the account you are currently using.');
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ?');
        $stmt->execute([$userId]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new RuntimeException('This account has booking history and is retained for records.');
        }
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        redirectBack('Account deleted.');
    }

    if ($action === 'booking_update') {
        $bookingId = requirePositiveId('booking_id');
        $requestedStatus = $_POST['status'] ?? '';
        $pickupLocation = clean($_POST['pickup_location'] ?? '');
        $pickupDate = clean($_POST['pickup_date'] ?? '');
        $returnDate = clean($_POST['return_date'] ?? '');
        $specialRequests = clean($_POST['special_requests'] ?? '');
        $reason = clean($_POST['cancellation_reason'] ?? '');

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT id, car_variant_id, daily_rate, status FROM bookings WHERE id=? LIMIT 1 FOR UPDATE");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();
        if (!$booking) throw new RuntimeException('Booking not found.');

        $transitions = [
            'pending' => ['pending','confirmed','cancelled'],
            'confirmed' => ['confirmed','active','cancelled'],
            'active' => ['active','completed'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
        ];
        if (!in_array($requestedStatus, $transitions[$booking['status']] ?? [], true)) throw new RuntimeException('That booking status transition is not allowed.');
        if (in_array($booking['status'], ['completed','cancelled'], true)) throw new RuntimeException('Completed and cancelled bookings are read-only.');

        if ($requestedStatus === 'cancelled') {
            if ($reason === '' || strlen($reason) > 500) throw new RuntimeException('A cancellation reason is required.');
            $pdo->prepare("UPDATE bookings SET status='cancelled', cancelled_at=NOW(), cancellation_reason=? WHERE id=?")->execute([$reason,$bookingId]);
            $pdo->prepare("UPDATE payments SET payment_status='refunded' WHERE booking_id=? AND payment_status='paid'")->execute([$bookingId]);
            $pdo->commit();
            redirectBack('Booking cancelled. Any recorded paid payment was marked refunded.');
        }

        if ($booking['status'] === 'active') {
            $pdo->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$requestedStatus,$bookingId]);
            $pdo->commit();
            redirectBack($requestedStatus === 'completed' ? 'Booking completed.' : 'Booking updated.');
        }

        if ($pickupLocation === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$pickupDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$returnDate)) throw new RuntimeException('Enter valid booking details.');
        $startDate = new DateTimeImmutable($pickupDate); $endDate = new DateTimeImmutable($returnDate);
        if ($endDate <= $startDate) throw new RuntimeException('Return date must be after pickup date.');

        if (!empty($booking['car_variant_id'])) {
            $variantStmt=$pdo->prepare("SELECT quantity,status FROM car_variants WHERE id=? LIMIT 1 FOR UPDATE");
            $variantStmt->execute([(int)$booking['car_variant_id']]); $variant=$variantStmt->fetch();
            if (!$variant || $variant['status']!=='available' || (int)$variant['quantity']<1) throw new RuntimeException('The assigned vehicle variant is not available.');
            $check=$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE car_variant_id=? AND id<>? AND status IN ('pending','confirmed','active') AND pickup_date < ? AND return_date > ?");
            $check->execute([(int)$booking['car_variant_id'],$bookingId,$returnDate,$pickupDate]);
            if ((int)$check->fetchColumn()>=(int)$variant['quantity']) throw new RuntimeException('Those dates would overbook this vehicle variant.');
        }

        $days=(int)$startDate->diff($endDate)->days; $total=$days*(float)$booking['daily_rate'];
        $stmt=$pdo->prepare('UPDATE bookings SET pickup_location=?,dropoff_location=?,pickup_date=?,return_date=?,total_days=?,total_amount=?,special_requests=?,status=? WHERE id=?');
        $stmt->execute([$pickupLocation,$pickupLocation,$pickupDate,$returnDate,$days,$total,$specialRequests?:null,$requestedStatus,$bookingId]);
        $pdo->commit();
        redirectBack('Booking updated.');
    }

    if ($action === 'booking_cancel') {
        $bookingId = requirePositiveId('booking_id');
        $reason = clean($_POST['cancellation_reason'] ?? '');
        if ($reason === '' || strlen($reason) > 500) throw new RuntimeException('A cancellation reason is required.');
        $pdo->beginTransaction();
        $stmt=$pdo->prepare("SELECT status FROM bookings WHERE id=? LIMIT 1 FOR UPDATE"); $stmt->execute([$bookingId]); $currentStatus=$stmt->fetchColumn();
        if (!in_array($currentStatus,['pending','confirmed'],true)) throw new RuntimeException('Only pending or confirmed bookings can be cancelled.');
        $pdo->prepare("UPDATE bookings SET status='cancelled',cancelled_at=NOW(),cancellation_reason=? WHERE id=?")->execute([$reason,$bookingId]);
        $pdo->prepare("UPDATE payments SET payment_status='refunded' WHERE booking_id=? AND payment_status='paid'")->execute([$bookingId]);
        $pdo->commit();
        redirectBack('Booking cancelled. Any recorded paid payment was marked refunded.');
    }

    if ($action === 'payment_create' || $action === 'payment_update') {
        $bookingId = requirePositiveId('booking_id');
        $amount = (float)($_POST['amount'] ?? 0);
        $method = $_POST['payment_method'] ?? '';
        $status = $_POST['payment_status'] ?? '';
        $reference = clean($_POST['transaction_reference'] ?? '');

        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }
        if (!in_array($method, ['cash', 'gcash', 'card', 'bank_transfer'], true)) {
            throw new RuntimeException('Invalid payment method.');
        }
        if (!in_array($status, ['pending', 'paid', 'failed', 'refunded'], true)) {
            throw new RuntimeException('Invalid payment status.');
        }

        $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;
        if ($action === 'payment_create') {
            $stmt = $pdo->prepare('INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_reference, paid_at) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$bookingId, $amount, $method, $status, $reference ?: null, $paidAt]);
            redirectBack('Payment record created.');
        }

        $paymentId = requirePositiveId('payment_id');
        $stmt = $pdo->prepare('UPDATE payments SET booking_id = ?, amount = ?, payment_method = ?, payment_status = ?, transaction_reference = ?, paid_at = ? WHERE id = ?');
        $stmt->execute([$bookingId, $amount, $method, $status, $reference ?: null, $paidAt, $paymentId]);
        redirectBack('Payment updated.');
    }

    if ($action === 'payment_delete') {
        $paymentId = requirePositiveId('payment_id');
        $stmt = $pdo->prepare('DELETE FROM payments WHERE id = ?');
        $stmt->execute([$paymentId]);
        redirectBack('Payment record deleted.');
    }


    if ($action === 'message_reply') {
        $messageId = requirePositiveId('message_id');
        $reply = clean($_POST['admin_reply'] ?? '');
        if ($reply === '' || strlen($reply) > 5000) {
            throw new RuntimeException('Write a reply of up to 5000 characters.');
        }

        $stmt = $pdo->prepare('SELECT id FROM contact_messages WHERE id = ? LIMIT 1');
        $stmt->execute([$messageId]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Support message not found.');
        }

        $stmt = $pdo->prepare("UPDATE contact_messages SET admin_reply = ?, replied_at = NOW(), status = 'replied' WHERE id = ?");
        $stmt->execute([$reply, $messageId]);
        redirectBack('Reply sent to the customer dashboard.');
    }

    if ($action === 'message_update') {
        $messageId = requirePositiveId('message_id');
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['unread', 'read', 'replied'], true)) {
            throw new RuntimeException('Invalid message status.');
        }
        $stmt = $pdo->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
        $stmt->execute([$status, $messageId]);
        redirectBack('Message updated.');
    }

    if ($action === 'message_delete') {
        $messageId = requirePositiveId('message_id');
        $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = ?');
        $stmt->execute([$messageId]);
        redirectBack('Message deleted.');
    }

    throw new RuntimeException('Unknown admin action.');
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        redirectBack('', 'That change conflicts with an existing record, such as a duplicate email, car, or transmission variant.');
    }
    redirectBack('', 'The database change could not be completed.');
} catch (Throwable $exception) {
    redirectBack('', $exception instanceof RuntimeException ? $exception->getMessage() : 'The requested change could not be completed.');
}
