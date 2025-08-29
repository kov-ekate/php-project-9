<?php

require __DIR__ . '/../vendor/autoload.php';
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;

$container = new Container();
$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->head('/', function ($request, $response) {
    return $response->withHeader('Content-Length', '0');
});

$app->get('/', function ($request, $response) {
    error_log('render / once');
    $params = [];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
});

$app->run();
