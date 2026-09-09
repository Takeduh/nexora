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

function bookingStatusOptions(string $status): array
{
    return match ($status) {
        'pending' => ['pending','confirmed','cancelled'],
        'confirmed' => ['confirmed','active','cancelled'],
        'active' => ['active','completed'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled'],
        default => [$status],
    };
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?next=../admin/admin-dashboard.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, first_name, last_name, email, role FROM users WHERE id = ? LIMIT 1');
$stmt->execute([(int)$_SESSION['user_id']]);
$admin = $stmt->fetch();

if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    exit('Admin access required.');
}

$_SESSION['user_role'] = 'admin';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$notice = $_GET['saved'] ?? ($_GET['crud_saved'] ?? '');
$error = $_GET['crud_error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'booking_status') {
                $bookingId = (int)($_POST['booking_id'] ?? 0);
                $status = $_POST['status'] ?? '';
                $reason = trim($_POST['cancellation_reason'] ?? '');
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT status FROM bookings WHERE id = ? LIMIT 1 FOR UPDATE');
                $stmt->execute([$bookingId]);
                $current = $stmt->fetchColumn();
                if ($current === false || !in_array($status, bookingStatusOptions((string)$current), true)) throw new RuntimeException('That booking status transition is not allowed.');
                if ($status === 'cancelled') {
                    if ($reason === '' || strlen($reason) > 500) throw new RuntimeException('A cancellation reason is required.');
                    $pdo->prepare("UPDATE bookings SET status='cancelled', cancelled_at=NOW(), cancellation_reason=? WHERE id=?")->execute([$reason,$bookingId]);
                    $pdo->prepare("UPDATE payments SET payment_status='refunded' WHERE booking_id=? AND payment_status='paid'")->execute([$bookingId]);
                } else {
                    $pdo->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$status,$bookingId]);
                }
                $pdo->commit();
                header('Location: admin-dashboard.php?saved=booking#bookings'); exit;
            }

            if ($action === 'variant_update') {
                $allowed = ['available', 'unavailable', 'maintenance'];
                $status = $_POST['status'] ?? '';
                $quantity = max(0, (int)($_POST['quantity'] ?? 0));
                if (!in_array($status, $allowed, true)) throw new RuntimeException('Invalid fleet status.');
                $stmt = $pdo->prepare('UPDATE car_variants SET status = ?, quantity = ? WHERE id = ?');
                $stmt->execute([$status, $quantity, (int)($_POST['variant_id'] ?? 0)]);
                header('Location: admin-dashboard.php?saved=fleet#fleet'); exit;
            }

            if ($action === 'message_status') {
                $allowed = ['unread', 'read', 'replied'];
                $status = $_POST['status'] ?? '';
                if (!in_array($status, $allowed, true)) throw new RuntimeException('Invalid message status.');
                $stmt = $pdo->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
                $stmt->execute([$status, (int)($_POST['message_id'] ?? 0)]);
                header('Location: admin-dashboard.php?saved=message#messages'); exit;
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'The update could not be saved.';
        }
    }
}

