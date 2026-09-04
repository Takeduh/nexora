<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | Nexora Car Rentals</title>
  <link rel="stylesheet" href="signup.css">
</head>
<body>
  <main class="signup-page">
    <section class="signup-panel" aria-labelledby="signup-title">
      <a class="brand" href="../index.php" aria-label="Nexora home">
        <img src="../Images/nexora.png" alt="Nexora Car Rentals">
      </a>

      <div class="signup-heading">
        <p class="eyebrow">Start your journey</p>
        <h1 id="signup-title">Create your Nexora account</h1>
        <p class="intro">Sign up to manage bookings and make every trip easier.</p>
      </div>

      <form class="signup-form" method="post" autocomplete="on">
        <div class="name-fields">
          <div class="field">
            <label for="first-name">First name</label>
            <input type="text" id="first-name" name="first_name" autocomplete="given-name" placeholder="Juan" required>
          </div>

          <div class="field">
            <label for="last-name">Last name</label>
            <input type="text" id="last-name" name="last_name" autocomplete="family-name" placeholder="Dela Cruz" required>
          </div>
        </div>

        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" autocomplete="email" placeholder="you@example.com" required>
        </div>

        <div class="field">
          <label for="phone">Phone number <span>(optional)</span></label>
          <input type="tel" id="phone" name="phone" autocomplete="tel" placeholder="+63 912 345 6789">
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" autocomplete="new-password" placeholder="At least 8 characters" minlength="8" required>
        </div>

        <div class="field">
          <label for="confirm-password">Confirm password</label>
          <input type="password" id="confirm-password" name="confirm_password" autocomplete="new-password" placeholder="Re-enter your password" minlength="8" required>
        </div>

        <label class="terms">
          <input type="checkbox" name="terms" value="1" required>
          <span>I agree to Nexora's <a href="#terms">Terms of Service</a> and <a href="#privacy">Privacy Policy</a>.</span>
        </label>

        <button type="submit">Create account</button>
      </form>

      <p class="login-prompt">Already have an account? <a href="../Login/login.php">Log in</a></p>
      <a class="back-link" href="../index.php">Back to Nexora</a>
    </section>
  </main>
</body>
</html>
