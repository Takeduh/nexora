<?php
session_start();
require_once __DIR__ . '/config/database.php';
if (empty($_SESSION['user_id'])) { header('Location: Login/login.php'); exit; }
$bookingId = (int)($_GET['booking'] ?? $_POST['booking_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$bookingId, (int)$_SESSION['user_id']]);
$booking = $stmt->fetch();
if (!$booking) { http_response_code(404); exit('Booking not found.'); }
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['payment_method'] ?? '';
    $allowed = ['cash','gcash','card','bank_transfer'];
    if (in_array($method, $allowed, true)) {
        $reference = trim($_POST['transaction_reference'] ?? '');
        $payStatus = $method === 'cash' ? 'pending' : 'pending';
        $stmt = $pdo->prepare('INSERT INTO payments (booking_id,amount,payment_method,payment_status,transaction_reference) VALUES (?,?,?,?,?)');
        $stmt->execute([$bookingId,$booking['total_amount'],$method,$payStatus,$reference !== '' ? $reference : null]);
        $message = 'Payment option saved. Your booking is pending confirmation.';
    } else $message = 'Please select a payment method.';
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Payment — Nexora</title><link rel="stylesheet" href="output.css"><link rel="stylesheet" href="styles.css"></head>
<body class="bg-gray-50 text-ink antialiased"><main class="max-w-[760px] mx-auto px-6 py-[70px]"><a href="index.php"><img src="Images/nexora-logo.png" alt="Nexora" style="max-width:170px;background:#0A1730;padding:12px;border-radius:10px"></a>
<div class="bg-white rounded-[14px] shadow-card-lg p-[22px] mt-8"><h1 class="text-2xl font-extrabold mb-4">Payment</h1>
<p class="mb-2"><strong>Booking #<?php echo $booking['id']; ?></strong> — <?php echo htmlspecialchars($booking['vehicle_name']); ?></p><p class="mb-5">Total: <strong>PHP <?php echo number_format((float)$booking['total_amount'], 2); ?></strong></p>
<?php if ($message): ?><p class="mb-4 text-sm font-semibold"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
<form method="post" class="grid gap-4"><input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>"><label>Payment method<select name="payment_method" required class="w-full border rounded-[9px] px-3 py-2.5 mt-1"><option value="">Select method</option><option value="cash">Cash</option><option value="gcash">GCash</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option></select></label>
<label>Transaction/reference number <span class="text-gray-500">(optional for now)</span><input type="text" name="transaction_reference" class="w-full border rounded-[9px] px-3 py-2.5 mt-1"></label><button class="btn btn-primary" type="submit">Save Payment Method</button></form>
<p class="text-sm text-gray-500 mt-4">This page records payment information only. It does not charge a card or GCash account yet.</p></div></main></body></html>
