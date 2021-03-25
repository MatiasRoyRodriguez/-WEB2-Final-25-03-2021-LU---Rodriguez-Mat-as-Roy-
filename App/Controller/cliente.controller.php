<?php

require_once('./App/Model/clienteModel.php');
require_once('./App/Model/cuentaModel.php');
require_once('./App/Model/operacionModel.php');
require_once('./App/View/clienteView.php');

class clienteControlle
{

    private $view;
    private $model;
    private $cuentaModel;
    private $operacionModel;

    function __construct()
    {
        $this->model = new clienteModel();
        $this->cuentaModel = new cuentaModel();
        $this->operacionModel = new operacionModel();
        $this->view = new clienteView();
    }

    public function altaCliente($params = null)
    {
        $msg = '';
        // Controlamos que los datos del cliente sean validos
        if ($_POST['nombre'] != '' && $_POST['dni'] != '' && $_POST['telefono'] != '' && $_POST['direccion'] != '' && $_POST['premium'] != '') {
            // Controlamos que los datos de la cuenta sean validos
            if ($_POST['fecha_alta'] != '' && $_POST['nro_cuenta'] != '' && $_POST['tipo_cuenta'] != '') {

                // Verificamos que el usuario admin este logeado
                if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true) {

                    $dniCliente = $_POST['dni'];
                    $checkCliente = $this->model->getClienteByDni($dniCliente);

                    // Controlamos que el dni del cliente no este en nuestra base de datos

                    if (!$checkCliente) {

                        $nombre = $_POST['nombre'];
                        $telefono = $_POST['telefono'];
                        $direccion = $_POST['direccion'];
                        $premium = $_POST['premium'];
                        $fecha_alta = $_POST['fecha_alta'];
                        $nro_cuenta = $_POST['nro_cuenta'];
                        $tipo_cuenta = $_POST['tipo_cuenta'];


                        if ($premium) {

                            $monto = 10000;

                            $this->model->createCliente($nombre, $dniCliente, $telefono, $direccion, $premium);
                            $cliente = $this->model->getClienteByDni($dniCliente);
                            $clienteId = $cliente->id;

                            $this->cuentaModel->createCuenta($fecha_alta, $nro_cuenta, $clienteId, $tipo_cuenta);
                            $cuenta = $this->cuentaModel->getCuentaByIdCliente($clienteId);
                            $cuentaId = $cuenta->id;
                            // En este caso podemos utilizar la fecha de alta de la cuenta que sera la misma que del deposito
                            $this->operacionModel->createOperacion($monto, $fecha_alta, 2, $cuentaId);
                        } else if (!$premium) {

                            $this->model->createCliente($nombre, $dniCliente, $telefono, $direccion, $premium);
                            $cliente = $this->model->getClienteByDni($dniCliente);
                            $clienteId = $cliente->id;

                            $this->cuentaModel->createCuenta($fecha_alta, $nro_cuenta, $clienteId, $tipo_cuenta);
                        }
                    }
                    // El cliente ya pertenece a la base de datos 
                    $msg = 'El cliente ya pertenece a la base de datos ';
                    $this->view->showError($msg);

                }
                // No tiene privilegios 
                $msg = 'No tiene privilegios  ';
                $this->view->showError($msg);

            }
            // Datos de cuenta invalidos
            $msg = 'Datos de cuenta invalidos';
            $this->view->showError($msg);

        }else{
            // Datos de cliente invalidos
            $msg = 'Datos de cliente invalidos';
            $this->view->showError($msg);
        }
        

    }

    public function getCuentasByIdCliente($params = null)
    {
        $msg = '';

        $idCliente = $params[':ID'];


        if ($idCliente) {
            $cuentasCliente = $this->cuentaModel->getCuentasByIdCliente($idCliente);
            if ($cuentasCliente) {

                $cuentasConOperaciones = $this->cuentaModel->getCuentaByIdClienteConOperaciones($idCliente);

                $arregloAsociativo = array();

                foreach ($cuentasConOperaciones as $cuenta) {

                    if (array_key_exists($cuenta->nro_cuenta, $arregloAsociativo)) {
                        $montoTotal = 0;
                        if ($cuenta->tipo_operacion === 2) {
                            $montoTotal += $cuenta->monto;
                        } else if ($cuenta->tipo_operacion === 1) {
                            $montoTotal -= $cuenta->monto;
                        }
                        $arregloAsociativo[$cuenta->nro_cuenta] += $montoTotal;
                    } else {
                        $arregloAsociativo[$cuenta->nro_cuenta] += $cuenta->monto;
                    }
                }
                // De esta manera solo con poner el numero de la cuenta en la vista ya tendremos el monto total
                // - de la cuenta, ya que es un arreglo asociativo. 
                // Los detalles de cada item pueden ser consumidos sin problemas en la vista ya que se retorna un objeto.

                $this->view->printTable($cuentasConOperaciones, $arregloAsociativo);
            }
            // No tiene cuentas
            $msg = 'No tiene cuentas';
            $this->view->showError($msg);
        }else{
            //No existe el cliente
            $msg = 'No existe el cliente';
            $this->view->showError($msg);
        }
    }

    public function transferenciaRapida($params = null)
    {
        $msg = '';

        // Verificamos que manden el DNI y el monto a transferir
        if ($_POST['dni'] != '' && $_POST['montoTranseferir'] != '') {
            $dniDestinatario = $_POST['dni'];
            $montoTranseferir = $_POST['montoTranseferir'];
            //Verificamos que el usuario este logeado
            if (isset($_SESSION['user']) && $_SESSION['user'] === true) {
                $idCliente = $_SESSION['user']->id;




                $cuentasCliente = $this->cuentaModel->getCuentasByIdCliente($idCliente);
                $cuentaCliente = $this->cuentaModel->getCuentaByIdCliente($idCliente);

                if ($cuentasCliente) {

                    $cuentasConOperaciones = $this->cuentaModel->getCuentaByIdClienteConOperaciones($idCliente);

                    $fondoTotal = 0;

                    foreach ($cuentasConOperaciones as $cuenta) {


                        if ($cuenta->tipo_operacion === 2) {
                            $fondoTotal += $cuenta->montoTranseferir;
                        } else if ($cuenta->tipo_operacion === 1) {
                            $fondoTotal -= $cuenta->montoTranseferir;
                        }
                    }
                }

                // Verificamos que tenga fondosSuficientes
                if( $fondoTotal - $montoTranseferir > 0){
                    // Verificamos que el destinatario exista
                    $destinatario = $this->model->getClienteByDni($dniDestinatario);
                    if( $destinatario){
                        $cuentaDestinario = $this->cuentaModel->getCuentaByIdCliente($destinatario->id);
                        $date = new DateTime('now');                    
    
                        // Hacemos la transferencia en la cuenta de destino
                        $this->operacionModel->createOperacion($montoTranseferir, $date,2,$cuentaDestinario);
                        // Y creamos la operacion de extracción en la cuenta del cliente
                        $this->operacionModel->createOperacion($montoTranseferir, $date,1,$cuentaCliente);
    
                    }
                    // Destinatario no existe
                    $msg = 'Destinatario no existe';
                    $this->view->showError($msg);
                }
                // No hay fondos
                $msg = 'No hay fondos';
                $this->view->showError($msg);
            }
            // No esta logeado
            $msg = 'No esta logeado';
            $this->view->showError($msg);
        }else{
            // Los valores enviados no son correctos
            $msg = 'Los valores enviados no son correctos';
            $this->view->showError($msg);
        }

    }
}
