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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    design
 * @package     base_default
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
?>
<title><?php echo $this->getTitle() ?></title>
<meta http-equiv="Content-Type" content="<?php echo $this->getContentType() ?>" />
<meta name="description" content="<?php echo htmlspecialchars($this->getDescription()) ?>" />
<meta name="keywords" content="<?php echo htmlspecialchars($this->getKeywords()) ?>" />
<meta name="robots" content="<?php echo htmlspecialchars($this->getRobots()) ?>" />

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

<meta name="HandheldFriendly" content="true"/>

<link rel="icon" href="<?php echo $this->getSkinUrl('favicon.ico') ?>" type="image/x-icon" />
<link rel="shortcut icon" href="<?php echo $this->getSkinUrl('favicon.ico') ?>" type="image/x-icon" />

<script type="text/javascript" src="http://yastatic.net/jquery/1.8.3/jquery.min.js"></script>
<script type="text/javascript" src="http://yastatic.net/jquery-ui/1.8.23/jquery-ui.min.js"></script>
<script type="text/javascript">jQuery.noConflict();</script>
<script type="text/javascript" src="http://yastatic.net/prototype/1.7.0.0/prototype.min.js"></script>

<script type="text/javascript">
//<![CDATA[
    var BLANK_URL = '<?php echo $this->helper('core/js')->getJsUrl('blank.html') ?>';
    var BLANK_IMG = '<?php echo $this->helper('core/js')->getJsUrl('spacer.gif') ?>';
    var SKIN_URL = '<?php echo $this->helper('core/js')->getJsSkinUrl('') ?>';
//]]>
</script>
<script type="text/javascript">
var mwfrg = '<?php echo Mage::getUrl().'freegift/cart/loadtotal';?>';
</script>

<?php echo $this->getCssJsHtml() ?>
<?php echo $this->getChildHtml() ?>
<?php echo $this->helper('core/js')->getTranslatorScript() ?>
<?php echo $this->getIncludes() ?>
