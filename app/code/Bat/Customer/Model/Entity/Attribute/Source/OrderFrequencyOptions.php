<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class OrderFrequencyOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $this->_options = [
            ['label' => __('Weekly'), 'value' => '0'],
            ['label' => __('Bi-Weekly'), 'value' => '1'],
            ['label' => __('4-Weekly'), 'value' => '2'],
        ];
        return $this->_options;
    }
}
