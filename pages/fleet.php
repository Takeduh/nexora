<?php
session_start();
require_once dirname(__DIR__) . '/config/fleet-data.php';
$fleetCars = getFleetCars($pdo, '../');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Our Fleet — Nexora</title>
  <link rel="stylesheet" href="../base.css">
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="fleet.css?v=20260908-2">
  <script>
    window.NEXORA_FLEET = <?= json_encode($fleetCars, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    window.NEXORA_IS_AUTHENTICATED = <?= !empty($_SESSION['user_id']) ? 'true' : 'false' ?>;
  </script>
  <script src="fleet.js?v=20260908-2" defer></script>
  <link rel="stylesheet" href="../responsive.css?v=1.0">
</head>
<body class="bg-white text-ink antialiased">
  
  <?php
    $siteRoot = '../';
    $siteHeaderVariant = 'public';
    $siteActivePage = 'fleet';
    require dirname(__DIR__) . '/includes/header.php';
  ?>


  <section class="fleet-hero">
    <div class="fleet-shell fleet-hero-inner">
      <div>
        <span class="fleet-eyebrow">NEXORA FLEET</span>
        <h1>Choose the right car for the road ahead.</h1>
        <p>Compare categories, transmissions, and daily rates in one place. Simple choices, clear pricing.</p>
      </div>
      <div class="fleet-hero-stat">
        <strong><?= count($fleetCars) ?></strong>
        <span>vehicle models currently listed</span>
      </div>
    </div>
  </section>

  
  <section class="fleet-trip-section">
    <div class="fleet-shell">
      <form class="fleet-trip-card" id="fleetSearchForm" novalidate>
        <div class="fleet-trip-heading">
          <span>Trip details</span>
          <small>Optional — you can also choose dates during checkout.</small>
        </div>

        <label class="fleet-field">
          <span>Pick-up location</span>
          <select id="fleetLoc">
            <option>Cebu City</option>
            <option>Manila</option>
            <option>Davao</option>
            <option>Cagayan de Oro</option>
            <option>Dumaguete</option>
            <option>Palawan</option>
            <option>Bohol</option>
          </select>
        </label>

        <label class="fleet-field">
          <span>Pick-up date</span>
          <input type="text" id="fleetPickupDisplay" inputmode="numeric" autocomplete="off" maxlength="10" placeholder="MM/DD/YYYY">
          <input type="hidden" id="fleetPickup">
        </label>

        <label class="fleet-field">
          <span>Return date</span>
          <input type="text" id="fleetReturnDisplay" inputmode="numeric" autocomplete="off" maxlength="10" placeholder="MM/DD/YYYY">
          <input type="hidden" id="fleetReturn">
        </label>

        <button type="submit" class="btn btn-primary fleet-apply-btn">Apply trip details</button>
        <p class="fleet-form-note" id="fleetFormNote" aria-live="polite"></p>
      </form>
    </div>
  </section>

  
  <section class="fleet-results-section">
    <div class="fleet-shell">
      <div class="fleet-results-header">
        <div>
          <span class="fleet-eyebrow fleet-eyebrow-dark">AVAILABLE VEHICLES</span>
          <h2>Browse the fleet</h2>
          <p id="fleetResultsCount">Choose a category or search for a specific model.</p>
        </div>

        <div class="fleet-sort-wrap">
          <label for="fleetSort">Sort by</label>
          <select id="fleetSort">
            <option value="recommended">Recommended</option>
            <option value="price-asc">Price: Low to High</option>
            <option value="price-desc">Price: High to Low</option>
            <option value="name-asc">Name: A to Z</option>
            <option value="name-desc">Name: Z to A</option>
            <option value="seats-desc">Seats: Most to Least</option>
          </select>
        </div>
      </div>

      <div class="fleet-toolbar">
        <div class="fleet-search-box">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.8-3.8"></path></svg>
          <input type="text" id="fleetKeyword" placeholder="Search Toyota, SUV, Vios...">
        </div>

        <div class="fleet-category-tabs" role="tablist" aria-label="Filter vehicles by category">
          <button class="filter-tab active" data-filter="all">All</button>
          <button class="filter-tab" data-filter="sedan">Sedan</button>
          <button class="filter-tab" data-filter="suv">SUV</button>
          <button class="filter-tab" data-filter="mpv">MPV</button>
          <button class="filter-tab" data-filter="van">Van</button>
          <button class="filter-tab" data-filter="pickup">Pickup</button>
          <button class="filter-tab" data-filter="sport">Sport</button>
        </div>
      </div>

      <div class="fleet-grid" id="fleetResultsGrid"></div>

      <div class="fleet-empty hidden" id="fleetEmptyState">
        <strong>No vehicles found</strong>
        <span>Try another category or search term.</span>
      </div>

      <div class="fleet-load-more-wrap">
        <button type="button" class="btn btn-dark-outline" id="fleetLoadMore">Load More Vehicles</button>
      </div>
    </div>
  </section>

  
  <?php
    $siteFooterNewsletter = false;
    require dirname(__DIR__) . '/includes/footer.php';
  ?>

</body>
</html>
