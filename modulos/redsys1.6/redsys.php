<?php
/*-----------------------------------------------------------------------------
Autor: Javier García
Autor E-Mail: cvirtual@redsys.es
Fecha: Marzo 2014
Version :2.0

-----------------------------------------------------------------------------*/

if (!defined('_CAN_LOAD_FILES_'))
	exit;

class redsys extends PaymentModule
{
	private	$_html = '';
	private $_postErrors = array();

	public function __construct(){
		$this->name = 'redsys';
		$this->tab = 'payments_gateways';
		$this->version = '2.0';

		// Array config con los datos de configuración
		$config = Configuration::getMultiple(array('REDSYS_URLTPV', 'REDSYS_CLAVE', 'REDSYS_NOMBRE', 'REDSYS_CODIGO','REDSYS_TIPOPAGO', 'REDSYS_TERMINAL', 'REDSYS_TIPOFIRMA', 'REDSYS_RECARGO', 'REDSYS_MONEDA', 'REDSYS_TRANS', 'REDSYS_NOTIFICACION', 'REDSYS_SSL', 'REDSYS_ERROR_PAGO', 'REDSYS_IDIOMAS_ESTADO'));
		// Establecer propiedades según los datos de configuración
		$this->env = $config['REDSYS_URLTPV'];
		switch($this->env){
			case 1: //Real
				$this->urltpv = "https://sis.redsys.es/sis/realizarPago";
				break;
			case 2: //Pruebas t
				$this->urltpv = "https://sis-t.redsys.es:25443/sis/realizarPago";
				break;
			case 3: // Pruebas i
				$this->urltpv = "https://sis-i.redsys.es:25443/sis/realizarPago";
				break;
			case 4: //Pruebas d
				$this->urltpv = "http://sis-d.redsys.es/sis/realizarPago";
				break;
		}
		$this->clave = $config['REDSYS_CLAVE'];
		if (isset($config['REDSYS_NOMBRE']))
			$this->nombre = $config['REDSYS_NOMBRE'];
		if (isset($config['REDSYS_CODIGO']))
			$this->codigo = $config['REDSYS_CODIGO'];
		if (isset($config['REDSYS_TIPOPAGO']))
			$this->tipopago = $config['REDSYS_TIPOPAGO'];
		if (isset($config['REDSYS_TERMINAL']))
			$this->terminal = $config['REDSYS_TERMINAL'];
		if (isset($config['REDSYS_TIPOFIRMA']))
			$this->tipofirma = $config['REDSYS_TIPOFIRMA'];
		if (isset($config['REDSYS_RECARGO']))
			$this->recargo = $config['REDSYS_RECARGO'];
		if (isset($config['REDSYS_MONEDA']))
			$this->moneda = $config['REDSYS_MONEDA'];
		if (isset($config['REDSYS_TRANS']))
			$this->trans = $config['REDSYS_TRANS'];
		if (isset($config['REDSYS_NOTIFICACION']))
			$this->notificacion = $config['REDSYS_NOTIFICACION'];
		if (isset($config['REDSYS_SSL']))
			$this->ssl = $config['REDSYS_SSL'];
		if (isset($config['REDSYS_ERROR_PAGO']))
			$this->error_pago = $config['REDSYS_ERROR_PAGO'];
		if (isset($config['REDSYS_IDIOMAS_ESTADO']))
			$this->idiomas_estado = $config['REDSYS_IDIOMAS_ESTADO'];


		parent::__construct();

		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Redsys');
		$this->description = $this->l('Aceptar pagos con tarjeta mediante Redsys');

		// Mostrar aviso en la página principal de módulos si faltan datos de configuración.
		if (!isset($this->urltpv)
		OR !isset($this->clave)
		OR !isset($this->nombre)
		OR !isset($this->codigo)
		OR !isset($this->tipopago)
		OR !isset($this->terminal)
		OR !isset($this->tipofirma)
		OR !isset($this->recargo)
		OR !isset($this->moneda)
		OR !isset($this->trans)
		OR !isset($this->notificacion)
		OR !isset($this->ssl)
		OR !isset($this->error_pago)
		OR !isset($this->idiomas_estado))


		$this->warning = $this->l('Faltan datos por configurar del mod. Redsys.');
	}

