<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: back-end/authentication/login.php");
    exit;
}

$userName = htmlspecialchars($_SESSION["user_name"] ?? '');
$isAdmin  = !empty($_SESSION['isAdmin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — ABC Company</title>
  <?php include 'frontend/components/_fonts.html'; ?>
  <?php include 'frontend/components/buttons.html'; ?>
</head>
<body class="min-h-screen bg-gray-100 flex flex-col">

  <?php include 'frontend/components/navbar.html'; ?>

  <main class="flex-1 flex flex-col items-center justify-center gap-6 py-16">
    <h1 class="font-heading text-4xl text-nav-green-accent">
      Welcome, <?= $userName ?>!
    </h1>
    <div class="flex gap-4">
      <a href="back-end/apis/crud/vehicles/read.php" class="btn btn-primary">Browse Vehicles</a>
      <?php if ($isAdmin): ?>
        <a href="back-end/apis/crud/vehicles/create.php" class="btn btn-secondary">Add Vehicle</a>
        <a href="back-end/apis/crud/users/index.php" class="btn btn-secondary">Manage Users</a>
      <?php endif ?>
      <a href="back-end/authentication/logout.php" class="btn btn-danger">Logout</a>
    </div>
  </main>

</body>
</html>
