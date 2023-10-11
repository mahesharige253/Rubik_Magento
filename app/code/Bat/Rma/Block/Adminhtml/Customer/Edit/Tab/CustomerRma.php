<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\Rma\Block\Adminhtml\Customer\Edit\Tab;

/**
 * RMA Grid
 */
class CustomerRma extends \Magento\Rma\Block\Adminhtml\Customer\Edit\Tab\Rma
{
    /**
     * Prepare grid columns
     *
     * @return \Magento\Rma\Block\Adminhtml\Customer\Edit\Tab\Rma
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->addColumn(
            'batch_id',
            [
                'header' => __('Batch Id'),
                'index' => 'batch_id',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );
        return $this;
    }
}
