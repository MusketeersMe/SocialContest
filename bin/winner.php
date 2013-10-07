<?php
/*
 * Scans Service Bus for potential entries, adds them to the database.
 *
 * Should run once per minute, or indefinitely...
 */

use WindowsAzure\Common\ServicesBuilder;
use Zend\Config\Config;
use Socon\AzureHelper;
use Socon\Model\EntryAccessorTable;
use Socon\Model\EntryRepositoryTable;
use WindowsAzure\Common\ServiceException;

require __DIR__ . '/../vendor/autoload.php';

// Read in application configuration
$config_path = __DIR__ . '/../src/Config/config.php';
$config = new Config(include $config_path);

// readability
$contest_info = $config->contest;

// Don't do anything if the winners are selected manually
if (false === $contest_info->pick_automatically) {
    exit;
}

// Figure out if we should pick the next winner or not
$timezone = new \DateTimeZone(ini_get('date.timezone'));

// the date and time at which the contest starts
$contest_start = new \DateTime($contest_info->start_date, $timezone);
// the date and time at which the contest ends
$contest_end = new \DateTime($contest_info->end_date, $timezone);
list ($days, $hours, $minutes) = explode(':', $contest_info->interval);
$interval = new \DateInterval('P' . $days . 'DT' . $hours . 'H' . $minutes . 'S');
$now = new \DateTime('now', $timezone);

// the time each day that the daily contest starts
$start_time = new \DateTime($now->format('Y-m-d') . ' ' . $contest_info->daily_start,
    $timezone);
// the time each day that the daily contest stops
$end_time = new \DateTime($now->format('Y-m-d') . ' ' . $contest_info->daily_end,
    $timezone);

// Make sure the last pick is either at the end of the day or the
$last_pick = clone $contest_end;

if ($last_pick > $end_time) {
    $last_pick = clone $end_time;
}
$last_pick->add($interval);


// Only pick if the contest is happening
// so if we are outside the start day+time, daily start time, and end day+time we are done.
if ($now < $contest_start || $now >= $last_pick || $now <= $start_time) {
    exit;
}

$azure = new AzureHelper($config);

// get our table service for storing incoming entries
try {
    // Create table REST proxy.
    /** @var WindowsAzure\Table\TableRestProxy $tableRestProxy */
    $connectionString = $azure->getStorageString();
    $tableRestProxy = ServicesBuilder::getInstance()->createTableService($connectionString);
    $mapper = new EntryAccessorTable($tableRestProxy, $azure->getEntryTableName());
} catch(ServiceException $e) {
    // Handle exception based on error codes and messages.
    // Error codes and messages are here:
    // http://msdn.microsoft.com/en-us/library/windowsazure/hh780735
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code . ": " . $error_message . "<br />";
}


$repo = new EntryRepositoryTable($tableRestProxy);
$repo->setTableName($azure->getEntryTableName());
$repo->setAccessor($mapper);
$last_winner = $repo->getLatestWinner();
$previous_pick = ($contest_start > $start_time ? $contest_start : $start_time);

if ($last_winner) {
    $previous_pick = $last_winner->getUpdated();
    $previous_pick->setTimezone($timezone);
}

// Don't pick if the last pick was on or after the contest deadline
if ($previous_pick >= $contest_end || $previous_pick >= $end_time) {
    exit;
}

$next_award = clone $previous_pick;
$next_award->add($interval);

if ($next_award > $contest_end) {
    $next_award = $contest_end;
}

if ($next_award > $end_time) {
    $next_award = $end_time;
}

// Don't pick if the next award is more than the interval away OR the contest hasn't ended yet
if ($next_award > $now) {
    exit;
}

if (! $repo->pickWinnerFromEntries()) {
    $time = date_format(date_create(), 'Y-m-d H:i:s');
    echo "Unable to pick a valid winner at $time.\n";
    error_log("Winner Cron: Unable to pick valid winner at $time");
    exit(1);
}