$stats = [
    'bookings' => (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'users' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'active' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'active'")->fetchColumn(),
    'unread' => (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn(),
    'revenue' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'paid'")->fetchColumn(),
];

$bookings = $pdo->query(
    "SELECT b.id, b.vehicle_name, b.transmission, b.pickup_location, b.pickup_date, b.return_date, b.total_amount, b.status, b.cancelled_at, b.cancellation_reason, b.created_at,
            u.first_name, u.last_name, u.email,
            (SELECT p.payment_status FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) AS payment_status
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     ORDER BY b.created_at DESC
     LIMIT 30"
)->fetchAll();

$variants = $pdo->query(
    "SELECT v.id, v.car_id, v.transmission, v.daily_rate, v.quantity, v.status,
            c.brand, c.model, c.category,
            (SELECT COUNT(*) FROM bookings b
             WHERE b.car_variant_id = v.id
               AND b.status IN ('pending','confirmed','active')
               AND b.pickup_date <= CURDATE()
               AND b.return_date > CURDATE()) AS booked_today,
            (SELECT COUNT(*) FROM bookings b
             WHERE b.car_variant_id = v.id
               AND b.status IN ('pending','confirmed','active')
               AND b.return_date > CURDATE()) AS upcoming_reservations
     FROM car_variants v
     JOIN cars c ON c.id = v.car_id
     ORDER BY c.brand, c.model, FIELD(v.transmission,'automatic','manual')"
)->fetchAll();

$carsInventory = [];
foreach ($variants as $variant) {
    $carId = (int)$variant['car_id'];
    if (!isset($carsInventory[$carId])) {
        $carsInventory[$carId] = [
            'car_id' => $carId,
            'brand' => $variant['brand'],
            'model' => $variant['model'],
            'category' => $variant['category'],
            'variants' => [],
        ];
    }
    $variant['booked_today'] = (int)$variant['booked_today'];
    $variant['upcoming_reservations'] = (int)$variant['upcoming_reservations'];
    $variant['available_today'] = max(0, (int)$variant['quantity'] - $variant['booked_today']);
    $carsInventory[$carId]['variants'][] = $variant;
}
$carsInventory = array_values($carsInventory);

$messages = $pdo->query(
    "SELECT id, user_id, name, email, subject, message, status, admin_reply, replied_at, created_at
     FROM contact_messages
     ORDER BY created_at DESC
     LIMIT 20"
)->fetchAll();

$users = $pdo->query(
    "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.role, u.created_at,
            (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS booking_count
     FROM users u
     WHERE u.role = 'user'
     ORDER BY u.created_at DESC
     LIMIT 20"
)->fetchAll();

$payments = $pdo->query(
    "SELECT p.id, p.amount, p.payment_method, p.payment_status, p.transaction_reference, p.created_at,
            b.id AS booking_id, b.vehicle_name,
            u.first_name, u.last_name
     FROM payments p
     JOIN bookings b ON b.id = p.booking_id
     JOIN users u ON u.id = b.user_id
     ORDER BY p.created_at DESC
     LIMIT 20"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Nexora</title>
    <link rel="stylesheet" href="../base.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="../shared.css?v=1.5">
    <link rel="stylesheet" href="admin.css?v=1.0">
  <link rel="stylesheet" href="../responsive.css?v=1.0">
</head>
<body class="dashboard-page admin-page admin-dashboard-v2">
<div class="admin-app">
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="../index.php" class="admin-sidebar-brand"><img src="../Images/nexora-logo.png" alt="Nexora"></a>
        <nav class="admin-sidebar-nav" aria-label="Admin navigation">
            <a class="active" href="#overview"><span>⌂</span>Overview</a>
            <a href="#bookings"><span>▦</span>Bookings</a>
            <a href="#fleet"><span>◆</span>Fleet</a>
            <a href="#customers"><span>●</span>Customers</a>
            <a href="#payments"><span>₱</span>Payments</a>
            <a href="#messages"><span>✉</span>Support<?php if ($stats['unread'] > 0): ?><b><?= $stats['unread'] ?></b><?php endif; ?></a>
            <a href="admin-manage.php"><span>⚙</span>Manage Records</a>
        </nav>
        <div class="admin-sidebar-bottom">
            <a href="../pages/fleet.php">View public fleet</a>
            <a href="../auth/logout.php" class="admin-sidebar-logout">Logout</a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <button class="admin-menu-toggle" id="adminMenuToggle" type="button" aria-label="Toggle admin menu">☰</button>
            <label class="admin-global-search">
                <span>⌕</span>
                <input type="search" id="adminGlobalSearch" placeholder="Search visible records...">
            </label>
            <div class="admin-profile">
                <span class="admin-profile-role">Admin</span>
                <span class="admin-avatar"><?= e(strtoupper(substr($admin['first_name'], 0, 1))) ?></span>
                <div><strong><?= e($admin['first_name'] . ' ' . $admin['last_name']) ?></strong><small><?= e($admin['email']) ?></small></div>
            </div>
        </header>
        <div class="dash-shell admin-shell">

    <section class="admin-welcome" id="overview">
        <div>
            <span class="dash-eyebrow">Overview</span>
            <h1>Admin dashboard</h1>
            <p>Here’s what is happening with NEXORA today.</p>
        </div>
    </section>

    <?php if ($notice): ?><div class="dash-alert success">Update saved successfully.</div><?php endif; ?>
    <?php if ($error): ?><div class="dash-alert error"><?= e($error) ?></div><?php endif; ?>

    <section class="admin-stat-grid">
        <article class="admin-stat-card"><span class="admin-stat-icon">▦</span><div><small>Total bookings</small><strong><?= $stats['bookings'] ?></strong></div></article>
        <article class="admin-stat-card"><span class="admin-stat-icon">◆</span><div><small>Active rentals</small><strong><?= $stats['active'] ?></strong></div></article>
        <article class="admin-stat-card"><span class="admin-stat-icon">₱</span><div><small>Paid revenue</small><strong>₱<?= number_format($stats['revenue'], 2) ?></strong></div></article>
        <article class="admin-stat-card"><span class="admin-stat-icon">●</span><div><small>Customers</small><strong><?= $stats['users'] ?></strong></div></article>
        <article class="admin-stat-card admin-stat-alert"><span class="admin-stat-icon">✉</span><div><small>Unread messages</small><strong><?= $stats['unread'] ?></strong></div></article>
    </section>

    <section class="dash-panel admin-section" id="bookings">
        <div class="dash-panel-head"><div><span class="dash-section-label">Reservations</span><h2>Recent bookings</h2></div><span><?= count($bookings) ?> shown</span></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Booking</th><th>Customer</th><th>Trip</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><strong>#<?= (int)$booking['id'] ?> · <?= e($booking['vehicle_name']) ?></strong><small><?= e(ucfirst((string)$booking['transmission'])) ?></small></td>
                        <td><strong><?= e($booking['first_name'] . ' ' . $booking['last_name']) ?></strong><small><?= e($booking['email']) ?></small></td>
                        <td><strong><?= e(displayDate($booking['pickup_date'])) ?> → <?= e(displayDate($booking['return_date'])) ?></strong><small><?= e($booking['pickup_location']) ?></small></td>
                        <td>₱<?= number_format((float)$booking['total_amount'], 2) ?></td>
                        <td><span class="admin-payment-state"><?= e(ucfirst((string)($booking['payment_status'] ?? 'not recorded'))) ?></span></td>
                        <td><span class="status-badge status-<?= e($booking['status']) ?>"><?= e(ucfirst($booking['status'])) ?></span></td>
                        <td>
                            <button type="button" class="admin-edit-toggle">Edit</button>
                            <div class="admin-edit-panel" hidden>
                                <form method="post" class="inline-admin-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="booking_status">
                                    <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                                    <select name="status" aria-label="Booking status">
                                        <?php foreach (bookingStatusOptions($booking['status']) as $status): ?><option value="<?= $status ?>" <?= $booking['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?>
                                    </select>
                                    <?php if (in_array($booking['status'], ['pending','confirmed'], true)): ?><input type="text" name="cancellation_reason" maxlength="500" placeholder="Reason if cancelling" aria-label="Cancellation reason"><?php endif; ?>
                                    <button <?= in_array($booking['status'], ['completed','cancelled'], true)?'disabled':'' ?>>Update</button>
                                </form>
                                <small class="admin-edit-note">Bookings are retained for rental history, so they are not deleted from the dashboard.</small>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="dash-panel admin-section" id="fleet">
        <div class="dash-panel-head inventory-panel-head">
            <div><span class="dash-section-label">Inventory</span><h2>Fleet inventory</h2></div>
            <a href="../pages/fleet.php">View public fleet</a>
        </div>

        <div class="inventory-toolbar">
            <label class="inventory-search">
                <span>Search fleet</span>
                <input type="search" id="inventorySearch" placeholder="Search brand, model, or category">
            </label>
            <span class="inventory-summary"><?= count($carsInventory) ?> cars</span>
        </div>

        <div class="inventory-list grouped-inventory" id="fleetCarList">
            <div class="inventory-list-head" aria-hidden="true">
                <span>Vehicle</span><span>Transmission</span><span>Availability today</span><span>Inventory settings</span>
            </div>
            <?php foreach ($carsInventory as $index => $car):
                $first = $car['variants'][0];
            ?>
            <article class="inventory-list-row inventory-car-row"
                     data-search="<?= e(strtolower($car['brand'] . ' ' . $car['model'] . ' ' . $car['category'])) ?>"
                     <?= $index >= 6 ? ' hidden' : '' ?>>
                <div class="inventory-vehicle">
                    <span class="inventory-category"><?= e($car['category']) ?></span>
                    <strong><?= e($car['brand'] . ' ' . $car['model']) ?></strong>
                    <small><?= count($car['variants']) ?> transmission<?= count($car['variants']) === 1 ? '' : 's' ?></small>
                </div>

                <div class="inventory-transmission-control">
                    <label>
                        <span>Transmission</span>
                        <select class="variant-selector">
                            <?php foreach ($car['variants'] as $variant): ?>
                            <option
                                value="<?= (int)$variant['id'] ?>"
                                data-rate="<?= e(number_format((float)$variant['daily_rate'], 2, '.', '')) ?>"
                                data-quantity="<?= (int)$variant['quantity'] ?>"
                                data-status="<?= e($variant['status']) ?>"
                                data-booked="<?= (int)$variant['booked_today'] ?>"
                                data-available="<?= (int)$variant['available_today'] ?>"
                                data-upcoming="<?= (int)$variant['upcoming_reservations'] ?>">
                                <?= e(ucfirst($variant['transmission'])) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <span class="variant-rate">₱<?= number_format((float)$first['daily_rate'], 2) ?>/day</span>
                </div>

                <div class="inventory-live-stats">
                    <strong class="available-count"><?= (int)$first['available_today'] ?> / <?= (int)$first['quantity'] ?> available</strong>
                    <span><b class="booked-count"><?= (int)$first['booked_today'] ?></b> booked today</span>
                    <span><b class="upcoming-count"><?= (int)$first['upcoming_reservations'] ?></b> upcoming reservations</span>
                </div>

                <div class="inventory-edit-shell">
                    <button type="button" class="admin-edit-toggle">Edit</button>
                    <div class="admin-edit-panel inventory-edit-panel" hidden>
                        <form method="post" class="inventory-row-form grouped-form">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="variant_update">
                            <input type="hidden" name="variant_id" class="variant-id-input" value="<?= (int)$first['id'] ?>">
                            <label class="inventory-field"><span>Total units</span><input class="quantity-input" type="number" min="0" name="quantity" value="<?= (int)$first['quantity'] ?>"></label>
                            <label class="inventory-field"><span>Status</span><select class="status-input" name="status"><?php foreach (['available','unavailable','maintenance'] as $status): ?><option value="<?= $status ?>" <?= $first['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label>
                            <button type="submit">Update</button>
                        </form>
                        <form method="post" action="admin-crud.php" class="admin-delete-form" onsubmit="return confirm('Delete this transmission variant? This is blocked if it has active or upcoming bookings.');">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="variant_delete">
                            <input type="hidden" name="return_to" value="admin-dashboard.php#fleet">
                            <input type="hidden" name="variant_id" class="variant-delete-id" value="<?= (int)$first['id'] ?>">
                            <button type="submit" class="admin-danger-btn">Delete</button>
                        </form>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php if (count($carsInventory) > 8): ?>
        <div class="inventory-load-more">
            <div class="inventory-buttons">
                <button type="button" id="loadMoreCars" class="dash-secondary-btn">Load more</button>
                <button type="button" id="showLessCars" class="dash-secondary-btn" hidden>Show less</button>
            </div>
            <span id="carCount">Showing 6 of <?= count($carsInventory) ?> cars</span>
        </div>
        <?php endif; ?>
    </section>

    <section class="dash-panel admin-section" id="customers">
        <div class="dash-panel-head"><div><span class="dash-section-label">Accounts</span><h2>Recent customers</h2></div><span><?= count($users) ?> shown</span></div>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Customer</th><th>Contact</th><th>Bookings</th><th>Joined</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($users as $customer): ?>
        <tr>
            <td><strong><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></strong><small>User #<?= (int)$customer['id'] ?></small></td>
            <td><strong><?= e($customer['email']) ?></strong><small><?= e($customer['phone'] ?: 'No phone') ?></small></td>
            <td><?= (int)$customer['booking_count'] ?></td>
            <td><?= e(displayDate($customer['created_at'])) ?></td>
            <td>
                <button type="button" class="admin-edit-toggle">Edit</button>
                <div class="admin-edit-panel admin-row-editor" hidden>
                    <form method="post" action="admin-crud.php" class="admin-compact-edit">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="user_update">
                        <input type="hidden" name="return_to" value="admin-dashboard.php#customers">
                        <input type="hidden" name="user_id" value="<?= (int)$customer['id'] ?>">
                        <input name="first_name" required maxlength="100" value="<?= e($customer['first_name']) ?>" aria-label="First name">
                        <input name="last_name" required maxlength="100" value="<?= e($customer['last_name']) ?>" aria-label="Last name">
                        <input type="email" name="email" required maxlength="150" value="<?= e($customer['email']) ?>" aria-label="Email">
                        <input name="phone" maxlength="30" value="<?= e((string)$customer['phone']) ?>" placeholder="Phone" aria-label="Phone">
                        <select name="role" aria-label="Role"><option value="user" <?= $customer['role'] === 'user' ? 'selected' : '' ?>>User</option><option value="admin" <?= $customer['role'] === 'admin' ? 'selected' : '' ?>>Admin</option></select>
                        <button type="submit">Update</button>
                    </form>
                    <form method="post" action="admin-crud.php" onsubmit="return confirm('Delete this customer account? Accounts with booking history are protected.');">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="user_delete">
                        <input type="hidden" name="return_to" value="admin-dashboard.php#customers">
                        <input type="hidden" name="user_id" value="<?= (int)$customer['id'] ?>">
                        <button type="submit" class="admin-danger-btn">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </section>

    <section class="dash-panel admin-section" id="payments">
        <div class="dash-panel-head"><div><span class="dash-section-label">Records</span><h2>Recent payments</h2></div><span><?= count($payments) ?> shown</span></div>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Customer</th><th>Booking</th><th>Method</th><th>Amount</th><th>Status</th><th>Reference</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($payments as $payment): ?>
        <tr>
            <td><?= e($payment['first_name'] . ' ' . $payment['last_name']) ?></td>
            <td>#<?= (int)$payment['booking_id'] ?> · <?= e($payment['vehicle_name']) ?></td>
            <td><?= e(ucwords(str_replace('_',' ',$payment['payment_method']))) ?></td>
            <td>₱<?= number_format((float)$payment['amount'], 2) ?></td>
            <td><span class="admin-payment-state payment-<?= e($payment['payment_status']) ?>"><?= e(ucfirst($payment['payment_status'])) ?></span></td>
            <td><?= e($payment['transaction_reference'] ?: '—') ?></td>
            <td>
                <button type="button" class="admin-edit-toggle">Edit</button>
                <div class="admin-edit-panel admin-row-editor" hidden>
                    <form method="post" action="admin-crud.php" class="admin-compact-edit">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="payment_update">
                        <input type="hidden" name="return_to" value="admin-dashboard.php#payments">
                        <input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>">
                        <input type="hidden" name="booking_id" value="<?= (int)$payment['booking_id'] ?>">
                        <input type="number" min="0.01" step="0.01" name="amount" value="<?= e(number_format((float)$payment['amount'], 2, '.', '')) ?>" aria-label="Amount">
                        <select name="payment_method" aria-label="Payment method"><?php foreach (['cash','gcash','card','bank_transfer'] as $method): ?><option value="<?= $method ?>" <?= $payment['payment_method'] === $method ? 'selected' : '' ?>><?= e(ucwords(str_replace('_',' ',$method))) ?></option><?php endforeach; ?></select>
                        <select name="payment_status" aria-label="Payment status"><?php foreach (['pending','paid','failed','refunded'] as $status): ?><option value="<?= $status ?>" <?= $payment['payment_status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select>
                        <input name="transaction_reference" maxlength="100" value="<?= e((string)$payment['transaction_reference']) ?>" placeholder="Reference" aria-label="Transaction reference">
                        <button type="submit">Update</button>
                    </form>
                    <form method="post" action="admin-crud.php" onsubmit="return confirm('Delete this payment record?');">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="payment_delete">
                        <input type="hidden" name="return_to" value="admin-dashboard.php#payments">
                        <input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>">
                        <button type="submit" class="admin-danger-btn">Delete</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </section>

    <section class="dash-panel admin-section" id="messages">
        <div class="dash-panel-head"><div><span class="dash-section-label">Support</span><h2>Contact messages</h2></div><span><?= count($messages) ?> shown</span></div>
        <div class="message-admin-list">
            <?php foreach ($messages as $message): ?>
            <article class="message-admin-card">
                <div class="message-admin-head">
                    <div><strong><?= e($message['subject'] ?: 'General inquiry') ?></strong><span><?= e($message['name']) ?> · <?= e($message['email']) ?></span></div>
                    <small><?= e(date('m/d/Y g:i A', strtotime($message['created_at']))) ?></small>
                </div>
                <p><?= e($message['message']) ?></p>
                <?php if (!empty($message['admin_reply'])): ?>
                    <div class="admin-existing-reply"><strong>Latest reply</strong><p><?= e($message['admin_reply']) ?></p><small><?= $message['replied_at'] ? e(date('m/d/Y g:i A', strtotime($message['replied_at']))) : '' ?></small></div>
                <?php endif; ?>
                <div class="message-admin-actions">
                    <button type="button" class="admin-reply-toggle">Reply</button>
                    <button type="button" class="admin-edit-toggle">Edit</button>
                </div>
                <div class="admin-reply-panel" hidden>
                    <form method="post" action="admin-crud.php" class="admin-reply-form">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="message_reply">
                        <input type="hidden" name="return_to" value="admin-dashboard.php#messages">
                        <input type="hidden" name="message_id" value="<?= (int)$message['id'] ?>">
                        <textarea name="admin_reply" maxlength="5000" required placeholder="Write a reply that will appear on the customer's dashboard..."><?= e((string)$message['admin_reply']) ?></textarea>
                        <button type="submit">Send reply</button>
                    </form>
                </div>
                <div class="admin-edit-panel" hidden>
                    <form method="post" action="admin-crud.php" class="inline-admin-form message-status-form">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="message_update">
                        <input type="hidden" name="return_to" value="admin-dashboard.php#messages">
                        <input type="hidden" name="message_id" value="<?= (int)$message['id'] ?>">
                        <select name="status"><?php foreach (['unread','read','replied'] as $status): ?><option value="<?= $status ?>" <?= $message['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select>
                        <button type="submit">Update</button>
                    </form>
                    <form method="post" action="admin-crud.php" onsubmit="return confirm('Delete this support message?');">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="message_delete">
                        <input type="hidden" name="return_to" value="admin-dashboard.php#messages">
                        <input type="hidden" name="message_id" value="<?= (int)$message['id'] ?>">
                        <button type="submit" class="admin-danger-btn">Delete</button>
                    </form>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

        </div>
    </main>
</div>
<script>
(() => {
    const list = document.getElementById('fleetCarList');
    const search = document.getElementById('inventorySearch');
    const loadMore = document.getElementById('loadMoreCars');
    const showLess = document.getElementById('showLessCars');
    const counter = document.getElementById('carCount');
    const initialVisible = 6;
    let visibleLimit = initialVisible;

    if (!list) return;
    const rows = [...list.querySelectorAll('.inventory-car-row')];

    const syncVariant = (row) => {
        const selector = row.querySelector('.variant-selector');
        const option = selector?.selectedOptions[0];
        if (!option) return;

        row.querySelector('.variant-id-input').value = option.value;
        const deleteId = row.querySelector('.variant-delete-id');
        if (deleteId) deleteId.value = option.value;
        row.querySelector('.quantity-input').value = option.dataset.quantity || '0';
        row.querySelector('.status-input').value = option.dataset.status || 'available';
        row.querySelector('.variant-rate').textContent = `₱${Number(option.dataset.rate || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}/day`;
        row.querySelector('.available-count').textContent = `${option.dataset.available || 0} / ${option.dataset.quantity || 0} available`;
        row.querySelector('.booked-count').textContent = option.dataset.booked || '0';
        row.querySelector('.upcoming-count').textContent = option.dataset.upcoming || '0';
    };

    rows.forEach(row => {
        row.querySelector('.variant-selector')?.addEventListener('change', () => syncVariant(row));
        syncVariant(row);
    });

    const matchingRows = () => {
        const term = (search?.value || '').trim().toLowerCase();
        return rows.filter(row => !term || row.dataset.search.includes(term));
    };

    const render = () => {
        const matches = matchingRows();
        const searching = Boolean((search?.value || '').trim());
        rows.forEach(row => { row.hidden = true; });
        matches.slice(0, searching ? matches.length : visibleLimit).forEach(row => { row.hidden = false; });

        const shown = matches.filter(row => !row.hidden).length;
        if (counter) counter.textContent = `Showing ${shown} of ${matches.length} cars`;
        if (loadMore) loadMore.hidden = searching || shown >= matches.length;
        if (showLess) showLess.hidden = searching || visibleLimit <= initialVisible;
    };

    search?.addEventListener('input', () => { visibleLimit = initialVisible; render(); });
    loadMore?.addEventListener('click', () => { visibleLimit += 6; render(); });
    showLess?.addEventListener('click', () => {
        visibleLimit = initialVisible;
        render();
        document.getElementById('fleet')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    render();

    const sidebar = document.getElementById('adminSidebar');
    const sectionLinks = [...document.querySelectorAll('.admin-sidebar-nav a[href^="#"]')];
    document.getElementById('adminMenuToggle')?.addEventListener('click', () => sidebar?.classList.toggle('open'));

    const setActiveSection = (id) => {
        sectionLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
        });
    };

    sectionLinks.forEach(link => {
        link.addEventListener('click', () => {
            sidebar?.classList.remove('open');
            const id = link.getAttribute('href').slice(1);
            setActiveSection(id);
        });
    });

    const trackedSections = sectionLinks
        .map(link => document.getElementById(link.getAttribute('href').slice(1)))
        .filter(Boolean);

    const updateActiveSection = () => {
        const topOffset = 120;
        let currentId = 'overview';

        trackedSections.forEach(section => {
            if (section.getBoundingClientRect().top <= topOffset) {
                currentId = section.id;
            }
        });

        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4) {
            const lastSection = trackedSections[trackedSections.length - 1];
            if (lastSection) currentId = lastSection.id;
        }

        setActiveSection(currentId);
    };

    let scrollTicking = false;
    const requestSectionUpdate = () => {
        if (scrollTicking) return;
        scrollTicking = true;
        requestAnimationFrame(() => {
            updateActiveSection();
            scrollTicking = false;
        });
    };

    window.addEventListener('scroll', requestSectionUpdate, { passive: true });
    window.addEventListener('resize', requestSectionUpdate);
    updateActiveSection();

    const globalSearch = document.getElementById('adminGlobalSearch');
    globalSearch?.addEventListener('input', () => {
        const term = globalSearch.value.trim().toLowerCase();
        document.querySelectorAll('.admin-table tbody tr, .message-admin-card').forEach(item => {
            item.hidden = Boolean(term) && !item.textContent.toLowerCase().includes(term);
        });
    });

    document.querySelectorAll('.admin-edit-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const panel = button.parentElement.querySelector(':scope > .admin-edit-panel') || button.nextElementSibling;
            if (!panel) return;
            panel.hidden = !panel.hidden;
            button.textContent = panel.hidden ? 'Edit' : 'Close';
        });
    });

    document.querySelectorAll('.admin-reply-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const card = button.closest('.message-admin-card');
            const panel = card?.querySelector('.admin-reply-panel');
            if (!panel) return;
            panel.hidden = !panel.hidden;
            button.textContent = panel.hidden ? 'Reply' : 'Close reply';
            if (!panel.hidden) panel.querySelector('textarea')?.focus();
        });
    });
})();
</script>
</body>
</html>
