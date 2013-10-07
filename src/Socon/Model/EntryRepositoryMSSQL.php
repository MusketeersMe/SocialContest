<?php
namespace Socon\Model;
use PDO;
use PDOStatement;
use DateTime;
use Socon\Model\Entry;

class EntryRepositoryMSSQL extends EntryRepository
{
    /**
     * @var PDO
     */
    private $dbh;

    /**
     * @var PDOStatement
     */
    private $stmt;

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
        $sql = "SELECT * FROM entries WHERE status = ? ORDER BY updated DESC";
        return $this->get($sql, [$status]);
    }

    /**
     * @param $status
     * @param int $limit
     * @return bool
     */
    protected function getByStatusWithLimit($status, $limit = 30)
    {
        $limit = (int) $limit;
        $sql = "SELECT TOP $limit * FROM entries WHERE status = ? ORDER BY updated DESC";
        return $this->get($sql, [$status]);
    }

    /**
     * @return bool|Entry
     */
    public function getLatestWinner()
    {
        // need a different SQL statement here.
        $sql = "SELECT TOP 1 * FROM entries WHERE status = ? ORDER BY updated DESC";
        if ($this->get($sql, [Entry::STATUS_WINNER])) {
            return $this->fetch();
        }

        return false;
    }

    /**
     * @param DateTime $since
     * @return bool|Entry
     */
    public function getWinnerSince(\DateTime $since)
    {
        // need a different SQL statement here.
        $sql = "SELECT * FROM entries WHERE status = ? AND updated >= ? ORDER BY updated DESC";
        if ($this->get($sql, [Entry::STATUS_WINNER, $since->format('Y-m-d H:i:s')])) {
            return $this->fetch();
        }

        return false;
    }

    /**
     * @param DateTime $since
     * @return bool
     */
    public function getIncomingSince(\DateTime $since)
    {
        // need a different SQL statement here.
        $sql = "SELECT * FROM entries WHERE status = ? AND updated >= ? ORDER BY updated DESC";
        return $this->get($sql, [Entry::STATUS_NEW, $since->format('Y-m-d H:i:s')]);
    }

    /**
     * @param DateTime $since
     * @return bool
     */
    public function getApprovedSince(\DateTime $since)
    {
        // need a different SQL statement here.
        $sql = "SELECT * FROM entries WHERE status = ? AND updated >= ? ORDER BY updated DESC";
        return $this->get($sql, [Entry::STATUS_APPROVED, $since->format('Y-m-d H:i:s')]);
    }

    /**
     * Get all approved entries from a username
     * @param $username
     * @return bool
     */
    public function getApprovedFromUsername($username)
    {
        $sql = "SELECT * FROM entries WHERE status = ? AND user_name = ?";
        return $this->get($sql, [Entry::STATUS_APPROVED, $username]);
    }

    /**
     * @param \Socon\Model\EntryAccessorMSSQL $accessor
     */
    public function setAccessor($accessor)
    {
        $this->accessor = $accessor;
    }

    /**
     * @param \PDO $dbh
     */
    public function setDbh($dbh)
    {
        $this->dbh = $dbh;
    }

    /**
     * Execute a SQL statement with the supplied parameters.
     *
     * @param $sql
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    protected function get($sql, array $params = [])
    {
        $entries = new \ArrayObject();

        $this->stmt = $this->dbh->prepare($sql);
        $return = $this->stmt->execute($params);

        if (! $return) {
            $error_info = $this->stmt->errorInfo();
            error_log($error_info[2]);
            exit();
        }

        while ($row = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
            $entries[] = $row;
        }

        $this->entries = $entries->getIterator();
        return true;
    }


}