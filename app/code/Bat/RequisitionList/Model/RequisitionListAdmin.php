<?php
namespace Bat\RequisitionList\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * RequisitionListAdmin Model
 *
 */
class RequisitionListAdmin extends AbstractModel
{
    
    /**
     * RequisitionListAdmin
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Bat\RequisitionList\Model\ResourceModel\RequisitionListAdmin::class);
    }

    /**
     * Return new products
     *
     * @param int $bestSeller
     * @return array
     */
    public function getBestSeller($bestSeller)
    {
        $tbl = $this->getResource()->getTable('requisition_list_admin');
         $select = $this->getResource()->getConnection()->select()->from(
             $tbl,
             ['entity_id']
         )
        ->where(
            "best_seller = ?",
            $bestSeller
        );
        return $this->getResource()->getConnection()->fetchCol($select);
    }
}
