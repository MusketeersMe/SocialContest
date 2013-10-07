<?php
/*
 * Scans Service Bus for potential entries, adds them to the database.
 *
 * Should run once per minute, or indefinitely...
 */

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;
use Zend\Config\Config;
use \DateTime;
use Socon\AzureHelper;
use Socon\Model\Entry;
use Socon\Model\EntryAccessorTable;

require __DIR__ . '/../vendor/autoload.php';

// Read in application configuration
$config_path = __DIR__ . '/../src/Config/config.php';

include __DIR__ . '/inc/approval.php';

echo "Starting up approved status worker.\n";
while (true) {
    try {
        $message = $serviceBusRestProxy->receiveQueueMessage($azure->getToApproveQueueName(), $options);
    } catch (\Exception $ex) {
        echo sprintf('%s; Receive Message Exception %s/%s', date('Y-m-d H:i:s'), $ex->getCode(), $ex->getMessage()) . "\n";
    }

    if (isset($message) && $message) {
        // get message properties
        echo "approved: Message received.\n";
        $id = $message->getProperty('id');
        $status = $message->getProperty('status');
        $prev_status = $message->getProperty('prev_status');

        try {
            $entry = $mapper->load($id, $prev_status);
            $entry->setStatus($status);
            $entry->save();
        } catch (ServiceException $e) {
            if (404 == $e->getCode()) {
                // entity doesn't exist anymore, no need to update
            } else {
                echo sprintf('%s; Could not update entity %s/%s', date('Y-m-d H:i:s'), $ex->getCode(), $ex->getMessage()) . "\n";
            }
        } catch (\Exception $e) {
            // status may have already changed.
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo sprintf('%s; Exception:/%s', date('Y-m-d H:i:s'), $ex->getCode(), $ex->getMessage()) . "\n";
        }

        // done
        $serviceBusRestProxy->deleteMessage($message);
    } else {
        usleep(500);
    }
}
