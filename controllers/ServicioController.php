<?php

namespace Controllers;

use Model\Servicio;
use MVC\Router;
use Intervention\Image\ImageManagerStatic as Image;


class ServicioController {
    //servicios/
    //boton Ver servicios
    public static function index(Router $router) {
        session_start();

        isAdmin();//si es ADMIN

        $servicios = Servicio::all();

        $router->render('servicios/index', [
            'nombre' => $_SESSION['nombre'],
            'servicios' => $servicios
            //la primero es la variable que se imprime en el index, y la Session es donde esta la informacion del login, donde se registro el usuario
        ]);
    }

    //servicios/crear
    //boton Nuevo servicio
    public static function crear(Router $router) {
        session_start();
        isAdmin();
        $servicio = new Servicio;
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $servicio->sincronizar($_POST);//posiblemente en el formulario ya tenga datos, y lo que se hace es "sincronizar" los datos de los inputs
            
            /** Crea una nueva instancia */
            $servicio = new Servicio($_POST['servicio']);

            // Generar un nombre único
            $nombreImagen = md5( uniqid( rand(), true ) ) . ".jpg";


            // Setear la imagen
            // Realiza un resize a la imagen con intervention
            if($_FILES['servicio']['tmp_name']['imagen']) {
                $image = Image::make($_FILES['servicio']['tmp_name']['imagen'])->fit(800,600);
                $servicio->setImagen($nombreImagen);
            }

            $alertas = $servicio->validar();

            if(empty($alertas)) {

                // Crear la carpeta para subir imagenes
                if(!is_dir(CARPETA_IMAGENES)) {
                    mkdir(CARPETA_IMAGENES);
                }

                // Guarda la imagen en el servidor
                $image->save(CARPETA_IMAGENES . $nombreImagen);

                $servicio->guardar();
                header('Location: /servicios');
            }
        }

        $router->render('servicios/crear', [
            'nombre' => $_SESSION['nombre'],
            'servicio' => $servicio,
            'alertas' => $alertas
        ]);
    }

    //servicios/actualizar?id=17
    //boton Actualizar
    public static function actualizar(Router $router) {
        session_start();
        isAdmin();

        if(!is_numeric($_GET['id'])) return;//actualizar?id=17   solo acepta numeros en la url (validacion, si no retorna todo y ya no se ejecuta el resto del codigo)

        $servicio = Servicio::find($_GET['id']);//Busca un servicio por su id
        $alertas = [];

        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $servicio->sincronizar($_POST);//posiblemente en el formulario ya tenga datos, y lo que se hace es "sincronizar" los datos de los inputs

            $alertas = $servicio->validar();

            if(empty($alertas)) {
                $servicio->guardar();
                header('Location: /servicios');
            }
        }

        $router->render('servicios/actualizar', [
            'nombre' => $_SESSION['nombre'],
            'servicio' => $servicio,
            'alertas' => $alertas
            //la primero es la variable que se imprime en el index, y la Session es donde esta la informacion del login, donde se registro el usuario
        ]);
    }

    // servicios/eliminar
    public static function eliminar() {
        session_start();
        isAdmin();

        //esto es un formulario, con un input hidden que contiene el valor del id, cuando se envia el formulario se ejecuta este codigo por medio de Post 
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];//obtiene el id
            $servicio = Servicio::find($id);//Busca un servicio por su id
            $servicio->eliminar();
            header('Location: /servicios');
        }
    }
}