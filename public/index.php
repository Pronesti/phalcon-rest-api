<?php

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\FactoryDefault;
use Phalcon\Http\Response;
use Phalcon\Loader;
use Phalcon\Mvc\Micro;

$loader = new Loader();
$loader->registerNamespaces(
    [
        //'MyApp\Models' => __DIR__.'/app/models/',
        'MyApp\Models' => '../app/models/',
    ]
);
$loader->register();

$container = new FactoryDefault();
$container->set(
    'db',
    function () {
        return new Mysql(
            [
                'host' => 'db',
                'username' => 'root',
                'password' => 'root',
                'dbname' => 'robotics',
            ]
        );
    }
);

$app = new Micro($container);

$app->get(
    '/api/robots',
    function () use ($app) {
        $phql = 'SELECT id, name '
              .'FROM MyApp\Models\Robots '
              .'ORDER BY name'
        ;

        $robots = $app
            ->modelsManager
            ->executeQuery($phql)
        ;

        $data = [];

        foreach ($robots as $robot) {
            $data[] = [
                'id' => $robot->id,
                'name' => $robot->name,
            ];
        }

        echo json_encode($data);
    }
);

// Searches for robots with $name in their name
$app->get(
    '/api/robots/search/{name}',
    function ($name) use ($app) {
        $phql = 'SELECT * '
              .'FROM MyApp\Models\Robots '
              .'WHERE name '
              .'LIKE :name: '
              .'ORDER BY name'
        ;

        $robots = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'name' => '%'.$name.'%',
                ]
            )
        ;

        $data = [];

        foreach ($robots as $robot) {
            $data[] = [
                'id' => $robot->id,
                'name' => $robot->name,
            ];
        }

        echo json_encode($data);
    }
);

$app->get(
    '/api/robots/{id:[0-9]+}',
    function ($id) use ($app) {
        $phql = 'SELECT * '
              .'FROM MyApp\Models\Robots '
              .'WHERE id = :id:'
        ;

        $robot = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'id' => $id,
                ]
            )
            ->getFirst()
        ;

        $response = new Response();
        if (false === $robot) {
            $response->setJsonContent(
                [
                    'status' => 'NOT-FOUND',
                ]
            );
        } else {
            $response->setJsonContent(
                [
                    'status' => 'FOUND',
                    'data' => [
                        'id' => $robot->id,
                        'name' => $robot->name,
                    ],
                ]
            );
        }

        return $response;
    }
);

$app->post(
    '/api/robots',
    function () use ($app) {
        $robot = $app->request->getJsonRawBody();
        $phql = 'INSERT INTO MyApp\ModelsRobots '
               .'(name, type, year) '
               .'VALUES '
               .'(:name:, :type:, :year:)'
        ;

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'name' => $robot->name,
                    'type' => $robot->type,
                    'year' => $robot->year,
                ]
            )
        ;

        $response = new Response();

        if (true === $status->success()) {
            $response->setStatusCode(201, 'Created');

            $robot->id = $status->getModel()->id;

            $response->setJsonContent(
                [
                    'status' => 'OK',
                    'data' => $robot,
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->put(
    '/api/robots/{id:[0-9]+}',
    function ($id) use ($app) {
        $robot = $app->request->getJsonRawBody();
        $phql = 'UPDATE MyApp\Models\Robots '
               .'SET name = :name:, type = :type:, year = :year: '
               .'WHERE id = :id:';

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'id' => $id,
                    'name' => $robot->name,
                    'type' => $robot->type,
                    'year' => $robot->year,
                ]
            )
        ;

        $response = new Response();

        if (true === $status->success()) {
            $response->setJsonContent(
                [
                    'status' => 'OK',
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

// Deletes robots based on primary key
$app->delete(
    '/api/robots/{id:[0-9]+}',
    function ($id) use ($app) {
        $phql = 'DELETE '
              .'FROM MyApp\Models\Robots '
              .'WHERE id = :id:';

        $status = $app
            ->modelsManager
            ->executeQuery(
                $phql,
                [
                    'id' => $id,
                ]
            )
        ;

        $response = new Response();

        if (true === $status->success()) {
            $response->setJsonContent(
                [
                    'status' => 'OK',
                ]
            );
        } else {
            $response->setStatusCode(409, 'Conflict');

            $errors = [];
            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status' => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, 'Not Found')->sendHeaders();
    echo 'This is crazy, but this page was not found!';
});

try {
    $app->handle(
        $_SERVER['REQUEST_URI']
    );
} catch (\Exception $e) {
    echo 'Exception: ', $e->getMessage();
}
