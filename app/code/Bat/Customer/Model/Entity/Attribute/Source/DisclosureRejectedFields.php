<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class DisclosureRejectedFields extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Account Closing Date'), 'value' => 'account_closing_date'],
                ['label' => __('Bank Account Card'), 'value' => 'bank_account_card'],
                ['label' => __('Consent Form'), 'value' => 'Consent Form']
            ];
        }
        return $this->_options;
    }
}
