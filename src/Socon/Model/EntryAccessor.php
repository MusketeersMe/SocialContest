<?php
namespace Socon\Model;

abstract class EntryAccessor {
    /**
     * @param $id
     * @return Entry
     */
    abstract public function load($id, $status);

    /**
     * hydrate
     *
     * @param $row
     * @return mixed
     */
    abstract public function hydrate($row);

    /**
     * @param Entry $entry
     * @return bool
     */
    abstract public function save(Entry $entry);

    /**
     * save
     * @param Entry $entry
     * @return bool
     */
    abstract public function insert(Entry $entry);

    /**
     * @param Entry $entry
     * @return bool
     */
    abstract public function update(Entry $entry);
}


class EntryAccessorException extends \Exception {};