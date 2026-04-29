<?php
session_start();

$pdo = new PDO("mysql:host=localhost;dbname=dealership_db", "root", "");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $pdo->query($sql);
    $user = $result->fetch();

    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["user"] = $user["full_name"];
        echo "Welcome " . $_SESSION["user"] . "<br>";
        echo "<a href='logout.php'>Logout</a>";
    } else {
        echo "Invalid login";
    }
}
?>

<h2>Login</h2>
<form method="post">
    Email: <input type="email" name="email"><br><br>
    Password: <input type="password" name="password"><br><br>
    <button type="submit">Login</button>
</form>
