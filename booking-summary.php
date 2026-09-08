<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Summary — Nexora</title>
  <link rel="stylesheet" href="output.css">
  <link rel="stylesheet" href="styles.css">
  <script src="vehicles-data.js" defer></script>
  <script src="booking-summary.js" defer></script>
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
        <a href="fleet.php" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Fleet</a>
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
      <h3 class="section-kicker on-dark">Almost There</h3>
      <h1 class="text-[32px] font-extrabold tracking-[-0.01em]">Booking Summary</h1>
    </div>
  </section>

  <!-- SUMMARY -->
  <section class="bg-gray-50 py-[88px]">
    <div class="max-w-[760px] mx-auto px-6">

      <!-- populated by booking-summary.js -->
      <div id="summaryContent"></div>

      <div id="summaryNotFound" class="text-center bg-white rounded-[14px] shadow-card-lg p-[22px] hidden">
        <h2 class="text-[19px] font-bold mb-2.5">We couldn't find that vehicle</h2>
        <p class="text-gray-600 mb-5">It may have been removed, or the link is incomplete. Head back to the fleet to pick a car.</p>
        <a href="fleet.php" class="btn btn-primary">Browse the Fleet</a>
      </div>

    </div>
  </section>

</body>
</html>
