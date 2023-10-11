<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class RejectedFields extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Mobilenumber'), 'value' => 'mobilenumber'],
                ['label' => __('Name'), 'value' => 'name'],
                ['label' => __('Company Name'), 'value' => 'Company Name'],
                ['label' => __('Consent Form'), 'value' => 'Consent Form'],
                ['label' => __('Business License'), 'value' => 'Business License'],
                ['label' => __('Tobacco Seller License'), 'value' => 'Tobacco Seller License']
            ];
        }
        return $this->_options;
    }
}
