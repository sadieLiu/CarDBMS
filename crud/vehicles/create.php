<?php
require_once '../db.php';
require_once '../auth.php';
requireAdmin();

$pdo    = getDB();
$errors = [];

$fuelTypes     = ['gas', 'EV', 'PHEV', 'hybrid', 'diesel', 'hydrogen'];
$transmissions = ['4WD', 'AWD', 'FWD', 'RWD'];
$driveTrains   = ['CVT', 'auto', 'manual'];
$bodyStyles    = ['sedan', 'SUV', 'truck', 'van', 'coupe', 'hatchback'];
$featuresList  = ['towHitch', 'backupCamera', 'thirdRowSeating', 'Navigation', 'moonRoof'];
$conditionsList= ['oneOwner', 'cleanTitle', 'noAccident', 'certifiedPreowned'];
$stores        = $pdo->query('SELECT storeID, storeName FROM STORE ORDER BY storeName')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $VIN          = trim($_POST['VIN']          ?? '');
    $licensePlate = trim($_POST['licensePlate'] ?? '');
    $listPrice    = trim($_POST['listPrice']    ?? '');
    $make         = trim($_POST['make']         ?? '');
    $model        = trim($_POST['model']        ?? '');
    $year         = (int)($_POST['year']        ?? 0);
    $MPG          = trim($_POST['MPG']          ?? '');
    $mileage      = (int)($_POST['mileage']     ?? 0);
    $bodyStyle    = trim($_POST['bodyStyle']    ?? '');
    $storeID      = (int)($_POST['storeID']     ?? 0);
    $fuelType     = trim($_POST['fuelType']     ?? '');
    $transmission = trim($_POST['transmission'] ?? '');
    $driveTrain   = trim($_POST['driveTrain']   ?? '');
    $exColor      = trim($_POST['exColor']      ?? '');
    $inColor      = trim($_POST['inColor']      ?? '');
    $features     = $_POST['features']          ?? [];
    $conditions   = $_POST['conditions']        ?? [];

    if (!$VIN || strlen($VIN) > 17)            $errors[] = 'VIN is required (max 17 chars).';
    if (!$licensePlate)                        $errors[] = 'License plate is required.';
    if (!$make)                                $errors[] = 'Make is required.';
    if (!$model)                               $errors[] = 'Model is required.';
    if ($year < 1900 || $year > date('Y') + 1) $errors[] = 'Enter a valid year.';
    if (!is_numeric($listPrice) || $listPrice < 0) $errors[] = 'Enter a valid list price.';
    if ($mileage < 0)                          $errors[] = 'Mileage cannot be negative.';
    if (!in_array($bodyStyle, $bodyStyles))    $errors[] = 'Select a valid body style.';
    if (!$storeID)                             $errors[] = 'Store is required.';
    if (!in_array($fuelType, $fuelTypes))      $errors[] = 'Select a valid fuel type.';
    if (!in_array($transmission, $transmissions)) $errors[] = 'Select a valid transmission.';
    if (!in_array($driveTrain, $driveTrains))  $errors[] = 'Select a valid drivetrain.';
    if (!$exColor)                             $errors[] = 'Exterior color is required.';
    if (!$inColor)                             $errors[] = 'Interior color is required.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $pdo->prepare('
                INSERT INTO VEHICLE (VIN, licensePlate, listPrice, make, model, year, MPG, mileage, bodyStyle, addedAt, storeID)
                VALUES (:VIN, :licensePlate, :listPrice, :make, :model, :year, :MPG, :mileage, :bodyStyle, NOW(), :storeID)
            ')->execute([
                ':VIN' => $VIN, ':licensePlate' => $licensePlate, ':listPrice' => $listPrice,
                ':make' => $make, ':model' => $model, ':year' => $year,
                ':MPG' => $MPG ?: null, ':mileage' => $mileage,
                ':bodyStyle' => $bodyStyle, ':storeID' => $storeID,
            ]);

            $pdo->prepare('
                INSERT INTO CAR_SPECIFICATIONS (VIN, fuelType, transmission, driveTrain, exColor, inColor)
                VALUES (:VIN, :fuelType, :transmission, :driveTrain, :exColor, :inColor)
            ')->execute([
                ':VIN' => $VIN, ':fuelType' => $fuelType, ':transmission' => $transmission,
                ':driveTrain' => $driveTrain, ':exColor' => $exColor, ':inColor' => $inColor,
            ]);

            if (!empty($features)) {
                $s = $pdo->prepare('INSERT IGNORE INTO AVAILABLE_FEATURES (VIN, feature) VALUES (:VIN, :feature)');
                foreach ($features as $f) {
                    if (in_array($f, $featuresList)) $s->execute([':VIN' => $VIN, ':feature' => $f]);
                }
            }

            if (!empty($conditions)) {
                $s = $pdo->prepare('INSERT IGNORE INTO CAR_CONDITIONS (VIN, condition) VALUES (:VIN, :condition)');
                foreach ($conditions as $c) {
                    if (in_array($c, $conditionsList)) $s->execute([':VIN' => $VIN, ':condition' => $c]);
                }
            }

            $pdo->commit();
            header('Location: read.php?success=Vehicle+added');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = $e->getCode() === '23000' ? 'VIN or license plate already exists.' : $e->getMessage();
        }
    }
}

