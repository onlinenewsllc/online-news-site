<?php
/**
 * User maintenance for the editing users
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Online News <useTheContactForm@onlinenewssite.com>
 * @copyright 2025 Online News
 * @license   https://onlinenewssite.com/license.html
 * @version   2025 05 12
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/onlinenewsllc/online-news-site
 */
session_start();
require 'z/system/configuration.php';
$includesPath = '../' . $includesPath;
require $includesPath . '/editor/authorization.php';
require $includesPath . '/editor/common.php';
//
// User-group authorization
//
if ($_SESSION['username'] !== 'admin') {
    include 'logout.php';
    exit;
}
//
// Variables
//
$adminPassPost = inlinePost('adminPass');
$edit = inlinePost('edit');
$emailEdit = null;
$emailPost = inlinePost('email');
$fullNameEdit = null;
$fullNamePost = html(inlinePost('fullName'));
$idUserEdit = '';
$idUserPost = inlinePost('idUser');
$message = '';
$passPost = inlinePost('pass');
$userEdit = '';
$userPost = inlinePost('user');
if (!empty($passPost)) {
    $hash = password_hash($passPost, PASSWORD_DEFAULT);
} else {
    $hash = null;
}
//
// Test admin password authentication
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT pass FROM users WHERE user=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['username']]);
$row = $stmt->fetch();
$dbh = null;
if (password_verify($adminPassPost, $row['pass'])) {
    //
    // Button: Add / update
    //
    if (isset($_POST['addUpdate'])) {
        //
        // Determine insert or update, check for unique user name
        //
        if (empty($_POST['existing'])) {
            $dbh = new PDO($dbEditors);
            $stmt = $dbh->prepare('SELECT user FROM users WHERE user=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$userPost]);
            $row = $stmt->fetch();
            $dbh = null;
            if (isset($row['user'])) {
                $message = 'The user name is already in use. User names must be unique.';
            } else {
                $dbh = new PDO($dbEditors);
                $stmt = $dbh->query('DELETE FROM users WHERE user IS NULL');
                $stmt = $dbh->prepare('INSERT INTO users (user) VALUES (?)');
                $stmt->execute([null]);
                $idUser = $dbh->lastInsertId();
                $dbh = null;
            }
        } else {
            $dbh = new PDO($dbEditors);
            $stmt = $dbh->prepare('SELECT idUser FROM users WHERE idUser=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idUserPost]);
            $row = $stmt->fetch();
            $dbh = null;
            extract($row);
        }
        //
        // Apply update
        //
        if (isset($_POST['user']) and isset($idUser)) {
            $dbh = new PDO($dbEditors);
            if (is_null($hash)) {
                $stmt = $dbh->prepare('UPDATE users SET user=?, fullName=?, email=?, userType=? WHERE idUser=?');
                $stmt->execute([$userPost, $fullNamePost, $emailPost, 1, $idUser]);
            } else {
                $stmt = $dbh->prepare('UPDATE users SET user=?, pass=?, fullName=?, email=?, userType=? WHERE idUser=?');
                $stmt->execute([$userPost, $hash, $fullNamePost, $emailPost, 1, $idUser]);
            }
            $dbh = null;
        } else {
            if (empty($message)) {
                $message = 'No user name was input.';
            }
        }
    }
    //
    // Button: Delete
    //
    if (isset($_POST['delete'])) {
        if ($userPost !== 'admin') {
            if (isset($_POST['user'])) {
                $dbh = new PDO($dbEditors);
                $stmt = $dbh->prepare('SELECT user FROM users WHERE user=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$userPost]);
                $row = $stmt->fetch();
                $dbh = null;
                if (isset($row['user'])) {
                    $dbh = new PDO($dbEditors);
                    $stmt = $dbh->prepare('DELETE FROM users WHERE user=?');
                    $stmt->execute([$userPost]);
                    $dbh = null;
                } else {
                    $message = 'The user name was not found.';
                }
            } else {
                $message = 'No user name was input.';
            }
        }
    }
} elseif (isset($_POST['addUpdate']) or isset($_POST['delete'])) {
    if (empty($_POST['adminPass'])) {
        $message = 'The admin password is required for all user maintenance.';
    } else {
        $message = 'The admin password is invalid.';
    }
}
//
// Button: Edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($dbEditors);
    $stmt = $dbh->prepare('SELECT idUser, user, fullName, email FROM users WHERE idUser=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idUserPost]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $emailEdit = $email;
        $fullNameEdit = $fullName;
        $idUserEdit = $idUser;
        $userEdit = $user;
    }
}
//
// HTML
//
require $includesPath . '/editor/header1.inc';
echo "  <title>Editing user maintenance</title>\n";
echo '  <script src="z/wait.js"></script>' . "\n";
require $includesPath . '/editor/header2.inc';
?>

  <nav class="n">
    <h4 class="m"><a class="s" href="usersEditors.php">Editing users</a> <a class="m" href="usersSubscribers.php">Patron mgt users</a> <a class="m" href="usersAdvertising.php">Advertising users</a> <a class="m" href="usersClassified.php">Classified users</a> <a class="m" href="usersMenu.php">Menu users</a> <a class="m" href="settings.php">Settings</a> <a class="m" href="classifiedSections.php">Classifieds</a></h4>
  </nav>
<?php echoIfMessage($message); ?>

  <div class="flex">
    <main>
      <h1>Editing user maintenance</h1>

      <form action="<?php echo $uri; ?>usersEditors.php" method="post">
        <p>The admin password is required for all user maintenance.</p>

        <p><label for="adminPass">Password</label><br>
        <input id="adminPass" name="adminPass" type="password" class="h" autofocus required></p>

        <h2>Add, update and delete users</h2>

        <p>Full name, user name and password are required to add a user. For an update, the full name and user name are required, the password will remain unchanged when left blank. The user name only is required for delete. User names must be unique.</p>

        <p><label for="fullName">Full name</label><br>
        <input id="fullName" name="fullName" class="h"<?php echoIfValue($fullNameEdit); ?>></p>

        <p><label for="user">User name</label><br>
        <input id="user" name="user" class="h" required<?php echoIfValue($userEdit); ?>><input name="idUser" type="hidden" <?php echoIfValue($idUserEdit); ?>></p>

        <p><label for="pass">Password</label><br>
        <input id="pass" name="pass" class="h"></p>

        <p><label for="email">Email (optional, for display on the public site)</label><br>
        <input id="email" name="email" type="email" class="h"<?php echoIfValue($emailEdit); ?>></p>

        <p><input type="submit" class="button" value="Add / update" name="addUpdate"> <input type="submit" class="button" value="Delete" name="delete"><input type="hidden" name="existing"<?php echoIfValue($edit); ?>></p>
      </form>
    </main>

    <aside>
      <h2>Editing users</h2>

<?php
$dbh = new PDO($dbEditors);
$rowcount = null;
$stmt = $dbh->query('SELECT idUser, user, pass, fullName, email FROM users WHERE userType = 1 ORDER BY fullName');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    if (empty($pass)) {
        $printPass = '<b>NOT SET!</b>';
    } else {
        $printPass = 'set.';
    }
    if (!empty($email)) {
        $fullName = '<a href="mailto:' . $email . '">' . $fullName . '</a>';
    }
    if ($user !== 'admin') {
        $rowcount++;
        echo '      <form action="' . $uri . 'usersEditors.php" method="post">' . "\n";
        echo '        <p>' . $fullName . " - Full name<br>\n";
        echo '        ' . html($user) . " - User name, count: $rowcount<br>\n";
        echo "        The password is $printPass<br>\n";
        echo '        <input name="idUser" type="hidden" value="' . $idUser . '"><input type="submit" class="button" value="Edit" name="edit"></p>' . "\n";
        echo "      </form>\n\n";
    }
}
$dbh = null;
?>
    </aside>
  </div>
</body>
</html>
