<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class LoginController {
    //login/
    //pagina principal /
    //donde empieza todo
    public static function login(Router $router) {
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarLogin();//muestra alertas 

            if(empty($alertas)) {
                // Comprobar que exista el usuario
                $usuario = Usuario::where('email', $auth->email);//donde esta el usuario 

                if($usuario) {
                    // Verificar el password
                    //compara la contraseña que escribio con la correcta y si esta verificado tambien
                    if( $usuario->comprobarPasswordAndVerificado($auth->password) ) {
                        // Autenticar el usuario
                        session_start();

                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre . " " . $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        // Redireccionamiento
                        //si en la columna de Admin de la BD esta en 1 es ADMIN y crea la session admin
                        if($usuario->admin === "1") {
                            $_SESSION['admin'] = $usuario->admin ?? null;
                            header('Location: /admin');//es admin
                        } else {
                            header('Location: /cita');//no es admin, es un cliente
                        }
                    }
                } else {
                    Usuario::setAlerta('error', 'Usuario no encontrado');
                }

            }
        }

        $alertas = Usuario::getAlertas();
        
        $router->render('auth/login', [
            'alertas' => $alertas
        ]);
    }

    //cierra sesion
    public static function logout() {
        session_start();
        $_SESSION = [];//vacia todo la que tenia de la Session
        header('Location: /');
    }

    //olvide/
    public static function olvide(Router $router) {

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarEmail();//el email es obligatorio

            if(empty($alertas)) {
                 $usuario = Usuario::where('email', $auth->email);//busca el usuario 

                 //si existe el suario y esta confirmado (1)(en pocas palabras que esta logueado y halla puesto sus datos y ya este registrado)
                 if($usuario && $usuario->confirmado === "1") {
                        
                    // Generar un token
                    $usuario->crearToken();
                    $usuario->guardar();//se guarda el BD

                    //  Enviar el email
                    //lo agarra de las classes de Email 
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();

                    // Alerta de exito
                    Usuario::setAlerta('exito', 'Revisa tu email');
                 } else {
                     Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado');
                     
                 }
            } 
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/olvide-password', [
            'alertas' => $alertas
        ]);
    }

    //recuperar?token=62ba05ff354fe
    public static function recuperar(Router $router) {
        $alertas = [];
        $error = false;// esta variable esta en recuperar-password
        //si esta FALSE NO se imprime codigo HTML del input password 

        $token = s($_GET['token']);//saca el token de la URL directamente por eso el GET

        // Buscar usuario por su token
        $usuario = Usuario::where('token', $token);//busca el token del usuario en la BD

        //si no existe el usario del token o el token no es valido se ejecuta esto
        if(empty($usuario)) {
            Usuario::setAlerta('error', 'Token No Válido');//una validacion
            $error = true;//si esta TRUE se imprime codigo HTML del input password 
        }

        //$error = true (input password)
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Leer el nuevo password y guardarlo

            $password = new Usuario($_POST);//guarda el password en la variable
            $alertas = $password->validarPassword();

            //si no hay alertas... todo esta correcto
            if(empty($alertas)) {
                $usuario->password = null;//quita el password anterior

                $usuario->password = $password->password;//se reasigna el nuevo password
                $usuario->hashPassword();//se hashea el pasword
                $usuario->token = null;//se quita el token para que no halla compliaciones

                $resultado = $usuario->guardar();//se guarda
                if($resultado) {
                    header('Location: /');  
                }
            }
        }

        $alertas = Usuario::getAlertas();
        $router->render('auth/recuperar-password', [
            'alertas' => $alertas, 
            'error' => $error
        ]);
    }

    //crear-cuenta/
    public static function crear(Router $router) {
        $usuario = new Usuario;

        // Alertas vacias
        $alertas = [];
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();//muestra alertas (validacion)

            // Revisar que alerta este vacio
            if(empty($alertas)) {
                // Verificar que el usuario no este registrado
                $resultado = $usuario->existeUsuario();

                if($resultado->num_rows) {
                    $alertas = Usuario::getAlertas();//si existe usuario muestra las alertas
                } else {
                    //Si el usuario no existe se ejecuta esto
                    // Hashear el Password
                    $usuario->hashPassword();

                    // Generar un Token único
                    $usuario->crearToken();

                    // Enviar el Email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);//tiene que estar muy bien ordenado para que no halla falla en los modelos

                    $email->enviarConfirmacion();

                    // Crear el usuario
                    $resultado = $usuario->guardar();
                    // debuguear($usuario);
                    if($resultado) {
                        header('Location: /mensaje');
                    }
                }
            }
        }
        
        $router->render('auth/crear-cuenta', [
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    //mensaje/
    //pagina normal que muestra que ya se enviaron las instrucciones
    public static function mensaje(Router $router) {
        $router->render('auth/mensaje');
    }

    //confirmar-cuenta?token=62bb6efd3cb68
    //esta pagina es cuando se crea una cuenta y se redirecciona a "cofirmar-cuenta con un token"
    public static function confirmar(Router $router) {
        $alertas = [];
        $token = s($_GET['token']);//saca el token de la URL
        $usuario = Usuario::where('token', $token);//busca el token del usuario en la BD

        //si no existe el token de usuario
        if(empty($usuario)) {
            // Mostrar mensaje de error
            Usuario::setAlerta('error', 'Token No Válido');
        } else {
            // Modificar a usuario confirmado
            $usuario->confirmado = "1";//es para confirmar el correo, que de verdad es del usuario
            $usuario->token = null;//se borra el token para que no halla complicaciones
            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta Comprobada Correctamente');
        }
       
        // Obtener alertas
        $alertas = Usuario::getAlertas();

        // Renderizar la vista
        $router->render('auth/confirmar-cuenta', [
            'alertas' => $alertas
        ]);
    }
}