function sel($a, $b) { return $a === $b ? 'selected' : ''; }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Add Vehicle</title></head><body>
<h2>Add Vehicle</h2><a href="read.php">← Back</a>

<?php if ($errors): ?><ul style="color:red"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach ?></ul><?php endif ?>

<form method="post">
  <fieldset><legend>Identity</legend>
    <label>VIN* <input type="text" name="VIN" maxlength="17" value="<?= htmlspecialchars($_POST['VIN'] ?? '') ?>" required></label><br>
    <label>License Plate* <input type="text" name="licensePlate" maxlength="10" value="<?= htmlspecialchars($_POST['licensePlate'] ?? '') ?>" required></label><br>
  </fieldset>

  <fieldset><legend>Vehicle Info</legend>
    <label>Make* <input type="text" name="make" value="<?= htmlspecialchars($_POST['make'] ?? '') ?>" required></label><br>
    <label>Model* <input type="text" name="model" value="<?= htmlspecialchars($_POST['model'] ?? '') ?>" required></label><br>
    <label>Year* <input type="number" name="year" min="1900" max="<?= date('Y')+1 ?>" value="<?= htmlspecialchars($_POST['year'] ?? '') ?>" required></label><br>
    <label>List Price* <input type="number" name="listPrice" min="0" step="0.01" value="<?= htmlspecialchars($_POST['listPrice'] ?? '') ?>" required></label><br>
    <label>Mileage* <input type="number" name="mileage" min="0" value="<?= htmlspecialchars($_POST['mileage'] ?? '') ?>" required></label><br>
    <label>MPG/Range <input type="text" name="MPG" value="<?= htmlspecialchars($_POST['MPG'] ?? '') ?>"></label><br>
    <label>Body Style*
      <select name="bodyStyle" required><option value="">-- Select --</option>
        <?php foreach ($bodyStyles as $b): ?><option value="<?= $b ?>" <?= sel($_POST['bodyStyle'] ?? '', $b) ?>><?= $b ?></option><?php endforeach ?>
      </select>
    </label><br>
    <label>Store*
      <select name="storeID" required><option value="">-- Select --</option>
        <?php foreach ($stores as $s): ?><option value="<?= $s['storeID'] ?>" <?= (($_POST['storeID'] ?? '') == $s['storeID']) ? 'selected' : '' ?>><?= htmlspecialchars($s['storeName']) ?></option><?php endforeach ?>
      </select>
    </label><br>
  </fieldset>

  <fieldset><legend>Specifications</legend>
    <label>Fuel Type* <select name="fuelType" required><option value="">-- Select --</option><?php foreach ($fuelTypes as $f): ?><option value="<?= $f ?>" <?= sel($_POST['fuelType'] ?? '', $f) ?>><?= $f ?></option><?php endforeach ?></select></label><br>
    <label>Transmission* <select name="transmission" required><option value="">-- Select --</option><?php foreach ($transmissions as $t): ?><option value="<?= $t ?>" <?= sel($_POST['transmission'] ?? '', $t) ?>><?= $t ?></option><?php endforeach ?></select></label><br>
    <label>Drivetrain* <select name="driveTrain" required><option value="">-- Select --</option><?php foreach ($driveTrains as $d): ?><option value="<?= $d ?>" <?= sel($_POST['driveTrain'] ?? '', $d) ?>><?= $d ?></option><?php endforeach ?></select></label><br>
    <label>Exterior Color* <input type="text" name="exColor" value="<?= htmlspecialchars($_POST['exColor'] ?? '') ?>" required></label><br>
    <label>Interior Color* <input type="text" name="inColor" value="<?= htmlspecialchars($_POST['inColor'] ?? '') ?>" required></label><br>
  </fieldset>

  <fieldset><legend>Conditions</legend>
    <?php foreach ($conditionsList as $c): ?><label><input type="checkbox" name="conditions[]" value="<?= $c ?>" <?= in_array($c, $_POST['conditions'] ?? []) ? 'checked' : '' ?>><?= $c ?></label><br><?php endforeach ?>
  </fieldset>

  <fieldset><legend>Features</legend>
    <?php foreach ($featuresList as $f): ?><label><input type="checkbox" name="features[]" value="<?= $f ?>" <?= in_array($f, $_POST['features'] ?? []) ? 'checked' : '' ?>><?= $f ?></label><br><?php endforeach ?>
  </fieldset>

  <br><button type="submit">Add Vehicle</button>
</form>
</body></html>
