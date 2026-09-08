<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function displayDate(string $date): string { $t=strtotime($date); return $t?date('m/d/Y',$t):$date; }
function methodName(string $type): string { return ['gcash'=>'GCash','card'=>'Card','bank_transfer'=>'Bank transfer','cash'=>'Cash'][$type] ?? ucfirst($type); }
function bookingRef(int $id, string $createdAt): string { return 'NXR-'.date('Ymd',strtotime($createdAt)).'-'.str_pad((string)$id,4,'0',STR_PAD_LEFT); }
if (empty($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$userId=(int)$_SESSION['user_id'];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$draft=$_SESSION['booking_draft'] ?? null;
if (!$draft) { header('Location: ../account/dashboard.php'); exit; }

$variantStmt=$pdo->prepare("SELECT v.id AS variant_id,v.transmission,v.daily_rate,v.quantity,v.status AS variant_status,c.id AS car_id,c.brand,c.model,c.status AS car_status FROM car_variants v INNER JOIN cars c ON c.id=v.car_id WHERE v.id=? LIMIT 1");
$variantStmt->execute([(int)$draft['car_variant_id']]);
$vehicle=$variantStmt->fetch();
if (!$vehicle || $vehicle['car_status']!=='active' || $vehicle['variant_status']!=='available') { unset($_SESSION['booking_draft']); exit('This vehicle is no longer available.'); }
$start=new DateTime($draft['pickup_date']); $end=new DateTime($draft['return_date']);
$totalDays=(int)$start->diff($end)->days; $totalAmount=$totalDays*(float)$vehicle['daily_rate']; $vehicleName=trim($vehicle['brand'].' '.$vehicle['model']);
$error=''; $selectedId=(int)($_GET['method'] ?? $_POST['saved_method_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD']==='POST') {
 if (!hash_equals($_SESSION['csrf_token'],$_POST['csrf_token']??'')) $error='Your session token expired. Refresh the page and try again.';
 else try {
  $action=$_POST['action']??'';
  if ($action==='add_payment_method') {
   $type=$_POST['method_type']??''; $label=trim($_POST['display_label']??''); $lastFour=preg_replace('/\D/','',$_POST['last_four']??'');
   if (!in_array($type,['cash','gcash','card','bank_transfer'],true)) throw new RuntimeException('Choose a valid payment method.');
   if ($label==='') $label=methodName($type);
   if ($type!=='cash' && strlen($lastFour)!==4) throw new RuntimeException('Enter the last 4 digits for this payment method.');
   if ($type==='cash') {
    $label='Cash'; $lastFour='';
    $cashCheck=$pdo->prepare("SELECT id FROM user_payment_methods WHERE user_id=? AND method_type='cash' LIMIT 1");
    $cashCheck->execute([$userId]);
    if ($cashCheck->fetch()) throw new RuntimeException('Cash is already saved as a payment method.');
   }
   $count=$pdo->prepare('SELECT COUNT(*) FROM user_payment_methods WHERE user_id=?'); $count->execute([$userId]); $isDefault=(int)$count->fetchColumn()===0?1:0;
   $stmt=$pdo->prepare('INSERT INTO user_payment_methods (user_id,method_type,display_label,last_four,is_default) VALUES (?,?,?,?,?)');
   $stmt->execute([$userId,$type,$label,$lastFour!==''?$lastFour:null,$isDefault]);
   header('Location: payment.php?method='.(int)$pdo->lastInsertId().'&added=1'); exit;
  }
  if ($action==='confirm_booking') {
   $methodId=filter_input(INPUT_POST,'saved_method_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]); if (!$methodId) throw new RuntimeException('Select a payment method before confirming your booking.');
   $stmt=$pdo->prepare('SELECT id,method_type,display_label,last_four FROM user_payment_methods WHERE id=? AND user_id=? LIMIT 1'); $stmt->execute([(int)$methodId,$userId]); $method=$stmt->fetch(); if (!$method) throw new RuntimeException('That payment method is no longer available.');
   $pdo->beginTransaction();
   $variantStmt=$pdo->prepare("SELECT v.id AS variant_id,v.transmission,v.daily_rate,v.quantity,v.status AS variant_status,c.id AS car_id,c.brand,c.model,c.status AS car_status FROM car_variants v INNER JOIN cars c ON c.id=v.car_id WHERE v.id=? LIMIT 1 FOR UPDATE");
   $variantStmt->execute([(int)$draft['car_variant_id']]); $locked=$variantStmt->fetch();
   if (!$locked || $locked['car_status']!=='active' || $locked['variant_status']!=='available' || (int)$locked['quantity']<1) throw new RuntimeException('This vehicle became unavailable. Please choose another one.');
   $availability=$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE car_variant_id=? AND status IN ('pending','confirmed','active') AND pickup_date < ? AND return_date > ?");
   $availability->execute([(int)$draft['car_variant_id'],$draft['return_date'],$draft['pickup_date']]);
   if ((int)$availability->fetchColumn()>=(int)$locked['quantity']) throw new RuntimeException('This vehicle was just booked for those dates. Please choose another vehicle or different dates.');
   $days=(int)(new DateTime($draft['pickup_date']))->diff(new DateTime($draft['return_date']))->days; $rate=(float)$locked['daily_rate']; $amount=$days*$rate; $name=trim($locked['brand'].' '.$locked['model']); $code='car-'.(int)$locked['car_id'].'-variant-'.(int)$locked['variant_id'];
   $insert=$pdo->prepare("INSERT INTO bookings (user_id,car_variant_id,vehicle_code,vehicle_name,transmission,pickup_location,dropoff_location,pickup_date,return_date,total_days,daily_rate,total_amount,status,special_requests) VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'pending',?)");
   $insert->execute([$userId,(int)$locked['variant_id'],$code,$name,strtolower($locked['transmission']),$draft['pickup_location'],$draft['pickup_location'],$draft['pickup_date'],$draft['return_date'],$days,$rate,$amount,$draft['special_requests']!==''?$draft['special_requests']:null]);
   $bookingId=(int)$pdo->lastInsertId();
   $reference=$method['display_label'].($method['last_four']?' •••• '.$method['last_four']:'');
   $pay=$pdo->prepare("INSERT INTO payments (booking_id,amount,payment_method,payment_status,transaction_reference) VALUES (?,?,?,'pending',?)"); $pay->execute([$bookingId,$amount,$method['method_type'],$reference]);
   $pdo->commit(); unset($_SESSION['booking_draft']); header('Location: ../account/dashboard.php?booking_confirmed='.$bookingId); exit;
  }
 } catch(Throwable $ex) { if($pdo->inTransaction())$pdo->rollBack(); $error=$ex instanceof RuntimeException?$ex->getMessage():'We could not confirm your booking. Please try again.'; }
}
$stmt=$pdo->prepare('SELECT id,method_type,display_label,last_four,is_default FROM user_payment_methods WHERE user_id=? ORDER BY is_default DESC,created_at DESC'); $stmt->execute([$userId]); $methods=$stmt->fetchAll(); if(!$selectedId&&$methods)$selectedId=(int)$methods[0]['id'];


$availabilityStmt=$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE car_variant_id=? AND status IN ('pending','confirmed','active') AND pickup_date < ? AND return_date > ?");
$availabilityStmt->execute([(int)$draft['car_variant_id'],$draft['return_date'],$draft['pickup_date']]);
$reservedUnits=(int)$availabilityStmt->fetchColumn();
$currentRemaining=max(0,(int)$vehicle['quantity']-$reservedUnits);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Confirm booking — Nexora</title><link rel="stylesheet" href="../css/output.css"><link rel="stylesheet" href="../styles.css"><link rel="stylesheet" href="../css/payment.css?v=4.1">  <link rel="stylesheet" href="../css/responsive.css?v=1.0">
</head><body class="payment-page">
<?php $siteRoot='../'; $siteHeaderVariant='checkout'; $siteCheckoutLabel='Secure booking step'; require dirname(__DIR__) . '/includes/header.php'; ?>
<main class="payment-shell"><div class="payment-progress"><span class="done">1. Vehicle</span><span class="done">2. Rental details</span><span class="current">3. Confirm booking</span></div>
<div class="payment-heading"><div><span class="payment-eyebrow">Final review</span><h1>Confirm your booking</h1><p>Select a saved payment method or add a new one before submitting your reservation.</p></div><div class="payment-status-chip"><?= $currentRemaining <= 2 ? 'Low availability · '.(int)$currentRemaining.' left' : 'Pending admin approval after submission' ?></div></div>
<?php if(isset($_GET['added'])):?><div class="payment-alert payment-alert-success"><strong>Payment method added.</strong><span>It is selected and ready to use.</span></div><?php endif;?><?php if($error):?><div class="payment-alert payment-alert-error"><strong>Could not continue.</strong><span><?=e($error)?></span></div><?php endif;?>
<div class="payment-layout"><section class="payment-main-card"><div class="payment-section-heading"><span>Payment method</span><button type="button" class="payment-add-link" id="openPaymentModal">+ Add new payment method</button></div>
<form method="post" class="payment-form"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="action" value="confirm_booking">
<?php if(!$methods):?><div class="payment-empty-methods"><strong>No saved payment methods yet.</strong><p>Add one to continue.</p><button type="button" class="payment-submit secondary" id="openPaymentModalEmpty">Add payment method</button></div><?php else:?><div class="saved-method-list"><?php foreach($methods as $m):?><label class="saved-method-card"><input type="radio" name="saved_method_id" value="<?=(int)$m['id']?>" <?=$selectedId===(int)$m['id']?'checked':''?> required><span class="payment-method-marker"></span><span class="saved-method-copy"><strong><?=e($m['display_label'])?></strong><small><?=e(methodName($m['method_type']))?><?=$m['last_four']?' •••• '.e($m['last_four']):''?><?=$m['is_default']?' · Default':''?></small></span></label><?php endforeach;?></div>
<div class="payment-notice"><span class="payment-notice-icon">i</span><p><strong>No external charge is processed yet.</strong> This prototype stores the selected method type/label only. Never enter a full card number, CVV, PIN, or banking password.</p></div><button type="submit" class="payment-submit">Confirm booking <span>→</span></button><?php endif;?></form></section>
<aside class="payment-summary-card"><div class="payment-summary-title"><span>Booking summary</span><a href="../pages/fleet.php">Change vehicle</a></div><div class="payment-vehicle"><div class="payment-vehicle-mark">N</div><div><strong><?=e($vehicleName)?></strong><span><?=e(ucfirst($vehicle['transmission']))?> transmission</span></div></div><dl class="payment-details"><div><dt>Pick-up</dt><dd><?=e(displayDate($draft['pickup_date']))?></dd></div><div><dt>Return</dt><dd><?=e(displayDate($draft['return_date']))?></dd></div><div><dt>Duration</dt><dd><?=$totalDays?> day<?=$totalDays===1?'':'s'?></dd></div><div><dt>Daily rate</dt><dd>₱<?=number_format((float)$vehicle['daily_rate'],2)?></dd></div></dl><div class="payment-location-block"><span>Pick-up location</span><strong><?=e($draft['pickup_location'])?></strong></div><div class="payment-total"><span>Estimated total</span><strong>₱<?=number_format($totalAmount,2)?></strong></div><p class="payment-summary-note">Confirming submits the reservation. The booking remains pending until Nexora approves it.</p></aside></div><div class="payment-bottom-links"><a href="../pages/fleet.php">← Back to fleet</a><a href="../pages/contact.php">Need help? Contact support</a></div></main>
<div class="payment-modal" id="paymentModal" hidden><div class="payment-modal-backdrop" data-close-modal></div><div class="payment-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle"><button type="button" class="payment-modal-close" data-close-modal>×</button><span class="payment-eyebrow">Saved payment method</span><h2 id="paymentModalTitle">Add payment method</h2><p class="payment-modal-note">For non-cash methods, enter only a label and last 4 digits. Cash requires no extra details. Do not enter full account or card credentials.</p><form method="post" class="payment-modal-form"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="action" value="add_payment_method"><label>Method<select name="method_type" id="methodType" required><option value="gcash">GCash</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option><option value="cash">Cash</option></select></label><label id="methodLabelField">Label<input name="display_label" maxlength="100" placeholder="Personal GCash or Visa"></label><label id="lastFourField">Last 4 digits<input name="last_four" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" placeholder="1234"></label><button class="payment-submit" type="submit">Save payment method</button></form></div></div>
<script>(()=>{const m=document.getElementById('paymentModal'),o=()=>{m.hidden=false;document.body.classList.add('modal-open')},c=()=>{m.hidden=true;document.body.classList.remove('modal-open')};document.getElementById('openPaymentModal')?.addEventListener('click',o);document.getElementById('openPaymentModalEmpty')?.addEventListener('click',o);m.querySelectorAll('[data-close-modal]').forEach(x=>x.addEventListener('click',c));document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!m.hidden)c()});const t=document.getElementById('methodType'),l=document.getElementById('methodLabelField'),li=l.querySelector('input'),f=document.getElementById('lastFourField'),i=f.querySelector('input'),sync=()=>{const cash=t.value==='cash';l.hidden=cash;f.hidden=cash;li.required=!cash;i.required=!cash;if(cash){li.value='';i.value=''}};t.addEventListener('change',sync);sync()})();</script></body></html>
