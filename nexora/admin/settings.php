<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php?next=admin/settings.php');
    exit;
}

$adminId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT id, first_name, last_name, email, phone, role, password, created_at
     FROM users
     WHERE id = ?
     LIMIT 1'
);
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin || $admin['role'] !== 'admin') {
    http_response_code(403);
    exit('Admin access required.');
}

$_SESSION['user_name'] = $admin['first_name'];
$_SESSION['user_role'] = 'admin';
$_SESSION['user_email'] = $admin['email'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$notice = $_GET['saved'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } else {
        try {
            $action = $_POST['action'] ?? '';

            if ($action === 'profile_update') {
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');

                if ($firstName === '' || $lastName === '') {
                    throw new RuntimeException('First name and last name are required.');
                }

                if (
                    strlen($firstName) > 100 ||
                    strlen($lastName) > 100 ||
                    strlen($phone) > 30
                ) {
                    throw new RuntimeException('One or more profile fields are too long.');
                }

                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET first_name = ?, last_name = ?, phone = ?
                     WHERE id = ? AND role = \'admin\''
                );
                $stmt->execute([
                    $firstName,
                    $lastName,
                    $phone !== '' ? $phone : null,
                    $adminId
                ]);

                $_SESSION['user_name'] = $firstName;

                header('Location: settings.php?saved=profile');
                exit;
            }

            if ($action === 'email_update') {
                $newEmail = strtolower(trim($_POST['new_email'] ?? ''));
                $currentPassword = $_POST['current_password'] ?? '';

                if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Enter a valid new email address.');
                }

                if (!password_verify($currentPassword, $admin['password'])) {
                    throw new RuntimeException('Your current password is incorrect.');
                }

                if ($newEmail === strtolower($admin['email'])) {
                    throw new RuntimeException('Enter a different email address.');
                }

                $check = $pdo->prepare(
                    'SELECT id
                     FROM users
                     WHERE email = ? AND id <> ?
                     LIMIT 1'
                );
                $check->execute([$newEmail, $adminId]);

                if ($check->fetch()) {
                    throw new RuntimeException('That email address is already in use.');
                }

                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET email = ?
                     WHERE id = ? AND role = \'admin\''
                );
                $stmt->execute([$newEmail, $adminId]);

                $_SESSION['user_email'] = $newEmail;

                header('Location: settings.php?saved=email');
                exit;
            }

            if ($action === 'password_update') {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (!password_verify($currentPassword, $admin['password'])) {
                    throw new RuntimeException('Your current password is incorrect.');
                }

                if (strlen($newPassword) < 8) {
                    throw new RuntimeException('Your new password must be at least 8 characters.');
                }

                if ($newPassword !== $confirmPassword) {
                    throw new RuntimeException('The new passwords do not match.');
                }

                if (password_verify($newPassword, $admin['password'])) {
                    throw new RuntimeException('Choose a password different from your current password.');
                }

                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET password = ?
                     WHERE id = ? AND role = \'admin\''
                );
                $stmt->execute([
                    password_hash($newPassword, PASSWORD_DEFAULT),
                    $adminId
                ]);

                session_regenerate_id(true);

                header('Location: settings.php?saved=password');
                exit;
            }
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000'
                ? 'That email address is already in use.'
                : 'The change could not be saved.';
        } catch (Throwable $exception) {
            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'The change could not be saved.';
        }
    }
}

$noticeMessages = [
    'profile' => 'Admin profile updated.',
    'email' => 'Admin email address updated.',
    'password' => 'Admin password changed successfully.'
];

$noticeText = $noticeMessages[$notice] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Settings — Nexora</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/shared.css?v=1.5">
    <link rel="stylesheet" href="../assets/css/admin.css?v=1.3">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=1.0">
