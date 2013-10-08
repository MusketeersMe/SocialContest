<?php
use Phlyty\App;
use Socon\View;
use Socon\Controller;
use Socon\View\Helper as ViewHelper;
use Zend\Config\Config;

require __DIR__ . '/../vendor/autoload.php';

// init app
$app = new App();

// Read in application configuration
$config_path = __DIR__ . '/../src/Config/config.php';
$config = new Config(include $config_path);

// Prepare viewModel for passing data from controllers to our views
$viewModel = new stdClass();
$app->setViewModelPrototype($viewModel);

// We'll use our simple PHP template as view renderer
$view = new View();
// add helpers, use __call
$view->setHelper(new ViewHelper);
ViewHelper::setCharset($config->charset);
$view->setTemplateDir(realpath(__DIR__ . '/../src/Socon/Template'));
$app->setView($view);

// map our routes
$app->get('/', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('PublicController', $viewModel, $config);
    $controller->index($app);
});

$app->get('/updates', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('PublicController', $viewModel, $config);
    $controller->updates($app);
});

$app->get('/admin', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('AdminController', $viewModel, $config);
    $controller->admin($app);
});

$app->get('/admin/incoming', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('AdminController', $viewModel, $config);
    $controller->incoming($app);
});

$app->get('/admin/approved', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('AdminController', $viewModel, $config);
    $controller->approved($app);
});

$app->get('/admin/winners', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('AdminController', $viewModel, $config);
    $controller->winners($app);
});

$app->get('/admin/denied', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('AdminController', $viewModel, $config);
    $controller->denied($app);
});

$app->post('/admin/update-status', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('AdminController', $viewModel, $config);
    $controller->updateStatus($app);
});

$app->post('/admin/pick-random-winner', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('AdminController', $viewModel, $config);
    $controller->pickRandomWinner($app);
});

$app->get('/admin/latest-incoming', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('AdminController', $viewModel, $config);
    $controller->latestIncoming($app);
});

$app->get('/next-winner', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('PublicController', $viewModel, $config);
    $controller->nextWinner($app);
});

$app->map(
    '/login',
    function($app) use ($config, $viewModel) {
        $controller = Controller::factory('LoginController', $viewModel, $config);
        $controller->login($app);
    })->via(['get', 'post']);

$app->get('/logout', function($app) use ($config, $viewModel) {
    $controller = Controller::factory('LoginController', $viewModel, $config);
    $controller->logout($app);
});

// let's do this!
$app->run();
