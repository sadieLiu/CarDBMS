<?php
require_once '../db.php';
require_once '../auth.php';
requireAdmin();

$pdo = getDB();
$VIN = trim($_GET['VIN'] ?? $_POST['VIN'] ?? '');
if (!$VIN) { header('Location: read.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM VEHICLE WHERE VIN = :VIN');
$stmt->execute([':VIN' => $VIN]);
$vehicle = $stmt->fetch();
if (!$vehicle) { header('Location: read.php?error=Vehicle+not+found'); exit; }

$chk = $pdo->prepare('SELECT COUNT(*) FROM TRANSACTION_RECORD WHERE VIN = :VIN');
$chk->execute([':VIN' => $VIN]);
$hasTxn = (int)$chk->fetchColumn() > 0;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$hasTxn) {
    try {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM AVAILABLE_FEATURES WHERE VIN=:VIN')->execute([':VIN'=>$VIN]);
        $pdo->prepare('DELETE FROM CAR_CONDITIONS     WHERE VIN=:VIN')->execute([':VIN'=>$VIN]);
        $pdo->prepare('DELETE FROM CAR_SPECIFICATIONS WHERE VIN=:VIN')->execute([':VIN'=>$VIN]);
        $pdo->prepare('DELETE FROM VEHICLE            WHERE VIN=:VIN')->execute([':VIN'=>$VIN]);
        $pdo->commit();
        header('Location: read.php?success=Vehicle+deleted');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Delete Vehicle</title></head><body>
<h2>Delete Vehicle</h2><a href="read.php">&#8592; Back</a>

<?php if ($hasTxn): ?>
  <p style="color:red">Cannot delete VIN <strong><?= htmlspecialchars($VIN) ?></strong> — it has transaction records.</p>
<?php else: ?>
  <?php if ($error): ?><p style="color:red"><?= htmlspecialchars($error) ?></p><?php endif ?>
  <p>Delete this vehicle permanently?</p>
  <table border="1" cellpadding="5">
    <tr><th>VIN</th><td><?= htmlspecialchars($vehicle['VIN']) ?></td></tr>
    <tr><th>Plate</th><td><?= htmlspecialchars($vehicle['licensePlate']) ?></td></tr>
    <tr><th>Make</th><td><?= htmlspecialchars($vehicle['make']) ?></td></tr>
    <tr><th>Model</th><td><?= htmlspecialchars($vehicle['model']) ?></td></tr>
    <tr><th>Year</th><td><?= $vehicle['year'] ?></td></tr>
  </table>
  <p style="color:orange">Also removes specifications, features, and conditions for this vehicle.</p>
  <form method="post">
    <input type="hidden" name="VIN" value="<?= htmlspecialchars($VIN) ?>">
    <button type="submit" style="color:red">Yes, Delete</button> <a href="read.php">Cancel</a>
  </form>
<?php endif ?>
</body></html>
