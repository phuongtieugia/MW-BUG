<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    design
 * @package     base_default
 * @copyright   Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
?>
<?php $_order = $this->getOrder() ?>
<?php 
if ($_order): 
$orderid=$_order->getId();
$orders=Mage::getModel('onestepcheckout/onestepcheckout')->getCollection()->addFieldToFilter('sales_order_id',$orderid);
$class_orders=get_class($orders);	
$o ='';

foreach($orders as $order)	
{
	$o = $order; break;		
}
?>
<table cellspacing="0" cellpadding="0" border="0" width="650" style="border:1px solid #EAEAEA;">
    <thead>
        <tr>
            <th align="left" bgcolor="#EAEAEA" style="font-size:13px; padding:3px 9px"><?php echo $this->__('Item') ?></th>
            <th align="left" bgcolor="#EAEAEA" style="font-size:13px; padding:3px 9px"><?php echo $this->__('Sku') ?></th>
            <th align="center" bgcolor="#EAEAEA" style="font-size:13px; padding:3px 9px"><?php echo $this->__('Qty') ?></th>
            <th align="right" bgcolor="#EAEAEA" style="font-size:13px; padding:3px 9px"><?php echo $this->__('Subtotal') ?></th>
        </tr>
    </thead>

    <?php $i=0; foreach ($_order->getAllItems() as $_item): ?>
    <?php if($_item->getParentItem()) continue; else $i++; ?>
    <tbody<?php echo $i%2 ? ' bgcolor="#F6F6F6"' : '' ?>>
        <?php echo $this->getItemHtml($_item) ?>
    </tbody>
    <?php endforeach; ?>

    <tbody>
        <?php echo $this->getChildHtml('order_totals') ?>
    </tbody>
</table>
<?php if ($this->helper('giftmessage/message')->isMessagesAvailable('order', $_order, $_order->getStore()) && $_order->getGiftMessageId()): ?>
    <?php $_giftMessage = $this->helper('giftmessage/message')->getGiftMessage($_order->getGiftMessageId()); ?>
    <?php if ($_giftMessage): ?>
<br />
<table cellspacing="0" cellpadding="0" border="0" width="100%" style="border:1px solid #EAEAEA;">
    <thead>
        <tr>
            <th align="left" bgcolor="#EAEAEA" style="font-size:13px; padding:3px 9px"><strong><?php echo $this->__('Gift Message for this Order') ?></strong></th>
        </tr>
    </thead>

    <tbody>

        <tr>
            <td colspan="4" align="left" style="padding:3px 9px">
            <strong><?php echo $this->__('From:'); ?></strong> <?php echo $this->escapeHtml($_giftMessage->getSender()) ?>
            <br /><strong><?php echo $this->__('To:'); ?></strong> <?php echo $this->escapeHtml($_giftMessage->getRecipient()) ?>
            <br /><strong><?php echo $this->__('Message:'); ?></strong><br /> <?php echo $this->escapeHtml($_giftMessage->getMessage()) ?>
            </td>
        </tr>
    </tbody>
</table>
    <?php endif; ?>
<?php endif; ?>
<?php if($o):?>
<br>
<table cellspacing="0" cellpadding="0" border="0" width="650" style="border:1px solid #EAEAEA;">
    <thead>
        <tr>
            <th colspan="4" align="left" bgcolor="#EAEAEA" style="font-size:13px; padding:3px 9px"><?php echo $this->__('Delivery Infomation') ?></th>
        </tr>
    </thead>
    <tbody>
	<?php if($o->getMwCustomercommentInfo()!=""):?>
        <tr>
			<td colspan="2" align="left" style="padding:3px 9px"><?php echo $this->__('My comment for this order') ?></td>
			<td colspan="2" style="padding:3px 9px"><?php echo $o->getMwCustomercommentInfo(); ?></td>
		</tr>
	<?php endif; ?>
	<?php if($o->getMwCustomerGiftmessage()!=""):?>
        <tr>
			<td colspan="2" align="left" style="padding:3px 9px"><?php echo $this->__('Gift message') ?></td>
			<td colspan="2" style="padding:3px 9px"><?php echo $o->getMwCustomerGiftmessage(); ?></td>
		</tr>
	<?php endif; ?>
	<?php if($o->getMwDeliverydateDate()!=""):?>
		<tr>
			<td colspan="2" align="left" style="padding:3px 9px"><?php echo $this->__('Delivery Date') ?></td>
			<td colspan="2" style="padding:3px 9px"><?php echo $o->getMwDeliverydateDate(); ?></td>
		</tr>
	<?php endif; ?>
    </tbody>
</table>
<?php endif; ?>
<?php endif; ?>
