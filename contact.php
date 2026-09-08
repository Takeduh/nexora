<?php
require_once __DIR__ . '/config/database.php';
$success = false; $error = '';
$name = ''; $email = ''; $subject = ''; $message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $subject = trim($_POST['subject'] ?? ''); $message = trim($_POST['message'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') $error = 'Please provide your name, a valid email, and a message.';
    else { $stmt=$pdo->prepare('INSERT INTO contact_messages (name,email,subject,message) VALUES (?,?,?,?)'); $stmt->execute([$name,$email,$subject!==''?$subject:null,$message]); $success=true; $name=$email=$subject=$message=''; }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Contact — Nexora</title><link rel="stylesheet" href="output.css"><link rel="stylesheet" href="styles.css"></head>
<body class="bg-gray-50 text-ink antialiased"><header class="bg-navy-900"><nav class="flex items-center justify-between px-6 py-4 max-w-[1180px] mx-auto"><a href="index.php"><img src="Images/nexora-logo.png" alt="Nexora" class="nexora-logo"></a><a href="index.php" class="btn btn-outline btn-sm">Home</a></nav></header>
<main class="max-w-[760px] mx-auto px-6 py-[70px]"><div class="bg-white rounded-[14px] shadow-card-lg p-[22px]"><h1 class="text-2xl font-extrabold mb-2">Contact Nexora</h1><p class="text-gray-600 mb-6">Send us a message and it will be saved to the Nexora contact inbox.</p>
<?php if($success): ?><p class="mb-4 font-semibold">Message sent successfully.</p><?php endif; ?><?php if($error): ?><p class="mb-4 font-semibold"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<form method="post" class="grid gap-4"><label>Name<input type="text" name="name" required value="<?php echo htmlspecialchars($name); ?>" class="w-full border rounded-[9px] px-3 py-2.5 mt-1"></label><label>Email<input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>" class="w-full border rounded-[9px] px-3 py-2.5 mt-1"></label><label>Subject<input type="text" name="subject" value="<?php echo htmlspecialchars($subject); ?>" class="w-full border rounded-[9px] px-3 py-2.5 mt-1"></label><label>Message<textarea name="message" required rows="6" class="w-full border rounded-[9px] px-3 py-2.5 mt-1"><?php echo htmlspecialchars($message); ?></textarea></label><button type="submit" class="btn btn-primary">Send Message</button></form></div></main></body></html>
