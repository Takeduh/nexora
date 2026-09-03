<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Nexora Car Rentals</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
  <main class="login-page">
    <section class="login-panel" aria-labelledby="login-title">
      <a class="brand" href="../index.php" aria-label="Nexora home">
        <img src="../Images/nexora.png" alt="Nexora Car Rentals">
      </a>

      <div class="login-heading">
        <p class="eyebrow">Welcome back</p>
        <h1 id="login-title">Log in to Nexora</h1>
        <p class="intro">Manage your bookings and get back on the road.</p>
      </div>

      <form class="login-form" method="post" autocomplete="on">
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" autocomplete="email" placeholder="you@example.com" required>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
        </div>

        <label class="remember-me">
          <input type="checkbox" name="remember" value="1">
          <span>Remember me</span>
        </label>

        <button type="submit">Login</button>
      </form>

      <p class="signup-prompt">Don't have an account? <a href="../index.php">Sign up</a></p>
      <a class="back-link" href="../index.php">Back to Nexora</a>
    </section>
  </main>
</body>
</html>
