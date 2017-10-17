<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class MW_Ddate_Model_Mysql4_Order_Grid_Collection extends Mage_Sales_Model_Mysql4_Order_Grid_Collection
{
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'increment_id') {
        	$field = 'main_table.' . $field;
        }
		if ($field == 'ddate') {
			$field = 'mwddate.' . $field;
		}

        return parent::addFieldToFilter($field, $condition);
    }
}
