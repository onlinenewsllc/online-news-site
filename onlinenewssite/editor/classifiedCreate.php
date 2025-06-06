<?php
/**
 * For management to place classified ads
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
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT userType FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if (empty($row['userType']) or strval($row['userType']) !== '4') {
    include 'logout.php';
    exit;
}
//
// Variables
//
$categoryIdEdit = null;
$categoryIdPost = inlinePost('categoryId');
$descriptionEdit = null;
$descriptionPost = securePost('description');
$durationEdit = null;
$durationPost = intval(inlinePost('duration'));
$idAdEdit = null;
$idAdPost = inlinePost('idAd');
$invoiceEdit = null;
$invoicePost = inlinePost('invoice');
$message = '';
$startDateEdit = null;
$startDatePost = inlinePost('startDate');
$startTime = strtotime($startDatePost);
$endDate = date("Y-m-d", $startTime + ($durationPost * 7 * 86400));
$titleEdit = null;
$titlePost = inlinePost('title');
$photosOrdered = [1, 2, 3, 4, 5, 6, 7];
$photosReverse = array_reverse($photosOrdered);
$photoAvailable = null;
//
// Button: Add / update
//
if (isset($_POST['addUpdate'])) {
    //
    // Determine insert or update
    //
    if (empty($idAdPost)) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->query('DELETE FROM ads WHERE title IS NULL');
        $stmt = $dbh->prepare('INSERT INTO ads (title) VALUES (?)');
        $stmt->execute([null]);
        $idAdPost = $dbh->lastInsertId();
        $dbh = null;
    }
    //
    // Apply the update except for the image
    //
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('UPDATE ads SET email=?, title=?, description=?, categoryId=?, startDate=?, duration=?, invoice=?, photos=? WHERE idAd=?');
    $stmt->execute([muddle($_SESSION['username']), $titlePost, $descriptionPost, $categoryIdPost, $startDatePost, $durationPost, $invoicePost, $_SESSION['userId'], $idAdPost]);
    $dbh = null;
    //
    // Store the image, if any
    //
    if (!empty($_FILES)
        and $_FILES['image']['size'] > 0
        and $_FILES['image']['error'] === 0
    ) {
        $sizes = getimagesize($_FILES['image']['tmp_name']);
        if ($sizes['mime'] === 'image/jpeg') {
            //
            // Check for available images
            //
            foreach ($photosReverse as $photo) {
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
                $stmt->setFetchMode(PDO::FETCH_NUM);
                $stmt->execute([$idAdPost]);
                $row = $stmt->fetch();
                $dbh = null;
                if (empty($row['0'])) {
                    $photoAvailable = $photo;
                }
            }
            if (is_null($photoAvailable)) {
                $message = 'All seven images have been used.';
            } else {
                //
                // Calculate the aspect ratio
                //
                $widthOriginal = $sizes['0'];
                $heightOriginal = $sizes['1'];
                $aspectRatio = $widthOriginal / $heightOriginal;
                //
                // Reduce an oversize image
                //
                if ($widthOriginal > 2360) {
                    $widthHD = 2360;
                    $heightHD = round($widthHD / $aspectRatio);
                    $hd = imagecreatetruecolor($widthHD, $heightHD);
                    imageinterlace($hd, true);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresampled($hd, $srcImage, 0, 0, 0, 0, $widthHD, $heightHD, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($hd, null, 83);
                    imagedestroy($hd);
                    $hdImage = ob_get_contents();
                    ob_end_clean();
                } else {
                    $hdImage = file_get_contents($_FILES['image']['tmp_name']);
                }
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('UPDATE ads SET photo' . $photoAvailable . '=? WHERE idAd=?');
                $stmt->execute([$hdImage, $idAdPost]);
                $dbh = null;
            }
        } else {
            $message = 'The uploaded file was not in the JPG format.';
        }
    }
}
//
// Button: Delete ad
//
if (isset($_POST['delete']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
    $stmt->execute([$idAdPost]);
    $dbh = null;
}
//
// Button: Publish
//
if (isset($_POST['publish'])) {
    //
    // Determine insert or update
    //
    if (empty($idAdPost)) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->query('DELETE FROM ads WHERE title IS NULL');
        $stmt = $dbh->prepare('INSERT INTO ads (title) VALUES (?)');
        $stmt->execute([null]);
        $idAdPost = $dbh->lastInsertId();
        $dbh = null;
    }
    if (empty($titlePost)) {
        $message = 'Title is a required field.';
    }
    if (empty($descriptionPost)) {
        $message = 'Description is a required field.';
    }
    if (empty($categoryIdPost)) {
        $message = 'Category is a required field and must be a subcategory.';
    }
    if (empty($startDatePost)) {
        $message = 'Start date is a required field.';
    }
    if (empty($durationPost)) {
        $message = 'Duration is a required field.';
    }
    if (empty($startDatePost) and empty($durationPost)) {
        $message = 'Start date and duration are required fields.';
    }
    if (isset($titlePost) and isset($descriptionPost) and isset($categoryIdPost) and isset($startDatePost) and isset($durationPost)) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->query('DELETE FROM ads WHERE title IS NULL');
        $stmt = $dbh->prepare('INSERT INTO ads (title) VALUES (?)');
        $stmt->execute([null]);
        $idAdPublish = $dbh->lastInsertId();
        $num = [];
        foreach ($photosOrdered as $photo) {
            $dbh = new PDO($dbClassifieds);
            $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
            $stmt->setFetchMode(PDO::FETCH_NUM);
            $stmt->execute([$idAdPost]);
            $row = $stmt->fetch();
            $dbh = null;
            if (isset($row['0'])) {
                $num[] = '1';
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('UPDATE ads SET photo' . $photo . '=? WHERE idAd=?');
                $stmt->execute([$row['0'], $idAdPublish]);
                $dbh = null;
            } else {
                $num[] = '0';
            }
        }
        $photosPublished = json_encode($num);
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('UPDATE ads SET email=?, title=?, description=?, categoryId=?, endDate=?, startDate=?, duration=?, invoice=?, photos=? WHERE idAd=?');
        $stmt->execute([muddle($_SESSION['username']), $titlePost, $descriptionPost, $categoryIdPost, $endDate, $startDatePost, $durationPost, $invoicePost, $photosPublished, $idAdPublish]);
        $dbh = null;
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
        $stmt->execute([$idAdPost]);
        $dbh = null;
    }
    $dbh = null;
}
//
// Button: Delete photos
//
if (isset($_POST['photoDelete']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('UPDATE ads SET photo1=?, photo2=?, photo3=?, photo4=?, photo5=?, photo6=?, photo7=? WHERE idAd=?');
    $stmt->execute([null, null, null, null, null, null, null, $idAdPost]);
    $dbh = null;
}
//
// Set the edit variables
//
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->prepare('SELECT idAd, email, title, description, categoryId, endDate, startDate, duration, invoice FROM ads WHERE photos=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $categoryIdEdit = $row['categoryId'];
    $descriptionEdit = $row['description'];
    $durationEdit = $row['duration'];
    $emailEdit = $row['email'];
    $endDateEdit = $row['endDate'];
    $idAdEdit = $row['idAd'];
    $invoiceEdit = $row['invoice'];
    $startDateEdit = $row['startDate'];
    $titleEdit = $row['title'];
}
//
// HTML
//
require $includesPath . '/editor/header1.inc';
?>
  <title>Create a new classified ad</title>
  <link rel="icon" type="image/png" href="images/32.png">
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.min.css">
  <link rel="stylesheet" type="text/css" href="z/base.css">
  <link rel="stylesheet" type="text/css" href="z/admin.css">
  <script src="z/jquery.min.js"></script>
  <script src="z/jquery-ui.min.js"></script>
  <script src="z/datepicker.js"></script>
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="images/192.png">
</head>
<?php require $includesPath . '/editor/body.inc'; ?>

  <nav class="n">
    <h4 class="m"><a class="m" href="classifieds.php">Pending review</a><a class="s" href="classifiedCreate.php">Create</a><a class="m" href="classifiedEdit.php">Edit</a></h4>
  </nav>
<?php echoIfMessage($message); ?>

  <div class="column">
    <h1>Create a classified ad</h1>

    <p>One classified ad at a time may be created and edited by each classifieds management user. Until either published or deleted, the ad will be here available for further editing each time the user logs in.</p>

    <form action="<?php echo $uri; ?>classifiedCreate.php" method="post" enctype="multipart/form-data">
      <p><label for="title">Title</label><br>
      <input id="title" name="title" class="wide"<?php echoIfValue($titleEdit); ?>><input type="hidden" name="idAd"<?php echoIfValue($idAdEdit); ?>></p>

      <p><label for="description">Description</label><br>
      <textarea id="description" name="description" class="wide" rows="9"><?php echoIfText($descriptionEdit); ?></textarea><p>

      <p><label for="invoice"><input id="invoice" name="invoice" type="checkbox" value="1"<?php echoIfYes($invoiceEdit); ?>> Send an invoice to also have the add in the print version of the paper.</label></p>

      <p><label for="categoryId">Categories (select a subcategory)</label><br>
        <select id="categoryId" name="categoryId" size="1">
<?php
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $row = array_map('strval', $row);
    extract($row);
    echo '        <option value="">' . html($section) . "</option>\n";
    $stmt = $dbh->prepare('SELECT idSubsection, subsection FROM subsections WHERE parentId=? ORDER BY sortOrderSubsection');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idSection]);
    foreach ($stmt as $row) {
        $row = array_map('strval', $row);
        extract($row);
        if ($idSubsection === strval($categoryIdEdit)) {
            $selected = ' selected';
        } else {
            $selected = null;
        }
        echo '        <option value="' . $idSubsection . '"' . $selected . '>&nbsp;&nbsp;&nbsp;' . html($subsection) . "</option>\n";
    }
}
$dbh = null;
?>
      </select></p>

      <p><label for="startDate">Start date</label><br>
      <input class="datepicker date" id="startDate" name="startDate"<?php echoIfValue($startDateEdit); ?>></p>

      <p><label for="duration">Duration (weeks)</label><br>
      <input type="number" id="duration" name="duration" class="date"<?php echoIfValue($durationEdit); ?>></p>

      <p><label for="image">Photo upload (JPG image only<?php uploadFilesizeMaximum(); ?>)</label><br>
      <input id="image" name="image" type="file" class="wide" accept="image/jpeg"></p>

      <p>Up to seven images may be included in an ad. Upload one image at a time. Edit the listing to add each additional image. JPG is the only permitted image format. The best image size is 2360 pixels wide. Larger images are reduced to that width.</p>

      <p><input type="submit" class="button" name="addUpdate" value="Add/update"> <input type="submit" class="button" name="publish" value="Publish"></p>

      <p><input type="submit" class="button" name="photoDelete" value="Delete photos"> <input type="submit" class="button" name="delete" value="Delete ad"></p>
    </form>

<?php
if (isset($idAdEdit)) {
    foreach ($photosOrdered as $photo) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute([$idAdEdit]);
        $row = $stmt->fetch();
        $dbh = null;
        if (!empty($row['0'])) {
            echo '    <p><img class="wide border" src="imagen.php?i=' . muddle($idAdEdit) . $photo . '" alt=""></p>' . "\n\n";
        }
    }
}
?>
  </div>
</body>
</html>
