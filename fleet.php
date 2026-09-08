<?php
require_once __DIR__ . '/config/fleet-data.php';
$fleetCars = getFleetCars($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Our Fleet — Nexora</title>
  <link rel="stylesheet" href="output.css">
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="fleet.css?v=20260908-1">
  <script>window.NEXORA_FLEET = <?= json_encode($fleetCars, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;</script>
  <script src="fleet.js" defer></script>
</head>
<body class="bg-white text-ink antialiased">

  <!-- NAVIGATION -->
  <header class="sticky top-0 z-[100] bg-navy-900/92 backdrop-blur-md border-b border-white/[0.06]">
    <nav class="flex items-center justify-between px-6 py-4 max-w-[1180px] mx-auto">
      <a href="index.php" class="flex items-center gap-3 text-white">
        <img src="Images/nexora-logo.png" alt="Nexora Car Rentals" class="nexora-logo" onerror="this.style.display='none'">
      </a>
      <div class="nav-links flex items-center gap-[30px]">
        <a href="index.php" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Home</a>
        <a href="fleet.php" class="text-white text-[14.5px] font-semibold hover:text-white transition">Fleet</a>
        <a href="index.php#services" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Services</a>
        <a href="index.php#difference" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">About</a>
        <a href="index.php#blog" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Blog</a>
        <a href="contact.php" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Contact</a>
      </div>
      <div class="flex items-center gap-[18px]">
        <a href="Login/login.php" class="btn btn-outline btn-sm">Login</a>
        <a href="SignUp/signup.php" class="btn btn-primary btn-sm">Sign Up</a>
      </div>
    </nav>
  </header>

  <!-- PAGE HEADER -->
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

  <!-- TRIP DETAILS -->
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

  <!-- RESULTS -->
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

  <!-- FOOTER -->
  <footer class="footer-revamp" id="footer">
    <div class="footer-inner max-w-[1180px] mx-auto">
      <div class="footer-topline">
        <span class="footer-kicker">NEXORA / ROAD AHEAD</span>
        <p>Good cars, clear roads, and a better way to get there.</p>
      </div>

      <div class="footer-grid">
        <section class="footer-column footer-brand-column" aria-labelledby="footer-brand-title">
          <h2 id="footer-brand-title">Drive with confidence.</h2>
          <p>Reliable rentals, straightforward support, and vehicles ready for the journeys that matter.</p>
          <div class="social-list" aria-label="Social media links">
            <a href="https://www.facebook.com" aria-label="Facebook" class="social-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H9v3h2v7h3v-7h3l1-3h-4v-1.5c0-.5.5-.5.5-.5z" fill="currentColor"/></svg><span>Facebook</span></a>
            <a href="https://www.instagram.com" aria-label="Instagram" class="social-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.6"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg><span>Instagram</span></a>
            <a href="https://x.com" aria-label="X" class="social-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 4l16 16M20 4L4 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span>X</span></a>
            <a href="https://www.linkedin.com" aria-label="LinkedIn" class="social-link"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.6"/><path d="M8 10v7M8 7v.01M12 17v-4.5c0-1.4 1-2.5 2.5-2.5S17 11 17 12.5V17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg><span>LinkedIn</span></a>
          </div>
        </section>

        <section class="footer-column" aria-labelledby="footer-explore-title">
          <h2 id="footer-explore-title" class="footer-heading">Explore</h2>
          <nav class="footer-links" aria-label="Footer navigation">
            <a href="fleet.php">Our Fleet</a>
            <a href="index.php#services">Services</a>
            <a href="index.php#difference">About Nexora</a>
            <a href="index.php#blog">Customer Stories</a>
          </nav>
        </section>

        <section class="footer-column" aria-labelledby="footer-contact-title">
          <h2 id="footer-contact-title" class="footer-heading">Contact</h2>
          <p><a href="contact.php" style="font-weight:700">Send us a message</a></p>
          <ul class="contact-list">
            <li class="contact-item"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg><a href="mailto:hello@nexora-rentals.com">hello@nexora-rentals.com</a></li>
            <li class="contact-item"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h3l1.5 4-2 1.5a14 14 0 006 6l1.5-2 4 1.5v3a2 2 0 01-2 2C11.4 19 5 12.6 5 5a2 2 0 012-2z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg><a href="tel:+63325550147">+63 32 555 0147</a></li>
            <li class="contact-item"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.1 7-11.5A7 7 0 105 9.5C5 14.9 12 21 12 21z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="9.5" r="2.2" stroke="currentColor" stroke-width="1.7"/></svg><span>Cebu Business Park<br>Cebu City, Philippines</span></li>
          </ul>
          <p class="footer-hours"><strong>Open daily</strong><br>6:00 AM – 10:00 PM</p>
        </section>
      </div>

      <div class="footer-bottom">
        <span>© 2026 Nexora Car Rentals. All rights reserved.</span>
        <div class="footer-meta"><span>Secure payments</span><span>Privacy</span><span>Terms</span></div>
      </div>
    </div>
  </footer>

</body>
</html>
