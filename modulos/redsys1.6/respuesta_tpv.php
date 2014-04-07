<?php
/*-----------------------------------------------------------------------------
Autor: Javier García
Autor E-Mail: cvirtual@redsys.es
Fecha: Marzo 2014
Version :2.0
-----------------------------------------------------------------------------*/

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/redsys.php');

if (!empty($_POST)){

	// Recoger datos de respuesta
	$total     = $_POST["Ds_Amount"];
	$pedido    = $_POST["Ds_Order"];
	$codigo    = $_POST["Ds_MerchantCode"];
	$moneda    = $_POST["Ds_Currency"];
	$respuesta = $_POST["Ds_Response"];
	$firma_remota = $_POST["Ds_Signature"];

	// Creamos objeto
	$redsys = new redsys();
	//Verificamos opciones
	$error_pago = Configuration::get('REDSYS_ERROR_PAGO');
	// Contraseña Secreta
	$clave = Configuration::get('REDSYS_CLAVE');

	// Cálculo del SHA1
	$mensaje = $total . $pedido . $codigo . $moneda . $respuesta . $clave;
	$firma_local = strtoupper(sha1($mensaje));

	if ($firma_local == $firma_remota){
		// Formatear variables
		// NINO - eliminar el punto de los miles para evitar error en pago
		// ORIGINAL - $total  = number_format($total / 100,4);
		$total  = number_format($total / 100,4,'.', '');
		$pedido = substr($pedido,0,8);
		$pedido = intval($pedido);
		$respuesta = intval($respuesta);
		$moneda_tienda = 1; // Euros
		if ($respuesta < 101){
			// Compra válida
			$mailvars=array();
			$cart = new Cart($pedido);
			$redsys->validateOrder($pedido, _PS_OS_PAYMENT_, $total, $redsys->displayName, NULL, $mailvars, NULL, false, $cart->secure_key);
		}
		else {
			// Compra no válida
			if ($error_pago=="no"){
				//se anota el pedido como no pagado
				$redsys->validateOrder($pedido, _PS_OS_ERROR_, 0, $redsys->displayName, 'errores:'.$respuesta);
				}
			else if ($error_pago=="si"){
				//Se permite al cliente intentar otra vez el pago
			}
		}
	}
	else {
		//se anota el pedido como no pagado
		$redsys->validateOrder($pedido, _PS_OS_ERROR_, 0, $redsys->displayName, 'errores:'.$respuesta);
	}
}
?>
