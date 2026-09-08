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

if (empty($_SESSION['user_id'])) {
    header('Location: Login/login.php?next=../admin-dashboard.php');
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

$notice = $_GET['saved'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'booking_status') {
                $allowed = ['pending', 'confirmed', 'active', 'completed', 'cancelled'];
                $status = $_POST['status'] ?? '';
                if (!in_array($status, $allowed, true)) throw new RuntimeException('Invalid booking status.');
                $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
                $stmt->execute([$status, (int)($_POST['booking_id'] ?? 0)]);
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
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'The update could not be saved.';
        }
    }
}

$stats = [
    'users' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'pending' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'active' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'active'")->fetchColumn(),
    'unread' => (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn(),
];

$bookings = $pdo->query(
    "SELECT b.id, b.vehicle_name, b.transmission, b.pickup_location, b.pickup_date, b.return_date, b.total_amount, b.status, b.created_at,
            u.first_name, u.last_name, u.email,
            (SELECT p.payment_status FROM payments p WHERE p.booking_id = b.id ORDER BY p.id DESC LIMIT 1) AS payment_status
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     ORDER BY b.created_at DESC
     LIMIT 30"
)->fetchAll();

$variants = $pdo->query(
    "SELECT v.id, v.transmission, v.daily_rate, v.quantity, v.status, c.brand, c.model, c.category
     FROM car_variants v
     JOIN cars c ON c.id = v.car_id
     ORDER BY c.brand, c.model, v.transmission"
)->fetchAll();

$messages = $pdo->query(
    "SELECT id, name, email, subject, message, status, created_at
     FROM contact_messages
     ORDER BY created_at DESC
     LIMIT 20"
)->fetchAll();

$users = $pdo->query(
    "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
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
    <link rel="stylesheet" href="output.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="dashboard.css?v=1.2">
</head>
<body class="dashboard-page admin-page">
<header class="dash-header">
    <nav class="dash-nav">
        <a href="index.php" class="dash-brand"><img src="Images/nexora-logo.png" alt="Nexora"></a>
        <div class="dash-nav-links">
            <a href="dashboard.php">My dashboard</a>
            <a href="fleet.php">Fleet</a>
            <a href="contact.php">Contact</a>
            <a href="logout.php" class="dash-logout">Logout</a>
        </div>
    </nav>
</header>

<main class="dash-shell">
    <section class="dash-hero admin-hero">
        <div>
            <span class="dash-eyebrow">Nexora administration</span>
            <h1>Operations dashboard</h1>
            <p>Manage bookings, fleet availability, customers, payments, and support messages.</p>
        </div>
        <div class="admin-identity"><span>Administrator</span><strong><?= e($admin['first_name'] . ' ' . $admin['last_name']) ?></strong></div>
    </section>

    <?php if ($notice): ?><div class="dash-alert success">Update saved successfully.</div><?php endif; ?>
    <?php if ($error): ?><div class="dash-alert error"><?= e($error) ?></div><?php endif; ?>

    <section class="dash-stats">
        <article><span>Customers</span><strong><?= $stats['users'] ?></strong></article>
        <article><span>Pending bookings</span><strong><?= $stats['pending'] ?></strong></article>
        <article><span>Active rentals</span><strong><?= $stats['active'] ?></strong></article>
        <article><span>Unread messages</span><strong><?= $stats['unread'] ?></strong></article>
    </section>

    <nav class="admin-section-nav" aria-label="Dashboard sections">
        <a href="#bookings">Bookings</a>
        <a href="#fleet">Fleet variants</a>
        <a href="#customers">Customers</a>
        <a href="#payments">Payments</a>
        <a href="#messages">Messages</a>
    </nav>

    <section class="dash-panel admin-section" id="bookings">
        <div class="dash-panel-head"><div><span class="dash-section-label">Reservations</span><h2>Recent bookings</h2></div><span><?= count($bookings) ?> shown</span></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Booking</th><th>Customer</th><th>Trip</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><strong>#<?= (int)$booking['id'] ?> · <?= e($booking['vehicle_name']) ?></strong><small><?= e(ucfirst((string)$booking['transmission'])) ?></small></td>
                        <td><strong><?= e($booking['first_name'] . ' ' . $booking['last_name']) ?></strong><small><?= e($booking['email']) ?></small></td>
                        <td><strong><?= e(displayDate($booking['pickup_date'])) ?> → <?= e(displayDate($booking['return_date'])) ?></strong><small><?= e($booking['pickup_location']) ?></small></td>
                        <td>₱<?= number_format((float)$booking['total_amount'], 2) ?></td>
                        <td><?= e(ucfirst((string)($booking['payment_status'] ?? 'not recorded'))) ?></td>
                        <td>
                            <form method="post" class="inline-admin-form">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="booking_status">
                                <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                                <select name="status" aria-label="Booking status">
                                    <?php foreach (['pending','confirmed','active','completed','cancelled'] as $status): ?><option value="<?= $status ?>" <?= $booking['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?>
                                </select>
                                <button>Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="dash-panel admin-section" id="fleet">
        <div class="dash-panel-head"><div><span class="dash-section-label">Inventory</span><h2>Fleet variants</h2></div><a href="fleet.php">View public fleet</a></div>
        <div class="inventory-list" id="fleetVariantList">
            <div class="inventory-list-head" aria-hidden="true">
                <span>Vehicle</span><span>Transmission / rate</span><span>Quantity</span><span>Status</span><span>Action</span>
            </div>
            <?php foreach ($variants as $index => $variant): ?>
            <article class="inventory-list-row"<?= $index >= 8 ? ' hidden' : '' ?>>
                <div class="inventory-vehicle">
                    <span class="inventory-category"><?= e($variant['category']) ?></span>
                    <strong><?= e($variant['brand'] . ' ' . $variant['model']) ?></strong>
                </div>
                <div class="inventory-meta">
                    <strong><?= e(ucfirst($variant['transmission'])) ?></strong>
                    <span>₱<?= number_format((float)$variant['daily_rate'], 2) ?>/day</span>
                </div>
                <form method="post" class="inventory-row-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="variant_update">
                    <input type="hidden" name="variant_id" value="<?= (int)$variant['id'] ?>">
                    <label class="inventory-field"><span>Quantity</span><input type="number" min="0" name="quantity" value="<?= (int)$variant['quantity'] ?>"></label>
                    <label class="inventory-field"><span>Status</span><select name="status"><?php foreach (['available','unavailable','maintenance'] as $status): ?><option value="<?= $status ?>" <?= $variant['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label>
                    <button type="submit">Update</button>
                </form>
            </article>
            <?php endforeach; ?>
        </div>
        <?php if (count($variants) > 8): ?>
        <div class="inventory-load-more">
            <div class="inventory-buttons">
                <button type="button" id="loadMoreVariants" class="dash-secondary-btn">Load more</button>
                <button type="button" id="showLessVariants" class="dash-secondary-btn" hidden>Show less</button>
            </div>
            <span id="variantCount">Showing 8 of <?= count($variants) ?> variants</span>
        </div>
        <?php endif; ?>
    </section>

    <section class="dash-panel admin-section" id="customers">
        <div class="dash-panel-head"><div><span class="dash-section-label">Accounts</span><h2>Recent customers</h2></div><span><?= count($users) ?> shown</span></div>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Customer</th><th>Contact</th><th>Bookings</th><th>Joined</th></tr></thead><tbody>
        <?php foreach ($users as $customer): ?><tr><td><strong><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></strong><small>User #<?= (int)$customer['id'] ?></small></td><td><strong><?= e($customer['email']) ?></strong><small><?= e($customer['phone'] ?: 'No phone') ?></small></td><td><?= (int)$customer['booking_count'] ?></td><td><?= e(displayDate($customer['created_at'])) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>

    <section class="dash-panel admin-section" id="payments">
        <div class="dash-panel-head"><div><span class="dash-section-label">Records</span><h2>Recent payments</h2></div><span><?= count($payments) ?> shown</span></div>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Customer</th><th>Booking</th><th>Method</th><th>Amount</th><th>Status</th><th>Reference</th></tr></thead><tbody>
        <?php foreach ($payments as $payment): ?><tr><td><?= e($payment['first_name'] . ' ' . $payment['last_name']) ?></td><td>#<?= (int)$payment['booking_id'] ?> · <?= e($payment['vehicle_name']) ?></td><td><?= e(ucwords(str_replace('_',' ',$payment['payment_method']))) ?></td><td>₱<?= number_format((float)$payment['amount'], 2) ?></td><td><?= e(ucfirst($payment['payment_status'])) ?></td><td><?= e($payment['transaction_reference'] ?: '—') ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>

    <section class="dash-panel admin-section" id="messages">
        <div class="dash-panel-head"><div><span class="dash-section-label">Support</span><h2>Contact messages</h2></div><span><?= count($messages) ?> shown</span></div>
        <div class="message-admin-list">
            <?php foreach ($messages as $message): ?>
            <article class="message-admin-card">
                <div class="message-admin-head"><div><strong><?= e($message['subject'] ?: 'General inquiry') ?></strong><span><?= e($message['name']) ?> · <?= e($message['email']) ?></span></div><small><?= e(date('m/d/Y g:i A', strtotime($message['created_at']))) ?></small></div>
                <p><?= e($message['message']) ?></p>
                <form method="post" class="inline-admin-form message-status-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="message_status">
                    <input type="hidden" name="message_id" value="<?= (int)$message['id'] ?>">
                    <select name="status"><?php foreach (['unread','read','replied'] as $status): ?><option value="<?= $status ?>" <?= $message['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select>
                    <button>Save status</button>
                </form>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<script>
(() => {
    const list = document.getElementById('fleetVariantList');
    const loadMore = document.getElementById('loadMoreVariants');
    const showLess = document.getElementById('showLessVariants');
    const counter = document.getElementById('variantCount');
    const initialVisible = 8;

    if (!list || !loadMore || !showLess) return;

    const rows = [...list.querySelectorAll('.inventory-list-row')];

    const updateControls = () => {
        const visible = rows.filter(row => !row.hidden).length;
        if (counter) counter.textContent = `Showing ${visible} of ${rows.length} variants`;
        loadMore.hidden = visible >= rows.length;
        showLess.hidden = visible <= initialVisible;
    };

    loadMore.addEventListener('click', () => {
        rows.filter(row => row.hidden).slice(0, 8).forEach(row => { row.hidden = false; });
        updateControls();
    });

    showLess.addEventListener('click', () => {
        rows.forEach((row, index) => { row.hidden = index >= initialVisible; });
        updateControls();
        document.getElementById('fleet')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    updateControls();
})();
</script>
</body>
</html>
