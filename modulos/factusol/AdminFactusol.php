<?php

/*
 * Pestaña de adminstarción para el modulo de Factusol
 * 
 * @author  	= Hilari Moragrega
 * @version 	= 1.0 ( 2012-01-22 )
 * @copyright 	= Copyright (c) 2012 Hilari Moragrega 
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction, 
 * including without limitation the rights to use, copy, modify, merge, publish, distribute, 
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or 
 * substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING 
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, 
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

require_once( _PS_MODULE_DIR_.'factusol/factusol.inc.php' );

class AdminFactusol extends AdminTab
{
	private $module = 'factusol';
	public function __construct()
	{
		global $cookie, $_MODULES;
		$langFile = _PS_MODULE_DIR_.$this->module.'/'.Language::getIsoById(intval($cookie->id_lang)).'.php';
		if(file_exists($langFile))
		{
			require_once $langFile;
			$_MODULES = array_merge( $_MODULES, $_MODULE );
		}
		parent::__construct();
	}
	
	public function postProcess()
	{		
		if( isset( $_GET['updateConf'] ) )
		{
			$fs_debug	= false;
			$fs_echo 	= true;
			$fs_upstock = true;
			$fs_upprice = false;
			$fs_delete 	= false;
						
			$fs_report = isset( $_POST['fs_report'] ) ? $_POST['fs_report'] : 'Tuemail@pascual.com';  //cambiar email por el vuestro
			if( isset( $_POST['fs_debug'] ) && $_POST['fs_debug'] ) 		$fs_debug = true;
			if( isset( $_POST['fs_echo'] ) && ! $_POST['fs_echo'] ) 		$fs_echo = false;
			if( isset( $_POST['fs_upstock'] ) && ! $_POST['fs_upstock'] ) 	$fs_upstock = false;
			if( isset( $_POST['fs_upprice'] ) && $_POST['fs_upprice'] ) 	$fs_upprice = true;
			if( isset( $_POST['fs_delete'] ) && $_POST['fs_delete'] ) 		$fs_delete = true;
			$fs_ids = isset( $_POST['fs_ids'] ) ? intval( $_POST['fs_ids']) : 80000;
			$fs_tarifa = isset( $_POST['fs_tarifa'] ) ? intval( $_POST['fs_tarifa'] ) : '1';
						
			Configuration::updateValue( 'FS_REPORT_EMAIL', $fs_report );	
			Configuration::updateValue( 'FS_DEBUG', $fs_debug );	
			Configuration::updateValue( 'FS_ECHO', $fs_echo );
			Configuration::updateValue( 'FS_UPDATE_STOCK', $fs_upstock );
			Configuration::updateValue( 'FS_UPDATE_PRICE', $fs_upprice );
			Configuration::updateValue( 'FS_SET_UNAVAILABLE', $fs_delete );
			Configuration::updateValue( 'FS_TARIFA', $fs_tarifa );
			Configuration::updateValue( 'FS_CLIENTES_OFFSET', $fs_ids );
		}
	}
		
	public function display()
	{ 	
		global $currentIndex;
		
		// Datos sobre actualizaciones
		echo '
		<style> label{ text-align: left; width: 450px; } .ml5 {margin-left: 5px;} .big{font-size:18px; font-weight: bolder; color: black; float:right;}</style>
		<h2>'.$this->l('Update Data').'</h2>
		<fieldset>		
			<legend><img src="../img/t/AdminInformation.gif" alt="" />'.$this->l('Pending Clients and Orders').'</legend>
			<p>'.$this->l('Pending Files to download by Factusol').'</p>
			<br />
			<label>'.$this->l('New clients pending to download:').'<span class="big">'.self::getClientesPendientes( ).'</span></label>
			<label>'.$this->l('New orders pending to download:').'<span class="big">'.self::getPedidosPendientes( ).'</span></label>
			<div class="clear">&nbsp;</div>
		</fieldset>
		<br/>';
			
		// Catalogo
		$log = self::GetLogData( );
		echo '<fieldset>		
			<legend><img src="../img/t/AdminInformation.gif" alt="" />'.$this->l('Catalog').'</legend>
			<p>'.$this->l('Notes about the last importation process from Factusol to PrestaShop').'</p>
			<br />			
			<label>'.$this->l('Importation date:').'<span class="big">'.$log['date'].'</span></label>
			<label>'.$this->l('Updated Stocks:').'<span class="big" style="color: green">'.$log['up_stock'].'</span></label>
			<label>'.$this->l('Updated Prices:').'<span class="big" style="color: green">'.$log['up_price'].'</span></label>
			<label>'.$this->l('Prodcuts deactivated:').'<span class="big" style="color: grey">'.$log['up_deactivate'].'</span></label>
			<label>'.$this->l('Errors in log:').'<span class="big" style="color: red">'.$log['errors'].'</span></label>
			<div class="clear">&nbsp;</div>
			<a href="' . __PS_BASE_URI__ . 'modules/factusol/factusync.log" title="Descargar" target="_blank">
				<input type="button" value="'.$this->l('Download Log').'" class="button" style="cursor: pointer;"/>
			</a>
		</fieldset>
		<br/>';

	 	// Configuración
		$fs_report 	= Configuration::get( 'FS_REPORT_EMAIL' );
		$fs_debug 	= Configuration::get( 'FS_DEBUG' );
		$fs_echo 	= Configuration::get( 'FS_ECHO' );
		$fs_upstock = Configuration::get( 'FS_UPDATE_STOCK' );
		$fs_upprice = Configuration::get( 'FS_UPDATE_PRICE' );
		$fs_tarifa 	= Configuration::get( 'FS_TARIFA' );
		$fs_delete 	= Configuration::get( 'FS_SET_UNAVAILABLE' );
		$fs_ids 	= Configuration::get( 'FS_CLIENTES_OFFSET' );
	
		echo '
		<h2>'.$this->l('Configuration').'</h2>
		<form action="'.$currentIndex.'&updateConf'.$this->table.'=1&token='.$this->token.'" method="post" enctype="multipart/form-data">
		<fieldset>
			<legend><img src="../img/t/AdminInformation.gif" alt="" />'.$this->l('Variables').'</legend>
			<p>'.$this->l('Change the behaviour of the catalog update with the next variables').'</p>
			<br />
			<label>'.$this->l('Email to send the log with the importation results:').'</label>
			<div class="margin-form">
				<input type="text" name="fs_report" size="25" value="'.$fs_report.'"/>
			</div>
			<div class="clear">&nbsp;</div>
			<label>'.$this->l('Debug mode (Simulation):').'</label>
			<div class="margin-form">
				<input type="radio" name="fs_debug" value="1" '.($fs_debug ? 'checked' : '').'>'.$this->l('Yes').'</input>
				<input type="radio" name="fs_debug" value="0" '.(!$fs_debug ? 'checked' : '').' class="ml5">'.$this->l('No').'</input>
			</div>
			<div class="clear">&nbsp;</div>
			<label>'.$this->l('Show the imporation log on the browser once finished:').'</label>
			<div class="margin-form">
				<input type="radio" name="fs_echo" value="1" '.($fs_echo ? 'checked' : '').'>'.$this->l('Yes').'</input>
				<input type="radio" name="fs_echo" value="0" '.(!$fs_echo ? 'checked' : '').' class="ml5">'.$this->l('No').'</input>
			</div>
			<div class="clear">&nbsp;</div>
			<label>'.$this->l('Update stocks with Factusol information:').'</label>
			<div class="margin-form">
				<input type="radio" name="fs_upstock" value="1" '.($fs_upstock ? 'checked' : '').'>'.$this->l('Yes').'</input>
				<input type="radio" name="fs_upstock" value="0" '.(!$fs_upstock ? 'checked' : '').' class="ml5">'.$this->l('No').'</input>
			</div>
			<div class="clear">&nbsp;</div>
			<label>'.$this->l('Update prices using Factusol information:').'</label> 
			<div class="margin-form">
				<input type="radio" name="fs_upprice" value="1" '.($fs_upprice ? 'checked' : '').'>'.$this->l('Yes').'</input>
				<input type="radio" name="fs_upprice" value="0" '.(!$fs_upprice ? 'checked' : '').' class="ml5">'.$this->l('No').'</input>
			</div>
			<div class="clear">&nbsp;</div>
			<label>'.$this->l('Price rate number to use:').'</label>
			<div class="margin-form">
				<input type="text" name="fs_tarifa" size="2" value="'.$fs_tarifa.'"/>
			</div>
			<div class="clear">&nbsp;</div>
			<label>'.$this->l('Client ID Offset:').'</label>
			<div class="margin-form">
				<input type="text" name="fs_ids" size="10" value="'.$fs_ids.'"/>
			</div>
			<div class="clear">&nbsp;</div>
			<label>'.$this->l('Deactivate the products that are not exported by Factusol:').'</label> 
			<div class="margin-form">
				<input type="radio" name="fs_delete" value="1" '.($fs_delete ? 'checked' : '').'>'.$this->l('Yes').'</input>
				<input type="radio" name="fs_delete" value="0" '.(!$fs_delete ? 'checked' : '').' class="ml5">'.$this->l('No').'</input>
			</div>
			<div class="clear">&nbsp;</div>
			<input type="submit" value="'.$this->l('Update').'" class="button" style="cursor: pointer;"/>
		</fieldset>
		</form>';
	}
	
	public static function getClientesPendientes( )
	{
		$clientes = 0;
		foreach( scandir( FSM_CLIENTS_DIR ) as $f )
			if( preg_match( '/\d+\.txt/i', $f ) ) $clientes++;

		return $clientes;
	}
	
	public static function getPedidosPendientes( )
	{
		$pedidos = 0;
		foreach( scandir( FSM_ORDERS_DIR ) as $f )
			if( preg_match( '/\d+\.txt/i', $f ) ) $pedidos++;
			
		return $pedidos;
	}
	
	public static function GetLogData( )
	{
		$logdata = array( );

		$logdata['up_stock']		= 0;
		$logdata['up_price'] 		= 0;
		$logdata['up_deactivate'] 	= 0;
		$logdata['errors'] 			= 0;
		
		$logdata['date'] = date( 'd/m/Y', filemtime( FS_LOG_FILE ) );
		
		$f = fopen( FS_LOG_FILE, 'r' );
		if( ! $f ) return $logdata;
		
		while( ( $line = fgets( $f ) ) != false )
		{
			if( preg_match( '/Nuevo stock/i', $line ) ) 			$logdata['up_stock'] += 1;
			if( preg_match( '/Nuevo precio/i', $line ) )			$logdata['up_price'] += 1;
			if( preg_match( '/Desactivando producto/i', $line ) )	$logdata['up_deactivate'] += 1;
			if( preg_match( '/ERROR/i', $line )	)					$logdata['errors'] += 1;
		}
		
		fclose( $f );
		
		return $logdata;
	}
			
}
?>