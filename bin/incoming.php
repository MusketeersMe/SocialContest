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
use Socon\DB;
use Socon\Service\QueueProcessor;
use Socon\Model\Entry;
use Socon\Model\EntryAccessorTable;

require __DIR__ . '/../vendor/autoload.php';

// Read in application configuration
$config_path = __DIR__ . '/../src/Config/config.php';
$config = new Config(include $config_path);
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
    exit(); // can't continue
}

try {
    // get our endpoint and connection string
    $connectionString = $azure->getServiceBusString();
    /** @var WindowsAzure\ServiceBus\Internal\IServiceBus $serviceBusRestProxy */
    $serviceBusRestProxy = ServicesBuilder::getInstance()->createServiceBusService($connectionString);
    echo sprintf("Monitoring topic %s, sub %s", $azure->getMessageTopicName(), $config->azure->service_bus->subscription) . "\n";
} catch (\Exception $e) {
    echo $e->getMessage();
    exit();
}

// Use container for storing image blobs
$connectionString = $azure->getStorageString();
try {
    $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);
} catch (\Exception $e) {
    echo $e->getMessage();
    exit();
}

try {
    // Set receive mode to PeekLock (default is ReceiveAndDelete)
    $options = new ReceiveMessageOptions();
    $options->setPeekLock();
} catch(ServiceException $e) {
    // Handle exception based on error codes and messages.
    // Error codes and messages are here:
    // http://msdn.microsoft.com/en-us/library/windowsazure/hh780735
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code . ": " . $error_message . "<br />";
}

// services helper class
$queueProcessor = new QueueProcessor($config->hashtags->toArray());

// Get message.
while (true) {
    try {
        $message = $serviceBusRestProxy->receiveSubscriptionMessage(
            $azure->getMessageTopicName(),
            $azure->getMessageSubscriptionName(),
            $options
        );
    } catch (\Exception $ex) {
        echo sprintf('%s; Receive Message Exception %s/%s', date('Y-m-d H:i:s'), $ex->getCode(), $ex->getMessage()) . "\n";
        // continue;
    }

    if (isset($message) && $message) {
        // contains "Message from ...";
        $body = $message->getBody();
        $properties = $message->getProperties();

        // see if we keep it, returns false if it should be discarded
        $item = $queueProcessor->normalizeIncoming($body, $properties);

        // save to persistent store
        if ($item) {
            // save the image to blob container
            $image = file_get_contents($item['media_url'], "r");
            $parts = parse_url($item['media_url']);
            $blob_name = str_replace('/', '-', trim($parts['path'], '/'));

            // Upload blob
            /** @var WindowsAzure\Blob\BlobRestProxy $blobRestProxy */
            $blobRestProxy->createBlockBlob($azure->getImageContainerName(), $blob_name, $image);
            $item['blob_name'] = $blob_name;

            $entry = new Entry($mapper);
            if ($entry->hydrate($item)->save()) {
                $image = '';
                echo sprintf('%s; Saved message from %s', date('Y-m-d H:i:s'), $item['source']) . "\n";
            }
        } else {
            echo sprintf('%s; Skipped - %s', date('Y-m-d H:i:s'), $body) . "\n";
            usleep(300);
        }
        // Delete message. Not necessary if peek lock is not set.
        try {
            $serviceBusRestProxy->deleteMessage($message);
        } catch (\Exception $ex) {
            echo sprintf('%s; Exception %s/%s', date('Y-m-d H:i:s'), $ex->getCode(), $ex->getMessage()) . "\n";
        }
    } else {
        echo sprintf('%s; no messages in subscription topic', date('Y-m-d H:i:s')) . "\n";
        sleep(10);
    }
}
