<?php

require __DIR__ . '/../vendor/autoload.php';

use App\UrlRepository;
use App\Url;
use App\UrlCheckRepository;
use App\UrlCheck;
use App\Validator;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Slim\Middleware\Session;
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

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$router = $app->getRouteCollector()->getRouteParser();

$container->set('session', function () {
    return new SessionHelper();
});

$app->add(new Session());

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('home');

$app->get('/urls', function ($request, $response) use ($router) {
    $session = $this->get('session');
    $errors = $session->get('errors', []);
    $url = $session->get('url', ''); 
    $session->delete('errors');
    $session->delete('url');

    $urlRepository = $this->get(UrlRepository::class);
    $urls = $urlRepository->getEntities();

    $flash = $this->get('flash')->getMessages();

    $params = [
        'urls' => $urls,
        'errors' => $errors,
        'url' => $url,
        'flash' => $flash,
        'router' => $router
    ];

    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);
    $urlCheckRepository = $this->get(UrlCheckRepository::class);
    $url = $urlRepository->find($id);

    if (is_null($url)) {
        return $response->getBody->write('Страница не найдена')->withStatus(404);
    }

    $message = $this->get('flash')->getMessages();
    $urlChecks = $urlCheckRepository->findByUrlId($id);

    $params = [
        'url' => $url,
        'urlCheck' => $urlChecks,
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

    $session = $this->get('session');

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
            $errors['url'] = 'Этот URL уже существует';
            $session->set('errors', $errors);
            $session->set('url', $urlData);
            $this->get('flash')->addMessage('error', 'Этот URL уже существует');
            return $response->withHeader('Location', $router->urlFor('urls.index'))
                             ->withStatus(302);
        }
    } else {
        $session->set('errors', $errors);
        $session->set('url', $urlData);
        if ($errors['url'] === 'URL не должен быть пустым') {
            $this->get('flash')->addMessage('error', 'URL не должен быть пустым');
        } else {
            $this->get('flash')->addMessage('error', 'Некорректный URL');
        }
        return $response->withHeader('Location', $router->urlFor('urls.index'))
                        ->withStatus(302);
    }
})->setName('url.post');

$app->post('/urls/{id}/checks', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $UrlCheckRepository = $this->get(UrlCheckRepository::class);
    $urlData = ['url_id' => $id];
    $url = UrlCheck::fromArray($urlData);
    $UrlCheckRepository->save($url);
    return $response->withHeader('Location', $router->urlFor('url.show', ['id' => $id]))
                    ->withStatus(302);
})->setName('url.post.check');

$app->post('/urls/{id}/delete', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $urlRepository = $this->get(UrlRepository::class);

    $urlRepository->delete($id);

    $this->get('flash')->addMessage('success', 'URL успешно удален');

    // Получаем URL для перенаправления
    $url = $router->urlFor('urls.index');

    // Устанавливаем заголовок Location для перенаправления
    $response = $response->withHeader('Location', $url);

    // Устанавливаем HTTP-код 302 для перенаправления (Found)
    $response = $response->withStatus(302);

    return $response;
})->setName('urls.delete'); // Имя маршрута

$app->run();
