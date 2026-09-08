<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/utils/validation.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$name = '';
$email = '';
$subject = '';
$message = '';
$error = '';
$sent = isset($_GET['sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $errors = validationErrors([
        validateRequired($name, 'Name'),
        validateRequired($email, 'Email'),
        validateEmailFormat($email),
        validateRequired($message, 'Message'),
        validateLength($name, 'Name', 1, 120),
        validateLength($subject, 'Subject', 0, 180),
        validateLength($message, 'Message', 1, 5000),
    ]);

    if ($errors) {
        $error = implode(' ', $errors);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages (name, email, subject, message)
             VALUES (:name, :email, :subject, :message)'
        );
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':email', strtolower($email));
        $stmt->bindValue(':subject', $subject !== '' ? $subject : null);
        $stmt->bindValue(':message', $message);
        $stmt->execute();

        header('Location: contact.php?sent=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — Nexora</title>
    <meta name="description" content="Get in touch with Nexora for booking, fleet, payment, or rental support.">
    <link rel="stylesheet" href="../css/output.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="../css/contact.css?v=3.0">
  <link rel="stylesheet" href="../css/responsive.css?v=1.0">
</head>
<body class="contact-page">
<?php
$siteRoot = '../';
    $siteHeaderVariant = 'public';
$siteActivePage = 'contact';
require dirname(__DIR__) . '/includes/header.php';
?>

<main>
    <section class="contact-hero">
        <div class="contact-shell contact-hero-grid">
            <div class="contact-hero-copy">
                <span class="contact-eyebrow">Nexora support</span>
                <h1>How can we help?</h1>
                <p>Questions about a reservation, vehicle, payment, or your rental? Send us a message and we’ll keep it organized in your Nexora support inbox.</p>

                <div class="contact-highlights" aria-label="Support highlights">
                    <div>
                        <strong>Simple</strong>
                        <span>One form for rental concerns</span>
                    </div>
                    <div>
                        <strong>Organized</strong>
                        <span>Messages saved securely in your system</span>
                    </div>
                    <div>
                        <strong>Helpful</strong>
                        <span>Booking and payment support in one place</span>
                    </div>
                </div>
            </div>

            <aside class="contact-info-card">
                <span class="contact-info-kicker">Need assistance?</span>
                <h2>Contact information</h2>
                <div class="contact-info-list">
                    <div class="contact-info-item">
                        <span class="contact-info-icon" aria-hidden="true">@</span>
                        <div>
                            <small>Email</small>
                            <strong>support@nexora.local</strong>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <span class="contact-info-icon" aria-hidden="true">?</span>
                        <div>
                            <small>Support</small>
                            <strong>Booking & rental concerns</strong>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <span class="contact-info-icon" aria-hidden="true">24</span>
                        <div>
                            <small>Message access</small>
                            <strong>Submit anytime</strong>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="contact-form-section">
        <div class="contact-shell contact-form-grid">
            <div class="contact-form-intro">
                <span class="contact-section-number">01</span>
                <h2>Send us a message</h2>
                <p>Give us enough detail to understand what you need. Required fields are marked with an asterisk.</p>

                <div class="contact-topic-list">
                    <span>Booking questions</span>
                    <span>Vehicle availability</span>
                    <span>Payment concerns</span>
                    <span>General inquiries</span>
                </div>
            </div>

            <div class="contact-form-card">
                <?php if ($sent): ?>
                    <div class="contact-alert contact-alert-success" role="status">
                        <strong>Message sent.</strong>
                        <span>Thanks for contacting Nexora. Your message has been saved successfully.</span>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="contact-alert contact-alert-error" role="alert">
                        <strong>Check your details.</strong>
                        <span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" class="contact-form">
                    <div class="contact-field-row">
                        <label class="contact-field">
                            <span>Full name *</span>
                            <input type="text" name="name" autocomplete="name" required maxlength="150" value="<?= e($name) ?>" placeholder="Juan Dela Cruz">
                        </label>

                        <label class="contact-field">
                            <span>Email address *</span>
                            <input type="email" name="email" autocomplete="email" required maxlength="255" value="<?= e($email) ?>" placeholder="juan@example.com">
                        </label>
                    </div>

                    <label class="contact-field">
                        <span>Subject</span>
                        <input type="text" name="subject" maxlength="255" value="<?= e($subject) ?>" placeholder="How can we help?">
                    </label>

                    <label class="contact-field">
                        <span>Message *</span>
                        <textarea name="message" required rows="7" maxlength="4000" placeholder="Tell us about your concern, booking, or question..."><?= e($message) ?></textarea>
                    </label>

                    <div class="contact-form-footer">
                        <p>By submitting, your message will be stored in the Nexora support database.</p>
                        <button type="submit" class="contact-submit">Send message <span aria-hidden="true">→</span></button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<?php
$siteFooterNewsletter = false;
require dirname(__DIR__) . '/includes/footer.php';
?>
</body>
</html>
