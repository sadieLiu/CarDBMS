<?php
$pdo = new PDO("mysql:host=localhost;dbname=USEDCARS", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$error   = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST["name"]     ?? '');
    $email    = trim($_POST["email"]    ?? '');
    $phone    = trim($_POST["phone"]    ?? '');
    $password = $_POST["password"] ?? '';

    if (!$name || !$email || !$phone || strlen($password) < 6) {
        $error = "All fields required. Password min 6 chars.";
    } else {
        try {
            $pdo->beginTransaction();

            $row = $pdo->query('SELECT COALESCE(MAX(userID),0)+1 AS nextID FROM USER')->fetch(PDO::FETCH_ASSOC);
            $nextID = (int)$row['nextID'];

            $pdo->prepare('INSERT INTO USER (userID, name, email, phone) VALUES (:id, :name, :email, :phone)')
                ->execute([':id' => $nextID, ':name' => $name, ':email' => $email, ':phone' => (int)$phone]);

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO RAINBOW (userID, hashbrown) VALUES (:id, :hash)')
                ->execute([':id' => $nextID, ':hash' => $hashed]);

            $pdo->commit();
            $success = "Registered! You can now log in.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = $e->getCode() === '23000' ? "Email already registered." : "Registration failed.";
        }
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
  <title>Register — ABC Company</title>
  <?php include '../../frontend/components/_fonts.html'; ?>
  <?php include '../../frontend/components/buttons.html'; ?>
  <?php include '../../frontend/components/inputs.html'; ?>
</head>
<body class="min-h-screen bg-gray-100 flex flex-col">

  <?php include '../../frontend/components/navbar.html'; ?>

  <main class="flex-1 flex items-center justify-center py-12">
    <div class="bg-white border border-panel-border rounded-[10px] shadow-panel w-[367px] flex flex-col overflow-hidden">
      <h1 class="font-heading text-2xl text-nav-green-accent text-center pt-8 pb-6">Register</h1>

      <form method="post" class="flex flex-col gap-4 px-8 pb-0">
        <?php if ($error): ?>
          <p class="font-body text-sm text-red-600 text-center"><?= htmlspecialchars($error) ?></p>
        <?php endif ?>
        <?php if ($success): ?>
          <p class="font-body text-sm text-nav-green-accent text-center"><?= htmlspecialchars($success) ?></p>
        <?php endif ?>

        <div class="input-group">
          <label class="input-label" for="reg-name">Full Name</label>
          <input id="reg-name" class="input-text" type="text" name="name" required>
        </div>
        <div class="input-group">
          <label class="input-label" for="reg-email">Email</label>
          <input id="reg-email" class="input-text" type="email" name="email" required autocomplete="email">
        </div>
        <div class="input-group">
          <label class="input-label" for="reg-phone">Phone</label>
          <input id="reg-phone" class="input-text" type="tel" name="phone" required>
        </div>
        <div class="input-group">
          <label class="input-label" for="reg-password">Password</label>
          <input id="reg-password" class="input-text" type="password" name="password" required autocomplete="new-password">
        </div>

        <button type="submit" class="w-full h-[38px] bg-nav-green font-heading text-2xl text-white rounded-none mt-2 cursor-pointer hover:opacity-90 transition-opacity">
          Sign Up
        </button>
      </form>

      <p class="font-body text-sm text-car-muted text-center py-4">
        Have an account? <a href="login.php" class="text-nav-green-accent hover:underline">Log In</a>
      </p>
    </div>
  </main>

</body>
</html>
