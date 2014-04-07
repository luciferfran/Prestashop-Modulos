<?php

/*
 * Includes y configuraciones globales para el modulo de Factusol
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

if( ! defined( '_PS_ROOT_DIR_' ) )
	require_once( '../../config/defines.inc.php' );
require_once( _PS_ROOT_DIR_.'/config/config.inc.php' );
require_once( _PS_ROOT_DIR_.'/classes/Configuration.php' );
######################## Base de datos
define('FS_WEBNAME', '' );  // Nombre de la web
define('FSDB_NAME_', '');
define('FSDB_SERVER_', 'localhost');
define('FSDB_USER_', '');
define('FSDB_PREFIX_', 'ps_');
define('FSDB_PASSWD_', '');
define('FSDB_TYPE_', 'MySQL');


######################## Factusol Module
define( 'FSM_DIR',				dirname(__FILE__) );
define( 'FSM_CLIENTS_DIR',		FSM_DIR.'/clientes/' );		// Directorio con los clientes de la web
define( 'FSM_ORDERS_DIR',		FSM_DIR.'/pedidos/' );		// Directorio con los pedidos creados en la web

define( 'FSM_IDS_OFFSET',		Configuration::get( 'FS_CLIENTES_OFFSET' ) );		// Offset con el que se crean los ids de cliente
define( 'FSM_ORDER_OFFSET',		8000000 );		// Offset con el que se crean los ids de pedido
define( 'EOL', 					"\n" );			// End Of Line

######################## Factusol Sync
define( 'FS_ECHO',					Configuration::get( 'FS_ECHO' ) ); 							// Mostrar el log por pantalla
define( 'FS_ECHO',					true ); 							// Mostrar el log por pantalla
define( 'FS_DIR',					dirname(__FILE__).'/' );
define( 'FS_SQL_FILE',				FS_DIR.'factusolweb.sql' );											// Fichero con la subida de datos de factusol
define( 'FS_LOG_FILE',				FS_DIR.'factusync.log' );

// Comportamiento
define( 'FS_DEBUG',					Configuration::get( 'FS_DEBUG' ) );															// En modo debug no se aplican cambios
define( 'FS_TARIFA',				Configuration::get( 'FS_TARIFA' ) );							// ID de la tarifa a aplicar
define( 'FS_SET_UNAVAILABLE',		Configuration::get( 'FS_SET_UNAVAILABLE' ) );					// Indica si los productos que no estan en factusol se deben marcar como no disponibles
define( 'FS_UPDATE_STOCK',			Configuration::get( 'FS_UPDATE_STOCK' ) );						// Indica si se debe actualizar el stock de los productos
define( 'FS_UPDATE_PRICE',			Configuration::get( 'FS_UPDATE_PRICE' ) );						// Indica si se debe actualizar el precio de los productos

// Tablas
define( 'FS_TABLE_ARTS',			'F_ART' );
define( 'FS_TABLE_RATES',			'F_LTA' );

// Configuracion de reporte
define( 'FS_REPORT_EMAIL',			Configuration::get( 'FS_REPORT_EMAIL' ) );		// Email donde mandar el log diario
define( 'FS_REPORT_SENDER',			'FactusolSync <factusync@example.com>' );
define( 'FS_REPORT_SUBJECT',		'Proceso de importacion Factusol -> Tobacc' );

// Errores
define( 'FS_ERROR_OPEN_SQL_FILE',	'No se ha podido abrir el fichero sql en '.FS_SQL_FILE );
define( 'FS_ERROR_BAKUP_SQL_FILE',	'No se ha podido realizar la copia de seguridad del fichero sql' );

define( 'FS_ERROR_DB_CONNECT',		'La conexión a la base de datos no se ha podido realizar' );
define( 'FS_ERROR_DB_SELECT',		'No se ha podido seleccionar la base de datos '.FSDB_NAME_ );
define( 'FS_ERROR_DB_UTF8',			'La base de datos no tiene soporte para nombres UTF-8' );

define( 'FS_ERROR_QUERY_FAILED',	'La ejecución de la instrucción ha fallado' );

define( 'FS_ERROR_IVA',				'IVA desconocido' );
define( 'FS_ERROR_COMBINATIONS',	'Multiples Combinaciones' );

define( 'FS_INFO_NO_CHANGE',		'Sin cambios' );

// Tipos de entradas en el log
define( 'FS_LOG_INFO',				0 );
define( 'FS_LOG_WARNING',			1 );
define( 'FS_LOG_ERROR',				2 );
define( 'FS_LOG_EXCEPTION',			3 );

?>