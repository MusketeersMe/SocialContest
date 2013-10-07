<?php
namespace Socon;

use \PDO;

/**
 * DB
 *
 * Class for connecting to the database for entries.
 */

class DB {

    /**
     * @var \PDO $dbh
     */
    protected $dbh;

    /**
     * @var DB $instance
     */
    static $instance;

    public function __construct($host, $dbname, $user, $password) {
        $connection = "dblib:host={$host};dbname={$dbname}";
        $this->dbh = new PDO($connection, $user, $password);
    }

    /**
     * getHandler
     *
     * @return PDO
     */
    public function getHandler()
    {
        return $this->dbh;
    }

    /**
     * setInstance
     *
     * Sets the connection instance the application will use
     *
     * @param $host
     * @param $dbname
     * @param $user
     * @param $password
     */
    static function setInstance($host, $dbname, $user, $password)
    {
        static::$instance = new DB($host, $dbname, $user, $password);
    }

    /**
     * getInstance
     *
     * Return the configured database connection
     * @return PDO
     */
    static function getInstance()
    {
        if (isset(static::$instance)) {
            return static::$instance->getHandler();
        }
    }
}

