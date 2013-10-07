<?php
namespace Socon\Model;
use Socon\Model\Entry;
use WindowsAzure\Table\TableRestProxy;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Table\Models\Entity;

class EntryRepositoryTable extends EntryRepository
{
    /**
     * @var TableRestProxy
     */
    private $tableRestProxy;

    const PARTITION_FILTER = "PartitionKey eq '%s'";

    const UPDATED_FILTER = " and updated ge datetime'%s'";

    /**
     * @var TableRestProxy
     */
    protected $tableName;

    /**
     * @param TableRestProxy $table
     */
    public function __construct(TableRestProxy $table)
    {
        $this->tableRestProxy = $table;
    }

    /**
     * @param mixed $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @param $filter
     * @return bool|\WindowsAzure\Table\Models\QueryEntitiesResult
     */
    protected function queryByFilter($filter)
    {
        try {
            $result = $this->tableRestProxy->queryEntities($this->tableName,
                $filter);
        } catch(ServiceException $e) {
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/en-us/library/windowsazure/dd179438.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
            return false;
        }
        return $result;
    }
    /**
     * getByStatus
     *
     * Get entries with the given status.
     *
     * @param string $status
     * @return bool
     */
    public function getByStatus($status)
    {
        return $this->get($this->getFilterByStatus($status));
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
            'created' => $entity->getPropertyValue('created'),
            'updated' => $entity->getPropertyValue('updated'),
            'blob_name' => $entity->getPropertyValue('blob_name'),
        ];

        return $row;
    }

    /**
     * @param $status
     * @param int $limit
     * @return bool
     */
    protected function getByStatusWithLimit($status, $limit = 30)
    {
        return $this->get($this->getFilterByStatus($status), $limit);
    }

    /**
     * @return bool|Entry
     */
    public function getLatestWinner()
    {
        if ($this->getByStatusWithLimit(1)) {
            return $this->fetch();
        }

        return false;
    }

    /**
     * @param \DateTime $since
     * @return bool|Entry
     */
    public function getWinnerSince(\DateTime $since)
    {
        $filter = $this->getFilterByStatusSince(Entry::STATUS_WINNER, $since);
        if ($this->get($filter)) {
            return $this->fetch();
        }

        return false;
    }

    /**
     * @param \DateTime $since
     * @return bool
     */
    public function getIncomingSince(\DateTime $since)
    {
        $filter = $this->getFilterByStatusSince(Entry::STATUS_NEW, $since);
        return $this->get($filter);
    }

    /**
     * @param \DateTime $since
     * @return bool
     */
    public function getApprovedSince(\DateTime $since)
    {
        $filter = $this->getFilterByStatusSince(Entry::STATUS_APPROVED, $since);
        return $this->get($filter);
    }

    /**
     * Get all approved entries from a username
     * @param $username
     * @return bool
     */
    public function getApprovedFromUsername($username)
    {
        $filter = $this->getFilterByStatus(Entry::STATUS_APPROVED);
        $filter .= " and user_name eq '$username'";
        return $this->get($filter);
    }

    /**
     * @param string $status
     * @return string
     */
    protected function getFilterByStatus($status)
    {
        return sprintf(self::PARTITION_FILTER, $status);
    }

    protected function getFilterByStatusSince($status, \DateTime $datetime)
    {
        return $this->getFilterByStatus($status) . ' ' . sprintf(self::UPDATED_FILTER,
            $datetime->format('Y-m-d\TH:i:s'));
    }

    /**
     * @param $filter
     * @param int $limit
     * @return bool
     */
    protected function get($filter, $limit = 0)
    {
        try {
            if ($limit) {
                $result = $this->tableRestProxy->queryEntities($this->tableName,
                    $filter, $limit);
            } else {
                $result = $this->tableRestProxy->queryEntities($this->tableName,
                    $filter);
            }

        } catch(ServiceException $e) {
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/en-us/library/windowsazure/dd179438.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
            error_log($code.": ".$error_message."\n");
            return false;
        }

        // Get all the entries, and reverse them before saving them.
        $entities = $result->getEntities();
        $entries = [];
        foreach ($entities as $entity) {
            $entries[] = $this->entityToArray($entity);
        }
        $this->entries = array_reverse($entries);
        return true;
    }


}