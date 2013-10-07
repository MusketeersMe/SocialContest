<?php
namespace Socon\Model;
use Socon\Model\Entry;

abstract class EntryRepository implements \Iterator, \Countable
{
    /**
     * @var Array
     */
    protected $entries = [];

    /**
     * @var EntryAccessor
     */
    protected $accessor;

    /**
     * @var array
     */
    protected $winnerUsernames;

    /**
     * getByStatus
     *
     * Get entries with the given status.
     *
     * @param string $status
     * @return bool
     */
    abstract public function getByStatus($status);

    /**
     * getByStatusWithLimit
     * 
     * @param $status
     * @param int $limit
     * @return bool
     */
    abstract protected function getByStatusWithLimit($status, $limit = 30);
    
    /**
     * getLatestWinner
     *
     * @return bool|Entry
     */
    abstract public function getLatestWinner();

    /**
     * getWinnerSince
     *
     * @param \DateTime $since
     * @return bool|Entry
     */
    abstract public function getWinnerSince(\DateTime $since);

    /**
     * getIncomingSince
     *
     * @param \DateTime $since
     * @return bool
     */
    abstract public function getIncomingSince(\DateTime $since);

    /**
     * getApprovedSince
     *
     * @param \DateTime $since
     * @return bool
     */
    abstract public function getApprovedSince(\DateTime $since);

    /**
     * getApprovedFromUsername
     *
     * Get all approved entries from a username
     * @param $username
     * @return bool
     */
    abstract public function getApprovedFromUsername($username);
    
    /**
     * getNew
     *
     * Get new entries.
     *
     * @return bool
     */
    public function getNew()
    {
        return $this->getByStatus(Entry::STATUS_NEW);
    }

    /**
     * getApproved
     *
     * Get approved entries.
     *
     * @return bool
     */
    public function getApproved()
    {
        return $this->getByStatus(Entry::STATUS_APPROVED);
    }

    /**
     * getWinners
     *
     * Get winning entries.
     *
     * @return bool
     */
    public function getWinners()
    {
        return $this->getByStatus(Entry::STATUS_WINNER);
    }

    /**
     * @param int $limit
     * @return bool
     */
    public function getLatestEntries($limit = 30)
    {
        if (0 == $limit) {
            return $this->getByStatus(Entry::STATUS_APPROVED);
        } else {
            return $this->getByStatusWithLimit(Entry::STATUS_APPROVED, $limit);
        }
    }

    /**
     * getDenied
     *
     * Get denied entries.
     *
     * @return bool
     */
    public function getDenied()
    {
        return $this->getByStatus(Entry::STATUS_DENIED);
    }

    /**
     * getPreviousWinners
     *
     * Gets all winners except latest in a scalable way
     *
     * @return bool
     */
    public function getPreviousWinners()
    {
        $result = $this->getWinners();

        if (! $result) {
            return false;
        }

        // Throw away the first one
        unset($this->entries[0]);

        return true;
    }

    /**
     * @return mixed|Entry
     */
    public function current()
    {
        return $this->fetch();
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return key($this->entries);
    }

    public function next()
    {
        next($this->entries);
    }

    public function rewind()
    {
        reset($this->entries);;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return (key($this->entries) !== NULL);
    }

    /**
     * @return bool|Entry
     */
    protected function fetch()
    {
        if ($this->valid()) {
            $row = current($this->entries);
            return $this->accessor->hydrate($row);
        }

        return false;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->entries);
    }

    /**
     * @param $accessor
     */
    public function setAccessor($accessor)
    {
        $this->accessor = $accessor;
    }


    /**
     * Pick a random winner from all entries
     * @param null $entries
     * @return bool|Entry
     * @throws \Exception
     */
    public function pickWinnerFromEntries($entries = null)
    {
        if (null == $entries) {
            $this->getApproved();
            $entries = $this->entries->getArrayCopy();
        }

        $winner_key = array_rand($entries);
        $winner_data = array_splice($entries, $winner_key, 1);
        $winner_data = $winner_data[0];

        if ($winner_data) {
            $winner = $this->accessor->hydrate($winner_data);
            try {
                $winner = $this->makeWinner($winner);
            } catch (\Exception $e) {
                throw $e;
            }

            if (! $winner) {
                $winner = $this->pickWinnerFromEntries($entries);
            }

            return $winner;
        }

        return false;
    }

    /**
     * Ensure someone can only win once
     * @param Entry $entry
     * @return bool|Entry
     * @throws \Exception
     */
    public function makeWinner(Entry $entry) {
        $winner_usernames = $this->getWinnerUsernames();
        $already_won = function($winners) use ($entry) {
            foreach ($winners as $winner) {
                if ($entry->getUserName() == $winner) {
                    return true;
                }
            }
            return false;
        };

        if ($already_won($winner_usernames)) {
            return false;
        }

        try {
            $entry->setStatus(Entry::STATUS_WINNER);
            $entry->save();
        } catch (\Exception $e) {
            throw $e;
        }

        return $entry;
    }

    /**
     * @return array
     */
    protected function getWinnerUsernames()
    {
        if (! $this->winnerUsernames) {
            $this->getWinners();
            /** @var \ArrayIterator $winners */
            $winners = $this->entries;
            $usernames = array_column($winners->getArrayCopy(), 'user_name');
            $this->winnerUsernames = $usernames;
        }

        return $this->winnerUsernames;
    }

}