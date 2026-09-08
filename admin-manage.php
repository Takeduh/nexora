<?php
session_start();
require_once __DIR__ . '/config/database.php';

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function selected(string $a, string $b): string { return $a === $b ? 'selected' : ''; }

if (empty($_SESSION['user_id'])) {
    header('Location: Login/login.php?next=../admin-manage.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, first_name, last_name, role FROM users WHERE id = ? LIMIT 1');
$stmt->execute([(int)$_SESSION['user_id']]);
$admin = $stmt->fetch();
if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    exit('Admin access required.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$section = $_GET['section'] ?? 'fleet';
$allowedSections = ['fleet', 'customers', 'bookings', 'payments', 'messages'];
if (!in_array($section, $allowedSections, true)) $section = 'fleet';

$notice = $_GET['crud_saved'] ?? '';
$error = $_GET['crud_error'] ?? '';

$cars = $pdo->query('SELECT * FROM cars ORDER BY brand, model')->fetchAll();
$variants = $pdo->query('SELECT v.*, c.brand, c.model FROM car_variants v JOIN cars c ON c.id = v.car_id ORDER BY c.brand, c.model, v.transmission')->fetchAll();
$users = $pdo->query("SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.role, u.created_at, (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS booking_count FROM users u ORDER BY u.created_at DESC")->fetchAll();
$bookings = $pdo->query("SELECT b.*, u.first_name, u.last_name, u.email FROM bookings b JOIN users u ON u.id = b.user_id ORDER BY b.created_at DESC LIMIT 100")->fetchAll();
$payments = $pdo->query("SELECT p.*, b.vehicle_name, u.first_name, u.last_name FROM payments p JOIN bookings b ON b.id = p.booking_id JOIN users u ON u.id = b.user_id ORDER BY p.created_at DESC LIMIT 100")->fetchAll();
$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 100')->fetchAll();

$editCar = null;
$editVariant = null;
$editUser = null;
$editBooking = null;
$editPayment = null;
foreach ($cars as $row) if ((int)$row['id'] === (int)($_GET['edit_car'] ?? 0)) $editCar = $row;
foreach ($variants as $row) if ((int)$row['id'] === (int)($_GET['edit_variant'] ?? 0)) $editVariant = $row;
foreach ($users as $row) if ((int)$row['id'] === (int)($_GET['edit_user'] ?? 0)) $editUser = $row;
foreach ($bookings as $row) if ((int)$row['id'] === (int)($_GET['edit_booking'] ?? 0)) $editBooking = $row;
foreach ($payments as $row) if ((int)$row['id'] === (int)($_GET['edit_payment'] ?? 0)) $editPayment = $row;

$returnTo = 'admin-manage.php?section=' . urlencode($section);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Records — Nexora</title>
    <link rel="stylesheet" href="output.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="dashboard.css?v=1.3">
</head>
<body class="dashboard-page admin-page">
<header class="dash-header">
    <nav class="dash-nav">
        <a href="index.php" class="dash-brand"><img src="Images/nexora-logo.png" alt="Nexora"></a>
        <div class="dash-nav-links">
            <a href="admin-dashboard.php">Admin dashboard</a>
            <a href="dashboard.php">My dashboard</a>
            <a href="fleet.php">Fleet</a>
            <a href="logout.php" class="dash-logout">Logout</a>
        </div>
    </nav>
</header>

<main class="dash-shell">
    <section class="dash-hero admin-hero">
        <div><span class="dash-eyebrow">CRUD management</span><h1>Manage Nexora records</h1><p>Create, view, update, and safely remove operational records.</p></div>
        <a href="admin-dashboard.php" class="dash-secondary-btn">Back to dashboard</a>
    </section>

    <?php if ($notice): ?><div class="dash-alert success"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="dash-alert error"><?= e($error) ?></div><?php endif; ?>

    <nav class="admin-section-nav crud-tabs" aria-label="Management sections">
        <a class="<?= $section === 'fleet' ? 'active' : '' ?>" href="?section=fleet">Fleet</a>
        <a class="<?= $section === 'customers' ? 'active' : '' ?>" href="?section=customers">Customers</a>
        <a class="<?= $section === 'bookings' ? 'active' : '' ?>" href="?section=bookings">Bookings</a>
        <a class="<?= $section === 'payments' ? 'active' : '' ?>" href="?section=payments">Payments</a>
        <a class="<?= $section === 'messages' ? 'active' : '' ?>" href="?section=messages">Messages</a>
    </nav>

    <?php if ($section === 'fleet'): ?>
        <section class="crud-grid">
            <article class="dash-panel crud-form-card">
                <span class="dash-section-label"><?= $editCar ? 'Update' : 'Create' ?></span>
                <h2><?= $editCar ? 'Edit car' : 'Add a car' ?></h2>
                <form action="admin-crud.php" method="post" class="crud-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                    <input type="hidden" name="action" value="<?= $editCar ? 'car_update' : 'car_create' ?>">
                    <?php if ($editCar): ?><input type="hidden" name="car_id" value="<?= (int)$editCar['id'] ?>"><?php endif; ?>
                    <label>Brand<input name="brand" required value="<?= e((string)($editCar['brand'] ?? '')) ?>"></label>
                    <label>Model<input name="model" required value="<?= e((string)($editCar['model'] ?? '')) ?>"></label>
                    <div class="crud-form-row"><label>Year<input name="year" type="number" min="1990" max="<?= (int)date('Y') + 2 ?>" value="<?= e((string)($editCar['year'] ?? '')) ?>"></label><label>Category<input name="category" required value="<?= e((string)($editCar['category'] ?? '')) ?>"></label></div>
                    <div class="crud-form-row"><label>Seats<input name="seats" type="number" min="1" required value="<?= e((string)($editCar['seats'] ?? 5)) ?>"></label><label>Fuel type<input name="fuel_type" required value="<?= e((string)($editCar['fuel_type'] ?? 'Gasoline')) ?>"></label></div>
                    <label>Luggage capacity<input name="luggage_capacity" type="number" min="0" value="<?= e((string)($editCar['luggage_capacity'] ?? '')) ?>"></label>
                    <label>Image path<input name="image" value="<?= e((string)($editCar['image'] ?? '')) ?>" placeholder="Images/vehicle.jpg"></label>
                    <label>Description<textarea name="description" rows="4"><?= e((string)($editCar['description'] ?? '')) ?></textarea></label>
                    <label>Status<select name="status"><option value="active" <?= selected((string)($editCar['status'] ?? 'active'), 'active') ?>>Active</option><option value="inactive" <?= selected((string)($editCar['status'] ?? 'active'), 'inactive') ?>>Inactive</option></select></label>
                    <div class="crud-actions"><button class="dash-primary-btn" type="submit"><?= $editCar ? 'Save car' : 'Add car' ?></button><?php if ($editCar): ?><a class="dash-secondary-btn" href="?section=fleet">Cancel</a><?php endif; ?></div>
                </form>
            </article>

            <article class="dash-panel crud-form-card">
                <span class="dash-section-label"><?= $editVariant ? 'Update' : 'Create' ?></span>
                <h2><?= $editVariant ? 'Edit variant' : 'Add transmission variant' ?></h2>
                <form action="admin-crud.php" method="post" class="crud-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                    <input type="hidden" name="action" value="<?= $editVariant ? 'variant_update' : 'variant_create' ?>">
                    <?php if ($editVariant): ?><input type="hidden" name="variant_id" value="<?= (int)$editVariant['id'] ?>"><?php else: ?><label>Car<select name="car_id" required><?php foreach ($cars as $car): ?><option value="<?= (int)$car['id'] ?>"><?= e($car['brand'] . ' ' . $car['model']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
                    <label>Transmission<select name="transmission"><option value="automatic" <?= selected((string)($editVariant['transmission'] ?? 'automatic'), 'automatic') ?>>Automatic</option><option value="manual" <?= selected((string)($editVariant['transmission'] ?? ''), 'manual') ?>>Manual</option></select></label>
                    <div class="crud-form-row"><label>Daily rate<input name="daily_rate" type="number" min="0.01" step="0.01" required value="<?= e((string)($editVariant['daily_rate'] ?? '')) ?>"></label><label>Quantity<input name="quantity" type="number" min="0" required value="<?= e((string)($editVariant['quantity'] ?? 1)) ?>"></label></div>
                    <label>Status<select name="status"><option value="available" <?= selected((string)($editVariant['status'] ?? 'available'), 'available') ?>>Available</option><option value="unavailable" <?= selected((string)($editVariant['status'] ?? ''), 'unavailable') ?>>Unavailable</option><option value="maintenance" <?= selected((string)($editVariant['status'] ?? ''), 'maintenance') ?>>Maintenance</option></select></label>
                    <div class="crud-actions"><button class="dash-primary-btn" type="submit"><?= $editVariant ? 'Save variant' : 'Add variant' ?></button><?php if ($editVariant): ?><a class="dash-secondary-btn" href="?section=fleet">Cancel</a><?php endif; ?></div>
                </form>
            </article>
        </section>

        <section class="dash-panel admin-section">
            <div class="dash-panel-head"><div><span class="dash-section-label">Read / update / delete</span><h2>Cars and variants</h2></div><span><?= count($cars) ?> cars</span></div>
            <div class="admin-table-wrap"><table class="admin-table crud-table"><thead><tr><th>Car</th><th>Details</th><th>Variants</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($cars as $car): ?>
                <?php $carVariants = array_values(array_filter($variants, fn($v) => (int)$v['car_id'] === (int)$car['id'])); ?>
                <tr><td><strong><?= e($car['brand'] . ' ' . $car['model']) ?></strong><small><?= e((string)$car['status']) ?></small></td><td><?= e((string)$car['category']) ?> · <?= (int)$car['seats'] ?> seats<small><?= e((string)($car['year'] ?? 'Year n/a')) ?> · <?= e((string)$car['fuel_type']) ?></small></td><td><?php foreach ($carVariants as $variant): ?><div class="crud-variant-line"><span><?= e(ucfirst($variant['transmission'])) ?> · ₱<?= number_format((float)$variant['daily_rate'], 2) ?> · Qty <?= (int)$variant['quantity'] ?></span><a href="?section=fleet&edit_variant=<?= (int)$variant['id'] ?>">Edit</a><form action="admin-crud.php" method="post" onsubmit="return confirm('Delete this variant?');"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><input type="hidden" name="action" value="variant_delete"><input type="hidden" name="variant_id" value="<?= (int)$variant['id'] ?>"><button class="link-danger">Delete</button></form></div><?php endforeach; ?></td><td><div class="crud-row-actions"><a href="?section=fleet&edit_car=<?= (int)$car['id'] ?>" class="mini-action">Edit</a><form action="admin-crud.php" method="post" onsubmit="return confirm('Delete this car and its variants? Historical booking snapshots are kept, but active/upcoming cars cannot be deleted.');"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><input type="hidden" name="action" value="car_delete"><input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>"><button class="mini-action danger">Delete</button></form></div></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        </section>
    <?php endif; ?>

    <?php if ($section === 'customers'): ?>
        <section class="crud-grid one-form">
            <article class="dash-panel crud-form-card">
                <span class="dash-section-label"><?= $editUser ? 'Update' : 'Create' ?></span><h2><?= $editUser ? 'Edit account' : 'Create account' ?></h2>
                <form action="admin-crud.php" method="post" class="crud-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><input type="hidden" name="action" value="<?= $editUser ? 'user_update' : 'user_create' ?>"><?php if ($editUser): ?><input type="hidden" name="user_id" value="<?= (int)$editUser['id'] ?>"><?php endif; ?>
                    <div class="crud-form-row"><label>First name<input name="first_name" required value="<?= e((string)($editUser['first_name'] ?? '')) ?>"></label><label>Last name<input name="last_name" required value="<?= e((string)($editUser['last_name'] ?? '')) ?>"></label></div>
                    <label>Email<input name="email" type="email" required value="<?= e((string)($editUser['email'] ?? '')) ?>"></label><label>Phone<input name="phone" value="<?= e((string)($editUser['phone'] ?? '')) ?>"></label>
                    <label>Role<select name="role"><option value="user" <?= selected((string)($editUser['role'] ?? 'user'), 'user') ?>>User</option><option value="admin" <?= selected((string)($editUser['role'] ?? ''), 'admin') ?>>Admin</option></select></label>
                    <?php if (!$editUser): ?><label>Temporary password<input name="password" type="password" minlength="8" required autocomplete="new-password"></label><?php endif; ?>
                    <div class="crud-actions"><button class="dash-primary-btn" type="submit"><?= $editUser ? 'Save account' : 'Create account' ?></button><?php if ($editUser): ?><a class="dash-secondary-btn" href="?section=customers">Cancel</a><?php endif; ?></div>
                </form>
            </article>
        </section>
        <section class="dash-panel admin-section"><div class="dash-panel-head"><div><span class="dash-section-label">Read / update / delete</span><h2>Accounts</h2></div><span><?= count($users) ?> accounts</span></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Name</th><th>Contact</th><th>Role</th><th>Bookings</th><th>Actions</th></tr></thead><tbody><?php foreach ($users as $row): ?><tr><td><strong><?= e($row['first_name'] . ' ' . $row['last_name']) ?></strong><small>#<?= (int)$row['id'] ?></small></td><td><?= e($row['email']) ?><small><?= e((string)($row['phone'] ?: 'No phone')) ?></small></td><td><?= e(ucfirst($row['role'])) ?></td><td><?= (int)$row['booking_count'] ?></td><td><div class="crud-row-actions"><a class="mini-action" href="?section=customers&edit_user=<?= (int)$row['id'] ?>">Edit</a><?php if ((int)$row['id'] !== (int)$admin['id']): ?><form action="admin-crud.php" method="post" onsubmit="return confirm('Delete this account? Accounts with booking history are retained.');"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><input type="hidden" name="action" value="user_delete"><input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>"><button class="mini-action danger">Delete</button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php endif; ?>

    <?php if ($section === 'bookings'): ?>
        <?php if ($editBooking): ?><section class="crud-grid one-form"><article class="dash-panel crud-form-card"><span class="dash-section-label">Update</span><h2>Edit booking #<?= (int)$editBooking['id'] ?></h2><form action="admin-crud.php" method="post" class="crud-form"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><input type="hidden" name="action" value="booking_update"><input type="hidden" name="booking_id" value="<?= (int)$editBooking['id'] ?>"><label>Pickup location<input name="pickup_location" required value="<?= e($editBooking['pickup_location']) ?>"></label><div class="crud-form-row"><label>Pickup date<input type="date" name="pickup_date" required value="<?= e($editBooking['pickup_date']) ?>"></label><label>Return date<input type="date" name="return_date" required value="<?= e($editBooking['return_date']) ?>"></label></div><label>Status<select name="status"><?php foreach (['pending','confirmed','active','completed','cancelled'] as $s): ?><option value="<?= $s ?>" <?= selected($editBooking['status'], $s) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></label><label>Special requests<textarea name="special_requests" rows="4"><?= e((string)$editBooking['special_requests']) ?></textarea></label><div class="crud-actions"><button class="dash-primary-btn">Save booking</button><a class="dash-secondary-btn" href="?section=bookings">Cancel</a></div></form></article></section><?php endif; ?>
        <section class="dash-panel admin-section"><div class="dash-panel-head"><div><span class="dash-section-label">Read / update</span><h2>Bookings</h2></div><span>Hard delete disabled for audit history</span></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Booking</th><th>Customer</th><th>Trip</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($bookings as $row): ?><tr><td><strong>#<?= (int)$row['id'] ?> · <?= e($row['vehicle_name']) ?></strong><small><?= e(ucfirst((string)$row['transmission'])) ?></small></td><td><?= e($row['first_name'] . ' ' . $row['last_name']) ?><small><?= e($row['email']) ?></small></td><td><?= e($row['pickup_date']) ?> → <?= e($row['return_date']) ?><small><?= e($row['pickup_location']) ?></small></td><td>₱<?= number_format((float)$row['total_amount'],2) ?></td><td><?= e(ucfirst($row['status'])) ?></td><td><div class="crud-row-actions"><a class="mini-action" href="?section=bookings&edit_booking=<?= (int)$row['id'] ?>">Edit</a><?php if (!in_array($row['status'], ['completed','cancelled'], true)): ?><form action="admin-crud.php" method="post" onsubmit="return confirm('Cancel this booking?');"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><input type="hidden" name="action" value="booking_cancel"><input type="hidden" name="booking_id" value="<?= (int)$row['id'] ?>"><button class="mini-action danger">Cancel</button></form><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php endif; ?>

    <?php if ($section === 'payments'): ?>
        <section class="crud-grid one-form"><article class="dash-panel crud-form-card"><span class="dash-section-label"><?= $editPayment ? 'Update' : 'Create' ?></span><h2><?= $editPayment ? 'Edit payment record' : 'Add payment record' ?></h2><form action="admin-crud.php" method="post" class="crud-form"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><input type="hidden" name="action" value="<?= $editPayment ? 'payment_update' : 'payment_create' ?>"><?php if ($editPayment): ?><input type="hidden" name="payment_id" value="<?= (int)$editPayment['id'] ?>"><?php endif; ?><label>Booking<select name="booking_id" required><?php foreach ($bookings as $b): ?><option value="<?= (int)$b['id'] ?>" <?= (int)($editPayment['booking_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>>#<?= (int)$b['id'] ?> · <?= e($b['vehicle_name'] . ' · ' . $b['first_name'] . ' ' . $b['last_name']) ?></option><?php endforeach; ?></select></label><div class="crud-form-row"><label>Amount<input name="amount" type="number" min="0.01" step="0.01" required value="<?= e((string)($editPayment['amount'] ?? '')) ?>"></label><label>Method<select name="payment_method"><?php foreach (['cash','gcash','card','bank_transfer'] as $m): ?><option value="<?= $m ?>" <?= selected((string)($editPayment['payment_method'] ?? 'cash'), $m) ?>><?= ucwords(str_replace('_',' ',$m)) ?></option><?php endforeach; ?></select></label></div><label>Status<select name="payment_status"><?php foreach (['pending','paid','failed','refunded'] as $s): ?><option value="<?= $s ?>" <?= selected((string)($editPayment['payment_status'] ?? 'pending'), $s) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></label><label>Transaction reference<input name="transaction_reference" value="<?= e((string)($editPayment['transaction_reference'] ?? '')) ?>"></label><div class="crud-actions"><button class="dash-primary-btn"><?= $editPayment ? 'Save payment' : 'Add payment' ?></button><?php if ($editPayment): ?><a class="dash-secondary-btn" href="?section=payments">Cancel</a><?php endif; ?></div></form></article></section>
        <section class="dash-panel admin-section"><div class="dash-panel-head"><div><span class="dash-section-label">Read / update / delete</span><h2>Payments</h2></div><span><?= count($payments) ?> shown</span></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Payment</th><th>Booking</th><th>Amount</th><th>Method</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($payments as $row): ?><tr><td><strong>#<?= (int)$row['id'] ?></strong><small><?= e((string)($row['transaction_reference'] ?: 'No reference')) ?></small></td><td>#<?= (int)$row['booking_id'] ?> · <?= e($row['vehicle_name']) ?><small><?= e($row['first_name'] . ' ' . $row['last_name']) ?></small></td><td>₱<?= number_format((float)$row['amount'],2) ?></td><td><?= e(ucwords(str_replace('_',' ',$row['payment_method']))) ?></td><td><?= e(ucfirst($row['payment_status'])) ?></td><td><div class="crud-row-actions"><a class="mini-action" href="?section=payments&edit_payment=<?= (int)$row['id'] ?>">Edit</a><form action="admin-crud.php" method="post" onsubmit="return confirm('Delete this payment record?');"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><input type="hidden" name="action" value="payment_delete"><input type="hidden" name="payment_id" value="<?= (int)$row['id'] ?>"><button class="mini-action danger">Delete</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php endif; ?>

    <?php if ($section === 'messages'): ?>
        <section class="dash-panel admin-section"><div class="dash-panel-head"><div><span class="dash-section-label">Read / update / delete</span><h2>Contact messages</h2></div><span>Created by public contact form</span></div><div class="message-admin-list"><?php foreach ($messages as $row): ?><article class="message-admin-card"><div class="message-admin-head"><div><strong><?= e($row['subject'] ?: 'General inquiry') ?></strong><span><?= e($row['name']) ?> · <?= e($row['email']) ?></span></div><small><?= e(date('m/d/Y', strtotime($row['created_at']))) ?></small></div><p><?= e($row['message']) ?></p><div class="crud-row-actions"><form action="admin-crud.php" method="post" class="inline-admin-form"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><input type="hidden" name="action" value="message_update"><input type="hidden" name="message_id" value="<?= (int)$row['id'] ?>"><select name="status"><?php foreach (['unread','read','replied'] as $s): ?><option value="<?= $s ?>" <?= selected($row['status'],$s) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select><button>Save</button></form><form action="admin-crud.php" method="post" onsubmit="return confirm('Delete this contact message?');"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="return_to" value="<?= e($returnTo) ?>"><input type="hidden" name="action" value="message_delete"><input type="hidden" name="message_id" value="<?= (int)$row['id'] ?>"><button class="mini-action danger">Delete</button></form></div></article><?php endforeach; ?></div></section>
    <?php endif; ?>
</main>
</body>
</html>
