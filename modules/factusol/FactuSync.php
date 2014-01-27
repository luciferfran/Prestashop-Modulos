<?php

/*
 * Script  Básico de Sincronizacion entre Factusol y PrestaShop
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
require_once( dirname( __FILE__ ).'/factusol.inc.php' );

class FactuSync
{
	// Array con los inserts a llevar a cabo
	private $inserts 	= array( );
	
	// Conexión a la base de datos
	private $db			= null;
	
	// Datos del fichero de actualizacion
	private $data		= null;
	
	// Buffer del log
	private static $log = null;
	
	// Se invoca al realizar unsa subida de datos generica desde Factusol
	public function Synchronize( )
	{
		self::ResetLog( );
		
		self::Log( ' ############### ' );
		self::Log( 'Importando datos desde Factusol' );
		
		if( FS_ECHO ) $this->InitHTML( );
		
		try
		{
			// Iniciamos la conexion a la bbdd
			self::Log( 'Conectando con la base de datos' );
			$this->InitDB( );
			
			// Leemos los datos del fichero en memoria y guardamos una copia
			self::Log( 'Leyendo el fichero de actualización' );
			$this->ReadSqlFile( );
			
			// Leemos los SQL statements y los ejecutamos
			self::Log( 'Cargando los cambios en la base de datos' );
			$this->ParseSql( );

			// Aplicamos los cambios realizados
			self::Log( 'Aplicando los cambios en los productos' );
			$this->ApplyChanges( );
			
			// Comprobamos si hay productos nuevos
			self::Log( 'Comprobando productos nuevos' );
			$this->GetNewProducts( );
		}
		catch( Exception $e )
		{
			self::Log( $e->getMessage( ), FS_LOG_EXCEPTION );
		}	
		
		if( FS_ECHO ) $this->CloseHTML( );
		
		// Enviamos el log por email
		self::ReportLog( );
	}
		
	// Recibe un objeto cliente y prepara lo inserta en el archivo de exportacion para factusol
	public function ExportClient( $client )
	{
		$line = 
		
		$mode = file_exists( FS_CLIENTS_FILE ) ? FILE_APPEND | LOCK_EX : 0;
		if( ! file_put_contents( $mode ) )
		{
			self::Log( FS_ERROR_CLIENT.': '.$client['email'], FS_LOG_ERROR );
			return false;
		}
		
		self::Log( 'Añadido cliente al fichero de exportación: '.$client['email'] );
		return true;
	}
		
	private function InitHTML( )
	{
		echo "<html><body style='font-family: \"Courier New\"; font-size: 12px'>";	
	}
	
	private function CloseHTML( )
	{
		echo "</body></html>";
	}
		
	// Mejor gestionar nosotros la conexion y los posibles errores
	private function InitDB( )
	{	
		// Conectamos al servidor mysql
		$this->db = mysql_connect( FSDB_SERVER_, FSDB_USER_, FSDB_PASSWD_ );
		if( ! $this->db )
			throw new Exception( FS_ERROR_DB_CONNECT );
			
		// Seleccionamos la base de datos
		if( ! mysql_select_db( FSDB_NAME_, $this->db ) )
			throw new Exception( FS_ERROR_DB_SELECT );
		
		// Inicimos el modo utf8
		if( ! mysql_query( 'SET NAMES \'utf8\'', $this->db ) )
			throw new Exception( FS_ERROR_DB_UTF8 );
	}
	
	private function ReadSqlFile( )
	{
		// Abrimos el fichero para leer
		$f = fopen( FS_SQL_FILE, 'r' );
		if( ! $f ) 
			throw new Exception( FS_ERROR_OPEN_SQL_FILE );
		
		// Cargamos el contenido del fichero
		$this->data = fread( $f, filesize( FS_SQL_FILE ) );
		
		// Cerramos el archivo
		fclose( $f );
		
		$backup = FS_SQL_FILE.date( 'YmdHis' );
		if( ! copy( FS_SQL_FILE, $backup ) )
			self::Log( FS_ERROR_BAKUP_FILE, FS_LOG_WARNING );
	}
		
	/* -----------------------------------------------------------------------------------------
   	$Id: module.fsw_populate.php v 1.0 2009-02-17 RedondoWS $
   	FSx Connector
   	(c) 2008 RWS (www.redondows.com / www.xcgarrido.de)
   	-----------------------------------------------------------------------------------------

   	osCommerce, Open Source E-Commerce Solutions
   	http://www.oscommerce.com
   	--------------------------------------------------------------------------------------- */
	private function ParseSql( )
	{
		// Comprobamos el separador de las instrucciones
		$sep = ";\r\n";
		if( strpos( $this->data, $sep, 0 ) === false ) $sep = ";\n";

		// Dividimos el contenido en instrucciones SQL
		$start = $end = $sql_count = $sql_ok = 0;
		while( strpos( $this->data, $sep, $start ) )
		{
			$end = strpos( $this->data, $sep, $start) + 1;
			$sql = substr( $this->data, $start, $end - $start );
			$sql = str_replace( "`PREC3PCL` Decimal(12,4) NOT NULL, `PREC3PCL` Decimal(12,4) NOT NULL", 
								"`PREC3PCL` Decimal(12,4) NOT NULL", $sql );
			$sql_count++;
			if( ! mysql_query( $sql, $this->db ) )
				self::Log( 'Error ejecutando la instrucción: '.$sql, FS_LOG_ERROR );
			else $sql_ok++;
				
			$start = $end;
		}
		
		$end = strpos( $this->data, ";", $start ) + 1;
		if( $sql = substr( $this->data, $start, $end - $start ) )
		{
			$sql = str_replace(	"`PREC3PCL` Decimal(12,4) NOT NULL, `PREC3PCL` Decimal(12,4) NOT NULL", 
								"`PREC3PCL` Decimal(12,4) NOT NULL", $sql );
			$sql_count++;
			if( ! mysql_query( $sql, $this->db ) )
				self::Log( 'Error ejecutando la instrucción: '.$sql, FS_LOG_ERROR );
			else $sql_ok++;
		}
		
		self::Log( "Se han ejecutado correctamente $sql_ok de $sql_count instrucciones", 
			( $sql_count == $sql_ok ) ? FS_LOG_INFO : FS_LOG_WARNING );
	}
	
	private function ApplyChanges( )
	{
		$sql = 'SELECT p.id_product, p.reference, p.price, p.active, p.available_for_order, p.quantity, 
				a.id_product_attribute, SUM(a.quantity) as quantity_comb,
				fa.CODART, fa.USTART, fa.TIVART,
				fr.PRELTA
				FROM '.FSDB_NAME_.'.'.FSDB_PREFIX_.'product p 
				LEFT JOIN '.FSDB_NAME_.'.'.FSDB_PREFIX_.'product_attribute a 
				 ON( a.id_product = p.id_product )
				LEFT JOIN '.FSDB_NAME_.'.'.FS_TABLE_ARTS.' fa
				 ON ( fa.CODART = p.reference )
				LEFT JOIN '.FSDB_NAME_.'.'.FS_TABLE_RATES.' fr 
				 ON ( fr.ARTLTA = p.reference AND fr.TARLTA = \''.FS_TARIFA.'\' )
				WHERE p.reference != \'\' 
				AND fa.CODART IS NOT NULL
				GROUP BY p.reference
				ORDER BY p.reference' ;
		
		$rs = mysql_query( $sql, $this->db );
		if( ! $rs )
			throw new Exception( FS_ERROR_QUERY_FAILED.': '.$sql );
	
		$total_products = mysql_num_rows( $rs );
		$total_ok = 0;

		self::Log( 'Se han obtenido '.$total_products.' productos a actualizar' );	

		while( $product = mysql_fetch_assoc( $rs ) ) 
		{
			if( $this->UpdateProduct( $product ) )
				$total_ok++;
		}
		
		self::Log( "Se han actualizado correctamente $total_ok de $total_products products", 
			( $total_products == $total_ok ) ? FS_LOG_INFO : FS_LOG_WARNING );
	}
	
	private function UpdateProduct( & $product )
	{
		$logbase = 	'Producto '.str_pad( $product['reference'], 10 ).': ';
		
		// Comprobamos que este en factusol, sino desactivamos
		if( is_null( $product['CODART'] ) && FS_SET_UNAVAILABLE )
		{
			// Comprobamos que este activo
			if( ! $product['active'] )
			{
				$log = $logbase.str_pad( 'Producto desactivado', 30 );
				self::Log( $log );
				return true;
			}
				
			// Comprobamos que sea un producto de tobacc
			if( ! preg_match( '/^\d+/i', $product['reference'] ) )
			{
				$log = $logbase.str_pad( 'Programa de puntos', 30 );
				self::Log( $log );
				return true;
			}
			
			// Es un prodcuto de tobacc, lo desactivamos
			$log = $logbase.str_pad( 'Desactivando producto', 30 );
			$ok  = $this->DeactivateProduct( $product['id_product'] );
			
			// Guardamos el resultado en el log	 
			$log .= $ok ? '[OK]   ' : '[ERROR]';	
			self::Log( $log, ( $ok ? FS_LOG_INFO : FS_LOG_ERROR ) );
			return $ok;
		}

		$price = $stock = true;
		
		if( FS_UPDATE_PRICE )
		{		
			// Esta en factusol; actualizamos precio
			$price = $this->UpdatePrice( $product, $info );
			$log   = $logbase.str_pad( 'Actualizando Precio', 30 );
			$log  .= $price ? '[OK]   ' : '[ERROR]';
			$log  .= ' ( '.$info.' ) ';
			self::Log( $log, ( $price ? FS_LOG_INFO : FS_LOG_ERROR ) );
		}
		
		if( FS_UPDATE_STOCK )
		{
			// Esta en factusol; actualizamos stock
			$info = '';
			$stock = $this->UpdateStock( $product, $info );
			$log   = $logbase.str_pad( 'Actualizando Stock', 30 );
			$log  .= $stock ? '[OK]   ' : '[ERROR]'; 
			$log  .= ' ( '.$info.' ) ';
			self::Log( $log, ( $stock ? FS_LOG_INFO : FS_LOG_ERROR ) );
		}
	
		return $price && $stock;
	}
		
	private function UpdatePrice( & $product, & $info = null )
	{
		// Coprobamos si ha cambiado el precio
		if( $product['PRELTA'] == $product['price'] )
		{
			$info = FS_INFO_NO_CHANGE;
			return true;
		}
		
		// Actualizamos la base de datos
		if( ! $this->SetPrice( $product['id_product'], $product['PRELTA'] ) )
		{
			$info = FS_ERROR_QUERY_FAILED;
			return false;
		}
		
		$info = 'Nuevo precio: '.$product['price'].' => '.$product['PRELTA'];
		return true;
	}
		
	private function UpdateStock( & $product, & $info = null )
	{
		// Comprobamos que no tenga combinaciones
		if( ! is_null( $product['id_product_attribute'] ) )
		{
			$info = FS_ERROR_COMBINATIONS.' Stock Total: '.$product['USTART'];
			return false;
		}
			
		// Coprobamos si ha cambiado el precio
		if( intval( $product['USTART'] ) == intval( $product['quantity'] ) )
		{
			$info = FS_INFO_NO_CHANGE;
			return true;
		}
		
		// Actualizamos la base de datos
		if( ! $this->SetStock( $product['id_product'], $product['USTART'] ) )
		{
			$info = FS_ERROR_QUERY_FAILED;
			return false;
		}
		
		$info = 'Nuevo stock: '.$product['quantity'].' => '.$product['USTART'];
		return true;
	}	
		
	private function SetPrice( $id_product, $price )
	{
		$sql = 'UPDATE '.FSDB_NAME_.'.'.FSDB_PREFIX_.'product SET price = '.floatval( $price ).' WHERE id_product = '.$id_product;
		if( FS_DEBUG ) 
		{ 
			self::Log( $sql, FS_LOG_WARNING );
			return true;
		}
		
		return mysql_query( $sql );
	}
	
	private function SetStock( $id_product, $quantity )	
	{
		$sql = 'UPDATE '.FSDB_NAME_.'.'.FSDB_PREFIX_.'product SET quantity = '.intval( $quantity ).' WHERE id_product = '.$id_product;
		if( FS_DEBUG ) 
		{ 
			self::Log( $sql, FS_LOG_WARNING );
			return true;
		}
		
		return mysql_query( $sql );
	}
			
	private function DeactivateProduct( $id_product )
	{
		$sql = 'UPDATE '.FSDB_NAME_.'.'.FSDB_PREFIX_.'product SET active = 0 WHERE id_product = '.$id_product;
		if( FS_DEBUG ) 
		{ 
			self::Log( $sql, FS_LOG_WARNING );
			return true;
		}
		
		return mysql_query( $sql );
	}
	
	private function GetNewProducts( )
	{		
		$sql = 'SELECT fa.CODART, fa.DESART, fa.USTART, fa.TIVART, fr.PRELTA, p.id_product, p.reference, p.price, p.active, p.quantity 
				FROM '.FSDB_NAME_.'.'.FS_TABLE_ARTS.' fa
				LEFT JOIN '.FSDB_NAME_.'.'.FSDB_PREFIX_.'product p ON ( fa.CODART = p.reference ) 
				LEFT JOIN '.FSDB_NAME_.'.'.FS_TABLE_RATES.' fr ON ( fa.CODART = fr.ARTLTA AND fr.TARLTA =  \''.FS_TARIFA.'\' ) 
				WHERE fa.CODART !=  \'\'
				AND p.id_product IS NULL';
	
		$rs = mysql_query( $sql, $this->db );
		if( ! $rs )
			throw new Exception( FS_ERROR_QUERY_FAILED.': '.$sql );
	
		$new_products = mysql_num_rows( $rs );
	
		self::Log( 'Hay '.$new_products.' productos nuevos en la importacion' );	

		while( $product = mysql_fetch_assoc( $rs ) ) 
		{	
			self::Log( 'Nueva referencia: ' . str_pad( $product['CODART'], 10 ).' '.str_pad( $product['DESART'], 50 ) .
			' ( Precio: '.str_pad( $product['PRELTA'], 10 ).' - Stock: '.$product['USTART'].' )' );
		}
	}
			
	// Guarda en el fichero log los datos del proceso de importacion / exportacion
	private static function Log( $msg, $type = FS_LOG_INFO )
	{
		$line = date( '[Y-m-d H:i:s]' );
		switch( $type )
		{
			case FS_LOG_INFO: 		$line .= '[INFO]'; break;
			case FS_LOG_WARNING: 	$line .= '[WARN]'; break;
			case FS_LOG_ERROR: 		$line .= '[ERRO]'; break;
			case FS_LOG_EXCEPTION: 	$line .= '[EXCE]'; break;
		}
		$line .= " $msg";
		
		// Mostramos por pantalla
		if( FS_ECHO ) echo str_replace( ' ', '&nbsp;', $line ).'<br>';
		
		// Guardamos en el log
		self::$log .= $line."\n";
		file_put_contents( FS_LOG_FILE, $line."\n", FILE_APPEND );
	}
	
	// Envia el log actual al email seleccionado
	private static function ReportLog( )
	{
		if( FS_REPORT_SENDER == '' ) return false;
		
		// Comprobamos si ha habido errores de algun tipo
		$errors  = preg_match( '/WARN/', self::$log );
		$errors |= preg_match( '/ERRO/', self::$log );
		$errors |= preg_match( '/EXCE/', self::$log );
		
		// Mostramos el resultado general en el asunto 
		$subject  = ( $errors ) ? '[ERRORES]' : '[OK]';
		$subject .= ' '.FS_REPORT_SUBJECT;
		 
		$headers = 'From: '. FS_REPORT_SENDER . "\r\n";
		@mail( FS_REPORT_EMAIL, $subject, utf8_decode(self::$log), $headers );
	}
	
	// Reinicializa el log
	private static function ResetLog( )
	{
		file_put_contents( FS_LOG_FILE, '' );
	}
}

// Lanzamos el script
$factuSync = new FactuSync( );
$factuSync->Synchronize( );

?>