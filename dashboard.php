<?php
session_start();
require_once __DIR__ . '/config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function displayDate(string $date): string
{
    $time = strtotime($date);
    return $time ? date('m/d/Y', $time) : $date;
}

function bookingRef(array $booking): string
{
    $stamp = date('Ymd', strtotime($booking['created_at']));
    return 'NXR-' . $stamp . '-' . str_pad((string)$booking['id'], 4, '0', STR_PAD_LEFT);
}

if (empty($_SESSION['user_id'])) {
    header('Location: Login/login.php?next=../dashboard.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT id, first_name, last_name, email, phone, role, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$_SESSION['user_name'] = $user['first_name'];
$_SESSION['user_role'] = $user['role'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$notice = $_GET['saved'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } else {
        try {
            $action = $_POST['action'] ?? '';
            if ($action === 'profile_update') {
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $email = strtolower(trim($_POST['email'] ?? ''));
                $phone = trim($_POST['phone'] ?? '');
                if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Enter a valid name and email address.');
                }
                $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?');
                $stmt->execute([$firstName, $lastName, $email, $phone ?: null, $userId]);
                $_SESSION['user_name'] = $firstName;
                header('Location: dashboard.php?saved=profile#profile');
                exit;
            }

            if ($action === 'booking_cancel') {
                $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if (!$bookingId) {
                    throw new RuntimeException('Invalid booking.');
                }
                $stmt = $pdo->prepare("SELECT b.status, (SELECT p.payment_status FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) AS payment_status FROM bookings b WHERE b.id = ? AND b.user_id = ? LIMIT 1");
                $stmt->execute([(int)$bookingId, $userId]);
                $targetBooking = $stmt->fetch();
                if (!$targetBooking || !in_array($targetBooking['status'], ['pending', 'confirmed'], true)) {
                    throw new RuntimeException('Only pending or confirmed bookings can be cancelled from your dashboard.');
                }
                if (($targetBooking['payment_status'] ?? '') === 'paid') {
                    throw new RuntimeException('Paid bookings need to be cancelled through Nexora support so the payment can be handled correctly.');
                }
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
                $stmt->execute([(int)$bookingId, $userId]);
                header('Location: dashboard.php?saved=cancelled#bookings');
                exit;
            }
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000' ? 'That email address is already in use.' : 'The change could not be saved.';
        } catch (Throwable $exception) {
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'The change could not be saved.';
        }
    }
}

$stmt = $pdo->prepare(
    "SELECT b.*,
        (SELECT p.payment_status FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) AS payment_status,
        (SELECT p.payment_method FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) AS payment_method
     FROM bookings b
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC"
);
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();

$activeStatuses = ['pending', 'confirmed', 'active'];
$activeCount = 0;
$completedCount = 0;
$totalSpent = 0.0;
$nextBooking = null;
$today = date('Y-m-d');

foreach ($bookings as $booking) {
    if (in_array($booking['status'], $activeStatuses, true)) {
        $activeCount++;
    }
    if ($booking['status'] === 'completed') {
        $completedCount++;
        $totalSpent += (float)$booking['total_amount'];
    }
    if ($nextBooking === null && in_array($booking['status'], ['pending', 'confirmed', 'active'], true) && $booking['return_date'] >= $today) {
        $nextBooking = $booking;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — Nexora</title>
    <link rel="stylesheet" href="output.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="dashboard.css?v=1.3">
</head>
<body class="dashboard-page">
<header class="dash-header">
    <nav class="dash-nav">
        <a href="index.php" class="dash-brand"><img src="Images/nexora-logo.png" alt="Nexora"></a>
        <div class="dash-nav-links">
            <a href="fleet.php">Fleet</a>
            <a href="contact.php">Support</a>
            <?php if ($user['role'] === 'admin'): ?><a href="admin-dashboard.php">Admin</a><?php endif; ?>
            <a href="logout.php" class="dash-logout">Logout</a>
        </div>
    </nav>
</header>

<main class="dash-shell">
    <?php if ($notice): ?><div class="dash-alert success"><?= $notice === 'profile' ? 'Profile updated.' : 'Booking cancelled.' ?></div><?php endif; ?>
    <?php if ($error): ?><div class="dash-alert error"><?= e($error) ?></div><?php endif; ?>
    <section class="dash-hero">
        <div>
            <span class="dash-eyebrow">My Nexora</span>
            <h1>Welcome back, <?= e($user['first_name']) ?>.</h1>
            <p>Track your reservations, payment status, and rental history in one place.</p>
        </div>
        <a href="fleet.php" class="dash-primary-btn">Book another car</a>
    </section>

    <section class="dash-stats" aria-label="Account summary">
        <article><span>Active bookings</span><strong><?= $activeCount ?></strong></article>
        <article><span>Completed trips</span><strong><?= $completedCount ?></strong></article>
        <article><span>Total bookings</span><strong><?= count($bookings) ?></strong></article>
        <article><span>Completed rental value</span><strong>₱<?= number_format($totalSpent, 0) ?></strong></article>
    </section>

    <?php if ($nextBooking): ?>
    <section class="dash-feature-card">
        <div class="dash-feature-copy">
            <span class="dash-section-label">Current / upcoming rental</span>
            <div class="dash-booking-title-row">
                <h2><?= e($nextBooking['vehicle_name']) ?></h2>
                <span class="status-badge status-<?= e($nextBooking['status']) ?>"><?= e(ucfirst($nextBooking['status'])) ?></span>
            </div>
            <p class="dash-ref"><?= e(bookingRef($nextBooking)) ?></p>
            <div class="dash-trip-grid">
                <div><span>Pick-up</span><strong><?= e(displayDate($nextBooking['pickup_date'])) ?></strong></div>
                <div><span>Return</span><strong><?= e(displayDate($nextBooking['return_date'])) ?></strong></div>
                <div><span>Location</span><strong><?= e($nextBooking['pickup_location']) ?></strong></div>
                <div><span>Transmission</span><strong><?= e(ucfirst((string)$nextBooking['transmission'])) ?></strong></div>
            </div>
        </div>
        <div class="dash-feature-total">
            <span>Estimated total</span>
            <strong>₱<?= number_format((float)$nextBooking['total_amount'], 2) ?></strong>
            <?php if (empty($nextBooking['payment_status']) && !in_array($nextBooking['status'], ['cancelled', 'completed'], true)): ?>
                <a href="payment.php?booking=<?= (int)$nextBooking['id'] ?>" class="dash-primary-btn">Add payment method</a>
            <?php else: ?>
                <span class="payment-pill"><?= e(ucfirst((string)($nextBooking['payment_status'] ?? 'not recorded'))) ?></span>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="dash-main-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <div><span class="dash-section-label">Reservations</span><h2>Booking history</h2></div>
                <a href="fleet.php">Browse fleet</a>
            </div>

            <?php if (!$bookings): ?>
                <div class="dash-empty">
                    <strong>No bookings yet.</strong>
                    <p>Your future Nexora reservations will appear here.</p>
                </div>
            <?php else: ?>
                <div class="booking-list">
                    <?php foreach ($bookings as $booking): ?>
                    <article class="booking-row">
                        <div class="booking-row-main">
                            <div class="booking-row-title">
                                <strong><?= e($booking['vehicle_name']) ?></strong>
                                <span class="status-badge status-<?= e($booking['status']) ?>"><?= e(ucfirst($booking['status'])) ?></span>
                            </div>
                            <span><?= e(bookingRef($booking)) ?> · <?= e(ucfirst((string)$booking['transmission'])) ?></span>
                            <small><?= e(displayDate($booking['pickup_date'])) ?> → <?= e(displayDate($booking['return_date'])) ?> · <?= e($booking['pickup_location']) ?></small>
                        </div>
                        <div class="booking-row-side">
                            <strong>₱<?= number_format((float)$booking['total_amount'], 2) ?></strong>
                            <span>Payment: <?= e(ucfirst((string)($booking['payment_status'] ?? 'not recorded'))) ?></span>
                            <?php if (empty($booking['payment_status']) && !in_array($booking['status'], ['cancelled', 'completed'], true)): ?>
                                <a href="payment.php?booking=<?= (int)$booking['id'] ?>">Add payment</a>
                            <?php endif; ?>
                            <?php if (in_array($booking['status'], ['pending', 'confirmed'], true)): ?>
                                <form method="post" class="user-cancel-form" onsubmit="return confirm('Cancel this booking?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="booking_cancel">
                                    <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                                    <button type="submit">Cancel booking</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <aside class="dash-side-stack">
            <section class="dash-panel profile-card" id="profile">
                <span class="dash-section-label">Account</span>
                <h2>Your profile</h2>
                <form method="post" class="profile-edit-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="profile_update">
                    <label>First name<input name="first_name" required value="<?= e($user['first_name']) ?>"></label>
                    <label>Last name<input name="last_name" required value="<?= e($user['last_name']) ?>"></label>
                    <label>Email<input type="email" name="email" required value="<?= e($user['email']) ?>"></label>
                    <label>Phone<input name="phone" value="<?= e((string)$user['phone']) ?>"></label>
                    <small>Member since <?= e(date('m/d/Y', strtotime($user['created_at']))) ?></small>
                    <button class="dash-secondary-btn" type="submit">Save profile</button>
                </form>
            </section>

            <section class="dash-panel help-card">
                <span class="dash-section-label">Need help?</span>
                <h2>Rental support</h2>
                <p>Questions about your booking, payment, or pickup location?</p>
                <a href="contact.php" class="dash-secondary-btn">Contact Nexora</a>
            </section>
        </aside>
    </div>
</main>
</body>
</html>
