<?php
require_once '../db.php';
require_once '../auth.php';
requireAdmin();

$pdo    = getDB();
$errors = [];
$VIN    = trim($_GET['VIN'] ?? $_POST['VIN'] ?? '');
if (!$VIN) { header('Location: read.php'); exit; }

$fuelTypes     = ['gas', 'EV', 'PHEV', 'hybrid', 'diesel', 'hydrogen'];
$transmissions = ['4WD', 'AWD', 'FWD', 'RWD'];
$driveTrains   = ['CVT', 'auto', 'manual'];
$bodyStyles    = ['sedan', 'SUV', 'truck', 'van', 'coupe', 'hatchback'];
$featuresList  = ['towHitch', 'backupCamera', 'thirdRowSeating', 'Navigation', 'moonRoof'];
$conditionsList= ['oneOwner', 'cleanTitle', 'noAccident', 'certifiedPreowned'];
$stores        = $pdo->query('SELECT storeID, storeName FROM STORE ORDER BY storeName')->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM VEHICLE WHERE VIN = :VIN');
$stmt->execute([':VIN' => $VIN]);
$vehicle = $stmt->fetch();
if (!$vehicle) { header('Location: read.php?error=Vehicle+not+found'); exit; }

$stmt = $pdo->prepare('SELECT * FROM CAR_SPECIFICATIONS WHERE VIN = :VIN');
$stmt->execute([':VIN' => $VIN]);
$specs = $stmt->fetch() ?: [];

$stmt = $pdo->prepare('SELECT feature FROM AVAILABLE_FEATURES WHERE VIN = :VIN');
$stmt->execute([':VIN' => $VIN]);
$existingFeatures = array_column($stmt->fetchAll(), 'feature');

$stmt = $pdo->prepare('SELECT `condition` FROM CAR_CONDITIONS WHERE VIN = :VIN');
$stmt->execute([':VIN' => $VIN]);
$existingConditions = array_column($stmt->fetchAll(), 'condition');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                UPDATE VEHICLE SET licensePlate=:lp, listPrice=:lpr, make=:make, model=:model,
                year=:year, MPG=:MPG, mileage=:mileage, bodyStyle=:bs, storeID=:sid
                WHERE VIN=:VIN
            ')->execute([
                ':lp'=>$licensePlate,':lpr'=>$listPrice,':make'=>$make,':model'=>$model,
                ':year'=>$year,':MPG'=>$MPG?:null,':mileage'=>$mileage,':bs'=>$bodyStyle,
                ':sid'=>$storeID,':VIN'=>$VIN
            ]);

            $pdo->prepare('
                INSERT INTO CAR_SPECIFICATIONS (VIN,fuelType,transmission,driveTrain,exColor,inColor)
                VALUES (:VIN,:ft,:tr,:dt,:ex,:in)
                ON DUPLICATE KEY UPDATE fuelType=VALUES(fuelType),transmission=VALUES(transmission),
                driveTrain=VALUES(driveTrain),exColor=VALUES(exColor),inColor=VALUES(inColor)
            ')->execute([':VIN'=>$VIN,':ft'=>$fuelType,':tr'=>$transmission,':dt'=>$driveTrain,':ex'=>$exColor,':in'=>$inColor]);

            $pdo->prepare('DELETE FROM AVAILABLE_FEATURES WHERE VIN=:VIN')->execute([':VIN'=>$VIN]);
            if (!empty($features)) {
                $s = $pdo->prepare('INSERT INTO AVAILABLE_FEATURES (VIN,feature) VALUES (:VIN,:f)');
                foreach ($features as $f) { if (in_array($f,$featuresList)) $s->execute([':VIN'=>$VIN,':f'=>$f]); }
            }

            $pdo->prepare('DELETE FROM CAR_CONDITIONS WHERE VIN=:VIN')->execute([':VIN'=>$VIN]);
            if (!empty($conditions)) {
                $s = $pdo->prepare('INSERT INTO CAR_CONDITIONS (VIN,`condition`) VALUES (:VIN,:c)');
                foreach ($conditions as $c) { if (in_array($c,$conditionsList)) $s->execute([':VIN'=>$VIN,':c'=>$c]); }
            }

            $pdo->commit();
            header('Location: read.php?success=Vehicle+updated');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }

    $vehicle = array_merge($vehicle, $_POST);
    $specs   = array_merge($specs,   $_POST);
    $existingFeatures   = $features;
    $existingConditions = $conditions;
}

