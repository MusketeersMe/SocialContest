<?php
namespace Socon;

use Zend\Config\Config;

class AzureHelper {

    protected $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function getStorageString() {
        $connectionString = sprintf(
            'DefaultEndpointsProtocol=%s;AccountName=%s;AccountKey=%s',
            $this->config->azure->storage->protocol,
            $this->config->azure->storage->name,
            $this->config->azure->storage->key
        );

        return $connectionString;
    }

    public function getServiceBusString() {
        // get our endpoint and connection string
        $endpoint = sprintf(
            "https://%s.servicebus.windows.net",
            $this->config->azure->service_bus->namespace
        );

        $connectionString = sprintf(
            "Endpoint=%s;SharedSecretIssuer=%s;SharedSecretValue=%s",
            $endpoint,
            $this->config->azure->service_bus->issuer,
            $this->config->azure->service_bus->key
        );

        return $connectionString;
    }

    public function getEntryTableName() {
        return $this->config->azure->storage->entry_table;
    }

    public function getMessageTopicName() {
        return $this->config->azure->service_bus->message_topic;
    }

    public function getMessageSubscriptionName() {
        return $this->config->azure->service_bus->message_subscription;
    }

    public function getToApproveQueueName() {
        return $this->config->azure->service_bus->to_approved_queue;
    }

    public function getToIncomingQueueName() {
        return $this->config->azure->service_bus->to_incoming_queue;
    }

    public function getToWinnerQueueName() {
        return $this->config->azure->service_bus->to_winner_queue;
    }

    public function getToDeniedQueueName() {
        return $this->config->azure->service_bus->to_denied_queue;
    }

    public function getImageContainerName() {
        return $this->config->azure->storage->image_container;
    }
}