<?php

require __DIR__ . '/../vendor/autoload.php';

use App\UrlRepository;
use App\Url;
use App\Validator;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Carbon\Carbon;
use Slim\Middleware\Session;


$container = new Container();

$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

$container->set(\PDO::class, function () {
    $databaseUrl = parse_url(getenv('DATABASE_URL'));
    $username = $databaseUrl['user'];
    $password = $databaseUrl['pass'];
    $host = $databaseUrl['host'];
    $port = $databaseUrl['port'] ?? 5432;
    $dbName = ltrim($databaseUrl['path'], '/');

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        return $pdo;
    } catch (\PDOException $e) {
        throw new \PDOException("Ошибка подключения к базе данных: " . $e->getMessage(), (int)$e->getCode());
    }
});

$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$container->set(UrlRepository::class, function ($container) {
    $pdo = $container->get(\PDO::class);
    return new UrlRepository($pdo);
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$router = $app->getRouteCollector()->getRouteParser();

$app->add(new Session());

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/urls', function ($request, $response) {
    $errors = $_SESSION['errors'] ?? [];
    $url = $_SESSION['url'] ?? '';

    unset($_SESSION['errors']);
    unset($_SESSION['url']);

    $urlRepository = $this->get(UrlRepository::class);
    $urls = $urlRepository->getEntities();
    $message = $this->get('flash')->getMessages();

    $params = [
        'urls' => $urls,
        'flash' => $message,
        'errors' => $errors,
        'url' => $url
    ];

    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('url.index');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);
    $url = $urlRepository->find($id);

    if (is_null($url)) {
        return $response->getBody->write('Страница не найдена')->withStatus(404);
    }

    $message = $this->get('flash')->getMessages();

    $params = [
        'url' => $url,
        'flash' => $message
    ];

    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('url.show');

$app->post('/urls', function ($request, $response) use ($router) {
    $urlRepository = $this->get(UrlRepository::class);
    $parsedBody = $request->getParsedBody();
    $urlData = $parsedBody['url'] ?? null;

    $validator = new Validator;
    $errors = $validator->validate($urlData);

    if (count($errors) === 0) {
        $normalUrl = $validator->normalyzer($urlData);
        $createdAt = Carbon::now()->format('Y-m-d H:i:s');
         if ($createdAt === null) {
            throw new \RuntimeException('Failed to get current date and time');
        }
        $data = ['name' => $normalUrl, 'createdAt' => $createdAt];
        echo '<pre>';
        var_dump($data);
        echo '<pre>';
        $url = Url::fromArray(['name' => $normalUrl, 'createdAt' => $createdAt]);
        $urlRepository->save($url);
        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        $id = $url->getId();
        return $response->withHeader('Location', $router->urlFor('url.show', ['id' => $id]))->withStatus(302);
    }

    $_SESSION['errors'] = $errors;
    $_SESSION['url'] = $urlData;

    $this->get('flash')->addMessage('error', 'Некорректный URL');
    return $response->withRedirect($router->urlFor('url.index'));
});

$app->run();
