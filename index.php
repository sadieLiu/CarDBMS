<?php
session_start();

$userName  = htmlspecialchars($_SESSION['user_name'] ?? '');
$isAdmin   = !empty($_SESSION['isAdmin']);
$heroImage = '/src/frontend/assets/hero-bg.jpg'; // replace with actual hero image path
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ABC Company — Find Your Next Car</title>
  <?php include 'src/frontend/components/_fonts.html'; ?>
  <?php include 'src/frontend/components/buttons.html'; ?>
</head>
<body class="min-h-screen flex flex-col">
  <?php include 'src/frontend/components/navbar.html'; ?>
  <?php include 'src/frontend/components/hero.html'; ?>
</body>
</html>
