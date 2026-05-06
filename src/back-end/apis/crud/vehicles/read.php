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

$stores     = $pdo->query('SELECT storeID, storeName FROM STORE ORDER BY storeName')->fetchAll();
$bodyStyles = ['sedan','SUV','truck','van','coupe','hatchback'];
$fuelTypes  = ['gas','EV','PHEV','hybrid','diesel','hydrogen'];

$userName = htmlspecialchars($_SESSION['user_name'] ?? '');
$isAdmin  = !empty($_SESSION['isAdmin']);

$filters = [
    'make'      => $filterMake,
    'bodyStyle' => $filterBody,
    'fuelType'  => $filterFuel,
    'storeID'   => $filterStore,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Cars — ABC Company</title>
  <?php include '../../../../frontend/components/_fonts.html'; ?>
  <?php include '../../../../frontend/components/buttons.html'; ?>
  <?php include '../../../../frontend/components/inputs.html'; ?>
  <style type="text/tailwindcss">
    .card-grid { @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6; }
  </style>
</head>
<body class="min-h-screen bg-gray-100 flex flex-col">

  <?php include '../../../../frontend/components/navbar.html'; ?>

  <div class="flex flex-1 gap-6 p-6">

    <?php include '../../../../frontend/components/sidebar.html'; ?>

    <main class="flex-1 flex flex-col gap-4">

      <?php if (!empty($_GET['success'])): ?>
        <p class="font-body text-sm text-nav-green-accent"><?= htmlspecialchars($_GET['success']) ?></p>
      <?php endif ?>
      <?php if (!empty($_GET['error'])): ?>
        <p class="font-body text-sm text-red-600"><?= htmlspecialchars($_GET['error']) ?></p>
      <?php endif ?>

      <div class="flex items-center justify-between">
        <span class="font-body text-sm text-car-muted"><?= count($vehicles) ?> vehicle<?= count($vehicles) !== 1 ? 's' : '' ?> found</span>
        <?php if ($isAdmin): ?>
          <a href="create.php" class="btn btn-primary">+ Add Vehicle</a>
        <?php endif ?>
      </div>

      <?php if (empty($vehicles)): ?>
        <p class="font-body text-car-muted text-center py-16">No vehicles found.</p>
      <?php else: ?>
        <div class="card-grid">
          <?php foreach ($vehicles as $v): ?>
            <?php
              $price    = '$' . number_format($v['listPrice'], 2);
              $title    = $v['year'] . ' ' . htmlspecialchars($v['make']) . ' ' . htmlspecialchars($v['model']);
              $subtitle = htmlspecialchars($v['storeName']);
              $imgSrc   = '';
            ?>
            <div class="relative">
              <?php include '../../../../frontend/components/card.html'; ?>
              <?php if ($isAdmin): ?>
                <div class="absolute top-2 right-2 flex gap-1">
                  <a href="update.php?VIN=<?= urlencode($v['VIN']) ?>" class="btn btn-secondary text-xs px-2 py-1">Edit</a>
                  <a href="delete.php?VIN=<?= urlencode($v['VIN']) ?>"
                     onclick="return confirm('Delete <?= htmlspecialchars($v['VIN']) ?>?')"
                     class="btn btn-danger text-xs px-2 py-1">Del</a>
                </div>
              <?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>

    </main>
  </div>

</body>
</html>
