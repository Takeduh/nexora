<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $next = 'booking-summary.php' . ($query !== '' ? '?' . $query : '');
    header('Location: login.php?' . http_build_query(['next' => $next]));
    exit;
}

require_once __DIR__ . '/config/database.php';
$variantId = filter_input(INPUT_GET, 'variant', FILTER_VALIDATE_INT);
$location = trim($_GET['loc'] ?? '');
$pickup = $_GET['pickup'] ?? '';
$returnDate = $_GET['return'] ?? '';

$vehicle = null;
if ($variantId) {
    $stmt = $pdo->prepare("SELECT
            v.id AS variant_id,
            v.transmission,
            v.daily_rate,
            v.quantity,
            c.brand,
            c.model,
            c.category,
            c.seats,
            c.fuel_type,
            c.image
        FROM car_variants v
        INNER JOIN cars c ON c.id = v.car_id
        WHERE v.id = ?
          AND c.status = 'active'
          AND v.status = 'available'
        LIMIT 1");
    $stmt->execute([$variantId]);
    $vehicle = $stmt->fetch() ?: null;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function validDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
}

$days = null;
$total = null;

$availabilityRemaining = null;
$availabilityLow = false;
$availabilityFull = false;
if ($vehicle && validDate($pickup) && validDate($returnDate)) {
    $aStart = new DateTimeImmutable($pickup);
    $aEnd = new DateTimeImmutable($returnDate);
    if ($aEnd > $aStart && $aStart >= new DateTimeImmutable('today')) {
        $availabilityStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE car_variant_id=? AND status IN ('pending','confirmed','active') AND pickup_date < ? AND return_date > ?");
        $availabilityStmt->execute([(int)$vehicle['variant_id'], $returnDate, $pickup]);
        $reserved = (int)$availabilityStmt->fetchColumn();
        $availabilityRemaining = max(0, (int)$vehicle['quantity'] - $reserved);
        $availabilityLow = $availabilityRemaining > 0 && $availabilityRemaining <= 2;
        $availabilityFull = $availabilityRemaining < 1;
    }
}

if ($vehicle && validDate($pickup) && validDate($returnDate)) {
    $start = new DateTime($pickup);
    $end = new DateTime($returnDate);
    if ($end > $start) {
        $days = (int)$start->diff($end)->days;
        $total = $days * (float)$vehicle['daily_rate'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout — Nexora</title>
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/booking-summary.css?v=20260908-1">
  <link rel="stylesheet" href="assets/css/responsive.css?v=1.0">
</head>
<body class="checkout-page">
  <?php
    $siteRoot = ''; $siteHeaderVariant = 'checkout';
    $siteCheckoutLabel = 'Secure checkout';
    require __DIR__ . '/includes/header.php';
  ?>

<main class="checkout-shell">
    <?php if (!$vehicle): ?>
      <div class="checkout-not-found">
        <h1>We couldn't find that vehicle variant</h1>
        <p>The selected transmission may be unavailable or the booking link may be incomplete.</p>
        <a href="fleet.php" class="btn btn-primary">Browse the Fleet</a>
      </div>
    <?php else: ?>
      <?php
        $vehicleName = trim($vehicle['brand'] . ' ' . $vehicle['model']);
        $categoryClass = preg_replace('/[^a-z0-9-]/', '', strtolower($vehicle['category']));
        $transmissionLabel = ucfirst(strtolower($vehicle['transmission']));
      ?>

      <div class="checkout-title-row">
        <div>
          <p class="checkout-eyebrow">NEXORA RENTALS</p>
          <h1>Review your booking</h1>
          <p>Confirm your rental details before continuing to final confirmation.</p>
        </div>
        <a href="fleet.php" class="checkout-change-car">← Change vehicle</a>
      </div>

      <form id="bookingCheckoutForm" method="post" action="process-booking.php" class="checkout-grid"
            data-daily-rate="<?= e((string)$vehicle['daily_rate']) ?>">
        <input type="hidden" name="car_variant_id" value="<?= (int)$vehicle['variant_id'] ?>">
        <input type="hidden" id="returnQuery" name="return_query" value="">

        <div class="checkout-main">
          <section class="checkout-card checkout-vehicle-card">
            <div class="checkout-card-heading">
              <span class="checkout-step">1</span>
              <div>
                <h2>Your vehicle</h2>
                <p>The selected model and transmission for this reservation.</p>
              </div>
            </div>

            <div class="checkout-vehicle">
              <div class="checkout-vehicle-media media-<?= e($categoryClass) ?>">
                <?php if (!empty($vehicle['image'])): ?>
                  <img src="<?= e((preg_match('~^(?:https?:)?//~i', (string)$vehicle['image']) ? '' : '') . (string)$vehicle['image']) ?>" alt="<?= e($vehicleName) ?>" onerror="this.style.display='none'">
                <?php endif; ?>
              </div>
              <div class="checkout-vehicle-info">
                <span class="checkout-category"><?= e($vehicle['category']) ?></span>
                <h3><?= e($vehicleName) ?></h3>
                <p>
                  <?= (int)$vehicle['seats'] ?> seats
                  <span>•</span>
                  <?= e($transmissionLabel) ?>
                  <span>•</span>
                  <?= e($vehicle['fuel_type']) ?>
                </p>
                <div class="checkout-rate">
                  <strong>₱<?= number_format((float)$vehicle['daily_rate'], 0) ?></strong>
                  <span>/ day</span>
                </div>
              </div>
            </div>
          </section>

          <section class="checkout-card">
            <div class="checkout-card-heading">
              <span class="checkout-step">2</span>
              <div>
                <h2>Rental details</h2>
                <p>You can complete or change your dates here.</p>
              </div>
            </div>

            <div class="checkout-fields">
              <div class="checkout-field checkout-field-full">
                <label for="pickupLocation">Pick-up location</label>
                <input
                  id="pickupLocation"
                  name="pickup_location"
                  type="text"
                  value="<?= e($location) ?>"
                  placeholder="Example: SM City Cebu"
                  autocomplete="street-address"
                  aria-describedby="locationMessage"
                  required
                >
                <small id="locationMessage">Enter any address or landmark that includes a supported major area, such as Cebu, Dumaguete, Bohol, Bacolod, Iloilo, or Manila.</small>
              </div>

              <div class="checkout-field">
                <label for="pickupDateDisplay">Pick-up date</label>
                <input
                  id="pickupDateDisplay"
                  type="text"
                  inputmode="numeric"
                  autocomplete="off"
                  placeholder="MM/DD/YYYY"
                  value="<?= validDate($pickup) ? e((new DateTime($pickup))->format('m/d/Y')) : '' ?>"
                  maxlength="10"
                  aria-describedby="dateMessage"
                  required
                >
                <input
                  id="pickupDate"
                  name="pickup_date"
                  type="hidden"
                  value="<?= validDate($pickup) ? e($pickup) : '' ?>"
                >
              </div>

              <div class="checkout-field">
                <label for="returnDateDisplay">Return date</label>
                <input
                  id="returnDateDisplay"
                  type="text"
                  inputmode="numeric"
                  autocomplete="off"
                  placeholder="MM/DD/YYYY"
                  value="<?= validDate($returnDate) ? e((new DateTime($returnDate))->format('m/d/Y')) : '' ?>"
                  maxlength="10"
                  aria-describedby="dateMessage"
                  required
                >
                <input
                  id="returnDate"
                  name="return_date"
                  type="hidden"
                  value="<?= validDate($returnDate) ? e($returnDate) : '' ?>"
                >
              </div>
            </div>

            <div id="dateMessage" class="checkout-date-message" role="status" aria-live="polite"></div>
          </section>

          <section class="checkout-card">
            <div class="checkout-card-heading">
              <span class="checkout-step">3</span>
              <div>
                <h2>Optional requests</h2>
                <p>Add anything our rental team should know.</p>
              </div>
            </div>

            <div class="checkout-field checkout-field-full">
              <label for="specialRequests">Special requests</label>
              <textarea id="specialRequests" name="special_requests" rows="4" placeholder="Example: child seat, accessibility request, arrival note..."></textarea>
              <small>Requests are subject to availability and are not included in the estimated total unless confirmed.</small>
            </div>
          </section>
        </div>

        <aside class="checkout-sidebar">
          <div class="checkout-order-card">
            <h2>Order summary</h2>

            <div class="checkout-order-line">
              <span>Vehicle</span>
              <strong><?= e($vehicleName) ?></strong>
            </div>
            <div class="checkout-order-line">
              <span>Transmission</span>
              <strong><?= e($transmissionLabel) ?></strong>
            </div>
            <div class="checkout-order-line">
              <span>Daily rate</span>
              <strong>₱<?= number_format((float)$vehicle['daily_rate'], 0) ?></strong>
            </div>
            <div class="checkout-order-line">
              <span>Duration</span>
              <strong id="summaryDuration"><?= $days ? $days . ($days === 1 ? ' day' : ' days') : 'Select dates' ?></strong>
            </div>

            <div class="checkout-order-divider"></div>

            <div class="checkout-total-row">
              <div>
                <span>Estimated total</span>
                <small id="summaryFormula">
                  <?= $days ? $days . ' × ₱' . number_format((float)$vehicle['daily_rate'], 0) : 'Based on your rental duration' ?>
                </small>
              </div>
              <strong id="summaryTotal"><?= $total !== null ? '₱' . number_format($total, 0) : '—' ?></strong>
            </div>

            <div id="availabilityMessage" class="checkout-date-message <?= $availabilityFull ? 'is-error' : ($availabilityRemaining !== null ? 'is-success' : '') ?>"><?php if ($availabilityRemaining !== null): ?><?= $availabilityFull ? 'Fully booked for these dates.' : ($availabilityLow ? 'Low availability: only '.(int)$availabilityRemaining.' left for these dates.' : (int)$availabilityRemaining.' available for these dates.') ?><?php endif; ?></div>
            <button id="continueBookingButton" type="submit" class="checkout-primary-button" <?= (!$days || $location === '' || $availabilityFull) ? 'disabled' : '' ?>>
              Confirm Booking
            </button>

            <p class="checkout-disclaimer">
              Your vehicle's price and availability are verified again before the booking is created.
            </p>

            <div class="checkout-trust-list">
              <div><span>✓</span> Price verified by the server</div>
              <div><span>✓</span> Availability checked before booking</div>
              <div><span>✓</span> No charge is made on this page</div>
            </div>
          </div>
        </aside>
      </form>
    <?php endif; ?>
  </main>

  <?php if ($vehicle): ?>
    <script src="assets/js/booking-summary.js?v=20260908-2"></script>
  <?php endif; ?>
</body>
</html>
