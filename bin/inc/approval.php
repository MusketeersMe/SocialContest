<?php
use Zend\Config\Config;
use Socon\AzureHelper;
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;
use \DateTime;
use Socon\Model\Entry;
use Socon\Model\EntryAccessorTable;

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
}

try {
    // get our endpoint and connection string
    $connectionString = $azure->getServiceBusString();
    /** @var WindowsAzure\ServiceBus\Internal\IServiceBus $serviceBusRestProxy */
    $serviceBusRestProxy = ServicesBuilder::getInstance()->createServiceBusService($connectionString);
    //echo sprintf("Monitoring topic %s, sub %s", $azure->getMessageTopicName(), $config->azure->service_bus->subscription) . "\n";
} catch (\Exception $e) {
    echo $e->getMessage();
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