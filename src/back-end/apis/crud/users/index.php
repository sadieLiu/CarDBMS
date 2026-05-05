<?php
require_once '../db.php';
require_once '../auth.php';
requireLogin();

$pdo    = getDB();
$errors = [];
$isAdmin = !empty($_SESSION['isAdmin']);
$selfID  = currentUserID();
$mode    = trim($_GET['mode'] ?? 'list');
$editID  = (int)($_GET['userID'] ?? 0);

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    requireAdmin();
    $delID = (int)($_POST['userID'] ?? 0);
    if ($delID && $delID !== $selfID) {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM TRANSACTION_RECORD WHERE userID=:id');
        $chk->execute([':id'=>$delID]);
        if ((int)$chk->fetchColumn() > 0) {
            header('Location: index.php?error=Cannot+delete+user+with+transactions');
            exit;
        }
        $pdo->prepare('DELETE FROM USER WHERE userID=:id')->execute([':id'=>$delID]);
        header('Location: index.php?success=User+deleted');
        exit;
    }
}

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    requireAdmin();
    $fName = trim($_POST['fName'] ?? '');
    $lName = trim($_POST['lName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phoneNum'] ?? '');
    $addr  = trim($_POST['address'] ?? '');
    $pw    = trim($_POST['pword'] ?? '');
    $adm   = isset($_POST['isAdmin']) ? 1 : 0;

    if (!$fName) $errors[] = 'First name required.';
    if (!$lName) $errors[] = 'Last name required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (!$phone) $errors[] = 'Phone required.';
    if (strlen($pw) < 6) $errors[] = 'Password must be 6+ chars.';

    if (empty($errors)) {
        try {
            $pdo->prepare('INSERT INTO USER (email,phoneNum,address,fName,lName,pword,isAdmin,addedAt) VALUES (:e,:p,:a,:f,:l,:pw,:adm,NOW())')
                ->execute([':e'=>$email,':p'=>$phone,':a'=>$addr,':f'=>$fName,':l'=>$lName,':pw'=>password_hash($pw,PASSWORD_DEFAULT),':adm'=>$adm]);
            header('Location: index.php?success=User+created');
            exit;
        } catch (PDOException $e) {
            $errors[] = $e->getCode()==='23000' ? 'Email already exists.' : $e->getMessage();
        }
    }
    $mode = 'create';
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $targetID = (int)($_POST['userID'] ?? 0);
    if (!$isAdmin && $targetID !== $selfID) exit('Access denied.');

    $fName = trim($_POST['fName'] ?? '');
    $lName = trim($_POST['lName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phoneNum'] ?? '');
    $addr  = trim($_POST['address'] ?? '');
    $pw    = trim($_POST['pword'] ?? '');

    if (!$fName) $errors[] = 'First name required.';
    if (!$lName) $errors[] = 'Last name required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (!$phone) $errors[] = 'Phone required.';
    if ($pw && strlen($pw) < 6) $errors[] = 'Password must be 6+ chars.';

    if (empty($errors)) {
        try {
            $sets = ['fName=:f','lName=:l','email=:e','phoneNum=:p','address=:a'];
            $params = [':f'=>$fName,':l'=>$lName,':e'=>$email,':p'=>$phone,':a'=>$addr,':id'=>$targetID];
            if ($pw) { $sets[] = 'pword=:pw'; $params[':pw'] = password_hash($pw,PASSWORD_DEFAULT); }
            if ($isAdmin) { $sets[] = 'isAdmin=:adm'; $params[':adm'] = isset($_POST['isAdmin'])?1:0; }
            $pdo->prepare('UPDATE USER SET '.implode(',',$sets).' WHERE userID=:id')->execute($params);
            header('Location: index.php?success=User+updated');
            exit;
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
        }
    }
    $mode = 'edit'; $editID = $targetID;
}

// Fetch for forms
$editUser = null;
if ($mode === 'edit' && $editID) {
    $stmt = $pdo->prepare('SELECT * FROM USER WHERE userID=:id');
    $stmt->execute([':id'=>$editID]);
    $editUser = $stmt->fetch();
    if (!$editUser) { header('Location: index.php?error=User+not+found'); exit; }
}

