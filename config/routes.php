<?php
// config/routes.php
use App\Controller\UserController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->add('create_user', '/api/users/create')
        // значение контроллера ммеет формат [controller_class, method_name]
        ->controller([UserController::class, 'create']);
    $routes->add('show_user', '/users/show/{id}')
        // значение контроллера ммеет формат [controller_class, method_name]
        ->controller([UserController::class, 'show']);
    $routes->add('update_user', '/users/update/{id}')
        // значение контроллера ммеет формат [controller_class, method_name]
        ->controller([UserController::class, 'update']);
    $routes->add('delete_user', '/users/delete/{id}')
        // значение контроллера ммеет формат [controller_class, method_name]
        ->controller([UserController::class, 'delete']);
        // еслм действие реализуется как метод __invoke() класса контроллера,
        // вы можете пропустить часть '::method_name':
        // ->controller(BlogController::class)

};