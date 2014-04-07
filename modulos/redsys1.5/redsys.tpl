<p class="payment_module">
	<a href="javascript:$('#redsys_form').submit();" title="{l s='Conectar con el TPV' mod='redsys'}" style="float:left"/>
		
		<img src="{$module_dir}tarjetas_redsys.png" alt="{l s='Conectar con el TPV' mod='redsys'}" style="float:left"/>
		
		{l s='Pago con tarjeta de cr&eacutedito/d&eacutebito a trav&eacutes de la plataforma Redsys' mod='redsys'}
	{if $fee>0}
		<br /><br />
		{l s='Este método de pago lleva asociado un recargo de ' mod='redsys'} <font color="red"/><b/>{convertPrice price=$fee}.</b/></font/> {l s='El recargo se sumará a los gastos de envío' mod='redsys'}
	</a>
	{/if}
</p>

<form action="{$urltpv}" method="post" id="redsys_form" class="hidden">	
	<input type="hidden" name="Ds_Merchant_Amount" value="{$cantidad}" />
    <input type="hidden" name="Ds_Merchant_Currency" value="{$moneda}" />
	<input type="hidden" name="Ds_Merchant_Order" value="{$pedido}" />
	<input type="hidden" name="Ds_Merchant_MerchantCode" value="{$codigo}" />
	<input type="hidden" name="Ds_Merchant_Terminal" value="{$terminal}" />
	<input type="hidden" name="Ds_Merchant_TransactionType" value="{$trans}" />
	<input type="hidden" name="Ds_Merchant_Titular" value="{$titular}" />
	<input type="hidden" name="Ds_Merchant_MerchantName" value="{$nombre}" />
  {if $notificacion>0}
	<input type="hidden" name="Ds_Merchant_MerchantURL" value="{$urltienda}" />
  {/if}
	<input type="hidden" name="Ds_Merchant_ProductDescription" value="{$productos}" />
	<input type="hidden" name="Ds_Merchant_UrlOK" value="{$UrlOk}" />
	<input type="hidden" name="Ds_Merchant_UrlKO" value="{$UrlKO}" />
	<input type="hidden" name="Ds_Merchant_MerchantSignature" value="{$firma}" />
	<input type="hidden" name="Ds_Merchant_ConsumerLanguage" value="{$idioma_tpv}" />
    <input type="hidden" name="Ds_Merchant_PayMethods" value="T" />
</form>