<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Bat\Rma\Block\Adminhtml\Rma;

/**
 * RMA Grid
 */
class Grid extends \Magento\Rma\Block\Adminhtml\Rma\Grid
{
    /**
     * Prepare grid columns
     *
     * @return \Magento\Rma\Block\Adminhtml\Rma\Grid
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
