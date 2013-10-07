<?php
/*
 * Setup required Azure services programatically.
 */

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Blob\Models\CreateContainerOptions;
use WindowsAzure\Blob\Models\PublicAccessType;
use WindowsAzure\ServiceBus\Models\QueueInfo;
use Zend\Config\Config;
use Socon\AzureHelper;
use Socon\Model\Entry;

require __DIR__ . '/../vendor/autoload.php';

// Read in application configuration and setup helper
$config_path = __DIR__ . '/../src/Config/config.php';
$config = new Config(include $config_path);
$azure = new AzureHelper($config);

// Create table REST proxy.
/** @var WindowsAzure\Table\TableRestProxy $tableRestProxy */
$connectionString = $azure->getStorageString();
$tableRestProxy = ServicesBuilder::getInstance()->createTableService($connectionString);

// get existing tables
$tables = $tableRestProxy->queryTables()->getTables();

// create our entry table
if (!in_array($config->azure->storage->entry_table, $tables)) {
    echo "Entries table does not exist, attempting to create.\n";
    try {
        $tableRestProxy->createTable($config->azure->storage->entry_table);
    } catch (ServiceException $ex) {
        $code = $ex->getCode();
        $error_message = $ex->getMessage();
        echo sprintf("Could not create table: %s/%s\n", $code, $error_message);
    }
} else {
    echo "Entries table found.\n";
    //$tableRestProxy->deleteTable($config->azure->storage->entry_table);
}
// Create blob for storing images
$connectionString = $azure->getStorageString();
$blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);

try {
    // Create container.
    echo "Creating image blob container.\n";
    $createContainerOptions = new CreateContainerOptions();
    $createContainerOptions->setPublicAccess(PublicAccessType::BLOBS_ONLY);

    $blobRestProxy->createContainer($azure->getImageContainerName(), $createContainerOptions);
}
catch(ServiceException $e){
    // Handle exception based on error codes and messages.
    // Error codes and messages are here:
    // http://msdn.microsoft.com/en-us/library/windowsazure/dd179439.aspx
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
}

// create queues for managing status changes
$connectionString = $azure->getServiceBusString();
$serviceBusRestProxy = ServicesBuilder::getInstance()->createServiceBusService($connectionString);

try {
    // Create "to approve" queue.
    echo "Creating to-approve queue.\n";
    $queueInfo = new QueueInfo($azure->getToApproveQueueName());
    $serviceBusRestProxy->createQueue($queueInfo);
} catch(ServiceException $e){
    // Handle exception based on error codes and messages.
    // Error codes and messages are here:
    // http://msdn.microsoft.com/en-us/library/windowsazure/dd179357
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
}

try {
    // Create "to incoming" subscription.
    echo "Creating to-incoming queue.\n";
    $queueInfo = new QueueInfo($azure->getToIncomingQueueName());
    $serviceBusRestProxy->createQueue($queueInfo);
} catch(ServiceException $e){
    // Handle exception based on error codes and messages.
    // Error codes and messages are here:
    // http://msdn.microsoft.com/en-us/library/windowsazure/dd179357
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
}

try {
    // Create "to denied" subscription.
    echo "Creating to-denied queue.\n";
    $queueInfo = new QueueInfo($azure->getToDeniedQueueName());
    $serviceBusRestProxy->createQueue($queueInfo);
} catch(ServiceException $e){
    // Handle exception based on error codes and messages.
    // Error codes and messages are here:
    // http://msdn.microsoft.com/en-us/library/windowsazure/dd179357
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
}

try {
    // Create "to denied" subscription.
    echo "Creating to-winner queue.\n";
    $queueInfo = new QueueInfo($azure->getToWinnerQueueName());
    $serviceBusRestProxy->createQueue($queueInfo);
} catch(ServiceException $e){
    // Handle exception based on error codes and messages.
    // Error codes and messages are here:
    // http://msdn.microsoft.com/en-us/library/windowsazure/dd179357
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
}