	public function install()
	{
		// Valores por defecto al instalar el módulo
		if (!parent::install()
			OR !Configuration::updateValue('REDSYS_URLTPV', '0')
			OR !Configuration::updateValue('REDSYS_NOMBRE', $this->l('Escriba el nombre de su tienda'))
			OR !Configuration::updateValue('REDSYS_TIPOPAGO','T')
			OR !Configuration::updateValue('REDSYS_TERMINAL', 1)
			OR !Configuration::updateValue('REDSYS_TIPOFIRMA', 0)
			OR !Configuration::updateValue('REDSYS_RECARGO', '00')
			OR !Configuration::updateValue('REDSYS_MONEDA', '978')
			OR !Configuration::updateValue('REDSYS_TRANS', 0)
			OR !Configuration::updateValue('REDSYS_NOTIFICACION', 0)
			OR !Configuration::updateValue('REDSYS_SSL', 'no')
			OR !Configuration::updateValue('REDSYS_ERROR_PAGO', 'no')
			OR !Configuration::updateValue('REDSYS_IDIOMAS_ESTADO', 'no')
			OR !$this->registerHook('payment')
			OR !$this->registerHook('paymentReturn'))
			return false;
		return true;
	}

	public function uninstall()
	{
	   // Valores a quitar si desinstalamos el módulo
		if (!Configuration::deleteByName('REDSYS_URLTPV')
			OR !Configuration::deleteByName('REDSYS_CLAVE')
			OR !Configuration::deleteByName('REDSYS_NOMBRE')
			OR !Configuration::deleteByName('REDSYS_CODIGO')
			OR !Configuration::deleteByName('REDSYS_TIPOPAGO')
			OR !Configuration::deleteByName('REDSYS_TERMINAL')
			OR !Configuration::deleteByName('REDSYS_TIPOFIRMA')
			OR !Configuration::deleteByName('REDSYS_RECARGO')
			OR !Configuration::deleteByName('REDSYS_MONEDA')
			OR !Configuration::deleteByName('REDSYS_TRANS')
			OR !Configuration::deleteByName('REDSYS_NOTIFICACION')
			OR !Configuration::deleteByName('REDSYS_SSL')
			OR !Configuration::deleteByName('REDSYS_ERROR_PAGO')
			OR !Configuration::deleteByName('REDSYS_IDIOMAS_ESTADO')
			OR !parent::uninstall())
			return false;
		return true;
	}

	private function _postValidation(){
	    // Si al enviar los datos del formulario de configuración hay campos vacios, mostrar errores.
		if (isset($_POST['btnSubmit'])){
			if (empty($_POST['clave']))
				$this->_postErrors[] = $this->l('Se requiere la Clave secreta de encript.');
			if (empty($_POST['nombre']))
				$this->_postErrors[] = $this->l('Se requiere el Nombre del comercio.');
			if (empty($_POST['tipopago']))
				$this->_postErrors[] = $this->l('Se requiere el Nombre del comercio.');
			if (empty($_POST['codigo']))
				$this->_postErrors[] = $this->l('Se requiere el Num. de comercio (FUC).');
			if (empty($_POST['terminal']))
				$this->_postErrors[] = $this->l('Se requiere el Terminal del comercio (FUC).');
			if (empty($_POST['recargo']))
				$this->_postErrors[] = $this->l('Si no desea aplicar recargo, ponga 00.');
			if (empty($_POST['moneda']))
				$this->_postErrors[] = $this->l('Se requiere el Tipo de moneda.');

		}
	}

