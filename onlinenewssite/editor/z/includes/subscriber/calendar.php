<?php
/**
 * Predefined menu item: Calendar
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
echo '    <div class="main">' . "\n";
echo "      <h1>Calendar for the next month</h1>\n\n";
//
// Select events for the next 31 days from now
//
$selectTime = time();
for ($i = 0; $i < 31; $i++) {
    $description = null;
    $selectDate = date("Y-m-d", $selectTime);
    $dayOfTheMonthSelect = date("j", strtotime($selectDate));
    $dayOfTheWeekSelect = date("l", strtotime($selectDate));
    $monthSelect = date("F", strtotime($selectDate));
    $yearSelect = date("Y", strtotime($selectDate));
    $firstSelect = date("Y-m-d", strtotime('first ' . $dayOfTheWeekSelect . ' of ' . $monthSelect . ' ' . $yearSelect));
    $secondSelect = date("Y-m-d", strtotime('second ' . $dayOfTheWeekSelect . ' of ' . $monthSelect . ' ' . $yearSelect));
    $thirdSelect = date("Y-m-d", strtotime('third ' . $dayOfTheWeekSelect . ' of ' . $monthSelect . ' ' . $yearSelect));
    $fourthSelect = date("Y-m-d", strtotime('fourth ' . $dayOfTheWeekSelect . ' of ' . $monthSelect . ' ' . $yearSelect));
    if ($firstSelect === $selectDate) {
        $weekSelect = 'first';
    } elseif ($secondSelect === $selectDate) {
        $weekSelect = 'second';
    } elseif ($thirdSelect === $selectDate) {
        $weekSelect = 'third';
    } elseif ($fourthSelect === $selectDate) {
        $weekSelect = 'fourth';
    } else {
        $weekSelect = null;
    }
    $dbh = new PDO($dbCalendar);
    $stmt = $dbh->prepare('SELECT description FROM oneTimeEvent WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$selectDate]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $temp = Parsedown::instance()->parse($row['description']);
        $temp = str_replace("\n", "\n\n      ", $temp);
        $description.= '    ' . $temp . "\n";
        $temp = null;
    }
    $stmt = $dbh->prepare('SELECT description FROM annualDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$weekSelect . ' ' . $dayOfTheWeekSelect . ' of ' . $monthSelect]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $temp = Parsedown::instance()->parse($row['description']);
        $temp = str_replace("\n", "\n\n      ", $temp);
        $description.= '    ' . $temp . "\n";
        $temp = null;
    }
    $stmt = $dbh->prepare('SELECT description FROM annual WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([mb_substr($selectDate, -5)]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $temp = Parsedown::instance()->parse($row['description']);
        $temp = str_replace("\n", "\n\n      ", $temp);
        $description.= '    ' . $temp . "\n";
        $temp = null;
    }
    $stmt = $dbh->prepare('SELECT description FROM monthlyDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$weekSelect . ' ' . $dayOfTheWeekSelect]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $temp = Parsedown::instance()->parse($row['description']);
        $temp = str_replace("\n", "\n\n      ", $temp);
        $description.= '    ' . $temp . "\n";
        $temp = null;
    }
    $stmt = $dbh->prepare('SELECT description FROM weeklyDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$dayOfTheWeekSelect]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $temp = Parsedown::instance()->parse($row['description']);
        $temp = str_replace("\n", "\n\n      ", $temp);
        $description.= '    ' . $temp . "\n";
        $temp = null;
    }
    $dbh = null;
    if (isset($description)) {
        echo '      <h3><br>' . "\n";
        echo '      ' . $dayOfTheWeekSelect . ', ' . $monthSelect . ' ' . $dayOfTheMonthSelect . ', ' . $yearSelect . "</h3>\n";
        echo '  ' . $description . "\n";
        $temp = null;
    }
    $selectTime = $selectTime + 86400;
}
$dbh = new PDO($dbCalendar);
$stmt = $dbh->query('SELECT description FROM note');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if (!empty($row['description'])) {
    $temp = Parsedown::instance()->parse($row['description']);
    $temp = str_replace("\n", "\n\n      ", $temp);
    echo '      <p><br>' . "\n";
    echo '      <span>Notes' . "</span></p>\n";
    echo '      ' . $temp . "\n\n";
}
echo '    </div>' . "\n";
?>
