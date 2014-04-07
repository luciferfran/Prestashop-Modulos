<?php

/*-----------------------------------------------------------------------------
Autor: Javier García
Autor E-Mail: cvirtual@redsys.es
Fecha: Marzo 2014
Version :2.0
-----------------------------------------------------------------------------*/

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');

$smarty->assign(array('this_path' => __PS_BASE_URI__));
$smarty->display(_PS_MODULE_DIR_.'redsys/pago_error.tpl');

include(dirname(__FILE__).'/../../footer.php');

?>
