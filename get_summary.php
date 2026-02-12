<?php
include 'db.php';

$startDate = $_GET['start'] ?? '';
$endDate   = $_GET['end'] ?? '';

$query = "SELECT COUNT(*) AS beats FROM purchases WHERE status = 'bekliyor'";
$params = [];

if ($startDate && $endDate) {
  $query .= " AND DATE(purchase_date) BETWEEN ? AND ?";
  $params[] = $startDate;
  $params[] = $endDate;
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
  $stmt->bind_param('ss', ...$params);
}
$stmt->execute();
$stmt->bind_result($beats);
$stmt->fetch();
$stmt->close();

$earnings = $beats * 0.75;

echo json_encode([
  'beats'    => $beats,
  'earnings' => number_format($earnings, 2)
]);
