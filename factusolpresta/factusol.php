<?php

/*
 * Modulo de Sincronizacion entre Factusol y PrestaShop
 * 
 * Este script actualizara el precio y el stock de los productos en prestashop con los valores 
 * del programa factusol, los productos que no se importen en el fichero se dejaran con sin stock
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

if (!defined('_PS_VERSION_'))
	exit;
	
require_once( dirname( __FILE__ ).'/factusol.inc.php' );
	
class factusol extends Module
{
	public function __construct()
	{	
		$this->name = 'factusolpresta';
		$this->tab = 'migration_tools';
		$this->version = '1.0';
		$this->author = 'Francisco Piedras Pérez';
		//$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Factusol Synchronization');
		$this->description = $this->l('Share data between Factusol and Prestashop.');
	}

	public function install( )
	{
		if( ! parent::install( ) )
			return false;
		
		if( ! Configuration::updateValue('FS_SET_UNAVAILABLE', false ) ||
			! Configuration::updateValue('FS_UPDATE_DEBUG', true ) ||
			! Configuration::updateValue('FS_UPDATE_STOCK', true ) ||
			! Configuration::updateValue('FS_UPDATE_PRICE', false ) ||
			! Configuration::updateValue('FS_TARIFA', 1 ) ||
			! Configuration::updateValue('FS_CLIENTES_OFFSET', 80000 ) ||
			! Configuration::updateValue('FS_ECHO', true ) ||
			! Configuration::updateValue('FS_REPORT_EMAIL', 'dennis@tobacccat') )
			return false;
		
		$names = array_fill( 1, 8, 'Factusol');
		if( ! $this->installModuleTab('AdminFactusol', $names, 9 ) ) 
			return false;
			
		// Resgistramos el hook al crear un usuario nuevo	
		if( ! $this->registerHook( 'createAccount' ) )
			return false;
			
		// Resgistramos el hook al crear un pedido nuevo	
		if( ! $this->registerHook( 'orderConfirmation' ) )
			return false;

		return true;
	}
	
	public function uninstall()
	{
		if( ! parent::uninstall( ) ) return false;
		
		if( ! Configuration::deleteByName('FS_SET_UNAVAILABLE' ) ||
			! Configuration::deleteByName('FS_DEBUG' ) ||
			! Configuration::deleteByName('FS_UPDATE_STOCK' ) ||
			! Configuration::deleteByName('FS_UPDATE_PRICE' ) ||
			! Configuration::deleteByName('FS_TARIFA' ) ||
			! Configuration::deleteByName('FS_CLIENTES_OFFSET' ) ||
			! Configuration::deleteByName('FS_ECHO' ) ||
			! Configuration::deleteByName('FS_REPORT_EMAIL' ) )
			return false;
			 
		if( ! $this->uninstallModuleTab('AdminFactusol'))
			return false;
			
		return true;
	}
	
	public function hookCreateAccount( $params )
	{
		$newCustomer = $params['newCustomer'];
		if (!Validate::isLoadedObject($newCustomer))
			return false;

		if( ! isset( $newCustomer->id ) )
			return false;
					
		$cod = FSM_IDS_OFFSET + $newCustomer->id;  // 1 => 500001 
		$address = new Address( AddressCore::getFirstCustomerAddressId( $newCustomer->id ) );
		$telf = ( ( $address->phone ) ? $address->phone : $address->phone_mobile );
		
		$client  = $cod . '\\ ' . EOL;
		$client .= 'SNOMCFG:' . trim( $newCustomer->firstname.' '.$newCustomer->lastname ) . EOL;
		$client .= 'SDOMCFG:' . trim( $address->address1.' '.$address->address2 ) . EOL;
		$client .= 'SCPOCFG:' . trim( $address->postcode ) . EOL;
		$client .= 'SPOBCFG:' . trim( $address->city ) . EOL;
		$client .= 'SNIFCFG:' . trim( $address->dni ) . EOL;
		if( $telf ) 
			$client .= 'STELCFG:' . trim( $telf ) . EOL;
		$client .= 'SEMACFG:' . trim( $newCustomer->email ). EOL;
		$client .= 'Terminado:Alta Cliente' . EOL;
	
		$headers = "From: Modulo Factusol \r\n";
		@mail ( FS_REPORT_EMAIL , 'Nuevo cliente registrado en la web '.FS_WEBNAME, $client, $headers );			
	
		file_put_contents( FSM_CLIENTS_DIR . $newCustomer->id . '.txt' , $client );
		chmod( FSM_CLIENTS_DIR . $newCustomer->id . '.txt', 0776 );

		return true;
	}
	
	public function hookOrderConfirmation( $params )
	{		
		$total = $params['total_to_pay'];
		$objOrder = $params['objOrder'];
		
		$cliente = FSM_IDS_OFFSET + intval( $objOrder->id_customer );

		$total_sin_ge = floatval( $params['total_to_pay'] ) - floatval( $objOrder->total_shipping );

		$order  = 'TIPPCL:8' . EOL;
		$order .= 'CODPCL:' . $objOrder->id . EOL ;
		$order .= 'REFPCL:WEB ' . $objOrder->id . EOL;
		$order .= 'FECPCL:' . $objOrder->date_add . EOL;
		$order .= 'AGEPCL:0' . EOL; 					// Agente
		$order .= 'CLIPCL:' . $cliente . EOL; 			// Cliente de codigo
		$order .= 'DIRPCL:0' . EOL;
		$order .= 'TIVPCL:0' . EOL;						// Con Iva
		$order .= 'REQPCL:0' . EOL;						// Sin RE
		$order .= 'ALMPCL:' . EOL;
		$order .= 'NET1PCL:' . $objOrder->total_products . EOL;
		$order .= 'NET4PCL:0' . EOL;
		$order .= 'NET2PCL:0' . EOL;
		$order .= 'NET3PCL:0' . EOL;
		$order .= 'PDTO1PCL:0' . EOL;
		$order .= 'PDTO2PCL:0' . EOL;
		$order .= 'PDTO3PCL:0' . EOL;
		$order .= 'PDTO4PCL:0' . EOL;
		$order .= 'IDTO1PCL:0' . EOL;
		$order .= 'IDTO2PCL:0' . EOL;
		$order .= 'IDTO3PCL:0' . EOL;
		$order .= 'IDTO4PCL:0' . EOL;
		$order .= 'PPPA1PCL:0' . EOL;
		$order .= 'PPPA2PCL:0' . EOL;
		$order .= 'PPPA3PCL:0' . EOL;
		$order .= 'PPPA4PCL:0' . EOL;	
		$order .= 'IPPA1PCL:0' . EOL;

		$order .= 'IPPA2PCL:0' . EOL;
		$order .= 'IPPA3PCL:0' . EOL;
		$order .= 'IPPA4PCL:0' . EOL;
		$order .= 'PFIN1PCL:0' . EOL;
		$order .= 'PFIN2PCL:0' . EOL;
		$order .= 'PFIN3PCL:0' . EOL;
		$order .= 'PFIN4PCL:0' . EOL;
		$order .= 'IFIN1PCL:0' . EOL;
		$order .= 'IFIN2PCL:0' . EOL;
		$order .= 'IFIN3PCL:0' . EOL;
		$order .= 'IFIN4PCL:0' . EOL;
		$order .= 'BAS1PCL:' . $objOrder->total_products . EOL;

		$order .= 'BAS2PCL:0' . EOL;
		$order .= 'BAS3PCL:0' . EOL;
		$order .= 'BAS4PCL:0' . EOL;
		$order .= 'PIVA1PCL:18' . EOL;
		$order .= 'PIVA2PCL:8' . EOL;
		$order .= 'PIVA3PCL:4' . EOL;
		$order .= 'IIVA1PCL:' . ( $total_sin_ge - floatval( $objOrder->total_products_wt ) ) . EOL;
		$order .= 'IIVA2PCL:0' . EOL;
		$order .= 'IIVA3PCL:0' . EOL;
		$order .= 'PREC1PCL:4' . EOL;
		$order .= 'PREC2PCL:1' . EOL;
		$order .= 'PREC3PCL:0.5' . EOL;
		$order .= 'IREC1PCL:0' . EOL;
		$order .= 'IREC2PCL:0' . EOL;
		$order .= 'IREC3PCL:0' . EOL;
		$order .= 'TOTPCL:' . $total_sin_ge . EOL;

		$shipping = ( $objOrder->total_shipping / ( 1 + $objOrder->carrier_tax_rate / 100 ) ) . '+IVA = '.$objOrder->total_shipping;
		
		$order .= 'FOPPCL:03' . EOL;
		$order .= 'OB1PCL:' . EOL;
		$order .= 'OB2PCL:' . utf8_decode( 'Compruebe la dirección de entrega, gastos de envío: ' ). $shipping . EOL;
		$order .= 'PPOPCL:' . EOL;		//Pedido por  varchar(40)
		$order .= 'ESTPCL:0' . EOL;		// :0   Estado: Pendiente
		
		$i = 1;
		$cart = $params['cart'];
		foreach( $cart->getProducts( ) as $p )
		{
			$order .= 'TIPLPC:8' . EOL;
			$order .= 'CODLPC:' . $objOrder->id . EOL;
			$order .= 'POSLPC:' . $i++ . EOL;
			$order .= 'ARTLPC:' . $p['reference'] . EOL;
			$order .= 'DESLPC:' . $p['name'] . EOL;
			$order .= 'CANLPC:' . $p['quantity'] . EOL;
			$order .= 'DT1LPC:0' . EOL;
			$order .= 'PRELPC:' . $p['price'] . EOL;
			$order .= 'TOTLPC:' . ( floatval( $p['price'] ) * floatval( $p['quantity'] ) ). EOL;
			$order .= 'IVALPC:0' . EOL; 		// tipo iva 0->21, 1->10, 2->8	
			$order .= 'IINLPC:0' . EOL ;		// iva no incluido en el precio
		}
		
		$order .= 'DOCPAGO:' . EOL;
		$order .= 'STATUS:OK';
		
		$filename = 'pedidofactusolweb' . ( FSM_ORDER_OFFSET + $objOrder->id );
				
		file_put_contents( FSM_ORDERS_DIR . $filename . '.txt', $order );
		chmod( FSM_ORDERS_DIR . $filename . '.txt', 0776 );

		return true;
	}
	
	private function installModuleTab( $tabClass, $tabName, $idTabParent )
	{
		$tab = new Tab( );
		$tab->name = $tabName;
		$tab->class_name = $tabClass;
		$tab->module = $this->name;
		$tab->id_parent = $idTabParent;
		if( ! $tab->save( ) )
			return false;
		
		return true;
	} 
	
	private function uninstallModuleTab( $tabClass )
	{
		$idTab = Tab::getIdFromClassName( $tabClass );
		if( $idTab != 0 )
		{
			$tab = new Tab($idTab);
			$tab->delete( );
			return true;
		}
		
		return false;
	} 

}
