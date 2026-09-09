<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

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

function methodName(string $type): string
{
    return [
        'gcash' => 'GCash',
        'card' => 'Card',
        'bank_transfer' => 'Bank transfer',
        'cash' => 'Cash'
    ][$type] ?? ucfirst($type);
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?next=../account/dashboard.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT id, first_name, last_name, email, phone, role, created_at
     FROM users
     WHERE id = ?
     LIMIT 1'
);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../auth/logout.php');
    exit;
}

$_SESSION['user_name'] = $user['first_name'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_email'] = $user['email'];

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

            if ($action === 'payment_method_add') {
                $type = $_POST['method_type'] ?? '';
                $label = trim($_POST['display_label'] ?? '');
                $lastFour = preg_replace('/\D/', '', $_POST['last_four'] ?? '');

                if (!in_array($type, ['cash', 'gcash', 'card', 'bank_transfer'], true)) {
                    throw new RuntimeException('Choose a valid payment method.');
                }

                if ($label === '') {
                    $label = methodName($type);
                }

                if ($type !== 'cash' && strlen($lastFour) !== 4) {
                    throw new RuntimeException('Enter the last 4 digits for this payment method.');
                }

                if ($type === 'cash') {
                    $label = 'Cash';
                    $lastFour = '';

                    $cashCheck = $pdo->prepare(
                        "SELECT id
                         FROM user_payment_methods
                         WHERE user_id = ? AND method_type = 'cash'
                         LIMIT 1"
                    );
                    $cashCheck->execute([$userId]);

                    if ($cashCheck->fetch()) {
                        throw new RuntimeException('Cash is already saved as a payment method.');
                    }
                }

                $count = $pdo->prepare(
                    'SELECT COUNT(*)
                     FROM user_payment_methods
                     WHERE user_id = ?'
                );
                $count->execute([$userId]);
                $isDefault = (int)$count->fetchColumn() === 0 ? 1 : 0;

                $stmt = $pdo->prepare(
                    'INSERT INTO user_payment_methods
                        (user_id, method_type, display_label, last_four, is_default)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $userId,
                    $type,
                    $label,
                    $lastFour !== '' ? $lastFour : null,
                    $isDefault
                ]);

                header('Location: dashboard.php?saved=method#wallet');
                exit;
            }

            if ($action === 'payment_method_update') {
                $methodId = filter_input(
                    INPUT_POST,
                    'method_id',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );
                $label = trim($_POST['display_label'] ?? '');
                $lastFour = preg_replace('/\D/', '', $_POST['last_four'] ?? '');

                $stmt = $pdo->prepare(
                    'SELECT method_type
                     FROM user_payment_methods
                     WHERE id = ? AND user_id = ?
                     LIMIT 1'
                );
                $stmt->execute([(int)$methodId, $userId]);
                $method = $stmt->fetch();

                if (!$method) {
                    throw new RuntimeException('Payment method not found.');
                }

                if ($method['method_type'] === 'cash') {
                    $label = 'Cash';
                    $lastFour = '';
                } else {
                    if ($label === '') {
                        $label = methodName($method['method_type']);
                    }

                    if (strlen($lastFour) !== 4) {
                        throw new RuntimeException('Enter exactly 4 digits.');
                    }
                }

                $stmt = $pdo->prepare(
                    'UPDATE user_payment_methods
                     SET display_label = ?, last_four = ?
                     WHERE id = ? AND user_id = ?'
                );
                $stmt->execute([
                    $label,
                    $lastFour !== '' ? $lastFour : null,
                    (int)$methodId,
                    $userId
                ]);

                header('Location: dashboard.php?saved=method#wallet');
                exit;
            }

            if ($action === 'payment_method_default') {
                $methodId = filter_input(
                    INPUT_POST,
                    'method_id',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );

                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'SELECT id
                     FROM user_payment_methods
                     WHERE id = ? AND user_id = ?
                     LIMIT 1
                     FOR UPDATE'
                );
                $stmt->execute([(int)$methodId, $userId]);

                if (!$stmt->fetchColumn()) {
                    throw new RuntimeException('Payment method not found.');
                }

                $pdo->prepare(
                    'UPDATE user_payment_methods
                     SET is_default = 0
                     WHERE user_id = ?'
                )->execute([$userId]);

                $pdo->prepare(
                    'UPDATE user_payment_methods
                     SET is_default = 1
                     WHERE id = ? AND user_id = ?'
                )->execute([(int)$methodId, $userId]);

                $pdo->commit();

                header('Location: dashboard.php?saved=method#wallet');
                exit;
            }

            if ($action === 'payment_method_delete') {
                $methodId = filter_input(
                    INPUT_POST,
                    'method_id',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );

                $stmt = $pdo->prepare(
                    'SELECT is_default
                     FROM user_payment_methods
                     WHERE id = ? AND user_id = ?
                     LIMIT 1'
                );
                $stmt->execute([(int)$methodId, $userId]);
                $wasDefault = $stmt->fetchColumn();

                if ($wasDefault === false) {
                    throw new RuntimeException('Payment method not found.');
                }

                $pdo->prepare(
                    'DELETE FROM user_payment_methods
                     WHERE id = ? AND user_id = ?'
                )->execute([(int)$methodId, $userId]);

                if ((int)$wasDefault === 1) {
                    $stmt = $pdo->prepare(
                        'SELECT id
                         FROM user_payment_methods
                         WHERE user_id = ?
                         ORDER BY created_at DESC
                         LIMIT 1'
                    );
                    $stmt->execute([$userId]);
                    $nextId = $stmt->fetchColumn();

                    if ($nextId) {
                        $pdo->prepare(
                            'UPDATE user_payment_methods
                             SET is_default = 1
                             WHERE id = ?'
                        )->execute([(int)$nextId]);
                    }
                }

                header('Location: dashboard.php?saved=method#wallet');
                exit;
            }

            if ($action === 'booking_cancel') {
                $bookingId = filter_input(
                    INPUT_POST,
                    'booking_id',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );
                $reason = trim($_POST['cancellation_reason'] ?? '');

                if (!$bookingId) {
                    throw new RuntimeException('Invalid booking.');
                }

                if ($reason === '' || strlen($reason) > 500) {
                    throw new RuntimeException(
                        'Please provide a cancellation reason of up to 500 characters.'
                    );
                }

                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "SELECT b.status,
                        (
                            SELECT p.payment_status
                            FROM payments p
                            WHERE p.booking_id = b.id
                            ORDER BY p.id DESC
                            LIMIT 1
                        ) AS payment_status
                     FROM bookings b
                     WHERE b.id = ? AND b.user_id = ?
                     LIMIT 1
                     FOR UPDATE"
                );
                $stmt->execute([(int)$bookingId, $userId]);
                $targetBooking = $stmt->fetch();

                if (
                    !$targetBooking ||
                    !in_array($targetBooking['status'], ['pending', 'confirmed'], true)
                ) {
                    throw new RuntimeException(
                        'Only pending or confirmed bookings can be cancelled from your dashboard.'
                    );
                }

                if (($targetBooking['payment_status'] ?? '') === 'paid') {
                    throw new RuntimeException(
                        'Paid bookings require admin cancellation so the payment can be marked for refund.'
                    );
                }

                $pdo->prepare(
                    "UPDATE bookings
                     SET status = 'cancelled',
                         cancelled_at = NOW(),
                         cancellation_reason = ?
                     WHERE id = ? AND user_id = ?"
                )->execute([$reason, (int)$bookingId, $userId]);

                $pdo->commit();

                header('Location: dashboard.php?saved=cancelled#bookings');
                exit;
            }
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $exception->getCode() === '23000'
                ? 'That email address is already in use.'
                : 'The change could not be saved.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'The change could not be saved.';
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT id, method_type, display_label, last_four, is_default
     FROM user_payment_methods
     WHERE user_id = ?
     ORDER BY is_default DESC, created_at DESC'
);
$stmt->execute([$userId]);
$paymentMethods = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT b.*,
        (
            SELECT p.payment_status
            FROM payments p
            WHERE p.booking_id = b.id
            ORDER BY p.id DESC
            LIMIT 1
        ) AS payment_status,
        (
            SELECT p.payment_method
            FROM payments p
            WHERE p.booking_id = b.id
            ORDER BY p.id DESC
            LIMIT 1
        ) AS payment_method
     FROM bookings b
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC"
);
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();

