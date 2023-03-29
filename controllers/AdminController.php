<?php 

namespace Controllers;

use Model\AdminCita;
use MVC\Router;

class AdminController {
    //admin/
    //boton Ver citas
    public static function index( Router $router ) {
        session_start();

        isAdmin();

        $fecha = $_GET['fecha'] ?? date('Y-m-d');//obtiene la fecha por medio de la URL o si no del servidor
        $fechas = explode('-', $fecha);//separa la fecha por medio del -

        //0    1   2
        //año mes dia (forma por Default-> se tiene que ordenar)
        //mes dia año (forma correcta de ordenar)( fecha gregoriana de PHP)
        //1    2   0
        if( !checkdate( $fechas[1], $fechas[2], $fechas[0]) ) {//ordena y checa las fechas validas 
            header('Location: /404');
        }

        // Consultar la base de datos
        $consulta = "SELECT citas.id, citas.hora, CONCAT( usuarios.nombre, ' ', usuarios.apellido) as cliente, ";
        $consulta .= " usuarios.email, usuarios.telefono, servicios.nombre as servicio, servicios.precio  ";
        $consulta .= " FROM citas  ";
        $consulta .= " LEFT OUTER JOIN usuarios ";
        $consulta .= " ON citas.usuarioId=usuarios.id  ";
        $consulta .= " LEFT OUTER JOIN citasServicios ";
        $consulta .= " ON citasServicios.citaId=citas.id ";
        $consulta .= " LEFT OUTER JOIN servicios ";
        $consulta .= " ON servicios.id=citasServicios.servicioId ";
        $consulta .= " WHERE fecha =  '${fecha}' ";//aqui toma la fecha

        $citas = AdminCita::SQL($consulta);//Toma toda la consuta y la inserta en la BD (es el buscador de fechas)

        $router->render('admin/index', [
            'nombre' => $_SESSION['nombre'],
            'citas' => $citas, 
            'fecha' => $fecha
        ]);
    }
}