function sel($a, $b) { return $a === $b ? 'selected' : ''; }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Edit Vehicle</title></head><body>
<h2>Edit Vehicle — <?= htmlspecialchars($VIN) ?></h2><a href="read.php">← Back</a>

<?php if ($errors): ?><ul style="color:red"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach ?></ul><?php endif ?>

<form method="post">
  <input type="hidden" name="VIN" value="<?= htmlspecialchars($VIN) ?>">

  <fieldset><legend>Identity</legend>
    <p><strong>VIN:</strong> <?= htmlspecialchars($VIN) ?> (cannot change)</p>
    <label>License Plate* <input type="text" name="licensePlate" value="<?= htmlspecialchars($vehicle['licensePlate']) ?>" required></label><br>
  </fieldset>

  <fieldset><legend>Vehicle Info</legend>
    <label>Make* <input type="text" name="make" value="<?= htmlspecialchars($vehicle['make']) ?>" required></label><br>
    <label>Model* <input type="text" name="model" value="<?= htmlspecialchars($vehicle['model']) ?>" required></label><br>
    <label>Year* <input type="number" name="year" min="1900" max="<?= date('Y')+1 ?>" value="<?= $vehicle['year'] ?>" required></label><br>
    <label>List Price* <input type="number" name="listPrice" min="0" step="0.01" value="<?= $vehicle['listPrice'] ?>" required></label><br>
    <label>Mileage* <input type="number" name="mileage" min="0" value="<?= $vehicle['mileage'] ?>" required></label><br>
    <label>MPG/Range <input type="text" name="MPG" value="<?= htmlspecialchars($vehicle['MPG'] ?? '') ?>"></label><br>
    <label>Body Style* <select name="bodyStyle" required><?php foreach ($bodyStyles as $b): ?><option value="<?= $b ?>" <?= sel($vehicle['bodyStyle'],$b) ?>><?= $b ?></option><?php endforeach ?></select></label><br>
    <label>Store* <select name="storeID" required><?php foreach ($stores as $s): ?><option value="<?= $s['storeID'] ?>" <?= $vehicle['storeID']==$s['storeID']?'selected':'' ?>><?= htmlspecialchars($s['storeName']) ?></option><?php endforeach ?></select></label><br>
  </fieldset>

  <fieldset><legend>Specifications</legend>
    <label>Fuel Type* <select name="fuelType" required><?php foreach ($fuelTypes as $f): ?><option value="<?= $f ?>" <?= sel($specs['fuelType']??'',$f) ?>><?= $f ?></option><?php endforeach ?></select></label><br>
    <label>Transmission* <select name="transmission" required><?php foreach ($transmissions as $t): ?><option value="<?= $t ?>" <?= sel($specs['transmission']??'',$t) ?>><?= $t ?></option><?php endforeach ?></select></label><br>
    <label>Drivetrain* <select name="driveTrain" required><?php foreach ($driveTrains as $d): ?><option value="<?= $d ?>" <?= sel($specs['driveTrain']??'',$d) ?>><?= $d ?></option><?php endforeach ?></select></label><br>
    <label>Exterior Color* <input type="text" name="exColor" value="<?= htmlspecialchars($specs['exColor']??'') ?>" required></label><br>
    <label>Interior Color* <input type="text" name="inColor" value="<?= htmlspecialchars($specs['inColor']??'') ?>" required></label><br>
  </fieldset>

  <fieldset><legend>Conditions</legend>
    <?php foreach ($conditionsList as $c): ?><label><input type="checkbox" name="conditions[]" value="<?= $c ?>" <?= in_array($c,$existingConditions)?'checked':'' ?>><?= $c ?></label><br><?php endforeach ?>
  </fieldset>

  <fieldset><legend>Features</legend>
    <?php foreach ($featuresList as $f): ?><label><input type="checkbox" name="features[]" value="<?= $f ?>" <?= in_array($f,$existingFeatures)?'checked':'' ?>><?= $f ?></label><br><?php endforeach ?>
  </fieldset>

  <br><button type="submit">Save Changes</button> <a href="read.php">Cancel</a>
</form>
</body></html>