$supportStmt = $pdo->prepare(
    "SELECT id, subject, message, status, admin_reply, replied_at, created_at
     FROM contact_messages
     WHERE user_id = ? OR (user_id IS NULL AND LOWER(email) = LOWER(?))
     ORDER BY created_at DESC
     LIMIT 10"
);
$supportStmt->execute([$userId, $user['email']]);
$supportMessages = $supportStmt->fetchAll();

$activeStatuses = ['pending', 'confirmed', 'active'];
$activeCount = 0;
$completedCount = 0;
$totalSpent = 0.0;
$nextBooking = null;
$unreadReplies = 0;
$today = date('Y-m-d');

foreach ($bookings as $booking) {
    if (in_array($booking['status'], $activeStatuses, true)) {
        $activeCount++;
    }

    if ($booking['status'] === 'completed') {
        $completedCount++;
        $totalSpent += (float)$booking['total_amount'];
    }

    if (
        $nextBooking === null &&
        in_array($booking['status'], ['pending', 'confirmed', 'active'], true) &&
        $booking['return_date'] >= $today
    ) {
        $nextBooking = $booking;
    }
}

foreach ($supportMessages as $support) {
    if (!empty($support['admin_reply'])) {
        $unreadReplies++;
    }
}

$noticeMessages = [
    'method' => 'Payment methods updated.',
    'cancelled' => 'Booking cancelled.'
];

