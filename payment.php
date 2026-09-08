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
    header('Location: Login/login.php');
    exit;
}

$bookingId = (int)($_GET['booking'] ?? $_POST['booking_id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT id, vehicle_name, transmission, pickup_location, dropoff_location, pickup_date, return_date, total_days, daily_rate, total_amount, status
     FROM bookings
     WHERE id = ? AND user_id = ?
     LIMIT 1'
);
$stmt->execute([$bookingId, (int)$_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(404);
    exit('Booking not found.');
}

$error = '';
$saved = isset($_GET['saved']);
$selectedMethod = $_POST['payment_method'] ?? '';
$reference = trim($_POST['transaction_reference'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowedMethods = ['cash', 'gcash', 'card', 'bank_transfer'];

    if (!in_array($selectedMethod, $allowedMethods, true)) {
        $error = 'Please choose a payment method.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_reference)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $bookingId,
            $booking['total_amount'],
            $selectedMethod,
            'pending',
            $reference !== '' ? $reference : null,
        ]);

        header('Location: payment.php?booking=' . $bookingId . '&saved=1');
        exit;
    }
}

$methodLabels = [
    'gcash' => ['GCash', 'Record a GCash payment or reference number.'],
    'card' => ['Card', 'Save card as the chosen method. No charge is processed yet.'],
    'bank_transfer' => ['Bank transfer', 'Record a transfer reference from your bank.'],
    'cash' => ['Cash', 'Pay according to the rental handover arrangement.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment — Nexora</title>
    <meta name="description" content="Review your Nexora booking and choose a payment method.">
    <link rel="stylesheet" href="output.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="payment.css?v=3.0">
</head>
<body class="payment-page">
<header class="payment-header">
    <nav class="payment-nav">
        <a href="index.php" class="payment-brand" aria-label="Nexora home">
            <img src="Images/nexora-logo.png" alt="Nexora">
        </a>
        <div class="payment-security"><span aria-hidden="true">✓</span> Secure booking step</div>
    </nav>
</header>

<main class="payment-shell">
    <div class="payment-progress" aria-label="Booking progress">
        <span class="done">1. Vehicle</span>
        <span class="done">2. Rental details</span>
        <span class="current">3. Payment</span>
    </div>

    <div class="payment-heading">
        <div>
            <span class="payment-eyebrow">Booking #<?= (int)$booking['id'] ?></span>
            <h1>Choose how you’ll pay</h1>
            <p>Review your booking, select a payment method, and save it for confirmation.</p>
        </div>
        <div class="payment-status-chip"><?= e(ucfirst($booking['status'])) ?></div>
    </div>

    <?php if ($saved): ?>
        <div class="payment-alert payment-alert-success" role="status">
            <strong>Payment method saved.</strong>
            <span>Your booking is still pending confirmation. No external charge has been made by this page.</span>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="payment-alert payment-alert-error" role="alert">
            <strong>Payment method required.</strong>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="payment-layout">
        <section class="payment-main-card">
            <div class="payment-section-heading">
                <span>Payment method</span>
                <small>Select one option</small>
            </div>

            <form method="post" class="payment-form">
                <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">

                <div class="payment-method-grid">
                    <?php foreach ($methodLabels as $value => [$label, $description]): ?>
                        <label class="payment-method-card">
                            <input type="radio" name="payment_method" value="<?= e($value) ?>" <?= $selectedMethod === $value ? 'checked' : '' ?> required>
                            <span class="payment-method-marker" aria-hidden="true"></span>
                            <span class="payment-method-copy">
                                <strong><?= e($label) ?></strong>
                                <small><?= e($description) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label class="payment-reference-field">
                    <span>Transaction or reference number <small>Optional</small></span>
                    <input type="text" name="transaction_reference" maxlength="255" value="<?= e($reference) ?>" placeholder="Example: TXN-2026-001234">
                    <small>Useful for GCash or bank-transfer records. Leave blank for cash or if you don’t have one yet.</small>
                </label>

                <div class="payment-notice">
                    <span class="payment-notice-icon" aria-hidden="true">i</span>
                    <p><strong>Prototype payment recording:</strong> this page stores your selected payment method in Nexora. It does not charge a card, GCash account, or bank account yet.</p>
                </div>

                <button type="submit" class="payment-submit">Save payment method <span aria-hidden="true">→</span></button>
            </form>
        </section>

        <aside class="payment-summary-card">
            <div class="payment-summary-title">
                <span>Booking summary</span>
                <a href="fleet.php">Change vehicle</a>
            </div>

            <div class="payment-vehicle">
                <div class="payment-vehicle-mark">N</div>
                <div>
                    <strong><?= e($booking['vehicle_name']) ?></strong>
                    <span><?= e(ucfirst((string)($booking['transmission'] ?? ''))) ?> transmission</span>
                </div>
            </div>

            <dl class="payment-details">
                <div>
                    <dt>Pick-up</dt>
                    <dd><?= e(displayDate($booking['pickup_date'])) ?></dd>
                </div>
                <div>
                    <dt>Return</dt>
                    <dd><?= e(displayDate($booking['return_date'])) ?></dd>
                </div>
                <div>
                    <dt>Duration</dt>
                    <dd><?= (int)$booking['total_days'] ?> day<?= (int)$booking['total_days'] === 1 ? '' : 's' ?></dd>
                </div>
                <div>
                    <dt>Daily rate</dt>
                    <dd>₱<?= number_format((float)$booking['daily_rate'], 2) ?></dd>
                </div>
            </dl>

            <div class="payment-location-block">
                <span>Pick-up location</span>
                <strong><?= e($booking['pickup_location']) ?></strong>
                <?php if ($booking['dropoff_location'] !== $booking['pickup_location']): ?>
                    <small>Return: <?= e($booking['dropoff_location']) ?></small>
                <?php endif; ?>
            </div>

            <div class="payment-total">
                <span>Estimated total</span>
                <strong>₱<?= number_format((float)$booking['total_amount'], 2) ?></strong>
            </div>

            <p class="payment-summary-note">Final booking confirmation is handled separately from this payment-recording step.</p>
        </aside>
    </div>

    <div class="payment-bottom-links">
        <a href="fleet.php">← Back to fleet</a>
        <a href="contact.php">Need help? Contact support</a>
    </div>
</main>
</body>
</html>
