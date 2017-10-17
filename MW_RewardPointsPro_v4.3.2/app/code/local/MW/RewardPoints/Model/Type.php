<?php
    class MW_RewardPoints_Model_Type extends Varien_Object
    {
        const REGISTERING                          = 1;
        const SUBMIT_PRODUCT_REVIEW                = 2;
        const PURCHASE_PRODUCT                     = 3;
        const INVITE_FRIEND                        = 4;
        const FRIEND_REGISTERING                   = 5;
        const FRIEND_FIRST_PURCHASE                = 6;
        const RECIVE_FROM_FRIEND                   = 7;
        const CHECKOUT_ORDER                       = 8;
        const SEND_TO_FRIEND                       = 9;
        const EXCHANGE_TO_CREDIT                   = 10;
        const USE_TO_CHECKOUT                      = 11;
        const ADMIN_ADDITION                       = 12;
        const EXCHANGE_FROM_CREDIT                 = 13;
        const FRIEND_NEXT_PURCHASE                 = 14;
        const SUBMIT_POLL                          = 15;
        const SIGNING_UP_NEWLETTER                 = 16;
        const ADMIN_SUBTRACT                       = 17;
        const BUY_POINTS                           = 18;
        const SEND_TO_FRIEND_EXPIRED               = 19;
        const REFUND_ORDER                         = 20;
        const REFUND_ORDER_ADD_POINTS              = 21;
        const REFUND_ORDER_SUBTRACT_POINTS         = 22;
        const REFUND_ORDER_SUBTRACT_PRODUCT_POINTS = 23;
        const REFUND_ORDER_FREND_PURCHASE          = 24;
        const LIKE_FACEBOOK                        = 25;
        const CUSTOMER_BIRTHDAY                    = 26;
        const SPECIAL_EVENTS                       = 27;
        const EXPIRED_POINTS                       = 28;
        const SEND_FACEBOOK                        = 29;
        const CHECKOUT_ORDER_NEW                   = 30;
        const REFUND_ORDER_SUBTRACT_POINTS_NEW     = 31;
        const ORDER_CANCELLED_ADD_POINTS           = 32;
        const POSTING_TESTIMONIAL                  = 50;
        const CUSTOM_RULE                          = 51;
        const TAGGING_PRODUCT                      = 52;
        const COUPON_CODE                          = 53;

        static public function getTypeReward()
        {
            return array(
                self::REGISTERING           => Mage::helper('rewardpoints')->__('Signing Up'),
                self::SUBMIT_PRODUCT_REVIEW => Mage::helper('rewardpoints')->__('Posting Product Review'),
                self::INVITE_FRIEND         => Mage::helper('rewardpoints')->__('Referral Visitor (Friend click on referral link)'),
                self::FRIEND_REGISTERING    => Mage::helper('rewardpoints')->__('Referral Sign-Up'),
                self::FRIEND_FIRST_PURCHASE => Mage::helper('rewardpoints')->__('First Referral Purchase'),
                self::FRIEND_NEXT_PURCHASE  => Mage::helper('rewardpoints')->__('Next Referral Purchases'),
                self::SUBMIT_POLL           => Mage::helper('rewardpoints')->__('Voting Poll'),
                self::SIGNING_UP_NEWLETTER  => Mage::helper('rewardpoints')->__('Signing Up Newsletter'),
                self::LIKE_FACEBOOK         => Mage::helper('rewardpoints')->__('Facebook Like'),
                self::SEND_FACEBOOK         => Mage::helper('rewardpoints')->__('Facebook Share'),
                self::CUSTOMER_BIRTHDAY     => Mage::helper('rewardpoints')->__('Customer Birthday'),
                self::SPECIAL_EVENTS        => Mage::helper('rewardpoints')->__('Special Events'),
                // self::POSTING_TESTIMONIAL		=> Mage::helper('rewardpoints')->__('Posting Testimonial'),
                self::TAGGING_PRODUCT       => Mage::helper('rewardpoints')->__('Tagging Product'),
                self::CUSTOM_RULE           => Mage::helper('rewardpoints')->__('Custom Reward'),
                //self::COUPON_CODE		        => Mage::helper('rewardpoints')->__('Coupon Code')
            );
        }

        static public function getOptionArray()
        {
            return array(
                self::REGISTERING            => Mage::helper('rewardpoints')->__('Signing Up'),
                self::SUBMIT_PRODUCT_REVIEW  => Mage::helper('rewardpoints')->__('Posting Product Review'),
                self::PURCHASE_PRODUCT       => Mage::helper('rewardpoints')->__('Purchase Product'),
                self::INVITE_FRIEND          => Mage::helper('rewardpoints')->__('Referral Visitor (Friend click on referral link)'),
                self::FRIEND_REGISTERING     => Mage::helper('rewardpoints')->__('Referral Sign-Up'),
                self::FRIEND_FIRST_PURCHASE  => Mage::helper('rewardpoints')->__('First Referral Purchase'),
                self::RECIVE_FROM_FRIEND     => Mage::helper('rewardpoints')->__('Receive From Friend'),
                self::SEND_TO_FRIEND         => Mage::helper('rewardpoints')->__('Send Points To Friend'),
                self::CHECKOUT_ORDER         => Mage::helper('rewardpoints')->__('Checkout An Order'),
                self::CHECKOUT_ORDER_NEW     => Mage::helper('rewardpoints')->__('Checkout An Order'),
                self::EXCHANGE_TO_CREDIT     => Mage::helper('rewardpoints')->__('Exchange To Credit'),
                self::USE_TO_CHECKOUT        => Mage::helper('rewardpoints')->__('Use To Checkout'),
                self::ADMIN_ADDITION         => Mage::helper('rewardpoints')->__('Add By Admin'),
                self::ADMIN_SUBTRACT         => Mage::helper('rewardpoints')->__('Subtract By Admin'),
                self::EXCHANGE_FROM_CREDIT   => Mage::helper('rewardpoints')->__('Exchange From Credit'),
                self::FRIEND_NEXT_PURCHASE   => Mage::helper('rewardpoints')->__('Next Referral Purchases'),
                self::SUBMIT_POLL            => Mage::helper('rewardpoints')->__('Voting Poll'),
                self::SIGNING_UP_NEWLETTER   => Mage::helper('rewardpoints')->__('Signing Up Newsletter'),
                self::BUY_POINTS             => Mage::helper('rewardpoints')->__('Buy Reward Points'),
                self::SEND_TO_FRIEND_EXPIRED => Mage::helper('rewardpoints')->__('Send Points To Friend'),
                self::LIKE_FACEBOOK          => Mage::helper('rewardpoints')->__('Facebook Like'),
                self::SEND_FACEBOOK          => Mage::helper('rewardpoints')->__('Facebook Send Message'),
                self::CUSTOMER_BIRTHDAY      => Mage::helper('rewardpoints')->__('Customer Birthday'),
                self::SPECIAL_EVENTS         => Mage::helper('rewardpoints')->__('Special Events'),
                self::POSTING_TESTIMONIAL    => Mage::helper('rewardpoints')->__('Posting Testimonial'),
                self::TAGGING_PRODUCT        => Mage::helper('rewardpoints')->__('Tagging Product'),
                self::CUSTOM_RULE            => Mage::helper('rewardpoints')->__('Custom Reward'),
                self::COUPON_CODE            => Mage::helper('rewardpoints')->__('Coupon Code')
            );
        }

        static public function getLabel($type)
        {
            $options = self::getOptionArray();

            return $options[$type];
        }

        static public function getAddPointArray()
        {
            return array(
                self::REGISTERING,
                self::SUBMIT_PRODUCT_REVIEW,
                self::PURCHASE_PRODUCT,
                self::INVITE_FRIEND,
                self::FRIEND_REGISTERING,
                self::FRIEND_FIRST_PURCHASE,
                self::FRIEND_NEXT_PURCHASE,
                self::RECIVE_FROM_FRIEND,
                self::CHECKOUT_ORDER,
                self::CHECKOUT_ORDER_NEW,
                self::SUBMIT_POLL,
                self::SIGNING_UP_NEWLETTER,
                self::ADMIN_ADDITION,
                self::BUY_POINTS,
                self::REFUND_ORDER_ADD_POINTS,
                self::ORDER_CANCELLED_ADD_POINTS,
                self::LIKE_FACEBOOK,
                self::SEND_FACEBOOK,
                self::CUSTOMER_BIRTHDAY,
                self::SPECIAL_EVENTS,
                self::SEND_TO_FRIEND_EXPIRED,
                self::POSTING_TESTIMONIAL,
                self::TAGGING_PRODUCT,
                self::CUSTOM_RULE,
                self::COUPON_CODE

            );
        }

        static public function getSubtractPointArray()
        {
            return array(
                self::SEND_TO_FRIEND,
                self::EXCHANGE_TO_CREDIT,
                self::USE_TO_CHECKOUT,
                self::ADMIN_SUBTRACT,
                self::REFUND_ORDER_SUBTRACT_POINTS,
                self::REFUND_ORDER_SUBTRACT_PRODUCT_POINTS,
                self::REFUND_ORDER_FREND_PURCHASE,
                self::EXPIRED_POINTS

            );
        }

        static public function getTransactionDetail($type, $detail = null, $status = null, $is_admin = false)
        {
            $result = "";
            switch ($type)
            {
                case self::REGISTERING:
                    $result = Mage::helper('rewardpoints')->__("Reward for Registering");
                    break;
                case self::COUPON_CODE:
                    $detail_new = '';
                    $detail_new = Mage::getModel('rewardpoints/activerules')->load($detail)->getRuleName();
                    $result     = Mage::helper('rewardpoints')->__("%s", $detail_new);
                    break;
                case self::CUSTOM_RULE:
                    $detail_new = '';
                    $detail_new = Mage::getModel('rewardpoints/activerules')->load($detail)->getRuleName();
                    $result     = Mage::helper('rewardpoints')->__("%s", $detail_new);
                    break;
                case self::TAGGING_PRODUCT:
                    $product_id = $detail;
                    $object     = Mage::getModel('catalog/product')->load($product_id);
                    $url        = $object->getProductUrl();
                    if($is_admin)
                    {
                        $url = Mage::getUrl('adminhtml/catalog_product/edit', array('id' => $product_id));
                    }
                    $result = Mage::helper('rewardpoints')->__("Reward for Tagging Product <b><a href='%s'>%s</a></b>", $url, $object->getName());
                    break;
                case self::POSTING_TESTIMONIAL:
                    $result = Mage::helper('rewardpoints')->__("Posting Testimonial Id %s", $detail);
                    break;
                case self::SUBMIT_PRODUCT_REVIEW:
                    $detail = explode('|', $detail);
                    $review = Mage::getModel('review/review')->load($detail[0]);
                    $object = Mage::getModel('catalog/product');

                    if($review->getId())
                    {
                        $object->load($review->getEntityPkValue());
                    }
                    else
                    {
                        $object->load($detail[1]);
                    }

                    $url = $object->getProductUrl();
                    if($is_admin)
                    {
                        $url = Mage::getUrl('adminhtml/catalog_product/edit', array('id' => $object->getId()));
                    }
                    $result = Mage::helper('rewardpoints')->__("Reward for Posting Product Review <b><a href='%s'>%s</a></b>", $url, $object->getName());

                    break;
                case self::PURCHASE_PRODUCT:
                    $_detail    = explode('|', $detail);
                    $product_id = $_detail[0];
                    $object     = Mage::getModel('catalog/product')->load($product_id);
                    $url        = $object->getProductUrl();
                    if($is_admin)
                    {
                        $url = Mage::getUrl('adminhtml/catalog_product/edit', array('id' => $product_id));
                    }
                    /* order link */
                    $order     = Mage::getModel("sales/order")->loadByIncrementId($_detail[1]);
                    $order_url = Mage::getUrl('sales/order/view', array('order_id' => $order->getId()));
                    if($is_admin)
                    {
                        $order_url = Mage::getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId()));
                    }

                    $result = Mage::helper('rewardpoints')->__("Reward for purchasing product <b><a href='%s'>%s</a></b> in order <b><a href='%s'>#%s</a></b>", $url, $object->getName(), $order_url, $_detail[1]);
                    break;
                case self::INVITE_FRIEND:
                    $result = Mage::helper('rewardpoints')->__("Reward Referral Visitors: (<b>%s</b>)", $detail);
                    break;
                case self::FRIEND_REGISTERING:
                    $object = Mage::getModel('customer/customer')->load($detail);
                    $result = Mage::helper('rewardpoints')->__("Reward Referral Sign-Ups: (<b>%s</b>)", $object->getEmail());
                    break;
                case self::FRIEND_FIRST_PURCHASE:
                    $detail = explode('|', $detail);
                    $object = Mage::getModel('customer/customer')->load($detail[0]);

                    if($object->getEmail() == "")
                    {
                        $result = Mage::helper('rewardpoints')->__("Reward for the first purchase of friend");
                    }

                    else
                    {
                        $result = Mage::helper('rewardpoints')->__("Reward for the first purchase of friend (<b>%s</b>)", $object->getEmail());
                    }
                    break;
                case self::FRIEND_NEXT_PURCHASE:
                    $detail = explode('|', $detail);
                    $object = Mage::getModel('customer/customer')->load($detail[0]);
                    $result = Mage::helper('rewardpoints')->__("Reward for purchase of a friend (<b>%s</b>)", $object->getEmail());
                    break;
                case self::RECIVE_FROM_FRIEND:
                    $object = Mage::getModel('customer/customer')->load($detail);
                    $result = Mage::helper('rewardpoints')->__("Receive points from friend (<b>%s</b>)", $object->getEmail());
                    break;
                case self::SEND_TO_FRIEND:
                    $email = $detail;
                    if($status == MW_RewardPoints_Model_Status::COMPLETE)
                    {
                        $object = Mage::getModel('customer/customer')->load($detail);
                        $email  = $object->getEmail();
                    }

                    $result = Mage::helper('rewardpoints')->__("Send points to friend (<b>%s</b>)", $email);
                    break;
                case self::CHECKOUT_ORDER:
                    $order = Mage::getModel("sales/order")->loadByIncrementId($detail);
                    $url   = Mage::getUrl('sales/order/view', array('order_id' => $order->getId()));
                    if($is_admin)
                    {
                        $url = Mage::getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId()));
                    }
                    $result = Mage::helper('rewardpoints')->__("Reward for checkout order <b><a href='%s'>#%s</a></b>", $url, $detail);
                    break;
                case self::CHECKOUT_ORDER_NEW:
                    $_detail = explode('||', $detail);
                    $order   = Mage::getModel("sales/order")->loadByIncrementId($_detail[0]);
                    $url     = Mage::getUrl('sales/order/view', array('order_id' => $order->getId()));
                    if($is_admin)
                    {
                        $url = Mage::getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId()));
                    }
                    $result           = Mage::helper('rewardpoints')->__("Reward for checkout order <b><a href='%s'>#%s</a></b><br>", $url, $_detail[0]);
                    $_details         = array();
                    $_detail_rules    = array();
                    $_detail_products = array();
                    $_details         = unserialize($_detail[1]);
                    $_detail_rules    = $_details[1];
                    $_detail_products = $_details[2];

                    foreach ($_detail_rules as $_detail_rule)
                    {
                        $_detail_rule_child = explode('|', $_detail_rule);
                        $result .= Mage::helper('rewardpoints')->__("+%s points (%s) <br>", $_detail_rule_child[0], trim($_detail_rule_child[1]));
                    }
                    foreach ($_detail_products as $_detail_product)
                    {
                        $_detail_product_child = explode('|', $_detail_product);
                        $result .= Mage::helper('rewardpoints')->__("+%s points for product: %s <br>", $_detail_product_child[0], $_detail_product_child[1]);
                    }
                    break;
                case self::EXCHANGE_TO_CREDIT:
                    $result = Mage::helper('rewardpoints')->__("Exchange to %s credits", round($detail, 0));
                    break;
                case self::USE_TO_CHECKOUT:
                    $order = Mage::getModel("sales/order")->loadByIncrementId($detail);
                    $url   = Mage::getUrl('sales/order/view', array('order_id' => $order->getId()));
                    if($is_admin)
                    {
                        $url = Mage::getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId()));
                    }
                    $result = Mage::helper('rewardpoints')->__("Use to checkout order <b><a href='%s'>#%s</a></b>", $url, $detail);
                    break;
                case self::ADMIN_ADDITION:
                    $detail = explode('|', $detail);
                    if($detail[0] == '')
                    {
                        $detail[0] = 'Updated by Admin';
                    }
                    $result = Mage::helper('rewardpoints')->__("%s", $detail[0]);
                    break;
                case self::ADMIN_SUBTRACT:
                    $detail = explode('|', $detail);
                    if($detail[0] == '')
                    {
                        $detail[0] = 'Updated by Admin';
                    }
                    $result = Mage::helper('rewardpoints')->__("%s", $detail[0]);
                    break;
                case self::EXCHANGE_FROM_CREDIT:
                    $result = Mage::helper('rewardpoints')->__("Exchange from credit");
                    break;
                case self::SUBMIT_POLL:
                    $result = Mage::helper('rewardpoints')->__("Reward for Voting in a Poll");
                    break;
                case self::SIGNING_UP_NEWLETTER:
                    $result = Mage::helper('rewardpoints')->__("Reward for Signing up Newsletter");
                    break;
                case self::SEND_TO_FRIEND_EXPIRED:
                    $result = Mage::helper('rewardpoints')->__("The sendding points to friend(<strong>%s</strong>) was expired", $detail);
                    break;
                case self::ORDER_CANCELLED_ADD_POINTS:
                    $order = Mage::getModel("sales/order")->loadByIncrementId($detail);
                    $url   = Mage::getUrl('sales/order/view', array('order_id' => $order->getId()));
                    if($is_admin)
                    {
                        $url = Mage::getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId()));
                    }
                    $result = Mage::helper('rewardpoints')->__("Restore spent points for cancelled order <a href='%s'><strong>#%s</strong></a>", $url, $detail);
                    break;
                case self::REFUND_ORDER_ADD_POINTS:
                    $order = Mage::getModel("sales/order")->loadByIncrementId($detail);
                    $url   = Mage::getUrl('sales/order/view', array('order_id' => $order->getId()));
                    if($is_admin)
                    {
                        $url = Mage::getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId()));
                    }
                    $result = Mage::helper('rewardpoints')->__("Restore spent points for refunded order <a href='%s'><strong>#%s</strong></a>", $url, $detail);
                    break;
                case self::REFUND_ORDER_SUBTRACT_POINTS:
                    $order = Mage::getModel("sales/order")->loadByIncrementId($detail);
                    $url   = Mage::getUrl('sales/order/view', array('order_id' => $order->getId()));
                    if($is_admin)
                    {
                        $url = Mage::getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId()));
                    }
                    $result = Mage::helper('rewardpoints')->__("Subtract reward points for refunded order <a href='%s'><strong>#%s</strong></a>", $url, $detail);
                    break;
                case self::REFUND_ORDER_SUBTRACT_PRODUCT_POINTS:
                    $_detail    = explode('|', $detail);
                    $product_id = $_detail[0];
                    $object     = Mage::getModel('catalog/product')->load($product_id);
                    $url        = $object->getProductUrl();
                    if($is_admin)
                    {
                        $url = Mage::getUrl('adminhtml/catalog_product/edit', array('id' => $product_id));
                    }
                    $result = Mage::helper('rewardpoints')->__("Subtract earned points for product <b><a href='%s'>%s</a></b> (refunded)", $url, $object->getName());
                    break;
                case self::REFUND_ORDER_FREND_PURCHASE:
                    $detail = explode('|', $detail);
                    $object = Mage::getModel('customer/customer')->load($detail[0]);
                    $result = Mage::helper('rewardpoints')->__("Subtract earned points for purchase of friend (<b>%s</b>) (refunded)", $object->getEmail());
                    break;
                case self::LIKE_FACEBOOK:
                    $result = Mage::helper('rewardpoints')->__("Reward for Facebook Like: (<b><a href='%s'>Visit Link</a></b>)", $detail);
                    break;
                case self::SEND_FACEBOOK:
                    $result = Mage::helper('rewardpoints')->__("Reward for Facebook Send: (<b><a href='%s'>Visit Link</a></b>)", $detail);
                    break;
                case self::CUSTOMER_BIRTHDAY:
                    $result = Mage::helper('rewardpoints')->__("Reward for Your Birthdays");
                    break;
                case self::SPECIAL_EVENTS:
                    $result = Mage::helper('rewardpoints')->__("Reward for Special Events: (<b>%s</b>)", $detail);
                    break;
                case self::EXPIRED_POINTS:
                    if($detail == '')
                    {
                        $result = Mage::helper('rewardpoints')->__("Subtract earned points for expriring points");
                    }
                    else
                    {
                        $result = Mage::helper('rewardpoints')->__("Points expiration of transaction ID #%s", $detail);
                    }
                    break;

            }

            /*if($is_admin)
            {
                $result = str_replace('You','Customer',$result);
                $result = str_replace('Your','Customer\'s',$result);
            }*/

            return $result;
        }

        static public function getAmountWithSign($amount, $type)
        {
            $result = $amount;
            switch ($type)
            {
                case self::REGISTERING:
                case self::SUBMIT_PRODUCT_REVIEW:
                case self::PURCHASE_PRODUCT:
                case self::INVITE_FRIEND:
                case self::FRIEND_REGISTERING:
                case self::FRIEND_FIRST_PURCHASE:
                case self::FRIEND_NEXT_PURCHASE:
                case self::RECIVE_FROM_FRIEND:
                case self::CHECKOUT_ORDER:
                case self::CHECKOUT_ORDER_NEW:
                case self::SUBMIT_POLL:
                case self::SIGNING_UP_NEWLETTER:
                case self::ADMIN_ADDITION:
                case self::BUY_POINTS:
                case self::REFUND_ORDER_ADD_POINTS:
                case self::ORDER_CANCELLED_ADD_POINTS:
                case self::LIKE_FACEBOOK:
                case self::SEND_FACEBOOK:
                case self::CUSTOMER_BIRTHDAY:
                case self::SPECIAL_EVENTS:
                case self::SEND_TO_FRIEND_EXPIRED:
                case self::POSTING_TESTIMONIAL:
                case self::TAGGING_PRODUCT:
                case self::CUSTOM_RULE:
                case self::COUPON_CODE:
                    $result = "+" . $amount;
                    break;
                case self::SEND_TO_FRIEND:
                case self::EXCHANGE_TO_CREDIT:
                case self::USE_TO_CHECKOUT:
                case self::ADMIN_SUBTRACT:
                case self::REFUND_ORDER_SUBTRACT_POINTS:
                case self::REFUND_ORDER_SUBTRACT_PRODUCT_POINTS:
                case self::REFUND_ORDER_FREND_PURCHASE:
                case self::EXPIRED_POINTS:
                    $result = -$amount;
                    break;
            }

            return $result;
        }
    }