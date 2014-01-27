
<?php

// Referencia producto, Precio Mayorista (costo), Stock, Precio de venta sin iva (PVP)
// el script funciona así:
// lee el fichero y accede a la base de datos con la REFERENCIA del producto
// actualiza stock, precio mayorista, precio de venta sin iva.
// Por www.cannabissearch.es 403

$dir = opendir('.'); //$dir = opendir('./updater');
echo "Conectamos a la bb.dd." . '<br>'; flush();
mysql_connect("localhost", "Usuario BD", "Contraseña BD") or die(mysql_error());
mysql_select_db("Nombre BD") or die(mysql_error());

// Como la primera fila son los nombres de las columnas:
$fila = 1;
// Tenemos que actualizar en dos tablas:
$update_table = "ps_product inner join ps_stock_available on (ps_product.id_product = ps_stock_available.id_product)";
//antes que nada, stock de todo a 0 para no tener descatalogados con stock:
mysql_query ("UPDATE $update_table SET ps_stock_available.quantity=0");
echo "Abrimos el fichero." . '<br>'; flush();
$handle = fopen("update.csv", "r");
$falta = fopen("falta.txt", "w");
echo "Recorremos el CSV..." . '<br>'; flush();
while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
$num = count($data);
echo "<p>" . '<br>'; flush();
echo "( $fila )" . '<br>'; flush();
$fila++;
for ($c = 0; $c < $num; $c++) {
	
	//Referencia producto //Numero de columna correspondiente a update.csv
	if ($c == 2) { 
		$reference = $data[$c];

		  echo $reference . " - Referencia Producto" . '<br>'; flush();
        $buscaid = mysql_query("SELECT id_product FROM ps_product WHERE reference='$reference'") or die (mysql_error()); //
        $id_product = mysql_result($buscaid, '0+i');
          echo $id_product . " - Referencia Producto" . '<br>'; flush();
    }
	//precio de compra //Numero de columna correspondiente a update.csv
    if ($c == 4) { 
	    $compra = $data[$c];
        mysql_query("UPDATE $update_table SET wholesale_price='$compra' WHERE reference='$reference'") or die(mysql_error());
        echo $compra . " - Coste actualizado tabla ps:_product" . '<br>'; flush();
        mysql_query("UPDATE ps_product_shop SET wholesale_price='$compra' WHERE id_product='$id_product'") or die(mysql_error());
        echo $compra . " - Coste actualizado tabla ps:_product_shop" . '<br>'; flush();
    }
    //Precio PVP //Numero de columna correspondiente a update.csv
    if ($c == 5) { 
        $price = $data[$c];
        mysql_query("UPDATE $update_table SET price='$price' WHERE reference='$reference'")or die(mysql_error());
        echo $price . " - PVP tabla ps_product actualizado" . '<br>'; flush();
        
		mysql_query("UPDATE ps_product_shop SET price='$price' WHERE id_product='$id_product'") or die(mysql_error());
        echo $price . " - PVP tabla ps_product.shop actualizado" . '<br>'; flush();
    }
	//Stock //Numero de columna correspondiente a update.csv
    if ($c == 6) { 
        $quantity = $data[$c];
        mysql_query("UPDATE $update_table SET ps_stock_available.quantity='$quantity' WHERE reference='$reference'") or die(mysql_error());
        echo $quantity . " - stock actualizado" . '<br>'; flush();
    }


echo "_____________________________________________________<p>";
}
}
fclose($handle);
fclose($falta);
//mandamos el archivo por mail
# Leer el contenido del archivo
$archivo = file_get_contents("falta.txt");
# De quien
$nombre_from = 'Alcualizacion precio y stock'; $email_from = "info@tudominio.com";
# Para quien
$email_to = "tuemail@tudomonio.com";
# Asunto
$asunto = "Articulos faltantes";
# Encabezado del E-mail
$header = "From: ".$nombre_from." <".$email_from.">\r\n";
# Envio del email
$ok = mail($email_to,$asunto,$archivo,$header);
# Si el email se envío, se imprime...
echo ($ok) ? "Enviado..." : "Falló el envío";

echo " <p>" . '<br>'; flush();
echo " - - - ACTUALIZACION COMPLETADA - - - <p>" . '<br>'; flush();
//Borro los archivos viejos para no tener problema al renombrar o descargar
//unlink('update.csv');

echo "Archivos temporales eliminados" . '<br>'; flush();
echo "Todo hecho" . '<br>'; flush();
?>