$users = [];
if ($mode === 'list') {
    if ($isAdmin) {
        $users = $pdo->query('SELECT * FROM USER ORDER BY lName,fName')->fetchAll();
    } else {
        $stmt = $pdo->prepare('SELECT * FROM USER WHERE userID=:id');
        $stmt->execute([':id'=>$selfID]);
        $users = [$stmt->fetch()];
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Users</title></head><body>
<h2>Users</h2>

<?php if (!empty($_GET['success'])): ?><p style="color:green"><?= htmlspecialchars($_GET['success']) ?></p><?php endif ?>
<?php if (!empty($_GET['error'])): ?><p style="color:red"><?= htmlspecialchars($_GET['error']) ?></p><?php endif ?>
<?php if ($errors): ?><ul style="color:red"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach ?></ul><?php endif ?>

<?php if ($mode === 'create' && $isAdmin): ?>
  <h3>Create User</h3><a href="index.php">&#8592; Back</a><br><br>
  <form method="post">
    <input type="hidden" name="action" value="create">
    <label>First Name* <input type="text" name="fName" value="<?= htmlspecialchars($_POST['fName']??'') ?>" required></label><br>
    <label>Last Name*  <input type="text" name="lName" value="<?= htmlspecialchars($_POST['lName']??'') ?>" required></label><br>
    <label>Email*      <input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" required></label><br>
    <label>Phone*      <input type="text" name="phoneNum" value="<?= htmlspecialchars($_POST['phoneNum']??'') ?>" required></label><br>
    <label>Address     <input type="text" name="address" value="<?= htmlspecialchars($_POST['address']??'') ?>"></label><br>
    <label>Password*   <input type="password" name="pword" required></label><br>
    <label><input type="checkbox" name="isAdmin" <?= isset($_POST['isAdmin'])?'checked':'' ?>> Admin</label><br>
    <br><button type="submit">Create</button>
  </form>

<?php elseif ($mode === 'edit' && $editUser): ?>
  <h3>Edit User #<?= $editUser['userID'] ?></h3><a href="index.php">&#8592; Back</a><br><br>
  <form method="post">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="userID" value="<?= $editUser['userID'] ?>">
    <label>First Name* <input type="text" name="fName" value="<?= htmlspecialchars($editUser['fName']) ?>" required></label><br>
    <label>Last Name*  <input type="text" name="lName" value="<?= htmlspecialchars($editUser['lName']) ?>" required></label><br>
    <label>Email*      <input type="email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required></label><br>
    <label>Phone*      <input type="text" name="phoneNum" value="<?= htmlspecialchars($editUser['phoneNum']) ?>" required></label><br>
    <label>Address     <input type="text" name="address" value="<?= htmlspecialchars($editUser['address']??'') ?>"></label><br>
    <label>New Password <input type="password" name="pword"> (blank = keep current)</label><br>
    <?php if ($isAdmin): ?><label><input type="checkbox" name="isAdmin" <?= $editUser['isAdmin']?'checked':'' ?>> Admin</label><br><?php endif ?>
    <br><button type="submit">Save</button> <a href="index.php">Cancel</a>
  </form>

<?php else: ?>
  <?php if ($isAdmin): ?><a href="index.php?mode=create">+ Create User</a><br><br><?php endif ?>
  <table border="1" cellpadding="5" cellspacing="0">
    <thead><tr><th>ID</th><th>First</th><th>Last</th><th>Email</th><th>Phone</th><th>Address</th><th>Admin</th><th>Added</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= $u['userID'] ?></td>
        <td><?= htmlspecialchars($u['fName']) ?></td>
        <td><?= htmlspecialchars($u['lName']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars($u['phoneNum']) ?></td>
        <td><?= htmlspecialchars($u['address']??'—') ?></td>
        <td><?= $u['isAdmin'] ? 'Yes' : 'No' ?></td>
        <td><?= htmlspecialchars($u['addedAt']??'—') ?></td>
        <td>
          <?php if ($isAdmin || $u['userID']==$selfID): ?>
            <a href="index.php?mode=edit&userID=<?= $u['userID'] ?>">Edit</a>
          <?php endif ?>
          <?php if ($isAdmin && $u['userID']!=$selfID): ?>
            |
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this user?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="userID" value="<?= $u['userID'] ?>">
              <button type="submit" style="color:red">Delete</button>
            </form>
          <?php endif ?>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
<?php endif ?>
</body></html>
