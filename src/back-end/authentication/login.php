<?php
session_start();

$pdo = new PDO("mysql:host=localhost;dbname=USEDCARS", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $pdo->prepare("
        SELECT u.userID, u.name, r.hashbrown
        FROM USER u
        JOIN RAINBOW r ON r.userID = u.userID
        WHERE u.email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["hashbrown"])) {
        $_SESSION["user_id"]   = $user["userID"];
        $_SESSION["user_name"] = $user["name"];
        header("Location: /src/dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password";
    }
}

$userName = '';
$isAdmin  = false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — ABC Company</title>
  <?php include '../../frontend/components/_fonts.html'; ?>
  <?php include '../../frontend/components/buttons.html'; ?>
  <?php include '../../frontend/components/inputs.html'; ?>
</head>
<body class="min-h-screen bg-gray-100 flex flex-col">

  <?php include '../../frontend/components/navbar.html'; ?>

  <main class="flex-1 flex items-center justify-center py-12">
    <?php include '../../frontend/components/login.html'; ?>
  </main>

  <p class="text-center font-body text-sm text-car-muted pb-6">
    No account? <a href="register.php" class="text-nav-green-accent hover:underline">Register</a>
  </p>

</body>
</html>
