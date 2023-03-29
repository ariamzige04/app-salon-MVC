<?php

namespace Controllers;

use Model\Cita;
use Model\CitaServicio;
use Model\Servicio;

class APIController {
    
    //api/servicios (URL)
    //esto lo utiliza la pagina principal /cita
    public static function index() {
        $servicios = Servicio::all();//obtiene todos los servicios de la base de datos 
        echo json_encode($servicios);//lo imprime en un json que lo puede leer con javascript por medio de async await
    }

    //api/citas (URL)
    //se envia todas la citas por medio de JS en json 
    //esto lo utiliza la pagina principal /cita (guarda la cita ya correcta y validada)
    public static function guardar() {
        
        // Almacena la Cita y devuelve el ID
        $cita = new Cita($_POST);//recibe el json
        
        $resultado = $cita->guardar();

        $id = $resultado['id'];//lo lee del json y la asigna a un variable

        // Almacena la Cita y el Servicio
        
        // Almacena los Servicios con el ID de la Cita
        $idServicios = explode(",", $_POST['servicios']);//separa los varios id de servicios del usuario
        //en Post Servicios es lo que se encuentra en JS --- datos.append('servicios', idServicios);
        foreach($idServicios as $idServicio) {
            $args = [
                'citaId' => $id,//es el id de la cita del usuario
                'servicioId' => $idServicio//es el id de los muchos servicios que selecciono
            ];
            $citaServicio = new CitaServicio($args);//se pasan los argumentos para guardar en la BD
            $citaServicio->guardar();
        }


        echo json_encode(['resultado' => $resultado]);
    }

    //api/eliminar (URL)
    public static function eliminar() {
        
        // ese boton esta en /admin (ver citas)
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];//toma el id del input hidden
            $cita = Cita::find($id);//busca por medio del id
            $cita->eliminar();
            header('Location:' . $_SERVER['HTTP_REFERER']);
            //http referer - es la pagina donde venias, guarda la referencia
        }
    }
}