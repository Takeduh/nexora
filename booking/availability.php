<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/utils/validation.php';
header('Content-Type: application/json; charset=utf-8');
$pickup = trim($_GET['pickup'] ?? '');
$returnDate = trim($_GET['return'] ?? '');
if (validateIsoDate($pickup, 'Pick-up date') !== null || validateIsoDate($returnDate, 'Return date') !== null) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Enter valid pickup and return dates.']); exit; }
$start = new DateTimeImmutable($pickup); $end = new DateTimeImmutable($returnDate); $today = new DateTimeImmutable('today');
if ($start < $today || $end <= $start) { http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Return date must be after pickup and pickup cannot be in the past.']); exit; }
$sql = "SELECT v.id, v.quantity, v.status AS variant_status, c.status AS car_status, COUNT(b.id) AS reserved_count FROM car_variants v INNER JOIN cars c ON c.id=v.car_id LEFT JOIN bookings b ON b.car_variant_id=v.id AND b.status IN ('pending','confirmed','active') AND b.pickup_date < ? AND b.return_date > ? GROUP BY v.id,v.quantity,v.status,c.status";
$stmt=$pdo->prepare($sql); $stmt->execute([$returnDate,$pickup]);
$variants=[];
foreach($stmt->fetchAll() as $row){ $quantity=max(0,(int)$row['quantity']); $reserved=max(0,(int)$row['reserved_count']); $remaining=max(0,$quantity-$reserved); $operational=$row['car_status']==='active'&&$row['variant_status']==='available'&&$quantity>0; $variants[(string)$row['id']]=['quantity'=>$quantity,'reserved'=>$reserved,'remaining'=>$operational?$remaining:0,'available'=>$operational&&$remaining>0,'low_stock'=>$operational&&$remaining>0&&$remaining<=2]; }
echo json_encode(['ok'=>true,'pickup'=>$pickup,'return'=>$returnDate,'variants'=>$variants], JSON_UNESCAPED_SLASHES);
