<?php

$siteHeaderVariant = $siteHeaderVariant ?? 'public';
$siteActivePage = $siteActivePage ?? '';
$siteDashboardMode = $siteDashboardMode ?? 'user';
$siteCheckoutLabel = $siteCheckoutLabel ?? 'Secure booking step';
$siteRoot = $siteRoot ?? ''; 

if ($siteHeaderVariant === 'public'):
    $loggedIn = !empty($_SESSION['user_id']);
    $role = $_SESSION['user_role'] ?? 'user';
?>
<header class="sticky top-0 z-[100] bg-navy-900/92 backdrop-blur-md border-b border-white/[0.06] site-public-header">
    <nav class="site-public-nav flex items-center justify-between px-6 py-4 max-w-[1180px] mx-auto" aria-label="Primary navigation">
        <a href="<?= $siteRoot ?>index.php" class="flex items-center gap-3 text-white" aria-label="Nexora home">
            <img src="<?= $siteRoot ?>Images/nexora-logo.png" alt="Nexora Car Rentals" class="nexora-logo" onerror="this.style.display='none'">
        </a>
        <div class="nav-links site-nav-links flex items-center gap-[30px]">
            <a href="<?= $siteRoot ?>index.php" class="<?= $siteActivePage === 'home' ? 'text-white' : 'text-white/78' ?> text-[14.5px] font-semibold hover:text-white transition">Home</a>
            <a href="<?= $siteRoot ?>fleet.php" class="<?= $siteActivePage === 'fleet' ? 'text-white' : 'text-white/78' ?> text-[14.5px] font-semibold hover:text-white transition">Fleet</a>
            <a href="<?= $siteRoot ?>index.php#services" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Services</a>
            <a href="<?= $siteRoot ?>index.php#difference" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">About</a>
            <a href="<?= $siteRoot ?>index.php#blog" class="text-white/78 text-[14.5px] font-semibold hover:text-white transition">Blog</a>
            <a href="<?= $siteRoot ?>contact.php" class="<?= $siteActivePage === 'contact' ? 'text-white' : 'text-white/78' ?> text-[14.5px] font-semibold hover:text-white transition">Contact</a>
        </div>
        <div class="site-auth-links flex items-center gap-[18px]">
            <?php if ($loggedIn): ?>
                <a href="<?= $siteRoot . ($role === 'admin' ? 'admin/admin-dashboard.php' : 'dashboard.php') ?>" class="btn btn-outline btn-sm">Dashboard</a>
                <a href="<?= $siteRoot ?>logout.php" class="btn btn-primary btn-sm">Logout</a>
            <?php else: ?>
                <a href="<?= $siteRoot ?>login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="<?= $siteRoot ?>signup.php" class="btn btn-primary btn-sm">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<?php elseif ($siteHeaderVariant === 'dashboard'): ?>
<header class="dash-header">
    <nav class="dash-nav" aria-label="Dashboard navigation">
        <a href="<?= $siteRoot ?>index.php" class="dash-brand"><img src="<?= $siteRoot ?>Images/nexora-logo.png" alt="Nexora"></a>
        <div class="dash-nav-links">
            <?php if ($siteDashboardMode === 'manage'): ?>
                <a href="<?= $siteRoot ?>admin/admin-dashboard.php">Admin dashboard</a>
                <a href="<?= $siteRoot ?>dashboard.php">My dashboard</a>
                <a href="<?= $siteRoot ?>fleet.php">Fleet</a>
            <?php elseif ($siteDashboardMode === 'admin'): ?>
                <a href="<?= $siteRoot ?>admin/admin-manage.php">Manage records</a>
                <a href="<?= $siteRoot ?>dashboard.php">My dashboard</a>
                <a href="<?= $siteRoot ?>fleet.php">Fleet</a>
            <?php else: ?>
                <a href="<?= $siteRoot ?>fleet.php">Fleet</a>
                <a href="<?= $siteRoot ?>contact.php">Support</a>
                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?><a href="<?= $siteRoot ?>admin/admin-dashboard.php">Admin</a><?php endif; ?>
            <?php endif; ?>
            <a href="<?= $siteRoot ?>logout.php" class="dash-logout">Logout</a>
        </div>
    </nav>
</header>
<?php elseif ($siteHeaderVariant === 'checkout'): ?>
<header class="site-step-header">
    <div class="site-step-header-inner">
        <a href="<?= $siteRoot ?>index.php" class="site-step-brand" aria-label="Nexora home">
            <img src="<?= $siteRoot ?>Images/nexora-logo.png" alt="Nexora Car Rentals" class="nexora-logo" onerror="this.style.display='none'">
        </a>
        <div class="site-step-security"><span aria-hidden="true">✓</span> <?= htmlspecialchars($siteCheckoutLabel, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</header>
<?php endif; ?>
