<?php

class cuentaModel{

    private $db;

    function __construct()
    {
        $this->db = new PDO('mysql:host=localhost;'.'dbname=bancovvba', 'root', '');
    }

    function createCuenta($fecha_alta, $nro_cuenta, $id_cliente, $tipo_cuenta ){
        $query = $this->db->prepare('INSERT INTO cuenta (fecha_alta, nro_cuenta, id_cliente, tipo_cuenta ) VALUES ( ?,?,?,?)');
        $query->execute(array($fecha_alta, $nro_cuenta, $id_cliente, $tipo_cuenta ));
    }

    function getCuentaByIdCliente( $id_cliente){
        $query = $this->db->prepare('SELECT * FROM cuenta WHERE cuenta.id_cliente = ?');
        $query->execute(array($id_cliente));
        return $query->fetch(PDO::FETCH_OBJ);
    }
    function getCuentasByIdCliente( $id_cliente){
        $query = $this->db->prepare('SELECT * FROM cuenta WHERE cuenta.id_cliente = ?');
        $query->execute(array($id_cliente));
        return $query->fetchAll(PDO::FETCH_OBJ);
    }
    function getCuentaByIdClienteConOperaciones( $id_cliente){
        $query = $this->db->prepare('SELECT * FROM cuenta INNER JOIN operacion WHERE cuenta.id_cliente = ?');
        $query->execute(array($id_cliente));
        return $query->fetch(PDO::FETCH_OBJ);
    }
}