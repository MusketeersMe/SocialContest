<?php
namespace Socon\Model;
use DateTime;
use Socon\Model\Entry;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Table\TableRestProxy;
use WindowsAzure\Table\Models\Entity;
use WindowsAzure\Table\Models\EdmType;

class EntryAccessorTable extends EntryAccessor
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @param TableRestProxy $proxy
     * @param $table
     */
    public function __construct(TableRestProxy $proxy, $table)
    {
        $this->proxy = $proxy;
        $this->table = $table;
    }

    /**
     * @param $id
     * @param $status
     * @return mixed|Entry
     * @throws EntryAccessorException
     * @throws \Exception|ServiceException
     */
    public function load($id, $status)
    {
        try {
            $result = $this->proxy->getEntity($this->table, $status, $id);
        } catch(ServiceException $e){
            throw $e;
        }

        if ($entity = $result->getEntity()) {
            return $this->hydrate($entity);
        }

        // nothing found
        throw new EntryAccessorException("Entry not found.");
    }

    /**
     * @param $entity
     * @return mixed|Entry
     * @throws \Exception
     */
    public function hydrate($entity)
    {
        // prepare an array we can really
        if ($entity instanceof Entity) {
            $row = $this->entityToArray($entity);
        } else if (is_array($entity)) {
            $row = $entity;
        } else {
            throw new \Exception("Cannot hydrate entry.");
        }

        $entry = new Entry($this);
        $entry->hydrate($row);
        return $entry;
    }

    /**
     * entityToArray
     *
     * Convert an Table entity to a simple php array.
     *
     * @param Entity $entity
     * @return array
     */
    protected function entityToArray(Entity $entity)
    {
        $row = [
            'id' => $entity->getRowKey(),
            'status' => $entity->getPartitionKey(),
            'content' => $entity->getPropertyValue('content'),
            'source' => $entity->getPropertyValue('source'),
            'external_id' => $entity->getPropertyValue('external_id'),
            'url' => $entity->getPropertyValue('url'),
            'user_name' => $entity->getPropertyValue('user_name'),
            'user_image' => $entity->getPropertyValue('user_image'),
            'user_url' => $entity->getPropertyValue('user_url'),
            'media_url' => $entity->getPropertyValue('media_url'),
            'blob_name' => $entity->getPropertyValue('blob_name'),
            'created' => $entity->getPropertyValue('created'),
            'updated' => $entity->getPropertyValue('updated'),
        ];

        return $row;
    }
    /**
     * @param Entry $entry
     * @return bool
     */
    public function save(Entry $entry)
    {
        if ($entry->getId()) {
            return $this->update($entry);
        } else {
            return $this->insert($entry);
        }
    }

    /**
     * @param Entry $entry
     * @return bool
     * @throws \Exception|\WindowsAzure\Common\ServiceException
     */
    public function insert(Entry $entry)
    {
        $id = str_replace('.', '', microtime(TRUE));

        $entity = new Entity();
        $entity->setPartitionKey($entry->getStatus());
        $entity->setRowKey($id);

        $entity->addProperty("content", null, $entry->getContent());
        $entity->addProperty("source", EdmType::STRING, $entry->getSource());
        $entity->addProperty("external_id", EdmType::STRING, $entry->getExternalId());
        $entity->addProperty("url", EdmType::STRING, $entry->getUrl());
        $entity->addProperty("user_name", EdmType::STRING, $entry->getUserName());
        $entity->addProperty("user_image", EdmType::STRING, $entry->getUserImage());
        $entity->addProperty("user_url", EdmType::STRING, $entry->getUserUrl());
        $entity->addProperty("media_url", EdmType::STRING, $entry->getMediaUrl());
        $entity->addProperty("blob_name", EdmType::STRING, $entry->getBlobName());

        if ($created = $entry->getCreated()) {
            if (!$created instanceof DateTime) {
                $created = new DateTime($created, new \DateTimeZone('UTC'));
            }
        }

        $entity->addProperty("created", EdmType::DATETIME, $created);
        $entity->addProperty("updated", EdmType::DATETIME, $created);

        try {
            $this->proxy->insertEntity($this->table, $entity);
            return true;
        } catch(ServiceException $e){
            // consuming code has to watch out for exceptions
            throw $e;
        }
    }

    /**
     * @param Entry $entry
     * @return bool
     * @throws \Exception|\WindowsAzure\Common\ServiceException
     */
    public function update(Entry $entry)
    {
        // if the status is the same we can replace it in the same partition key
        if ($entry->getStatus() == $entry->getPrevStatus()) {
            // simply replace what we have in entity with entry values

            // get the entity for this entry
            $result = $this->proxy->getEntity($this->table, $entry->getStatus(), $entry->getId());
            $entity = $result->getEntity();
            $entity = $this->updateProperties($entity, $entry);
            try {
                $this->proxy->updateEntity($this->table, $entity);
                return true;
            } catch(ServiceException $e){
                throw $e;
            }
        } else {
            // create a new one in the right partition
            $this->insert($entry);

            try {
                // then remove the old one
                $result = $this->proxy->getEntity($this->table, $entry->getPrevStatus(), $entry->getId());
                $entity = $result->getEntity();
                $this->proxy->deleteEntity($this->table, $entity->getPartitionKey(), $entity->getRowKey());

                return true;
            } catch(ServiceException $e){
                throw $e;
            }
        }
    }

    /**
     * @param Entry $entry
     * @return \WindowsAzure\Table\none
     */
    public function delete(Entry $entry) {
        return $this->proxy->deleteEntity($this->table, $entry->getStatus(), $entry->getId());
    }

    /**
     * @param Entity $entity
     * @param Entry $entry
     * @return Entity
     */
    protected function updateProperties(Entity $entity, Entry $entry)
    {
        $entity->setPropertyValue("content", null, $entry->getContent());
        $entity->setPropertyValue("source", EdmType::STRING, $entry->getSource());
        $entity->setPropertyValue("external_id", EdmType::STRING, $entry->getExternalId());
        $entity->setPropertyValue("url", EdmType::STRING, $entry->getUrl());
        $entity->setPropertyValue("user_name", EdmType::STRING, $entry->getUserName());
        $entity->setPropertyValue("user_image", EdmType::STRING, $entry->getUserImage());
        $entity->setPropertyValue("user_url", EdmType::STRING, $entry->getUserUrl());
        $entity->setPropertyValue("media_url", EdmType::STRING, $entry->getMediaUrl());
        $entity->setPropertyValue("status", EdmType::STRING, $entry->getStatus());
        $entity->setPropertyValue("blob_name", EdmType::STRING, $entry->getBlobName());

        if ($updated = $entry->getCreated()) {
            if (!$updated instanceof DateTime) {
                $updated = new DateTime($updated, new \DateTimeZone('UTC'));
            }
        }

        $entity->setPropertyValue("updated", EdmType::DATETIME, $updated);

        return $entity;
    }
}