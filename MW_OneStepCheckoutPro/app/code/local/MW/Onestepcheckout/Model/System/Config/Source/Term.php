<?php
class MW_Onestepcheckout_Model_System_Config_Source_Term
{

    const STATUS_OPTIONAL	= 1;
    const STATUS_REQUIRED	= 2;
    const STATUS_HIDE	= 0;

    public function toOptionArray()
    {
        $agreement = $this->getAllTermConditions();
        return $agreement;
    }

    public function getAllTermConditions(){
        $models = Mage::getModel('checkout/agreement')->getCollection()->getData();
        $result = array();
        $result[0] = "Custom agreement";
        foreach($models as $model){
            $result[$model['agreement_id']] = $model['name'];
        }
        return $result;
//        return $model;
    }
    public function getTermById($id){
        $models = Mage::getModel('checkout/agreement')->getCollection()
            ->addFieldToFilter('agreement_id',$id)
            ->getData();
        return $models[0]['content'];
    }
}