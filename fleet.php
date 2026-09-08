<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Our Fleet — Nexora</title>
  <link rel="stylesheet" href="output.css">
  <link rel="stylesheet" href="styles.css">
  <script src="vehicles-data.js" defer></script>
  <script src="script.js" defer></script>
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
        <a href="index.php#footer" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Contact</a>
      </div>
      <div class="flex items-center gap-[18px]">
        <a href="Login/login.php" class="btn btn-outline btn-sm">Login</a>
        <a href="SignUp/signup.php" class="btn btn-primary btn-sm">Sign Up</a>
      </div>
    </nav>
  </header>

  <!-- PAGE HEADER -->
  <section class="bg-navy-900 text-white py-[88px]">
    <div class="max-w-[1180px] mx-auto px-6 text-center">
      <h3 class="section-kicker on-dark">Our Full Fleet</h3>
      <h1 class="text-[36px] font-extrabold tracking-[-0.01em] mb-3.5">Find Your Perfect Ride</h1>
      <p class="text-white/62 text-base leading-relaxed max-w-[460px] mx-auto">Browse every vehicle in the Nexora fleet, filter by type, and pick the one that fits your trip.</p>
    </div>
  </section>

  <!-- SEARCH -->
  <section class="bg-gray-50 border-b border-gray-200">
    <div class="max-w-[1180px] mx-auto px-6 py-[30px]">
      <form class="bg-white rounded-[14px] shadow-card-lg p-[22px] grid grid-cols-[1.2fr_1fr_1fr_auto] gap-3.5 items-end" id="fleetSearchForm" novalidate>
        <div class="flex flex-col gap-1.5">
          <label for="fleetLoc" class="text-[11.5px] font-bold tracking-[0.06em] uppercase text-gray-500">Pick-up Location</label>
          <select id="fleetLoc" class="border-[1.5px] border-gray-200 rounded-[9px] px-3 py-2.5 text-sm text-ink bg-white outline-none transition focus:border-blue-500">
            <option>Cebu City</option>
            <option>Manila</option>
            <option>Davao</option>
            <option>Cagayan de Oro</option>
            <option>Dumaguete</option>
            <option>Palawan</option>
            <option>Bohol</option>
          </select>
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="fleetPickup" class="text-[11.5px] font-bold tracking-[0.06em] uppercase text-gray-500">Pick-up Date</label>
          <input type="date" id="fleetPickup" class="border-[1.5px] border-gray-200 rounded-[9px] px-3 py-2.5 text-sm text-ink bg-white outline-none transition focus:border-blue-500">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="fleetReturn" class="text-[11.5px] font-bold tracking-[0.06em] uppercase text-gray-500">Return Date</label>
          <input type="date" id="fleetReturn" class="border-[1.5px] border-gray-200 rounded-[9px] px-3 py-2.5 text-sm text-ink bg-white outline-none transition focus:border-blue-500">
        </div>
        <button type="submit" class="btn btn-primary whitespace-nowrap h-11 self-end">Apply to Search</button>
      </form>
    </div>
  </section>

  <!-- RESULTS -->
  <section class="bg-white py-[88px]">
    <div class="max-w-[1180px] mx-auto px-6">

      <div class="flex flex-wrap items-center justify-between gap-3.5 mb-7">
        <div class="fleet-keyword-wrap">
          <input type="text" id="fleetKeyword" placeholder="Search by car name..." class="w-full border-[1.5px] border-gray-200 rounded-[9px] px-3.5 py-2.5 text-sm text-ink bg-white outline-none transition focus:border-blue-500">
        </div>
        <div class="flex justify-center gap-2 flex-wrap" role="tablist" aria-label="Filter vehicles by category">
          <button class="filter-tab active" data-filter="all">All Vehicles</button>
          <button class="filter-tab" data-filter="sedan">Sedan</button>
          <button class="filter-tab" data-filter="suv">SUV</button>
          <button class="filter-tab" data-filter="mpv">MPV</button>
          <button class="filter-tab" data-filter="van">Van</button>
          <button class="filter-tab" data-filter="pickup">Pickup</button>
          <button class="filter-tab" data-filter="sport">Sport</button>
        </div>
      </div>

      <p class="text-sm text-gray-500 mb-5" id="fleetResultsCount"></p>

      <!-- populated by fleet.js -->
      <div class="grid grid-cols-4 gap-[22px]" id="fleetResultsGrid"></div>

      <p class="text-center text-gray-500 py-[30px] hidden" id="fleetEmptyState">No vehicles match your filters. Try a different category or keyword.</p>

      <div class="text-center mt-10">
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
