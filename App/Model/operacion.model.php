<?php

class operacionModel{


    private $db;

    function __construct()
    {
        $this->db = new PDO('mysql:host=localhost;'.'dbname=bancovvba', 'root', '');
    }

    
    function createOperacion($monto, $fecha, $tipo_operación, $id_cuenta ){
        $query = $this->db->prepare('INSERT INTO cliente (monto, fecha, tipo_operación, id_cuenta ) VALUES ( ?,?,?,?)');
        $query->execute(array($monto, $fecha, $tipo_operación, $id_cuenta ));
    }

}