<?php
include(dirname(__FILE__).'/../config/config.inc.php');

echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
    <title>Prestahop Password Recovery</title>
    <link media="all" type="text/css" rel="stylesheet" href="../themes/prestashop/css/global.css" />
</head>
<body>
<div align="center">
    <div style="width:558px;">
        <a href="http://www.webbax.ch"><img src="logo.jpg" /></a>
        <h1 style="background:none">Réinitialiser le mot de passe Administrateur</h1>
        <div style="border:1px solid #dedfe0;padding:20px 0 20px">';
            if(Tools::isSubmit('updatePassword')){
                $email = Tools::getValue('email');
                $new_password = Tools::getValue('password');
                $new_password_encrypt = Tools::encrypt(Tools::getValue('password'));
                if(empty($new_password)){
                    echo '<div style="background-color:#f9e0e5;padding:10px;">Veuillez indiquer un nouveau mot de passe</div><br/>';
                }else{
                    Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'employee` SET `passwd`="'.pSQL($new_password_encrypt).'" WHERE `email`="'.pSQL($email).'"');
                    echo '<div style="background-color:#c8f3d0;padding:10px;">Le nouveau mot de passe pour le compte '.$email.' est à présent : <strong>'.$new_password.'</strong></div><br/>';
                }
            }

            $employees = Db::getInstance()->ExecuteS('SELECT * FROM '._DB_PREFIX_.'employee');
            echo '<table class="table" style="font-size:12px;">';
            foreach($employees as $employe){
            echo '<tr>
                    <td>'.$employe['firstname'].'</td>
                    <td>'.$employe['lastname'].'</td>
                    <td>'.$employe['email'].'<td></td>
                    <td><form method="post" action="">
                        <input type="hidden" name="email" value="'.$employe['email'].'"/>
                        <input type="text" name="password" />
                        <input type="submit" value="Modifier le mot de passe" name="updatePassword" />
                        </form>
                    </td>
                </tr>';
            }
            echo '</table>
        </div>
        <br/>
        Cet utilitaire est fourni gratuitement par <a href="http://www.webbax.ch">webbax</a>
    </div>
</div>
</body>
</html>';
?>