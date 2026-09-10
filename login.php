<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/validation.php';
$error = '';
$email = '';
$requestedNext = trim((string)($_GET['next'] ?? $_POST['next'] ?? ''));

function normalizeNext(string $next, string $fallback): string
{
    $next = html_entity_decode(trim($next), ENT_QUOTES, 'UTF-8');

    if (
        $next === '' ||
        str_contains($next, "") ||
        str_contains($next, "
") ||
        str_contains($next, '://') ||
        str_starts_with($next, '//')
    ) {
        return $fallback;
    }

    $parts = parse_url($next);

    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
        return $fallback;
    }

    $path = ltrim($parts['path'] ?? '', '/');

    $legacyPaths = [
        '../booking/booking-summary.php' => 'booking-summary.php',
        'booking/booking-summary.php' => 'booking-summary.php',
        '../account/dashboard.php' => 'dashboard.php',
        'account/dashboard.php' => 'dashboard.php',
        '../account/settings.php' => 'settings.php',
        'account/settings.php' => 'settings.php',
        '../pages/fleet.php' => 'fleet.php',
        'pages/fleet.php' => 'fleet.php',
        '../index.php' => 'index.php'
    ];

    if (isset($legacyPaths[$path])) {
        $path = $legacyPaths[$path];
    }

    $allowedPaths = [
        'booking-summary.php',
        'dashboard.php',
        'settings.php',
        'fleet.php',
        'index.php'
    ];

    if (!in_array($path, $allowedPaths, true)) {
        return $fallback;
    }

    $result = $path;

    if (!empty($parts['query'])) {
        $result .= '?' . $parts['query'];
    }

    if (!empty($parts['fragment'])) {
        $result .= '#' . rawurlencode($parts['fragment']);
    }

    return $result;
}

$next = normalizeNext(
    $requestedNext !== '' ? $requestedNext : (string)($_SESSION['auth_next'] ?? ''),
    'dashboard.php'
);

if ($requestedNext !== '') {
    $_SESSION['auth_next'] = $next;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $inputErrors = validationErrors([
        validateRequired($email, 'Email'),
        validateEmailFormat($email),
        validateRequired($password, 'Password'),
    ]);

    $user = false;
    if (!$inputErrors) {
        $stmt = $pdo->prepare('SELECT id, first_name, role, password FROM users WHERE email = :email LIMIT 1');
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['first_name'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        $defaultNext = $_SESSION['user_role'] === 'admin' ? 'admin/admin-dashboard.php' : 'dashboard.php';
        $destination = $requestedNext !== '' || !empty($_SESSION['auth_next'])
            ? normalizeNext($next, $defaultNext)
            : $defaultNext;
        unset($_SESSION['auth_next']);
        header('Location: ' . $destination);
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login | Nexora Car Rentals</title><link rel="stylesheet" href="assets/css/auth.css">    <link rel="stylesheet" href="assets/css/responsive.css?v=1.0">
</head>
<body><main class="auth-page"><section class="auth-panel" aria-labelledby="login-title">
<a class="brand" href="index.php" aria-label="Nexora home"><img src="Images/nexora.png" alt="Nexora Car Rentals"></a>
<div class="auth-heading"><p class="eyebrow">Welcome back</p><h1 id="login-title">Log in to Nexora</h1><p class="intro">Manage your bookings and get back on the road.</p></div>
<?php if ($error): ?><div class="auth-message auth-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form class="auth-form" method="post" autocomplete="on"><input type="hidden" name="next" value="<?php echo htmlspecialchars($next, ENT_QUOTES, 'UTF-8'); ?>"><div class="field"><label for="email">Email address</label><input type="email" id="email" name="email" autocomplete="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($email); ?>" required></div>
<div class="field"><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="current-password" placeholder="Enter your password" required></div>
<button type="submit">Login</button></form>
<p class="auth-prompt">Don't have an account? <a href="signup.php<?php echo $next !== '' ? '?next=' . rawurlencode($next) : ''; ?>">Sign up</a></p><a class="back-link" href="index.php">Back to Nexora</a>
</section></main></body></html>