	private function _postProcess(){
	    // Actualizar la configuración en la BBDD
			if (isset($_POST['btnSubmit'])){
			Configuration::updateValue('REDSYS_URLTPV', $_POST['urltpv']);
			Configuration::updateValue('REDSYS_CLAVE', $_POST['clave']);
			Configuration::updateValue('REDSYS_NOMBRE', $_POST['nombre']);
			Configuration::updateValue('REDSYS_CODIGO', $_POST['codigo']);
			Configuration::updateValue('REDSYS_TIPOPAGO', $_POST['tipopago']);
			Configuration::updateValue('REDSYS_TERMINAL', $_POST['terminal']);
			Configuration::updateValue('REDSYS_TIPOFIRMA', $_POST['tipofirma']);
			Configuration::updateValue('REDSYS_RECARGO', $_POST['recargo']);
			Configuration::updateValue('REDSYS_MONEDA', $_POST['moneda']);
			Configuration::updateValue('REDSYS_TRANS', $_POST['trans']);
			Configuration::updateValue('REDSYS_NOTIFICACION', $_POST['notificacion']);
			Configuration::updateValue('REDSYS_SSL', $_POST['ssl']);
			Configuration::updateValue('REDSYS_ERROR_PAGO', $_POST['error_pago']);
			Configuration::updateValue('REDSYS_IDIOMAS_ESTADO', $_POST['idiomas_estado']);
		}

		$this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> '.$this->l('Configuraci&oacute;n actualizada').'</div>';
	}

	private function _displayredsys()
	{
	    // Aparición el la lista de módulos
		$this->_html .= '<img src="../modules/redsys/redsys.png" style="float:left; margin-right:15px;"><b>'.$this->l('Este m&oacute;dulo te permite aceptar pagos con tarjeta.').'</b><br /><br />
		'.$this->l('Si el cliente elije este modo de pago, podr&aacute; pagar de forma autom&aacute;tica.').'<br /><br /><br />';
	}

