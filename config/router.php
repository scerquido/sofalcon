<?php

$router = $di->getRouter();

// Define your routes here
$router->add(
    '/api/:controller/a/:action/:params',
    [
        'controller' => 1,
        'action'     => 2,
        'params'     => 3,
    ]
);

$router->handle($_SERVER['REQUEST_URI']);

