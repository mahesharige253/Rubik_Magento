<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class ApprovalStatus extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('New'), 'value' => 0],
                ['label' => __('Approved'), 'value' => 1],
                ['label' => __('Rejected'), 'value' => 2],
                ['label' => __('Resubmitted'), 'value' => 3]
            ];
        }
        return $this->_options;
    }
}
