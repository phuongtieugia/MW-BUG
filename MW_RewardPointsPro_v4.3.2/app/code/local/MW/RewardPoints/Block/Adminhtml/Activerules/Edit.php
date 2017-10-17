<?php

    class MW_RewardPoints_Block_Adminhtml_Activerules_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
    {
        public function __construct()
        {
            parent::__construct();

            $this->_objectId   = 'rule_id';
            $this->_blockGroup = 'rewardpoints';
            $this->_controller = 'adminhtml_activerules';

            $this->_updateButton('save', 'label', Mage::helper('rewardpoints')->__('Save Rule'));
            $this->_removeButton('delete');
            //$this->_updateButton('delete', 'label', Mage::helper('affiliate')->__('Delete Member'));

            $this->_addButton('saveandcontinue', array(
                'label'   => Mage::helper('adminhtml')->__('Save And Continue Edit'),
                'onclick' => 'saveAndContinueEdit()',
                'class'   => 'save',
            ), -100);
            $edit = $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id')));
            //editForm.submit('".$edit."'+'back/edit/');
            $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('member_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'member_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'member_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit('" . $edit . "'+'back/edit/');
            }
            document.observe('dom:loaded', function () {
            		if($('default_expired').checked == false) {
            			if($('expired_day')) $('expired_day').up(1).show();
            		}else {
            			if($('expired_day')) $('expired_day').up(1).hide();
            		}
            			
            		$('default_expired').observe('click', function () {
            			if($('default_expired').checked == false) {
            				if($('expired_day')) $('expired_day').up(1).show();
            			}else {
            				if($('expired_day')) $('expired_day').up(1).hide();
            			}
            		});
            		if($('type_of_transaction').value == 6 || $('type_of_transaction').value == 14){
            				if($('reward_point').hasClassName('validate-digits')== true)$('reward_point').removeClassName('validate-digits');
							if($('note_reward_point')) $('note_reward_point').show();
            		}else{
            			if($('reward_point').hasClassName('validate-digits')== false)$('reward_point').addClassName('validate-digits');
            			if($('note_reward_point')) $('note_reward_point').hide();
            			
            			if($('type_of_transaction').value == 27){
            				if($('comment')) $('comment').up(1).show();
            				if($('comment')) $('date_event').up(1).show();
            			}else{
            				if($('comment')) $('comment').up(1).hide();
            				if($('comment')) $('date_event').up(1).hide();
            			}
            		}
            		
            		if($('type_of_transaction').value == 51 ){
            			if($('coupon_code')) $('coupon_code').up(1).show();
            		}else{
            			if($('coupon_code')) $('coupon_code').up(1).hide();
            		}
            		
            		$('type_of_transaction').observe('change', function () {
            		
            			if($('type_of_transaction').value == 51 ){
            				if($('coupon_code')) $('coupon_code').up(1).show();
	            		}else{
	            			if($('coupon_code')) $('coupon_code').up(1).hide();
	            		}
            		
            			if($('type_of_transaction').value == 6 || $('type_of_transaction').value == 14){
            				if($('reward_point').hasClassName('validate-digits')== true)$('reward_point').removeClassName('validate-digits');
							if($('note_reward_point')) $('note_reward_point').show();
							
            			}else{
            				if($('reward_point').hasClassName('validate-digits')== false)$('reward_point').addClassName('validate-digits');
            				if($('note_reward_point')) $('note_reward_point').hide();
            				
            				if($('type_of_transaction').value == 27){
	            				if($('comment')) $('comment').up(1).show();
	            				if($('comment')) $('date_event').up(1).show();
	            			}else{
	            				if($('comment')) $('comment').up(1).hide();
	            				if($('comment')) $('date_event').up(1).hide();
	            			}
            				
                		}
            			
				}); 
			}); 
        ";
        }

        public function getHeaderText()
        {
            if(Mage::registry('data_activerules') && Mage::registry('data_activerules')->getId())
            {
                return Mage::helper('rewardpoints')->__("Edit Rule '%s'", $this->htmlEscape(Mage::registry('data_activerules')->getRuleName()));
            }
            else
            {
                return Mage::helper('rewardpoints')->__('New Rule');
            }

        }
    }