$noticeText = $noticeMessages[$notice] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — Nexora</title>
    <link rel="stylesheet" href="../base.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="../shared.css?v=1.5">
    <link rel="stylesheet" href="dashboard.css?v=2.0">
    <link rel="stylesheet" href="../responsive.css?v=1.0">
</head>
<body class="customer-dashboard">
<div class="user-app">
    <aside class="user-sidebar" id="userSidebar">
        <a href="../index.php" class="user-sidebar-brand">
            <img src="../Images/nexora-logo.png" alt="Nexora">
        </a>

        <nav class="user-sidebar-nav" aria-label="Dashboard navigation">
            <a class="active" href="#overview" data-section="overview">
                <span>⌂</span>Overview
            </a>
            <a href="#bookings" data-section="bookings">
                <span>▦</span>My Bookings
            </a>
            <a href="#wallet" data-section="wallet">
                <span>₱</span>Wallet
            </a>
            <a href="#support" data-section="support">
                <span>✉</span>Support
            </a>
            <a href="settings.php">
                <span>⚙</span>Account Settings
            </a>
        </nav>

        <div class="user-sidebar-bottom">
            <a href="../pages/fleet.php" class="user-book-link">Book a car</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="../admin/admin-dashboard.php">Admin dashboard</a>
            <?php endif; ?>
            <a href="../index.php">Back to website</a>
            <a href="../auth/logout.php" class="user-logout">Logout</a>
        </div>
    </aside>

    <main class="user-main">
        <header class="user-topbar">
            <button type="button" class="user-menu-toggle" id="userMenuToggle" aria-label="Toggle dashboard menu">☰</button>

            <div class="user-topbar-title">
                <strong>My Dashboard</strong>
                <small>Manage your Nexora account and rentals</small>
            </div>

            <div class="user-profile">
                <span class="user-avatar"><?= e(strtoupper(substr($user['first_name'], 0, 1))) ?></span>
                <div>
                    <strong><?= e($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                    <small><?= e($user['email']) ?></small>
                </div>
            </div>
        </header>

        <div class="user-content">
            <?php if ($noticeText): ?>
                <div class="user-alert success"><?= e($noticeText) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="user-alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($_GET['booking_confirmed'])): ?>
                <?php
                $confirmed = null;
                foreach ($bookings as $booking) {
                    if ((int)$booking['id'] === (int)$_GET['booking_confirmed']) {
                        $confirmed = $booking;
                        break;
                    }
                }
                ?>
                <?php if ($confirmed): ?>
                    <div class="user-booking-confirmation">
                        <div>
                            <span>Booking submitted</span>
                            <strong><?= e(bookingRef($confirmed)) ?></strong>
                            <p>
                                Your <?= e($confirmed['vehicle_name']) ?> reservation was submitted
                                successfully and is pending Nexora approval.
                            </p>
                        </div>
                        <a href="#bookings">View booking</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <section class="user-section user-overview" id="overview">
                <div class="user-welcome">
                    <div>
                        <span class="user-eyebrow">My Nexora</span>
                        <h1>Welcome back, <?= e($user['first_name']) ?>.</h1>
                        <p>
                            Everything you need for your rentals, payments, support,
                            and account is now in one dashboard.
                        </p>
                    </div>
                    <a href="../pages/fleet.php" class="user-primary-btn">Book another car</a>
                </div>

                <div class="user-stats">
                    <article>
                        <span>Active bookings</span>
                        <strong><?= $activeCount ?></strong>
                        <small>Pending, confirmed, or active</small>
                    </article>
                    <article>
                        <span>Completed trips</span>
                        <strong><?= $completedCount ?></strong>
                        <small>Your finished rentals</small>
                    </article>
                    <article>
                        <span>Saved methods</span>
                        <strong><?= count($paymentMethods) ?></strong>
                        <small>Payment options in your wallet</small>
                    </article>
                    <article>
                        <span>Completed rental value</span>
                        <strong>₱<?= number_format($totalSpent, 0) ?></strong>
                        <small>Total from completed trips</small>
                    </article>
                </div>

                <?php if ($nextBooking): ?>
                    <article class="current-rental-card">
                        <div class="current-rental-main">
                            <div class="current-rental-heading">
                                <div>
                                    <span class="user-eyebrow">Current / upcoming rental</span>
                                    <h2><?= e($nextBooking['vehicle_name']) ?></h2>
                                </div>
                                <span class="user-status status-<?= e($nextBooking['status']) ?>">
                                    <?= e(ucfirst($nextBooking['status'])) ?>
                                </span>
                            </div>

                            <p class="rental-ref"><?= e(bookingRef($nextBooking)) ?></p>

                            <div class="current-rental-info">
                                <div>
                                    <span>Pick-up</span>
                                    <strong><?= e(displayDate($nextBooking['pickup_date'])) ?></strong>
                                </div>
                                <div>
                                    <span>Return</span>
                                    <strong><?= e(displayDate($nextBooking['return_date'])) ?></strong>
                                </div>
                                <div>
                                    <span>Location</span>
                                    <strong><?= e($nextBooking['pickup_location']) ?></strong>
                                </div>
                                <div>
                                    <span>Transmission</span>
                                    <strong><?= e(ucfirst((string)$nextBooking['transmission'])) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="current-rental-total">
                            <span>Estimated total</span>
                            <strong>₱<?= number_format((float)$nextBooking['total_amount'], 2) ?></strong>

                            <?php if (
                                empty($nextBooking['payment_status']) &&
                                !in_array($nextBooking['status'], ['cancelled', 'completed'], true)
                            ): ?>
                                <a
                                    href="../booking/payment.php?booking=<?= (int)$nextBooking['id'] ?>"
                                    class="user-primary-btn"
                                >
                                    Add payment method
                                </a>
                            <?php else: ?>
                                <span class="payment-state">
                                    <?= e(ucfirst((string)($nextBooking['payment_status'] ?? 'not recorded'))) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php else: ?>
                    <article class="user-empty-highlight">
                        <div>
                            <span class="user-eyebrow">Ready for your next trip?</span>
                            <h2>No active rental right now.</h2>
                            <p>Browse the fleet when you are ready to make another reservation.</p>
                        </div>
                        <a href="../pages/fleet.php" class="user-secondary-btn">Browse fleet</a>
                    </article>
                <?php endif; ?>
            </section>

            <section class="user-section user-card" id="bookings">
                <div class="user-section-head">
                    <div>
                        <span class="user-eyebrow">Reservations</span>
                        <h2>My bookings</h2>
                        <p>Review your rental history and manage eligible reservations.</p>
                    </div>
                    <a href="../pages/fleet.php">Browse fleet</a>
                </div>

                <?php if (!$bookings): ?>
                    <div class="user-empty">
                        <strong>No bookings yet.</strong>
                        <p>Your Nexora reservations will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="user-booking-list">
                        <?php foreach ($bookings as $booking): ?>
                            <article class="user-booking-row">
                                <div class="user-booking-main">
                                    <div class="user-booking-title">
                                        <strong><?= e($booking['vehicle_name']) ?></strong>
                                        <span class="user-status status-<?= e($booking['status']) ?>">
                                            <?= e(ucfirst($booking['status'])) ?>
                                        </span>
                                    </div>

                                    <span class="booking-reference">
                                        <?= e(bookingRef($booking)) ?>
                                        ·
                                        <?= e(ucfirst((string)$booking['transmission'])) ?>
                                    </span>

                                    <div class="booking-meta">
                                        <span><?= e(displayDate($booking['pickup_date'])) ?> → <?= e(displayDate($booking['return_date'])) ?></span>
                                        <span><?= e($booking['pickup_location']) ?></span>
                                    </div>
                                </div>

                                <div class="user-booking-payment">
                                    <strong>₱<?= number_format((float)$booking['total_amount'], 2) ?></strong>
                                    <span>
                                        Payment:
                                        <?= e(ucfirst((string)($booking['payment_status'] ?? 'not recorded'))) ?>
                                    </span>
                                    <?php if (!empty($booking['payment_method'])): ?>
                                        <span><?= e(methodName((string)$booking['payment_method'])) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="user-booking-actions">
                                    <?php if (
                                        in_array($booking['status'], ['pending', 'confirmed'], true) &&
                                        ($booking['payment_status'] ?? '') !== 'paid'
                                    ): ?>
                                        <details class="booking-cancel">
                                            <summary>Cancel booking</summary>
                                            <form
                                                method="post"
                                                onsubmit="return confirm('Cancel this booking? This cannot be undone.');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_token']) ?>"
                                                >
                                                <input type="hidden" name="action" value="booking_cancel">
                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?= (int)$booking['id'] ?>"
                                                >
                                                <textarea
                                                    name="cancellation_reason"
                                                    maxlength="500"
                                                    required
                                                    placeholder="Reason for cancellation"
                                                ></textarea>
                                                <button type="submit">Confirm cancellation</button>
                                            </form>
                                        </details>
                                    <?php elseif (
                                        in_array($booking['status'], ['pending', 'confirmed'], true) &&
                                        ($booking['payment_status'] ?? '') === 'paid'
                                    ): ?>
                                        <small>Contact support to cancel this paid booking.</small>
                                    <?php endif; ?>

                                    <?php if (
                                        $booking['status'] === 'cancelled' &&
                                        !empty($booking['cancellation_reason'])
                                    ): ?>
                                        <small>
                                            Reason: <?= e($booking['cancellation_reason']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="user-section user-card" id="wallet">
                <div class="user-section-head">
                    <div>
                        <span class="user-eyebrow">Wallet</span>
                        <h2>Payment methods</h2>
                        <p>
                            Save only the information needed to identify your preferred payment method.
                        </p>
                    </div>
                </div>

                <div class="wallet-security-note">
                    Nexora never asks for your full card number, CVV, PIN, or bank password.
                </div>

                <?php if ($paymentMethods): ?>
                    <div class="wallet-grid">
                        <?php foreach ($paymentMethods as $method): ?>
                            <article class="wallet-card">
                                <div class="wallet-card-head">
                                    <div>
                                        <span><?= e(methodName($method['method_type'])) ?></span>
                                        <strong><?= e($method['display_label']) ?></strong>
                                    </div>
                                    <?php if ($method['is_default']): ?>
                                        <span class="wallet-default">Default</span>
                                    <?php endif; ?>
                                </div>

                                <p>
                                    <?= $method['last_four'] ? '•••• ' . e($method['last_four']) : 'No extra details required' ?>
                                </p>

                                <?php if ($method['method_type'] !== 'cash'): ?>
                                    <details class="wallet-edit">
                                        <summary>Edit method</summary>
                                        <form method="post">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token']) ?>"
                                            >
                                            <input type="hidden" name="action" value="payment_method_update">
                                            <input
                                                type="hidden"
                                                name="method_id"
                                                value="<?= (int)$method['id'] ?>"
                                            >

                                            <label>
                                                Label
                                                <input
                                                    name="display_label"
                                                    maxlength="100"
                                                    value="<?= e($method['display_label']) ?>"
                                                >
                                            </label>

                                            <label>
                                                Last 4 digits
                                                <input
                                                    name="last_four"
                                                    inputmode="numeric"
                                                    maxlength="4"
                                                    pattern="[0-9]{4}"
                                                    value="<?= e((string)$method['last_four']) ?>"
                                                >
                                            </label>

                                            <button type="submit">Save changes</button>
                                        </form>
                                    </details>
                                <?php endif; ?>

                                <div class="wallet-actions">
                                    <?php if (!$method['is_default']): ?>
                                        <form method="post">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token']) ?>"
                                            >
                                            <input type="hidden" name="action" value="payment_method_default">
                                            <input
                                                type="hidden"
                                                name="method_id"
                                                value="<?= (int)$method['id'] ?>"
                                            >
                                            <button type="submit">Set default</button>
                                        </form>
                                    <?php endif; ?>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Remove this saved payment method?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_token']) ?>"
                                        >
                                        <input type="hidden" name="action" value="payment_method_delete">
                                        <input
                                            type="hidden"
                                            name="method_id"
                                            value="<?= (int)$method['id'] ?>"
                                        >
                                        <button type="submit" class="danger">Remove</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="user-empty compact">
                        <strong>No saved payment methods.</strong>
                        <p>Add one below so it is ready for future bookings.</p>
                    </div>
                <?php endif; ?>

                <details class="wallet-add">
                    <summary>Add payment method</summary>
                    <form method="post" class="user-form">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>"
                        >
                        <input type="hidden" name="action" value="payment_method_add">

                        <div class="user-form-grid">
                            <label>
                                Method
                                <select name="method_type" id="dashboardMethodType" required>
                                    <option value="gcash">GCash</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank transfer</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </label>

                            <label id="dashboardMethodLabelField">
                                Label
                                <input
                                    name="display_label"
                                    maxlength="100"
                                    placeholder="Personal GCash or Visa"
                                >
                            </label>

                            <label id="dashboardLastFourField">
                                Last 4 digits
                                <input
                                    name="last_four"
                                    inputmode="numeric"
                                    maxlength="4"
                                    pattern="[0-9]{4}"
                                    placeholder="1234"
                                >
                            </label>
                        </div>

                        <button type="submit" class="user-primary-btn">Add method</button>
                    </form>
                </details>
            </section>

            <section class="user-section user-card" id="support">
                <div class="user-section-head">
                    <div>
                        <span class="user-eyebrow">Support</span>
                        <h2>Support inbox</h2>
                        <p>Keep track of your questions and Nexora's replies.</p>
                    </div>
                    <a href="../pages/contact.php">New message</a>
                </div>

                <?php if (!$supportMessages): ?>
                    <div class="user-empty compact">
                        <strong>No support messages yet.</strong>
                        <p>Send us a message if you need help with a booking, payment, or pickup.</p>
                    </div>
                <?php else: ?>
                    <div class="support-list">
                        <?php foreach ($supportMessages as $support): ?>
                            <article class="support-card">
                                <div class="support-card-head">
                                    <div>
                                        <strong><?= e($support['subject'] ?: 'General inquiry') ?></strong>
                                        <small>
                                            <?= e(date('m/d/Y g:i A', strtotime($support['created_at']))) ?>
                                        </small>
                                    </div>
                                    <span class="user-status <?= $support['status'] === 'replied' ? 'status-confirmed' : 'status-pending' ?>">
                                        <?= e(ucfirst($support['status'])) ?>
                                    </span>
                                </div>

                                <p><?= e($support['message']) ?></p>

                                <?php if (!empty($support['admin_reply'])): ?>
                                    <div class="support-response">
                                        <span>Nexora reply</span>
                                        <p><?= e($support['admin_reply']) ?></p>
                                        <?php if (!empty($support['replied_at'])): ?>
                                            <small>
                                                <?= e(date('m/d/Y g:i A', strtotime($support['replied_at']))) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

<script src="dashboard.js?v=2.0"></script>
</body>
</html>
