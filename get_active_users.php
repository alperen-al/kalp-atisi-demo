<?php
include 'db.php';

$res = $conn->query("
  SELECT COUNT(*) 
    FROM users 
   WHERE last_active >= NOW() - INTERVAL 5 MINUTE
");
$active = $res->fetch_row()[0] ?? 0;
echo $active;
