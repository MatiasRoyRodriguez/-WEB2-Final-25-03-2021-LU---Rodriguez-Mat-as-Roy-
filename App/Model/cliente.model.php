<?php

class clienteModel{

    private $db;

    function __construct()
    {
        $this->db = new PDO('mysql:host=localhost;'.'dbname=bancovvba', 'root', '');
    }

    function getClienteByDni( $dni ){

        $query = $this->db->prepare('SELECT * FROM cliente WHERE dni = ?');
    
        $query->execute(array($dni));
        
        return $query->fetch(PDO::FETCH_OBJ);

    }
    function getClienteById( $id ){

        $query = $this->db->prepare('SELECT * FROM cliente WHERE id = ?');
    
        $query->execute(array($id));
        
        return $query->fetch(PDO::FETCH_OBJ);

    }
    function createCliente($nombre, $dni, $telefono, $direccion, $premium){
        $query = $this->db->prepare('INSERT INTO cliente (nombre, dni, telefono, direccion, premium) VALUES ( ?,?,?,?,?)');
        $query->execute(array($nombre, $dni, $telefono, $direccion, $premium));
    }
}