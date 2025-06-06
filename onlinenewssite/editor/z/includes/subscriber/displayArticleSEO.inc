<?php
/**
 * Display a single article as linked in sitemap.xml
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
//
// Look for the article in the published and archive databases
//
$dbh = new PDO($dbPublished);
$stmt = $dbh->prepare('SELECT * FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$aGet]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $row = array_map('strval', $row);
    extract($row);
    $database2 = $dbPublished2;
    $imagePath = 'images.php';
    $imagePath2 = 'imagep2.php';
} else {
    $dbNumber = 0;
    while ($dbNumber !== -1) {
        $db = str_replace('archive', 'archive-' . $dbNumber, $dbArchive);
        if ($dbNumber === 0
            or file_exists(str_replace('sqlite:', '', $db))
        ) {
            if ($dbNumber === 0) {
                $database = $dbArchive;
            } else {
                $database = $db;
            }
            $dbNumber++;
        } else {
            $dbNumber = -1;
            $database = null;
        }
        if (!empty($database)) {
            $dbh = new PDO($database);
            $stmt = $dbh->prepare('SELECT * FROM articles WHERE idArticle=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$aGet]);
            $row = $stmt->fetch();
            $dbh = null;
            if ($row) {
                $row = array_map('strval', $row);
                extract($row);
                $dbNumber = -1;
                $database2 = $dbArchive2;
                $imagePath = 'imagea.php';
                $imagePath2 = 'imagea2.php';
            }
        }
    }
}
if (empty($idArticle)) {
    echo '<meta http-equiv="refresh" content="0; url=' . $uri . '">';
    exit;
}
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT section FROM sections WHERE idSection=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$idSection]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    extract($row);
} else {
    $section = null;
}
//
// HTML
//
$html = '    <div class="main">' . "\n";
$html.= '      <p><span class="fr"><a href="' . $uri . '?m=archive-search" class="n">Archive search</a></span></p>' . "\n\n";
$bylineDateTime = isset($publicationDate) ? date("l, F j, Y", strtotime($publicationDate)) : null;
$html.= '      <p><b>' . $section . "</b></p>\n\n";
$html.= '      <h1>' . html($headline) . "</h1>\n\n";
if (!empty($standfirst)) {
    $html.= '    <h3>' . html($standfirst) . "</h3>\n\n";
}
if (!empty($hdImage)) {
    if ($photoName === 'third') {
        $html.= '    <p class="a"><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="third border" alt=""></p>' . "\n\n";
    } else {
        $html.= '    <p><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="wide border" alt=""></p>' . "\n\n";
    }
    if (!empty($photoCaption) and !empty($photoCredit)) {
        if ($photoName === 'third') {
            $html.= '    <h6 class="a">' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
        } else {
            $html.= '    <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
        }
    } elseif (!empty($photoCaption)) {
        if ($photoName === 'third') {
            $html.= '    <h6 class="a">' . html($photoCaption) . "</h6>\n\n";
        } else {
            $html.= '    <h6>' . html($photoCaption) . "</h6>\n\n";
        }
    } elseif (!empty($photoCredit)) {
        if ($photoName === 'third') {
            $html.= '    <h6 class="a">(' . $photoCredit . ")</h6>\n\n";
        } else {
            $html.= '    <h6>(' . $photoCredit . ")</h6>\n\n";
        }
    }
}
if (!empty($byline) or !empty($bylineDateTime)) {
    $html.= '      <h5 class="a">';
}
if (!empty($byline)) {
    $html.= 'By ' . $byline;
}
if (!empty($byline) and !empty($bylineDateTime)) {
    $html.= ', ';
}
if (!empty($bylineDateTime)) {
    $html.= html($bylineDateTime);
}
if (!empty($byline) or !empty($bylineDateTime)) {
    $html.= "</h5>\n\n";
}
$temp = Parsedown::instance()->parse($text);
$temp = str_replace("\n", "\n\n    ", $temp);
$temp = str_replace('<h1>', '<h1 class="a">', $temp);
$temp = str_replace('<h2>', '<h2 class="a">', $temp);
$temp = str_replace('<h3>', '<h3 class="a">', $temp);
$temp = str_replace('<p>', '<p class="a">', $temp);
$temp = str_replace('<ol>', '<ol class="a">', $temp);
$temp = str_replace('<ul>', '<ul class="a">', $temp);
$temp = str_replace('<li>', '  <li>', $temp);
//
// Insert ads between paragraphs
//
$dbh = new PDO($dbAdvertising);
$stmt = $dbh->query('SELECT adMinParagraphs, adMaxAdverts FROM maxAd');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
if ($row) {
    extract($row);
    if (empty($adMinParagraphs)) {
        $adMinParagraphs = 999;
    }
    if (empty($adMaxAdverts)) {
        $adMaxAdverts = 0;
    }
} else {
    $adMinParagraphs = 999;
    $adMaxAdverts = 0;
}
if ($adMaxAdverts !== 0) {
    $numParagraphs = mb_substr_count($temp, '<p class="a">');
    $maxAdsPara = round($numParagraphs / $adMinParagraphs);
    if ($maxAdsPara > $adMaxAdverts) {
        $maxAds = $adMaxAdverts;
    } else {
        $maxAds = $maxAdsPara;
    }
    if ($maxAds !== 0) {
        $space = intval(round($numParagraphs / ($maxAds + 1)));
        if ($space < $adMinParagraphs) {
            $space = $adMinParagraphs;
        }
    }
}
if (empty($space)) {
    $space = 999;
}
if (empty($numParagraphs)) {
    $numParagraphs = 0;
}
$temp2 = $temp;
$paragraph = explode('<p class="a">', $temp);
foreach ($paragraph as $key => $value) {
    $key = $key - 1;
    if ($key === -1) {
        $temp2 = '    ' . $value;
    } else {
        if ($key >= $space and $key <= $numParagraphs) {
            if (is_int($key / $space)) {
                $temp2.= advertisement();
                $temp2.= '      <p class="a">' . $value;
            } else {
                $temp2.= '  <p class="a">' . $value;
            }
        } else {
            $temp2.= '  <p class="a">' . $value;
        }
    }
}
$html.= '    ' . $temp2 . "\n";
$dbNumber2 = 0;
while ($dbNumber2 !== -1) {
    $db = str_replace('archive2', 'archive2-' . $dbNumber2, $dbArchive2);
    if ($dbNumber2 === 0
        or file_exists(str_replace('sqlite:', '', $db))
    ) {
        if ($dbNumber2 === 0) {
            $database = $dbArchive2;
        } else {
            $database = $db;
        }
        $dbNumber2++;
    } else {
        $dbNumber2 = -1;
        $dbh = null;
    }
    if (!empty($database)) {
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('SELECT idPhoto, photoCredit, photoCaption FROM imageSecondary WHERE idArticle=? ORDER BY time');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticle]);
        foreach ($stmt as $row) {
            extract($row);
            $dbNumber2 = -1;
            $html.= '    <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="wide border" alt=""></p>' . "\n\n";
            if (!empty($photoCaption) and !empty($photoCredit)) {
                $html.= '    <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
            } elseif (!empty($photoCaption)) {
                $html.= '    <h6>' . html($photoCaption) . "</h6>\n\n";
            } elseif (!empty($photoCredit)) {
                $html.= '    <h6>' . $photoCredit . "</h6>\n\n";
            }
        }
        $dbh = null;
    }
}
if ($survey === '1'
    and (strpos($database, 'archive') !== false)
) {
    //
    // Display a survey in the archives
    //
    $dbh = new PDO($dbSurvey);
    $stmt = $dbh->prepare('SELECT count(idArticle) FROM tally WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$aGet]);
    $row = $stmt->fetch();
    $total = $row['count(idArticle)'];
    if ($total === '0') {
        $total = 1;
    }
    $stmt = $dbh->prepare('SELECT idAnswer, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$aGet]);
    foreach ($stmt as $row) {
        extract($row);
        $stmt = $dbh->prepare('SELECT count(idAnswer) FROM tally WHERE idAnswer=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idAnswer]);
        $row = $stmt->fetch();
        $each = $row['count(idAnswer)'];
        $html.= '      <p class="a"><meter min="0" max="' . $total . '" value="' . $each . '" title="' . $answer . '">' . $answer . '"</meter> ' . $answer . ', ' . $each . "</p>\n\n";
    }
    $dbh = null;
}
$html.= "\n" . '      <p><span class="fr"><a href="' . $uri . '?m=archive-search" class="n">Archive search</a></span></p>' . "\n";
$html.= '    </div>' . "\n";
echo $html;
?>
