{capture name=path}{l s='Payment ERROR' mod='redsys'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<div class="cms"  style="min-height: 100px; margin-top: 22px;">
<img src="{$this_path}modules/redsys/error.gif" alt="Error in payment" longdesc="Error in payment" /></td></tr><tr>
<h2 style="font-style: normal;">{l s='Your credit card payment could not be accomplished' mod='redsys'}</h2><br />
<p>
{l s='We are sorry, but your payment has not been successfully accomplished. You can try again or choose another payment method. Remember that you can only use Visa and Mastercard credit cards, and Maestro debit cards as well (Spain only).' mod='redsys'}
</p>
<br/>
<p>
{l s='There are several reasons for this to happen:' mod='redsys'}
	<ul>
		<li>{l s='You mistook any of the digits of your credit card. Make sure you introduce them well.' mod='redsys'}</li>
		<li>{l s='Make sure your credit card has not expired and is valid. Maestro debit cards, for example, are only valid in Spain' mod='redsys'}</li>
		<li>{l s='There has been a problem with our payment gateway provider.' mod='redsys'}</li>
	<ul>
</p>
<br/>
<p>
{l s='In any case, you can contact us by mail or by phone and we will try to fix your problem together.' mod='redsys'}
</P>
<br />

<a href="{$base_dir_ssl}order.php?step=3" title="{l s='Pagos'}" style="text-transform: uppercase; border: 1px solid green; background-color: green; font-size: 13px; font-weight: bold; color: white; padding: 5px; float: right; margin-top: 20px;" title="Pagos" {$this_path}order.php?step=3">{l s='Try again' mod='redsys'}</a>

</div>