</head>
<body class="dashboard-page admin-page admin-dashboard-v2 admin-settings-v2">
<div class="admin-app">
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="../index.php" class="admin-sidebar-brand">
            <img src="../Images/nexora-logo.png" alt="Nexora">
        </a>

        <nav class="admin-sidebar-nav" aria-label="Admin navigation">
            <a href="admin-dashboard.php#overview"><span>⌂</span>Overview</a>
            <a href="admin-dashboard.php#bookings"><span>▦</span>Bookings</a>
            <a href="admin-dashboard.php#fleet"><span>◆</span>Fleet</a>
            <a href="admin-dashboard.php#customers"><span>●</span>Customers</a>
            <a href="admin-dashboard.php#payments"><span>₱</span>Payments</a>
            <a href="admin-dashboard.php#messages"><span>✉</span>Support</a>
            <a href="admin-manage.php"><span>▤</span>Manage Records</a>
            <a class="active" href="settings.php"><span>⚙</span>Account Settings</a>
        </nav>

        <div class="admin-sidebar-bottom">
            <a href="../fleet.php">View public fleet</a>
            <a href="../logout.php" class="admin-sidebar-logout">Logout</a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <button
                class="admin-menu-toggle"
                id="adminMenuToggle"
                type="button"
                aria-label="Toggle admin menu"
            >☰</button>

            <div class="admin-settings-top-title">
                <strong>Account Settings</strong>
                <small>Manage your admin profile and sign-in security</small>
            </div>

            <div class="admin-profile">
                <span class="admin-profile-role">Admin</span>
                <span class="admin-avatar">
                    <?= e(strtoupper(substr($admin['first_name'], 0, 1))) ?>
                </span>
                <div>
                    <strong><?= e($admin['first_name'] . ' ' . $admin['last_name']) ?></strong>
                    <small><?= e($admin['email']) ?></small>
                </div>
            </div>
        </header>

        <div class="dash-shell admin-shell admin-settings-shell">
            <?php if ($noticeText): ?>
                <div class="dash-alert success"><?= e($noticeText) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="dash-alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <section class="admin-settings-heading">
                <div>
                    <span class="dash-eyebrow">Administrator account</span>
                    <h1>Account settings</h1>
                    <p>
                        Update your personal information, login email, and password
                        without leaving the admin area.
                    </p>
                </div>

                <a href="admin-dashboard.php" class="dash-secondary-btn">
                    Back to dashboard
                </a>
            </section>

            <div class="admin-settings-grid">
                <article class="dash-panel admin-settings-card">
                    <div class="admin-settings-card-head">
                        <span>Personal information</span>
                        <h2>Profile</h2>
                    </div>

                    <form method="post" class="admin-settings-form">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>"
                        >
                        <input type="hidden" name="action" value="profile_update">

                        <div class="admin-settings-form-row">
                            <label>
                                First name
                                <input
                                    name="first_name"
                                    maxlength="100"
                                    required
                                    value="<?= e($admin['first_name']) ?>"
                                >
                            </label>

                            <label>
                                Last name
                                <input
                                    name="last_name"
                                    maxlength="100"
                                    required
                                    value="<?= e($admin['last_name']) ?>"
                                >
                            </label>
                        </div>

                        <label>
                            Phone number
                            <input
                                name="phone"
                                maxlength="30"
                                value="<?= e((string)$admin['phone']) ?>"
                                placeholder="+63 912 345 6789"
                            >
                        </label>

                        <div class="admin-settings-readonly">
                            <div>
                                <span>Account type</span>
                                <strong>Administrator</strong>
                            </div>

                            <div>
                                <span>Member since</span>
                                <strong>
                                    <?= e(date('m/d/Y', strtotime($admin['created_at']))) ?>
                                </strong>
                            </div>
                        </div>

                        <button type="submit" class="dash-primary-btn">
                            Save profile
                        </button>
                    </form>
                </article>

                <article class="dash-panel admin-settings-card">
                    <div class="admin-settings-card-head">
                        <span>Sign-in email</span>
                        <h2>Change email</h2>
                    </div>

                    <div class="admin-current-email">
                        <span>Current email</span>
                        <strong><?= e($admin['email']) ?></strong>
                    </div>

                    <form method="post" class="admin-settings-form">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>"
                        >
                        <input type="hidden" name="action" value="email_update">

                        <label>
                            New email address
                            <input
                                type="email"
                                name="new_email"
                                autocomplete="email"
                                required
                                placeholder="newadmin@example.com"
                            >
                        </label>

                        <label>
                            Current password
                            <input
                                type="password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                                placeholder="Confirm your password"
                            >
                        </label>

                        <small class="admin-settings-help">
                            Your current password is required before changing your login email.
                        </small>

                        <button type="submit" class="dash-primary-btn">
                            Change email
                        </button>
                    </form>
                </article>

                <article class="dash-panel admin-settings-card admin-settings-password">
                    <div class="admin-settings-card-head">
                        <span>Security</span>
                        <h2>Change password</h2>
                    </div>

                    <form method="post" class="admin-settings-form">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>"
                        >
                        <input type="hidden" name="action" value="password_update">

                        <label>
                            Current password
                            <input
                                type="password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >
                        </label>

                        <div class="admin-settings-form-row">
                            <label>
                                New password
                                <input
                                    type="password"
                                    name="new_password"
                                    autocomplete="new-password"
                                    minlength="8"
                                    required
                                    placeholder="At least 8 characters"
                                >
                            </label>

                            <label>
                                Confirm new password
                                <input
                                    type="password"
                                    name="confirm_password"
                                    autocomplete="new-password"
                                    minlength="8"
                                    required
                                    placeholder="Re-enter password"
                                >
                            </label>
                        </div>

                        <small class="admin-settings-help">
                            Use at least 8 characters. Your password is stored as a secure hash.
                        </small>

                        <button type="submit" class="dash-primary-btn">
                            Change password
                        </button>
                    </form>
                </article>
            </div>
        </div>
    </main>
</div>

<script>
(() => {
    const sidebar = document.getElementById('adminSidebar');

    document.getElementById('adminMenuToggle')?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
    });

    document.querySelectorAll('.admin-sidebar-nav a').forEach((link) => {
        link.addEventListener('click', () => sidebar?.classList.remove('open'));
    });
})();
</script>
</body>
</html>
