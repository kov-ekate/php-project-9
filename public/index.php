<?php

require __DIR__ . '/../vendor/autoload.php';

use App\UrlRepository;
use App\Url;
use App\UrlCheckRepository;
use App\UrlCheck;
use App\Validator;
use App\SeoAnalysis;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Slim\Middleware\Session;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use SlimSession\Helper as SessionHelper;


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
        $sql = file_get_contents(__DIR__ . '/../database.sql');
        $pdo->exec($sql);
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

$container->set(UrlCheckRepository::class, function ($container) {
    $pdo = $container->get(\PDO::class);
    return new UrlCheckRepository($pdo);
});

$container->set('session', function () {
    return new SessionHelper();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$router = $app->getRouteCollector()->getRouteParser();

$app->add(new Session());

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('home');

$app->get('/urls', function ($request, $response) use ($router) {
    $urlRepository = $this->get(UrlRepository::class);
    $urls = $urlRepository->getEntities();
    $urlCheckRepository = $this->get(UrlCheckRepository::class);
    $urlChecks = $urlCheckRepository->getEntities();
    $lastChecks = $urlRepository->getLastChecks();

    $urlsWithLastCheck = [];

    foreach ($urls as $url) {
        $urlId = $url->getId();
        $lastCheckData = $lastChecks[$urlId] ?? null;
        
        if ($lastCheckData) {
            $lastCheck = $lastCheckData['last_check'];
            $statusCode = $lastCheckData['last_status_code'];
        }

        $urlsWithLastCheck[] = [
            'id' => $urlId,
            'name' => $url->getName(),
            'last_check' => $lastCheck,
            'status_code' => $statusCode
        ];
    }

    $params = [
        'urls' => $urlsWithLastCheck,
        'errors' => [],
        'url' => $url,
        'urlChecks' => $urlChecks,
        'router' => $router
    ];

    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);
    $urlCheckRepository = $this->get(UrlCheckRepository::class);
    $url = $urlRepository->find($id);

    if (is_null($url)) {
        $flash = 'Некорректный URL';
        $params = [
            'flash' => $flash,
            'url' => $url
        ];
        return $this->get('renderer')->render($response->withStatus(422), 'urls/index.phtml', $params);
    }

    $message = $this->get('flash')->getMessages();
    $urlChecks = $urlCheckRepository->find($id);

    $params = [
        'router' => $router,
        'url' => $url,
        'urlChecks' => $urlChecks,
        'flash' => $message
    ];

    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('url.show');

$app->post('/urls', function ($request, $response) use ($router) {
    $urlRepository = $this->get(UrlRepository::class);
    $parsedBody = $request->getParsedBody();
    $urlData = $parsedBody['url']['name'] ?? null;

    $validator = new Validator;
    $errors = $validator->validate($urlData);

    if (count($errors) === 0) {
        $normalUrl = $validator->normalyzer($urlData);
        $url = Url::fromArray(['name' => $normalUrl]);
        $success = $urlRepository->save($url);
        if ($success) {
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
            $id = $url->getId();
            return $response->withHeader('Location', $router->urlFor('url.show', ['id' => $id]))
                            ->withStatus(302);
        } else {
            $this->get('flash')->addMessage('error', 'Страница уже существует');
            $urlWithId = $urlRepository->findByName($url);
            $id = $urlWithId->getId();
            return $response->withHeader('Location', $router->urlFor('url.show', ['id' => $id]))
                            ->withStatus(302);
        }
    } else {
        if ($errors['url'] === 'URL не должен быть пустым') {
            $flash = 'URL не должен быть пустым';
        } else {
            $flash = 'Некорректный URL';
        }

        $url = Url::fromArray(['name' => $urlData]);
        
        $params = [
            'errors' => $errors,
            'url' => $url,
            'flash' => $flash
        ];
        return $this->get('renderer')->render($response->withStatus(422), 'urls/index.phtml', $params);
    }
})->setName('url.post');

$app->post('/urls/{id}/checks', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);
    $urlCheckRepository = $this->get(UrlCheckRepository::class);

    $url = $urlRepository->find($id);
    $urlName = $url->getName();

    $client = new Client([
        'timeout' => 10.0,
        'connect_timeout' => 10.0,
        'http_errors' => false,
        'verify' => false
    ]);

    try {
        $responseGuzzle = $client->request('GET', $urlName);
        $statusCode = $responseGuzzle->getStatusCode();
        $html = $responseGuzzle->getBody()->getContents();

        $analysis = new SeoAnalysis;
        $seoData = $analysis->analyze($html);
        $h1 = $seoData['h1'];
        $title = $seoData['title'];
        $description = $seoData['description'];

        $urlData = [
            'url_id' => $id,
            'status_code' => $statusCode,
            'h1' => $h1,
            'title' => $title,
            'description' => $description
        ];

        $urlCheck = UrlCheck::fromArray($urlData);
        $success = $urlCheckRepository->save($urlCheck);
        if ($success) {
            $this->get('flash')->addMessage('success', 'Страница успешно проверена');
            return $response->withHeader('Location', $router->urlFor('url.show', ['id' => $id]))
                            ->withStatus(302);
        } else {
            $this->get('flash')->addMessage('error', 'Ошибка');
            return $response->withHeader('Location', $router->urlFor('url.show', ['id' => $id]))
                             ->withStatus(302);
        }
    } catch (ConnectException $e) {
        $this->get('flash')->addMessage('error', 'Сервер не отвечает или отсутствует подключение к интернету');
    } catch (\Exception $e) {
        $this->get('flash')->addMessage('error', 'Произошла непредвиденная ошибка: ' . $e->getMessage());
    }

    return $response->withHeader('Location', $router->urlFor('url.show', ['id' => $id]))
                    ->withStatus(302);
})->setName('url.post.check');

$app->run();
