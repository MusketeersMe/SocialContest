<?php
namespace Socon\Model;

use \PDO;

class EntryAccessorMSSQL extends EntryAccessor
{
    /**
     * @var PDO;
     */
    protected $dbh;

    /**
     * @param PDO $dbh
     */
    public function __construct(PDO $dbh) {
        $this->dbh = $dbh;
    }

    /**
     * @param $id
     * @param null $status
     * @return Entry
     * @throws EntryAccessorException
     */
    public function load($id, $status = NULL) {
        $stmt = $this->dbh->prepare("SELECT * FROM entries WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->hydrate($row);
        }

        // nothing found
        throw new EntryAccessorException("Entry not found.");
    }

    public function hydrate($row) {
        $row['id'] = $row['ID'];
        $entry = new Entry($this);
        $entry->hydrate($row);
        return $entry;
    }

    /**
     * @param Entry $entry
     * @return bool
     */
    public function save(Entry $entry) {
        if ($entry->getId()) {
            return $this->update($entry);
        } else {
            return $this->insert($entry);
        }
    }

    /**
     * save
     * @param Entry $entry
     * @return bool
     */
    public function insert(Entry $entry) {
        $stmt = $this->dbh->prepare(
            "INSERT INTO entries
                (source, external_id, url, user_name, user_image, user_url, status, created, content, media_url, updated)
            VALUES
                (:source, :external_id, :url, :user_name, :user_image, :user_url, :status, :created, :content, :media_url, :created)
        ");
        $stmt->bindValue(':source', $entry->getSource());
        $stmt->bindValue(':external_id', $entry->getExternalId());
        $stmt->bindValue(':url', $entry->getUrl());
        $stmt->bindValue(':user_name', $entry->getUserName());
        $stmt->bindValue(':user_image', $entry->getUserImage());
        $stmt->bindValue(':user_url', $entry->getUserUrl());
        $stmt->bindValue(':status', $entry->getStatus());
        $stmt->bindValue(':created', $entry->getCreated());
        $stmt->bindValue(':content', $entry->getContent());
        $stmt->bindValue(':media_url', $entry->getMediaUrl());
        return $stmt->execute();
    }

    /**
     * @param Entry $entry
     * @return bool
     */
    public function update(Entry $entry) {
        $stmt = $this->dbh->prepare(
            "UPDATE entries
            SET
                source = :source, external_id = :external_id, url = :url, user_name = :user_name,
                user_image = :user_image, user_url = :user_url, status = :status,
                content = :content, media_url = :media_url, updated = :updated
            WHERE
                ID = :id
        ");
        $stmt->bindValue(':id', $entry->getId());
        $stmt->bindValue(':source', $entry->getSource());
        $stmt->bindValue(':external_id', $entry->getExternalId());
        $stmt->bindValue(':url', $entry->getUrl());
        $stmt->bindValue(':user_name', $entry->getUserName());
        $stmt->bindValue(':user_image', $entry->getUserImage());
        $stmt->bindValue(':user_url', $entry->getUserUrl());
        $stmt->bindValue(':status', $entry->getStatus());
        $stmt->bindValue(':content', $entry->getContent());
        $stmt->bindValue(':media_url', $entry->getMediaUrl());

        $updated = new \DateTime('now', new \DateTimeZone('UTC'));
        $stmt->bindValue(':updated', $updated->format('Y-m-d H:i:s'));
        return $stmt->execute();
    }
}

