<?php

namespace Controllers;

use MVC\Router;

class CitaController {
    //cita/
    //las 3 secciones
    public static function index( Router $router ) {

        session_start();

        isAuth();//se esta autenticado el USUARIO

        $router->render('cita/index', [
            'nombre' => $_SESSION['nombre'],
            'id' => $_SESSION['id']
            //la primero es la variable que se imprime en el index, y la Session es donde esta la informacion del login, donde se registro el usuario
        ]);
    }
}