<?php
$pdo = new PDO("mysql:host=localhost;dbname=dealership_db", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash)
                           VALUES (:name, :email, :password)");

    if ($stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $password
    ])) {
        echo "Registered successfully! <a href='login.php'>Login</a>";
    } else {
        echo "Error!";
    }
}
?>
