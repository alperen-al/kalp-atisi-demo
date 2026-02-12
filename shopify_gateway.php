<?php
session_start();

// Formdan gelen bilgileri temizleyerek al
$note      = trim($_POST['note'] ?? '');
$email     = trim($_POST['email'] ?? '');
$refCode   = trim($_POST['refCode'] ?? '');
$datetime  = trim($_POST['date'] ?? '');
$latitude  = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');

// Geçici olarak sakla
$_SESSION['pending_note'] = [
  'note'      => $note,
  'email'     => $email,
  'refCode'   => $refCode,
  'date'      => $datetime,
  'latitude'  => $latitude,
  'longitude' => $longitude
];

// Shopify ödeme sayfasına yönlendir (Direct checkout link!)
header("Location: https://SENIN-MAGAZA.myshopify.com/cart/1234567890:1");
exit;