	private function _displayForm(){

	
		$tipopago = Tools::getValue('tipopago', $this->tipopago);
		$tipopago_a =  ($tipopago == ' ') ? ' selected="selected" ' : '';
		$tipopago_b = ($tipopago == 'C') ? ' selected="selected" ' : '';
		$tipopago_c = ($tipopago == 'T') ? ' selected="selected" ' : '';
	
		// Opciones para el select de monedas.
		$moneda = Tools::getValue('moneda', $this->moneda);
		$iseuro =  ($moneda == '978') ? ' selected="selected" ' : '';
		$isdollar = ($moneda == '840') ? ' selected="selected" ' : '';

		// Opciones para activar/desactivar SSL
		$ssl = Tools::getValue('ssl', $this->ssl);
		$ssl_si = ($ssl == 'si') ? ' checked="checked" ' : '';
		$ssl_no = ($ssl == 'no') ? ' checked="checked" ' : '';

		// Opciones para el comportamiento en error en el pago
		$error_pago = Tools::getValue('error_pago', $this->error_pago);
		$error_pago_si = ($error_pago == 'si') ? ' checked="checked" ' : '';
		$error_pago_no = ($error_pago == 'no') ? ' checked="checked" ' : '';

		// Opciones para activar los idiomas
		$idiomas_estado = Tools::getValue('idiomas_estado', $this->idiomas_estado);
		$idiomas_estado_si = ($idiomas_estado == 'si') ? ' checked="checked" ' : '';
		$idiomas_estado_no = ($idiomas_estado == 'no') ? ' checked="checked" ' : '';

		// Opciones entorno
		if (!isset($_POST['urltpv']))
			$entorno = Tools::getValue('env', $this->env);
				else
					$entorno = $_POST['urltpv'];
		$entorno_real =  ($entorno==1) ? ' selected="selected" ' : '';
		$entorno_t =  ($entorno==2) ? ' selected="selected" ' : '';
		$entorno_i =  ($entorno==3) ? ' selected="selected" ' : '';
		$entorno_d =  ($entorno==4) ? ' selected="selected" ' : '';

		// Opciones tipofirma
		$tipofirma = Tools::getValue('tipofirma', $this->tipofirma);
	  	$tipofirma_a =  ($tipofirma==0) ? ' checked="checked" ' : '';
	  	$tipofirma_c =  ($tipofirma==1) ? ' checked="checked" '  : '';

	    // Opciones notificacion
	    $notificacion = Tools::getValue('notificacion', $this->notificacion);
		$notificacion_s =  ($notificacion==1) ? ' checked="checked" '  : '';
		$notificacion_n =  ($notificacion==0) ? ' checked="checked" '  : '';

		// Mostar formulario
		$this->_html .=
		'<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
			<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Configuraci&oacute;n del TPV').'</legend>
				<table border="0" width="680" cellpadding="0" cellspacing="0" id="form">
					<tr><td colspan="2">'.$this->l('Por favor completa los datos de config. del comercio').'.<br /><br /></td></tr>
					<tr><td width="215" style="height: 35px;">'.$this->l('Entorno de Redsys').'</td><td><select name="urltpv"><option value="1"'.$entorno_real.'>'.$this->l('Real').'</option><option value="2"'.$entorno_t.'>'.$this->l('Pruebas en sis-t').'</option><option value="3"'.$entorno_i.'>'.$this->l('Pruebas en sis-i').'</option><option value="4"'.$entorno_d.'>'.$this->l('Pruebas en sis-d').'</option></select></td></tr>
					<tr><td width="215" style="height: 35px;">'.$this->l('Nombre del comercio').'</td><td><input type="text" name="nombre" value="'.htmlentities(Tools::getValue('nombre', $this->nombre), ENT_COMPAT, 'UTF-8').'" style="width: 200px;" /></td></tr>
					<tr><td width="215" style="height: 35px;">'.$this->l('N&uacute;mero de comercio (FUC)').'</td><td><input type="text" name="codigo" value="'.Tools::getValue('codigo', $this->codigo).'" style="width: 200px;" /></td></tr>
					<tr><td width="215" style="height: 35px;">'.$this->l('Tipos de pago permitidos').'</td><td><select name="tipopago" style="width: 120px;"><option value=""></option><option value=" "'.$tipopago_a.'>Todos</option><option value="C"'.$tipopago_b.'>Solo con Tarjeta</option><option value="T"'.$tipopago_c.'>Tarjeta y Iupay</option></select></td></tr>
					<tr><td width="215" style="height: 35px;">'.$this->l('Clave secreta de encriptaci&oacute;n').'</td><td><input type="text" name="clave" value="'.Tools::getValue('clave', $this->clave).'" style="width: 200px;" /></td></tr>
					<tr><td width="215" style="height: 35px;">'.$this->l('N&uacute;mero de terminal').'</td><td><input type="text" name="terminal" value="'.Tools::getValue('terminal', $this->terminal).'" style="width: 80px;" /></td></tr>
					<tr><td width="215" style="height: 35px;">'.$this->l('Tipo de firma').'</td><td><input type="radio" name="tipofirma" id="tipofirma_c" value="1"'.$tipofirma_c.'/>'.$this->l('Completa').'<input type="radio" name="tipofirma" id="tipofirma_a" value="0"'.$tipofirma_a.'/>'.$this->l('Ampliada').'</td></tr>
					<tr><td width="215" style="height: 35px;">'.$this->l('Tipo de moneda').'</td><td><select name="moneda" style="width: 80px;"><option value=""></option><option value="978"'.$iseuro.'>EURO</option><option value="840"'.$isdollar.'>DOLLAR</option></select></td></tr>
					<tr><td width="215" style="height: 35px;">'.$this->l('Tipo de transacci&oacute;n').'</td><td><input type="text" name="trans" value="'.Tools::getValue('trans', $this->trans).'" style="width: 80px;" /></td></tr>
					<tr><td width="215" style="height: 35px;">'.$this->l('Recargo (% de recargo en el precio)').'</td><td><input type="text" name="recargo" value="'.Tools::getValue('recargo', $this->recargo).'" style="width: 80px;" /></td></tr>
		</td></tr>
				</table>
			</fieldset>
			<br>
			<fieldset>
			<legend><img src="../img/admin/cog.gif" />'.$this->l('Personalizaci&oacute;n').'</legend>
			<table border="0" width="680" cellpadding="0" cellspacing="0" id="form">
		<tr>
		<td colspan="2">'.$this->l('Por favor completa los datos adicionales').'.<br /><br /></td>
		</tr>
		<tr>
		<td width="340" style="height: 35px;">'.$this->l('Notificaci&oacute;n HTTP (Inactivo no procesa pedido ni vacia el carrito)').'</td>
			<td>
			<input type="radio" name="notificacion" id="notificacion_1" value="1"'.$notificacion_s.'/>
			<img src="../img/admin/enabled.gif" alt="'.$this->l('Activado').'" title="'.$this->l('Activado').'" />
			<input type="radio" name="notificacion" id="notificacion_0" value="0"'.$notificacion_n.'/>
			<img src="../img/admin/disabled.gif" alt="'.$this->l('Desactivado').'" title="'.$this->l('Desactivado').'" />
			</td>
		</tr>
		<tr>
		<td width="340" style="height: 35px;">'.$this->l('SSL en URL de validaci&oacute;n').'</td>
			<td>
			<input type="radio" name="ssl" id="ssl_1" value="si" '.$ssl_si.'/>
			<img src="../img/admin/enabled.gif" alt="'.$this->l('Activado').'" title="'.$this->l('Activado').'" />
			<input type="radio" name="ssl" id="ssl_0" value="no" '.$ssl_no.'/>
			<img src="../img/admin/disabled.gif" alt="'.$this->l('Desactivado').'" title="'.$this->l('Desactivado').'" />
			</td>
		</tr>
		<tr>
		<td width="340" style="height: 35px;">'.$this->l('En caso de error, permitir elegir otro medio de pago').'</td>
			<td>
			<input type="radio" name="error_pago" id="error_pago_1" value="si" '.$error_pago_si.'/>
			<img src="../img/admin/enabled.gif" alt="'.$this->l('Activado').'" title="'.$this->l('Activado').'" />
			<input type="radio" name="error_pago" id="error_pago_0" value="no" '.$error_pago_no.'/>
			<img src="../img/admin/disabled.gif" alt="'.$this->l('Desactivado').'" title="'.$this->l('Desactivado').'" />
			</td>
		</tr>
		<tr>
		<td width="340" style="height: 35px;">'.$this->l('Activar los idiomas en el TPV').'</td>
			<td>
			<input type="radio" name="idiomas_estado" id="idiomas_estado_si" value="si" '.$idiomas_estado_si.'/>
			<img src="../img/admin/enabled.gif" alt="'.$this->l('Activado').'" title="'.$this->l('Activado').'" />
			<input type="radio" name="idiomas_estado" id="idiomas_estado_no" value="no" '.$idiomas_estado_no.'/>
			<img src="../img/admin/disabled.gif" alt="'.$this->l('Desactivado').'" title="'.$this->l('Desactivado').'" />
			</td>
		</tr>
		</table>
			</fieldset>
			<br>
		<input class="button" name="btnSubmit" value="'.$this->l('Guardar configuraci&oacute;n').'" type="submit" />
		</form>';
	}

