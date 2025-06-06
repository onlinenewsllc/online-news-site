<?php
/**
 * The news home page
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
require 'editor/z/system/configuration.php';
if ($freeOrPaid !== 'free') {
    include $includesPath . '/subscriber/authorization.php';
} else {
    $uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
}
require $includesPath . '/editor/common.php';
require $includesPath . '/parsedown-master/Parsedown.php';
//
// Variables
//
if (!empty($_GET['a'])) {
    $aGet = secure($_GET['a']);
} else {
    header('Location: ' . $uri);
    exit;
}
$anchorPath = null;
$database = $dbPublished;
$database2 = $dbPublished2;
$datePost = $today;
$editorView = null;
$imagePath = 'images.php';
$imagePath2 = 'imagep2.php';
$indexPath = '';
$use = 'news';
//
if (isset($_SESSION['userId'])) {
    $logOutHtml = ' | <a class="n" href="' . $uri . 'logout.php">Log out</a>';
} else {
    $logOutHtml = null;
}
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT name FROM names WHERE idName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $title = $row['name'];
    $name = '  <h2>' . html($row['name']) . "</h2>\n\n";
} else {
    $title = 'Subscriber';
    $name = null;
}
//
// Get article headline for SEO purposes
//
$headline = '';
if (isset($_GET['a'])) {
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$aGet]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $dbh = new PDO($dbPublished);
        $stmt = $dbh->prepare('SELECT headline FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$aGet]);
        $row = $stmt->fetch();
        $dbh = null;
        $row = array_map('strval', $row);
        extract($row);
        $headline = $headline . ' - ';
    }
}
//
// HTML
//
require $includesPath . '/editor/header1.inc';
echo '  <title>' . $headline . $title . "</title>\n";
require $includesPath . '/subscriber/header2.inc';
if (file_exists('z/local.css')) {
    echo '  <link rel="stylesheet" type="text/css" href="z/local.css">' . "\n";
}
echo '</head>

<body>' . "\n";
require $includesPath . '/subscriber/displayIndex.inc';
?>
</body>
</html>
