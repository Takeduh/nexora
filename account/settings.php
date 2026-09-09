<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?next=../account/settings.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT id, first_name, last_name, email, phone, role, password, created_at
     FROM users
     WHERE id = ?
     LIMIT 1'
);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../auth/logout.php');
    exit;
}

$_SESSION['user_name'] = $user['first_name'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_email'] = $user['email'];

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

                if (strlen($firstName) > 100 || strlen($lastName) > 100 || strlen($phone) > 30) {
                    throw new RuntimeException('One or more profile fields are too long.');
                }

                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET first_name = ?, last_name = ?, phone = ?
                     WHERE id = ?'
                );
                $stmt->execute([$firstName, $lastName, $phone !== '' ? $phone : null, $userId]);

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

                if (!password_verify($currentPassword, $user['password'])) {
                    throw new RuntimeException('Your current password is incorrect.');
                }

                if ($newEmail === strtolower($user['email'])) {
                    throw new RuntimeException('Enter a different email address.');
                }

                $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
                $check->execute([$newEmail, $userId]);

                if ($check->fetch()) {
                    throw new RuntimeException('That email address is already in use.');
                }

                $pdo->beginTransaction();

                $oldEmail = $user['email'];

                $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
                $stmt->execute([$newEmail, $userId]);

                $stmt = $pdo->prepare(
                    'UPDATE contact_messages
                     SET email = ?
                     WHERE user_id IS NULL AND LOWER(email) = LOWER(?)'
                );
                $stmt->execute([$newEmail, $oldEmail]);

                $pdo->commit();

                $_SESSION['user_email'] = $newEmail;
                header('Location: settings.php?saved=email');
                exit;
            }

            if ($action === 'password_update') {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (!password_verify($currentPassword, $user['password'])) {
                    throw new RuntimeException('Your current password is incorrect.');
                }

                if (strlen($newPassword) < 8) {
                    throw new RuntimeException('Your new password must be at least 8 characters.');
                }

                if ($newPassword !== $confirmPassword) {
                    throw new RuntimeException('The new passwords do not match.');
                }

                if (password_verify($newPassword, $user['password'])) {
                    throw new RuntimeException('Choose a password different from your current password.');
                }

                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

                session_regenerate_id(true);
                header('Location: settings.php?saved=password');
                exit;
            }
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $exception->getCode() === '23000'
                ? 'That email address is already in use.'
                : 'The change could not be saved.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'The change could not be saved.';
        }
    }
}

$noticeMessages = [
    'profile' => 'Personal information updated.',
    'email' => 'Email address updated.',
    'password' => 'Password changed successfully.'
];

$noticeText = $noticeMessages[$notice] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings — Nexora</title>
    <link rel="stylesheet" href="../base.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="../shared.css?v=1.5">
    <link rel="stylesheet" href="dashboard.css?v=2.1">
    <link rel="stylesheet" href="../responsive.css?v=1.0">
</head>
<body class="customer-dashboard">
<div class="user-app">
    <aside class="user-sidebar" id="userSidebar">
        <a href="../index.php" class="user-sidebar-brand">
            <img src="../Images/nexora-logo.png" alt="Nexora">
        </a>

        <nav class="user-sidebar-nav" aria-label="Dashboard navigation">
            <a href="dashboard.php#overview"><span>⌂</span>Overview</a>
            <a href="dashboard.php#bookings"><span>▦</span>My Bookings</a>
            <a href="dashboard.php#wallet"><span>₱</span>Wallet</a>
            <a href="dashboard.php#support"><span>✉</span>Support</a>
            <a class="active" href="settings.php"><span>⚙</span>Account Settings</a>
        </nav>

        <div class="user-sidebar-bottom">
            <a href="../pages/fleet.php" class="user-book-link">Book a car</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="../admin/admin-dashboard.php">Admin dashboard</a>
            <?php endif; ?>
            <a href="../index.php">Back to website</a>
            <a href="../auth/logout.php" class="user-logout">Logout</a>
        </div>
    </aside>

    <main class="user-main">
        <header class="user-topbar">
            <button type="button" class="user-menu-toggle" id="userMenuToggle" aria-label="Toggle dashboard menu">☰</button>

            <div class="user-topbar-title">
                <strong>Account Settings</strong>
                <small>Manage your personal information and sign-in security</small>
            </div>

            <div class="user-profile">
                <span class="user-avatar"><?= e(strtoupper(substr($user['first_name'], 0, 1))) ?></span>
                <div>
                    <strong><?= e($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                    <small><?= e($user['email']) ?></small>
                </div>
            </div>
        </header>

        <div class="user-content settings-page-content">
            <?php if ($noticeText): ?>
                <div class="user-alert success"><?= e($noticeText) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="user-alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <section class="settings-page-intro">
                <div>
                    <span class="user-eyebrow">My account</span>
                    <h1>Account settings</h1>
                    <p>Update your profile details, login email, and password from one dedicated page.</p>
                </div>
                <a href="dashboard.php" class="user-secondary-btn">Back to dashboard</a>
            </section>

            <section class="user-card settings-page-card">
                <div class="settings-grid">
                    <article class="settings-card">
                        <div class="settings-card-head">
                            <span>Personal information</span>
                            <strong>Profile</strong>
                        </div>

                        <form method="post" class="user-form">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="profile_update">

                            <div class="user-form-grid two">
                                <label>
                                    First name
                                    <input name="first_name" maxlength="100" required value="<?= e($user['first_name']) ?>">
                                </label>

                                <label>
                                    Last name
                                    <input name="last_name" maxlength="100" required value="<?= e($user['last_name']) ?>">
                                </label>
                            </div>

                            <label>
                                Phone number
                                <input
                                    name="phone"
                                    maxlength="30"
                                    value="<?= e((string)$user['phone']) ?>"
                                    placeholder="+63 912 345 6789"
                                >
                            </label>

                            <div class="settings-readonly">
                                <div>
                                    <span>Account type</span>
                                    <strong><?= e(ucfirst($user['role'])) ?></strong>
                                </div>
                                <div>
                                    <span>Member since</span>
                                    <strong><?= e(date('m/d/Y', strtotime($user['created_at']))) ?></strong>
                                </div>
                            </div>

                            <button type="submit" class="user-primary-btn">Save profile</button>
                        </form>
                    </article>

                    <article class="settings-card">
                        <div class="settings-card-head">
                            <span>Sign-in email</span>
                            <strong>Change email</strong>
                        </div>

                        <div class="current-email">
                            <span>Current email</span>
                            <strong><?= e($user['email']) ?></strong>
                        </div>

                        <form method="post" class="user-form">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="email_update">

                            <label>
                                New email address
                                <input
                                    type="email"
                                    name="new_email"
                                    autocomplete="email"
                                    required
                                    placeholder="newemail@example.com"
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

                            <small class="settings-help">
                                Your current password is required before changing your login email.
                            </small>

                            <button type="submit" class="user-primary-btn">Change email</button>
                        </form>
                    </article>

                    <article class="settings-card settings-password">
                        <div class="settings-card-head">
                            <span>Security</span>
                            <strong>Change password</strong>
                        </div>

                        <form method="post" class="user-form">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
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

                            <div class="user-form-grid two">
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

                            <small class="settings-help">
                                Use at least 8 characters. Your password is stored as a secure hash.
                            </small>

                            <button type="submit" class="user-primary-btn">Change password</button>
                        </form>
                    </article>
                </div>
            </section>
        </div>
    </main>
</div>

<script src="dashboard.js?v=2.1"></script>
</body>
</html>