	public function getContent()
	{
	    // Recoger datos
		$this->_html = '<h2>'.$this->displayName.'</h2>';
		if (!empty($_POST))
		{
			$this->_postValidation();
			if (!sizeof($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors AS $err)
					$this->_html .= '<div class="alert error">'. $err .'</div>';
		}
		else
			$this->_html .= '<br />';
		$this->_displayredsys();
		$this->_displayForm();
		return $this->_html;
	}

	public function hookPayment($params)
	{
		// Variables necesarias de fuera
		global $smarty, $cookie, $cart;

		// Aplicar Recargo
		$porcientorecargo = Tools::getValue('recargo', $this->recargo);
		$porcientorecargo = str_replace (',','.',$porcientorecargo);
		$totalcompra = floatval($cart->getOrderTotal(true, 3));
		$fee = ($porcientorecargo / 100) * $totalcompra;


		// Valor de compra
		$id_currency = intval(Configuration::get('PS_CURRENCY_DEFAULT'));
		$currency = new Currency(intval($id_currency));
		$cantidad = number_format(Tools::convertPrice($params['cart']->getOrderTotal(true, 3) + $fee, $currency), 2, '.', '');
		$cantidad = str_replace('.','',$cantidad);
		$cantidad = floatval($cantidad);

		// El número de pedido es  los 8 ultimos digitos del ID del carrito + el tiempo MMSS.
		$numpedido = str_pad($params['cart']->id, 8, "0", STR_PAD_LEFT) . date("is");

		$codigo = Tools::getValue('codigo', $this->codigo);
		$moneda = Tools::getValue('moneda', $this->moneda);
		$trans = Tools::getValue('trans', $this->trans);

		$ssl = Tools::getValue('ssl', $this->ssl);
		if ($ssl=='no')
		$urltienda = 'http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/redsys/respuesta_tpv.php';
		elseif($ssl=='si')
		$urltienda = 'https://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/redsys/respuesta_tpv.php';
		else
		$urltienda = 'ninguna';

		$clave = Tools::getValue('clave', $this->clave);

		// Cálculo del SHA1 $trans . $urltienda
		if(Tools::getValue('tipofirma', $this->tipofirma))
			$mensaje = $cantidad . $numpedido . $codigo . $moneda . $clave;
		else
			$mensaje = $cantidad . $numpedido . $codigo . $moneda . $trans . $urltienda . $clave;
		$firma = strtoupper(sha1($mensaje));

		$products = $params['cart']->getProducts();
		$productos = '';
		$id_cart = intval($params['cart']->id);

		//Activación de los idiomas del TPV
		$idiomas_estado = Tools::getValue('idiomas_estado', $this->idiomas_estado);
		if ($idiomas_estado=="si"){
			$ps_language = new Language(intval($cookie->id_lang));
			$idioma_web = $ps_language->iso_code;
			switch ($idioma_web) {
				case 'es':
				$idioma_tpv='001';
				break;
				case 'en':
				$idioma_tpv='002';
				break;
				case 'ca':
				$idioma_tpv='003';
				break;
				case 'fr':
				$idioma_tpv='004';
				break;
				case 'de':
				$idioma_tpv='005';
				break;
				case 'nl':
				$idioma_tpv='006';
				break;
				case 'it':
				$idioma_tpv='007';
				break;
				case 'sv':
				$idioma_tpv='008';
				break;
				case 'pt':
				$idioma_tpv='009';
				break;
				case 'pl':
				$idioma_tpv='011';
				break;
				case 'gl':
				$idioma_tpv='012';
				break;
				case 'eu':
				$idioma_tpv='013';
				break;
				default:
				$idioma_tpv='002';
			}
		}
		else {
			$idioma_tpv = '0';
		}

		foreach ($products as $product) {
			$productos .= $product['quantity'].' '.$product['name']."<br>";
		}
		$customer = new Customer((int)($cart->id_customer));
		$smarty->assign(array(
			'urltpv' => Tools::getValue('urltpv', $this->urltpv),
			'cantidad' => $cantidad,
			'moneda' => $moneda,
			'pedido' => $numpedido,
			'codigo' => $codigo,
			'tipopago' => Tools::getValue('tipopago', $this->tipopago),
			'terminal' => Tools::getValue('terminal', $this->terminal),
			'trans' => $trans,
			'merchantdata' => sha1($urltienda),
			'titular' => ($cookie->logged ? $cookie->customer_firstname.' '.$cookie->customer_lastname : false),
            'nombre' => Tools::getValue('nombre', $this->nombre),
			'urltienda' => $urltienda,
			'notificacion' => Tools::getValue('notificacion', $this->notificacion),
			'productos' => $productos,
			'UrlOk' => 'http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='. $id_cart .'&id_module='.(int)($this->id).'&id_order='.(int)($numpedido),
			'UrlKO' => 'http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/redsys/pago_error.php',
			'firma' => $firma,
			'idioma_tpv' => $idioma_tpv,
			'this_path' => $this->_path,
			'fee' => number_format($fee, 2, '.', '')
		));
		return $this->display(__FILE__, 'redsys.tpl');
    }
    public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return ;
		global $smarty;
		return $this->display(__FILE__, 'pago_correcto.tpl');
	}
}
?>