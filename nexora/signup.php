<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/validation.php';

$errors = [];
$values = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];
$next = trim((string)($_GET['next'] ?? $_POST['next'] ?? ''));

function safeNext(string $next, string $fallback): string
{
    if ($next === '' || str_contains($next, '://') || str_starts_with($next, '//')) {
        return $fallback;
    }
    return $next;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) {
        $values[$key] = trim($_POST[$key] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = validationErrors([
        validateRequired($values['first_name'], 'First name'),
        validateRequired($values['last_name'], 'Last name'),
        validateRequired($values['email'], 'Email'),
        validateEmailFormat($values['email']),
        validateLength($password, 'Password', 8),
    ]);
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    if (!isset($_POST['terms'])) $errors[] = 'You must accept the Terms of Service and Privacy Policy.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$values['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, phone, password)
                 VALUES (:first_name, :last_name, :email, :phone, :password)'
            );
            $stmt->bindValue(':first_name', $values['first_name']);
            $stmt->bindValue(':last_name', $values['last_name']);
            $stmt->bindValue(':email', strtolower($values['email']));
            $stmt->bindValue(':phone', $values['phone'] !== '' ? $values['phone'] : null);
            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT));
            $stmt->execute();
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['user_name'] = $values['first_name'];
            $_SESSION['user_role'] = 'user';
            header('Location: ' . safeNext($next, 'dashboard.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | Nexora Car Rentals</title><link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="assets/css/responsive.css?v=1.0">
</head>
<body><main class="auth-page"><section class="auth-panel wide" aria-labelledby="signup-title">
<a class="brand" href="index.php" aria-label="Nexora home"><img src="Images/nexora.png" alt="Nexora Car Rentals"></a>
<div class="auth-heading"><p class="eyebrow">Start your journey</p><h1 id="signup-title">Create your Nexora account</h1><p class="intro">Sign up to manage bookings and make every trip easier.</p></div>
<?php if ($errors): ?><div class="auth-message auth-error"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div><?php endif; ?>
<form class="auth-form compact-gap" method="post" autocomplete="on"><input type="hidden" name="next" value="<?php echo htmlspecialchars($next, ENT_QUOTES, 'UTF-8'); ?>">
<div class="name-fields"><div class="field"><label for="first-name">First name</label><input type="text" id="first-name" name="first_name" autocomplete="given-name" placeholder="Juan" value="<?php echo htmlspecialchars($values['first_name']); ?>" required></div>
<div class="field"><label for="last-name">Last name</label><input type="text" id="last-name" name="last_name" autocomplete="family-name" placeholder="Dela Cruz" value="<?php echo htmlspecialchars($values['last_name']); ?>" required></div></div>
<div class="field"><label for="email">Email address</label><input type="email" id="email" name="email" autocomplete="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($values['email']); ?>" required></div>
<div class="field"><label for="phone">Phone number <span>(optional)</span></label><input type="tel" id="phone" name="phone" autocomplete="tel" placeholder="+63 912 345 6789" value="<?php echo htmlspecialchars($values['phone']); ?>"></div>
<div class="field"><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="new-password" placeholder="At least 8 characters" minlength="8" required></div>
<div class="field"><label for="confirm-password">Confirm password</label><input type="password" id="confirm-password" name="confirm_password" autocomplete="new-password" placeholder="Re-enter your password" minlength="8" required></div>
<label class="check-row top-align"><input type="checkbox" name="terms" value="1" required><span>I agree to Nexora's Terms of Service and Privacy Policy.</span></label>
<button type="submit">Create account</button></form>
<p class="auth-prompt">Already have an account? <a href="login.php<?php echo $next !== '' ? '?next=' . rawurlencode($next) : ''; ?>">Log in</a></p><a class="back-link" href="index.php">Back to Nexora</a>
</section></main></body></html>
