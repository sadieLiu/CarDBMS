<?php
require_once '../db.php';
require_once '../auth.php';
requireLogin();

$pdo = getDB();

$filterMake  = trim($_GET['make']      ?? '');
$filterBody  = trim($_GET['bodyStyle'] ?? '');
$filterFuel  = trim($_GET['fuelType']  ?? '');
$filterStore = (int)($_GET['storeID']  ?? 0);

$where = []; $params = [];
if ($filterMake)  { $where[] = 'v.make LIKE :make';       $params[':make']      = "%$filterMake%"; }
if ($filterBody)  { $where[] = 'v.bodyStyle = :bodyStyle'; $params[':bodyStyle'] = $filterBody; }
if ($filterFuel)  { $where[] = 'cs.fuelType = :fuelType';  $params[':fuelType']  = $filterFuel; }
if ($filterStore) { $where[] = 'v.storeID = :storeID';     $params[':storeID']   = $filterStore; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT v.*, s.storeName,
           cs.fuelType, cs.transmission, cs.driveTrain, cs.exColor, cs.inColor,
           GROUP_CONCAT(DISTINCT af.feature   SEPARATOR ', ') AS features,
           GROUP_CONCAT(DISTINCT cc.condition SEPARATOR ', ') AS conditions
    FROM VEHICLE v
    JOIN STORE s ON v.storeID = s.storeID
    LEFT JOIN CAR_SPECIFICATIONS cs ON v.VIN = cs.VIN
    LEFT JOIN AVAILABLE_FEATURES af ON v.VIN = af.VIN
    LEFT JOIN CAR_CONDITIONS     cc ON v.VIN = cc.VIN
    $whereSQL
    GROUP BY v.VIN
    ORDER BY v.make, v.model
");
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

$stores    = $pdo->query('SELECT storeID, storeName FROM STORE ORDER BY storeName')->fetchAll();
$bodyStyles = ['sedan','SUV','truck','van','coupe','hatchback'];
$fuelTypes  = ['gas','EV','PHEV','hybrid','diesel','hydrogen'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Vehicles</title></head><body>
<h2>Vehicles</h2>

<?php if (!empty($_GET['success'])): ?><p style="color:green"><?= htmlspecialchars($_GET['success']) ?></p><?php endif ?>
<?php if (!empty($_GET['error'])): ?><p style="color:red"><?= htmlspecialchars($_GET['error']) ?></p><?php endif ?>

<form method="get">
  <label>Make: <input type="text" name="make" value="<?= htmlspecialchars($filterMake) ?>"></label>
  <label>Body Style: <select name="bodyStyle"><option value="">All</option><?php foreach ($bodyStyles as $b): ?><option value="<?= $b ?>" <?= $filterBody===$b?'selected':'' ?>><?= $b ?></option><?php endforeach ?></select></label>
  <label>Fuel Type: <select name="fuelType"><option value="">All</option><?php foreach ($fuelTypes as $f): ?><option value="<?= $f ?>" <?= $filterFuel===$f?'selected':'' ?>><?= $f ?></option><?php endforeach ?></select></label>
  <label>Store: <select name="storeID"><option value="0">All</option><?php foreach ($stores as $s): ?><option value="<?= $s['storeID'] ?>" <?= $filterStore==$s['storeID']?'selected':'' ?>><?= htmlspecialchars($s['storeName']) ?></option><?php endforeach ?></select></label>
  <button type="submit">Filter</button> <a href="read.php">Clear</a>
</form>

<?php if (!empty($_SESSION['isAdmin'])): ?><a href="create.php">+ Add Vehicle</a><?php endif ?><br><br>

<?php if (empty($vehicles)): ?><p>No vehicles found.</p><?php else: ?>
<table border="1" cellpadding="5" cellspacing="0">
  <thead><tr>
    <th>VIN</th><th>Plate</th><th>Make</th><th>Model</th><th>Year</th>
    <th>Price</th><th>Mileage</th><th>MPG</th><th>Body</th>
    <th>Fuel</th><th>Trans</th><th>Drive</th><th>Ex.Color</th><th>In.Color</th>
    <th>Store</th><th>Conditions</th><th>Features</th><th>Added</th>
    <?php if (!empty($_SESSION['isAdmin'])): ?><th>Actions</th><?php endif ?>
  </tr></thead>
  <tbody>
  <?php foreach ($vehicles as $v): ?>
    <tr>
      <td><?= htmlspecialchars($v['VIN']) ?></td>
      <td><?= htmlspecialchars($v['licensePlate']) ?></td>
      <td><?= htmlspecialchars($v['make']) ?></td>
      <td><?= htmlspecialchars($v['model']) ?></td>
      <td><?= $v['year'] ?></td>
      <td>$<?= number_format($v['listPrice'], 2) ?></td>
      <td><?= number_format($v['mileage']) ?></td>
      <td><?= htmlspecialchars($v['MPG'] ?? '—') ?></td>
      <td><?= htmlspecialchars($v['bodyStyle']) ?></td>
      <td><?= htmlspecialchars($v['fuelType'] ?? '—') ?></td>
      <td><?= htmlspecialchars($v['transmission'] ?? '—') ?></td>
      <td><?= htmlspecialchars($v['driveTrain'] ?? '—') ?></td>
      <td><?= htmlspecialchars($v['exColor'] ?? '—') ?></td>
      <td><?= htmlspecialchars($v['inColor'] ?? '—') ?></td>
      <td><?= htmlspecialchars($v['storeName']) ?></td>
      <td><?= htmlspecialchars($v['conditions'] ?? '—') ?></td>
      <td><?= htmlspecialchars($v['features'] ?? '—') ?></td>
      <td><?= htmlspecialchars($v['addedAt']) ?></td>
      <?php if (!empty($_SESSION['isAdmin'])): ?>
      <td>
        <a href="update.php?VIN=<?= urlencode($v['VIN']) ?>">Edit</a> |
        <a href="delete.php?VIN=<?= urlencode($v['VIN']) ?>" onclick="return confirm('Delete <?= htmlspecialchars($v['VIN']) ?>?')">Delete</a>
      </td>
      <?php endif ?>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php endif ?>
</body></html>
