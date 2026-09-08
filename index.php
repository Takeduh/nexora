<?php
require_once __DIR__ . '/config/fleet-data.php';
$fleetCars = getFleetCars($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nexora — Rent the Perfect Car for Every Journey</title>
  <link rel="stylesheet" href="output.css">
  <link rel="stylesheet" href="styles.css">
  <script>window.NEXORA_FLEET = <?= json_encode($fleetCars, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;</script>
  <script src="script.js" defer></script>
</head>
<body class="bg-white text-ink antialiased">

  <!-- NAVIGATION -->
  <header class="sticky top-0 z-[100] bg-navy-900/92 backdrop-blur-md border-b border-white/[0.06]">
    <nav class="flex items-center justify-between px-6 py-4 max-w-[1180px] mx-auto">
      <a href="#home" class="flex items-center gap-3 text-white">
        <img src="Images/nexora-logo.png" alt="Nexora Car Rentals" class="nexora-logo" onerror="this.style.display='none'">
      </a>
      <div class="nav-links flex items-center gap-[30px]">
        <a href="#home" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Home</a>
        <a href="#fleet" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Fleet</a>
        <a href="#services" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Services</a>
        <a href="#difference" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">About</a>
        <a href="#blog" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Blog</a>
        <a href="contact.php" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Contact</a>
      </div>
      <div class="flex items-center gap-[18px]">
        <a href="Login/login.php" class="btn btn-outline btn-sm">Login</a>
        <a href="SignUp/signup.php" class="btn btn-primary btn-sm">Sign Up</a>
      </div>
    </nav>
  </header>

  <!-- HERO -->
  <section class="hero relative overflow-hidden bg-navy-900 pt-16" id="home">
    <img src="Images/hero-bg.png" alt="Nexora rental car driving through the city" class="hero-background" onerror="this.style.display='none'">
    <div class="absolute inset-0 z-[1] pointer-events-none bg-[linear-gradient(100deg,rgba(10,23,48,0.96)_0%,rgba(10,23,48,0.82)_34%,rgba(10,23,48,0.35)_66%,rgba(10,23,48,0.15)_100%)]"></div>

    <div class="relative z-[2] max-w-[1180px] mx-auto px-6">
      <div class="max-w-[620px]">
        <h3 class="section-kicker on-dark">Drive More. Worry Less.</h3>
        <h1 class="text-white text-[46px] font-extrabold tracking-[-0.01em] mb-[18px]">Rent the Perfect Car for Every Journey</h1>
        <p class="text-white/70 text-[16.5px] leading-[1.65] max-w-[460px] mb-[30px]">From weekend getaways to business trips across the Philippines, Nexora gets you a clean, reliable car in minutes — transparent pricing, zero surprises.</p>
        <div class="flex gap-3.5 mb-10">
          <a href="#book" class="btn btn-primary">Book Now</a>
          <a href="#fleet" class="btn btn-outline">See Our Fleet</a>
        </div>
      </div>

      <!-- BOOKING WIDGET -->
      <form class="bg-white rounded-[14px] shadow-card-lg p-[22px] grid grid-cols-[1.2fr_1fr_1fr_auto] gap-3.5 items-end relative -mt-1.5 translate-y-[70px] z-[2]" id="book" novalidate>
        <div class="flex flex-col gap-1.5">
          <label for="loc" class="text-[11.5px] font-bold tracking-[0.06em] uppercase text-gray-500">Pick-up Location</label>
          <select id="loc" class="border-[1.5px] border-gray-200 rounded-[9px] px-3 py-2.5 text-sm text-ink bg-white outline-none transition focus:border-blue-500">
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
          <label for="pickup" class="text-[11.5px] font-bold tracking-[0.06em] uppercase text-gray-500">Pick-up Date</label>
          <input type="text" id="pickupDisplay" inputmode="numeric" autocomplete="off" maxlength="10" placeholder="MM/DD/YYYY" required class="border-[1.5px] border-gray-200 rounded-[9px] px-3 py-2.5 text-sm text-ink bg-white outline-none transition focus:border-blue-500">
          <input type="hidden" id="pickup">
        </div>
        <div class="flex flex-col gap-1.5">
          <label for="return" class="text-[11.5px] font-bold tracking-[0.06em] uppercase text-gray-500">Return Date</label>
          <input type="text" id="returnDisplay" inputmode="numeric" autocomplete="off" maxlength="10" placeholder="MM/DD/YYYY" required class="border-[1.5px] border-gray-200 rounded-[9px] px-3 py-2.5 text-sm text-ink bg-white outline-none transition focus:border-blue-500">
          <input type="hidden" id="return">
        </div>
        <button type="submit" class="btn btn-primary whitespace-nowrap h-11 self-end">Search Cars</button>
        <p class="booking-note" id="bookingNote"></p>
      </form>
    </div>

  </section>

  <!-- SERVICES -->
  <section class="bg-navy-900 text-white py-[88px]" id="services">
    <div class="max-w-[1180px] mx-auto px-6">
      <div class="text-center max-w-[620px] mx-auto mb-12">
        <h3 class="section-kicker on-dark">Our Services</h3>
        <h2 class="text-[36px] font-bold mb-3.5">Everything You Need for the Road.</h2>
        <p class="text-white/62 text-base leading-relaxed">From choosing your car to returning it, Nexora makes every part of your rental simple.</p>
      </div>

      <div class="grid grid-cols-3 gap-px bg-white/[0.08] rounded-2xl overflow-hidden">
        <div class="why-card">
          <div class="why-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.2" stroke="currentColor" stroke-width="1.7"/><path d="M12 4v5.8M5.1 8l5 2.9M18.9 8l-5 2.9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
          <h4 class="text-white text-base font-bold">Self-Drive Rentals</h4>
          <p class="text-white/55 text-[13.8px] leading-[1.55]">Choose your own route with clean, reliable vehicles ready for the road.</p>
        </div>
        <div class="why-card">
          <div class="why-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 3v4M16 3v4M4 10h16M8 14h3M8 17h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
          <h4 class="text-white text-base font-bold">Flexible Rental Plans</h4>
          <p class="text-white/55 text-[13.8px] leading-[1.55]">Rent by the day, week, or month with straightforward pricing.</p>
        </div>
        <div class="why-card">
          <div class="why-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 13h11l3-5h3l2 5v4H3v-4z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 13l2-5-4-3M17 8l3 5M7 17v2M18 17v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <h4 class="text-white text-base font-bold">Airport & City Pickup</h4>
          <p class="text-white/55 text-[13.8px] leading-[1.55]">Start your trip smoothly with convenient pickup and return locations.</p>
        </div>
        <div class="why-card">
          <div class="why-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="4" y="7" width="16" height="13" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M9 7V5h6v2M4 12h16M9 12v2h6v-2" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></div>
          <h4 class="text-white text-base font-bold">Corporate Travel</h4>
          <p class="text-white/55 text-[13.8px] leading-[1.55]">Dependable transportation for meetings, teams, and business trips.</p>
        </div>
        <div class="why-card">
          <div class="why-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M9 3v4M15 3v4M4 10h16l3 3-5 5-2-2-3 3-2-2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
          <h4 class="text-white text-base font-bold">Easy Online Booking</h4>
          <p class="text-white/55 text-[13.8px] leading-[1.55]">Find a vehicle and request your rental in just a few minutes.</p>
        </div>
        <div class="why-card">
          <div class="why-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M5 4h14a2 2 0 012 2v9a2 2 0 01-2 2h-5l-2 3-2-3H5a2 2 0 01-2-2V6a2 2 0 012-2z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8 11h8M8 8h5M8 14h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
          <h4 class="text-white text-base font-bold">Roadside Support</h4>
          <p class="text-white/55 text-[13.8px] leading-[1.55]">Get dependable assistance whenever you need help during your trip.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- FLEET -->
  <section class="bg-gray-50 py-[88px]" id="fleet">
    <div class="max-w-[1180px] mx-auto px-6">
      <div class="text-center max-w-[620px] mx-auto mb-12">
        <h3 class="section-kicker">Our Fleet</h3>
        <h2 class="text-[36px] font-bold mb-3.5">Cars for Every Need</h2>
        <p class="text-gray-600 text-base leading-relaxed">From family trips to luxury city drives, we have the perfect vehicle for you.</p>
      </div>

      <div class="flex justify-center gap-2 flex-wrap mb-[38px]" role="tablist" aria-label="Filter vehicles by category">
        <button class="filter-tab active" data-filter="all">All Vehicles</button>
        <button class="filter-tab" data-filter="sedan">Sedan</button>
        <button class="filter-tab" data-filter="suv">SUV</button>
        <button class="filter-tab" data-filter="mpv">MPV</button>
        <button class="filter-tab" data-filter="van">Van</button>
        <button class="filter-tab" data-filter="pickup">Pickup</button>
        <button class="filter-tab" data-filter="sport">Sport</button>
      </div>

      <!-- populated by script.js -->
      <div class="grid grid-cols-4 gap-[22px]" id="fleetGrid"></div>

      <div class="text-center mt-10">
        <a href="fleet.php" class="btn btn-dark-outline">View All Vehicles</a>
      </div>
    </div>
  </section>

  <!-- DIFFERENCE -->
  <section class="bg-white py-[88px]" id="difference">
    <div class="max-w-[1180px] mx-auto px-6">
      <div class="grid grid-cols-2 gap-[60px] items-center">
        <div>
          <h3 class="section-kicker">Experience the Nexora Difference</h3>
          <h2 class="text-[32px] font-bold mb-3.5">More Than Just a Car Rental Company</h2>
          <p class="text-gray-600 text-[15.5px] leading-[1.65] mb-[22px] max-w-[440px]">We go the extra mile to deliver a smooth and enjoyable experience from booking to drop-off.</p>
          <div class="flex flex-col gap-3.5 mb-7">
            <div class="flex items-center gap-3 text-[15px] font-semibold">
              <span class="w-6 h-6 rounded-full bg-blue-050 text-blue-500 flex items-center justify-center shrink-0"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
              Well-maintained and clean vehicles
            </div>
            <div class="flex items-center gap-3 text-[15px] font-semibold">
              <span class="w-6 h-6 rounded-full bg-blue-050 text-blue-500 flex items-center justify-center shrink-0"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
              Flexible rental options
            </div>
            <div class="flex items-center gap-3 text-[15px] font-semibold">
              <span class="w-6 h-6 rounded-full bg-blue-050 text-blue-500 flex items-center justify-center shrink-0"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
              Affordable daily, weekly, and monthly rates
            </div>
            <div class="flex items-center gap-3 text-[15px] font-semibold">
              <span class="w-6 h-6 rounded-full bg-blue-050 text-blue-500 flex items-center justify-center shrink-0"><svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
              Hassle-free pick-up and return
            </div>
          </div>
          <a href="#footer" class="btn btn-primary">Learn More</a>
        </div>

        <div class="grid grid-cols-2 grid-rows-2 gap-3.5 aspect-square">
          <div class="diff-tile bg-[linear-gradient(150deg,#173a7a,#0a1730)]">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="white" stroke-width="1.6"/><path d="M12 4v3M12 17v3M4 12h3M17 12h3" stroke="white" stroke-width="1.6"/></svg>
            <span class="text-[12.5px] font-bold tracking-[0.03em] opacity-90">Easy Driving</span>
          </div>
          <div class="diff-tile bg-[linear-gradient(150deg,#2F6FED,#1c4fc0)]">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M2 12h6l2-3h4l2 3h6" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 12v3a1 1 0 001 1h12a1 1 0 001-1v-3" stroke="white" stroke-width="1.6"/></svg>
            <span class="text-[12.5px] font-bold tracking-[0.03em] opacity-90">Service First</span>
          </div>
          <div class="diff-tile bg-[linear-gradient(150deg,#0FA36B,#0a7a4f)]">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.1 7-11.5A7 7 0 105 9.5C5 14.9 12 21 12 21z" stroke="white" stroke-width="1.6"/></svg>
            <span class="text-[12.5px] font-bold tracking-[0.03em] opacity-90">Family Trips</span>
          </div>
          <div class="diff-tile bg-[linear-gradient(150deg,#0a1730,#152a52)]">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M3 20L12 4l9 16" stroke="white" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 20l4-7 4 7" stroke="white" stroke-width="1.6" stroke-linejoin="round"/></svg>
            <span class="text-[12.5px] font-bold tracking-[0.03em] opacity-90">Open Road</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- BLOG / TESTIMONIALS -->
  <section class="bg-gray-50 py-[88px]" id="blog">
    <div class="max-w-[1180px] mx-auto px-6">
      <div class="text-center max-w-[620px] mx-auto mb-12">
        <h3 class="section-kicker">Trusted by Thousands</h3>
        <h2 class="text-[36px] font-bold mb-3.5">What Our Customers Say</h2>
      </div>

      <div class="max-w-[760px] mx-auto relative">
        <!-- populated by script.js -->
        <div class="t-card" id="tCard"></div>
      </div>
    </div>
  </section>

  <!-- FINAL CTA -->
  <section class="final-cta-section">
    <div class="final-cta-panel">
      <div class="final-cta-content">
        <span class="eyebrow on-dark">Ready to Hit the Road?</span>
        <h2>Book Your Car Today</h2>
        <p>Fast booking, great cars, better journeys.</p>
        <a href="#book" class="btn btn-primary">Book Now</a>
      </div>
    </div>
  </section>

  <!-- TRUST STRIP -->
  <div class="bg-navy-800 border-t border-white/[0.08]">
    <div class="max-w-[1180px] mx-auto px-6 flex justify-between flex-wrap gap-[18px] py-[22px]">
      <div class="trust-item"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="text-blue-400 shrink-0"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6"/></svg>Free cancellation up to 24 hrs before pick-up</div>
      <div class="trust-item"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="text-blue-400 shrink-0"><path d="M3 12h18M3 6h18M3 18h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>No hidden fees</div>
      <div class="trust-item"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="text-blue-400 shrink-0"><rect x="4" y="10" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 10V7a4 4 0 018 0v3" stroke="currentColor" stroke-width="1.6"/></svg>Secure, encrypted payments</div>
      <div class="trust-item"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="text-blue-400 shrink-0"><path d="M12 2l2.9 6.26L21 9.27l-4.5 4.36L17.8 20 12 16.9 6.2 20l1.3-6.37L3 9.27 9.1 8.26 12 2z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>Loyalty points on every rental</div>
    </div>
  </div>

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
            <a href="#fleet">Our Fleet</a>
            <a href="#services">Services</a>
            <a href="#difference">About Nexora</a>
            <a href="#blog">Customer Stories</a>
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

        <section class="newsletter-panel" aria-labelledby="newsletter-title">
          <span class="footer-kicker">STAY UPDATED</span>
          <h2 id="newsletter-title">Keep your next trip close.</h2>
          <p>Get the latest offers, updates, and travel tips.</p>
          <form class="newsletter-form" id="newsletterForm">
            <input type="email" placeholder="Enter your email" aria-label="Email address" required>
            <button type="submit">Subscribe</button>
          </form>
          <p id="newsletterMessage" class="newsletter-message hidden" aria-live="polite"></p>
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
