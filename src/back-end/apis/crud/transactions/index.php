<?php
require_once '../db.php';
require_once '../auth.php';
requireLogin();

$pdo    = getDB();
$errors = [];

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    requireAdmin();
    $id = (int)($_POST['transactionID'] ?? 0);
    if ($id) { $pdo->prepare('DELETE FROM TRANSACTION_RECORD WHERE transactionID=:id')->execute([':id'=>$id]); }
    header('Location: index.php?success=Transaction+deleted');
    exit;
}

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $VIN    = trim($_POST['VIN']              ?? '');
    $userID = (int)($_POST['userID']          ?? 0);
    $sID    = (int)($_POST['storeID']         ?? 0);
    $price  = trim($_POST['transactionPrice'] ?? '');
    $date   = trim($_POST['transactionDate']  ?? '');

    if (!$VIN)                                    $errors[] = 'VIN is required.';
    if (!$userID)                                 $errors[] = 'Customer is required.';
    if (!$sID)                                    $errors[] = 'Store is required.';
    if (!is_numeric($price) || $price <= 0)       $errors[] = 'Enter a valid price.';
    if (!$date)                                   $errors[] = 'Date is required.';

    if ($VIN) {
        $chk = $pdo->prepare('SELECT VIN FROM VEHICLE WHERE VIN=:VIN');
        $chk->execute([':VIN'=>$VIN]);
        if (!$chk->fetch()) $errors[] = "VIN '$VIN' not found.";
    }

    if (empty($errors)) {
        try {
            $pdo->prepare('
                INSERT INTO TRANSACTION_RECORD (VIN,userID,storeID,transactionPrice,transactionDate)
                VALUES (:VIN,:uid,:sid,:price,:date)
            ')->execute([':VIN'=>$VIN,':uid'=>$userID,':sid'=>$sID,':price'=>$price,':date'=>$date]);
            header('Location: index.php?success=Transaction+recorded');
            exit;
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// READ
$filterUser  = (int)($_GET['userID']  ?? 0);
$filterStore = (int)($_GET['storeID'] ?? 0);
$where = []; $params = [];
if ($filterUser)  { $where[] = 't.userID=:uid';  $params[':uid']  = $filterUser; }
if ($filterStore) { $where[] = 't.storeID=:sid'; $params[':sid']  = $filterStore; }
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$txns = $pdo->prepare("
    SELECT t.*, v.make, v.model, v.year, v.licensePlate,
           u.fName, u.lName, u.email, s.storeName
    FROM TRANSACTION_RECORD t
    JOIN VEHICLE v ON t.VIN=v.VIN
    JOIN USER    u ON t.userID=u.userID
    JOIN STORE   s ON t.storeID=s.storeID
    $whereSQL
    ORDER BY t.transactionDate DESC
");
$txns->execute($params);
$txns = $txns->fetchAll();

$users  = $pdo->query('SELECT userID, fName, lName, email FROM USER ORDER BY lName')->fetchAll();
$stores = $pdo->query('SELECT storeID, storeName FROM STORE ORDER BY storeName')->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Transactions</title></head><body>
<h2>Transactions</h2>

<?php if (!empty($_GET['success'])): ?><p style="color:green"><?= htmlspecialchars($_GET['success']) ?></p><?php endif ?>
<?php if ($errors): ?><ul style="color:red"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach ?></ul><?php endif ?>

<details <?= $errors ? 'open' : '' ?>>
  <summary><strong>+ Record New Transaction</strong></summary><br>
  <form method="post">
    <input type="hidden" name="action" value="create">
    <label>VIN* <input type="text" name="VIN" value="<?= htmlspecialchars($_POST['VIN'] ?? '') ?>" required></label><br>
    <label>Customer*
      <select name="userID" required><option value="">-- Select --</option>
        <?php foreach ($users as $u): ?><option value="<?= $u['userID'] ?>" <?= (($_POST['userID']??'')==$u['userID'])?'selected':'' ?>><?= htmlspecialchars($u['fName'].' '.$u['lName'].' ('.$u['email'].')') ?></option><?php endforeach ?>
      </select>
    </label><br>
    <label>Store*
      <select name="storeID" required><option value="">-- Select --</option>
        <?php foreach ($stores as $s): ?><option value="<?= $s['storeID'] ?>" <?= (($_POST['storeID']??'')==$s['storeID'])?'selected':'' ?>><?= htmlspecialchars($s['storeName']) ?></option><?php endforeach ?>
      </select>
    </label><br>
    <label>Price* <input type="number" name="transactionPrice" min="0.01" step="0.01" value="<?= htmlspecialchars($_POST['transactionPrice'] ?? '') ?>" required></label><br>
    <label>Date* <input type="date" name="transactionDate" value="<?= htmlspecialchars($_POST['transactionDate'] ?? date('Y-m-d')) ?>" required></label><br>
    <br><button type="submit">Record</button>
  </form>
</details><hr>

<form method="get">
  <label>Customer: <select name="userID"><option value="0">All</option><?php foreach ($users as $u): ?><option value="<?= $u['userID'] ?>" <?= $filterUser==$u['userID']?'selected':'' ?>><?= htmlspecialchars($u['fName'].' '.$u['lName']) ?></option><?php endforeach ?></select></label>
  <label>Store: <select name="storeID"><option value="0">All</option><?php foreach ($stores as $s): ?><option value="<?= $s['storeID'] ?>" <?= $filterStore==$s['storeID']?'selected':'' ?>><?= htmlspecialchars($s['storeName']) ?></option><?php endforeach ?></select></label>
  <button type="submit">Filter</button> <a href="index.php">Clear</a>
</form><br>

<?php if (empty($txns)): ?><p>No transactions found.</p><?php else: ?>
<table border="1" cellpadding="5" cellspacing="0">
  <thead><tr>
    <th>#</th><th>Date</th><th>VIN</th><th>Vehicle</th><th>Plate</th>
    <th>Customer</th><th>Email</th><th>Store</th><th>Price</th>
    <?php if (!empty($_SESSION['isAdmin'])): ?><th>Actions</th><?php endif ?>
  </tr></thead>
  <tbody>
  <?php foreach ($txns as $t): ?>
    <tr>
      <td><?= $t['transactionID'] ?></td>
      <td><?= htmlspecialchars($t['transactionDate']) ?></td>
      <td><?= htmlspecialchars($t['VIN']) ?></td>
      <td><?= htmlspecialchars($t['year'].' '.$t['make'].' '.$t['model']) ?></td>
      <td><?= htmlspecialchars($t['licensePlate']) ?></td>
      <td><?= htmlspecialchars($t['fName'].' '.$t['lName']) ?></td>
      <td><?= htmlspecialchars($t['email']) ?></td>
      <td><?= htmlspecialchars($t['storeName']) ?></td>
      <td>$<?= number_format($t['transactionPrice'],2) ?></td>
      <?php if (!empty($_SESSION['isAdmin'])): ?>
      <td>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete #<?= $t['transactionID'] ?>?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="transactionID" value="<?= $t['transactionID'] ?>">
          <button type="submit" style="color:red">Delete</button>
        </form>
      </td>
      <?php endif ?>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php endif ?>
</body></html>
