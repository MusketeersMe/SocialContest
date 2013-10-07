<?php
namespace Socon;

use Socon\AzureHelper;
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use Zend\Config\Config;
use Phlyty\App;
use Socon\Model\EntryRepository;

/**
 * Class Controller
 *
 * Base Controller with some common functionality.
 *
 * @package Socon
 */
class Controller
{
    public $view;

    /**
     * @var \Zend\Config\Config
     */
    protected $config;

    /**
     * @var EntryRepository
     */
    protected $repo;

    /**
     * Override to execute code before every action method in the class.
     */
    public function init(App $app){}

    /**
     * setView
     *
     * @param mixed $view
     */
    public function setView($view)
    {
        $this->view = $view;
    }

    /**
     * getView
     *
     * @return mixed
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * setConfig
     *
     * @param mixed $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * getConfig
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $method
     * @param array $params
     * @return mixed
     * @throws \ErrorException
     */
    public function __call($method, $params = array())
    {
        $methods = get_class_methods($this);
        $method = $method . 'Action';
        if (in_array($method, $methods)) {
            // Only one argument may be called, and it's Phlyty/App
            /** @var \Phlyty\App $app */
            $app = array_pop($params);

            // may need app in here
            $this->init($app);

            return $this->$method($app);
            //return call_user_func(array($this, $method), $app);
        }
        throw (new \ErrorException("Method $method does not exist in " . get_called_class()));
    }

    /**
     * getRepo
     */
    protected function getRepo()
    {
        if (!isset($this->repo)) {
            $this->setTableRepo();
        }

        return clone $this->repo;
    }

    /**
     * setTableRepo
     *
     * Sets the repository as Azure Table Storage
     */
    protected function setTableRepo() {
        $azure = new AzureHelper($this->config);

        try {
            $connectionString = $azure->getStorageString();
            $tableRestProxy = ServicesBuilder::getInstance()->createTableService($connectionString);
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        $mapper = new \Socon\Model\EntryAccessorTable($tableRestProxy, $azure->getEntryTableName());
        $this->repo = new \Socon\Model\EntryRepositoryTable($tableRestProxy, $mapper);
        $this->repo->setTableName($azure->getEntryTableName());
        $this->repo->setAccessor($mapper);
    }

    /**
     * setMSSQLRepo
     *
     * Sets the repository as a MSSQL database
     */
    protected function setMSSQLRepo() {
        $azure = $this->config->azure;
        try {
            $dbh = new DB($azure->mssql->host, $azure->mssql->db, $azure->mssql->user, $azure->mssql->password);
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        $mapper = new \Socon\Model\EntryAccessorMSSQL($dbh->getHandler());
        $this->repo = new \Socon\Model\EntryRepositoryMSSQL($dbh->getHandler(), $mapper);
    }

    /**
     * factory
     *
     * @param $name
     * @param $viewModel
     * @param $config
     * @return mixed
     */
    static public function factory($name, $viewModel, $config) {
        $class = __NAMESPACE__ . '\\Controller\\' . $name;
        /** @var Controller $controller */
        $controller = new $class;
        $controller->setView($viewModel);
        $controller->setConfig($config);
        return $controller;
    }
}