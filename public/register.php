<?php
$pdo = new PDO("mysql:host=localhost;dbname=dealership_db", "root", "");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (full_name, email, password_hash)
            VALUES ('$name', '$email', '$password')";

    if ($pdo->exec($sql)) {
        echo "Registered successfully! <a href='login.php'>Login</a>";
    } else {
        echo "Error!";
    }
}
?>

<h2>Register</h2>
<form method="post">
    Name: <input type="text" name="name"><br><br>
    Email: <input type="email" name="email"><br><br>
    Password: <input type="password" name="password"><br><br>
    <button type="submit">Register</button>
</form>
