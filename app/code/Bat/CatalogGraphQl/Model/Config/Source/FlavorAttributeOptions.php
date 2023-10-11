<?php

namespace Bat\CatalogGraphQl\Model\Config\Source;

class FlavorAttributeOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $this->_options = [
                ['label' => __('--Please Select--'), 'value'=>''],
                ['label' => __('Mint Click'), 'value'=>'1'],
                ['label' => __('Tropic Click'), 'value'=>'2'],
                ['label' => __('Ruby Boost'), 'value'=>'3']
            ];
        return $this->_options;
    }
}
