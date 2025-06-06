<?php
/**
 * Cron daily after vacuum to back up the databases, keeps 30 days of backups
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
// Variables
//
date_default_timezone_set('America/Los_Angeles');
$startTime = time();
$today = date("Y-m-d");
$prior = null;
$databases = [
    '../databases/advertising.sqlite',
    '../databases/articleId.sqlite',
    '../databases/calendar.sqlite',
    '../databases/classifieds.sqlite',
    '../databases/edit.sqlite',
    '../databases/edit2.sqlite',
    '../databases/editors.sqlite',
    '../databases/logEditor.sqlite',
    '../databases/logSubscriber.sqlite',
    '../databases/menu.sqlite',
    '../databases/photoId.sqlite',
    '../databases/published.sqlite',
    '../databases/published2.sqlite',
    '../databases/settings.sqlite',
    '../databases/subscribers.sqlite',
    '../databases/survey.sqlite'
];
//
// Create the ../databases/backup directory
//
if (!file_exists('../databases/backup')) {
    mkdir('../databases/backup', 0755);
}
if (!file_exists('../databases/backup/' . $today)) {
    mkdir('../databases/backup/' . $today, 0755);
}
//
// Delete back ups older than 30 days
//
$folders = scandir('../databases/backup');
$folders = array_diff($folders, ['.', '..']);
arsort($folders);
$i = 0;
foreach ($folders as $folder) {
    $i++;
    if ($i > 30) {
        array_map('unlink', glob('../databases/backup/' . $folder . '/*'));
        rmdir('../databases/backup/' . $folder);
    }
}
//
// Create the back up databases
//
foreach ($databases as $database) {
    if (file_exists('backup.log')) {
        $prior = file_get_contents('backup.log');
    }
    $startSize = number_format(@filesize($database) / 1024);
    //
    // Parse the database file name
    //
    $filename = strrchr($database, '/');
    //
    // Create a copy of the live database in memory and release the live database
    //
    $dbh = new PDO('sqlite:' . $database);
    $dbhMemory = new PDO('sqlite::memory:');
    $stmt = $dbh->prepare('SELECT name, sql FROM sqlite_master WHERE type=? ORDER BY name');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(['table']);
    foreach ($stmt as $row) {
        extract($row);
        $stmt = $dbhMemory->query($sql);
        $stmt = $dbh->query('SELECT * FROM ' . $name);
        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            $values = '?';
            for ($i = 1; $i < count($row); $i++) {
                $values.= ', ?';
            }
            $stmt = $dbhMemory->prepare('INSERT INTO ' . $name . ' VALUES (' . $values . ')');
            $stmt->execute($row);
        }
    }
    $dbh = null;
    $dbh = new PDO('sqlite::memory:');
    $stmt = $dbh->query('CREATE TABLE "a" ("b")');
    $dbh = null;
    //
    // Write the back up databases to disk
    //
    $dbh = new PDO('sqlite:' . '../databases/backup/' . $today . '/' . $filename);
    $stmt = $dbhMemory->prepare('SELECT name, sql FROM sqlite_master WHERE type=? ORDER BY name');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(['table']);
    foreach ($stmt as $row) {
        extract($row);
        $stmt = $dbh->query($sql);
        $dbh->beginTransaction();
        $stmt = $dbhMemory->query('SELECT * FROM ' . $name);
        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            $values = '?';
            for ($i = 1; $i < count($row); $i++) {
                $values.= ', ?';
            }
            $stmt = $dbh->prepare('INSERT INTO ' . $name . ' VALUES (' . $values . ')');
            $stmt->execute($row);
        }
        $dbh->commit();
        //
        // Check integrity and size of the back up
        //
        $stmt = $dbh->query('PRAGMA integrity_check');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $row = $stmt->fetch();
        $integrity_check = isset($row['integrity_check']) ? $row['integrity_check'] : 0;
        if ($integrity_check !== 'ok') {
            if (file_exists('error_log')) {
                $priorLog = file_get_contents('error_log');
            } else {
                $priorLog = null;
            }
            $errorMessage = 'back up ' . $database . "\n";
            $errorMessage.= $integrity_check . "\n\n";
            file_put_contents('error_log', $errorMessage . $priorLog);
        }
        $endSize = number_format(filesize('../databases/backup/' . $today . '/' . $filename) / 1024);
        $body = ltrim($filename, '/') . "\n";
        $body.= 'Integrity: ' . $integrity_check . "\n";
        $body.= $startSize . ' KB original, ' . $endSize . ' KB back up' . "\n\n";
        file_put_contents('backup.log', $body . $prior);
    }
    //
    // Release the database handles
    //
    $dbh = null;
    $dbh = new PDO('sqlite::memory:');
    $stmt = $dbh->query('CREATE TABLE "a" ("b")');
    $dbh = null;
    $dbhMemory = null;
    $dbhMemory = new PDO('sqlite::memory:');
    $stmt = $dbhMemory->query('CREATE TABLE "a" ("b")');
    $dbhMemory = null;
}
//
// Write run stats to the backup.log, limit the size of the log
//
$prior = null;
if (file_exists('backup.log')) {
    $i = 0;
    $priorLog = file('backup.log');
    foreach ($priorLog as $value) {
        if ($i < 500) {
            $prior.= $value;
            $i++;
        }
    }
}
$endTime = time();
$dif = $endTime - $startTime;
$hours = intval($dif / 60 / 60);
$totalMinutes = intval($dif / 60);
$minutes = sprintf('%02d', $totalMinutes - ($hours * 60));
$seconds = sprintf('%02d', round($dif - ($totalMinutes * 60)));
$body = "\n" . $today . ', ' . number_format(memory_get_peak_usage() / 1024 / 1024, 1) . ' MB RAM used' . ', memory_limit: ' . ini_get('memory_limit') . "\n";
$body.= $hours . ':' . $minutes . ':' . $seconds . ' run time at ' . date("H:i:s") . ', max_execution_time: ' . ini_get('max_execution_time') . "\n\n";
file_put_contents('backup.log', $body . $prior);
//
// Add the run stats to the cron email
//
echo $hours . ':' . $minutes . ':' . $seconds . ' run time at ' . date("H:i:s") . "\n";
echo number_format(memory_get_peak_usage() / 1024 / 1024, 1) . ' MB RAM used' . "\n\n";
echo ini_get('max_execution_time') . ' max_execution_time' . "\n";
echo ini_get('memory_limit') . ' memory_limit';
?>
