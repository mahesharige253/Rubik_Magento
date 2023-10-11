<?php
namespace Bat\RequisitionList\Controller\Adminhtml\RequisitionList;

use Bat\RequisitionList\Model\RequisitionListAdminFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Bat\RequisitionList\Model\RequisitionListItemAdminFactory;
use Bat\RequisitionList\Helper\Data;
use Bat\GetCartGraphQl\Helper\Data as QuantityHelper;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListItemAdmin as RequisitionListItemResourceModel;

/**
 * @class Save
 * Save RequisitionList Details
 */
class Save extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var RequisitionListAdminFactory
     */
    private $requisitionListAdminFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var QuantityHelper
     */
     protected $quantityHelper;

     /**
      * @var RequisitionListItemAdminFactory
      */
      protected $requisitionListItemAdminFactory;

    /**
     * @var RequisitionListItemResourceModel
     */
    protected $requisitionListItemResourceModel;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RequisitionListAdminFactory $requisitionListAdminFactory
     * @param RequisitionListItemAdminFactory $requisitionListItemAdminFactory
     * @param Data $helper
     * @param QuantityHelper $quantityHelper
     * @param RequisitionListItemResourceModel $requisitionListItemResourceModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RequisitionListAdminFactory $requisitionListAdminFactory,
        RequisitionListItemAdminFactory $requisitionListItemAdminFactory,
        Data $helper,
        QuantityHelper $quantityHelper,
        RequisitionListItemResourceModel $requisitionListItemResourceModel
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->requisitionListAdminFactory = $requisitionListAdminFactory;
        $this->requisitionListItemAdminFactory = $requisitionListItemAdminFactory;
        $this->helper = $helper;
        $this->quantityHelper = $quantityHelper;
        $this->requisitionListItemResourceModel = $requisitionListItemResourceModel;
        parent::__construct($context);
    }

    /**
     * Create New RequisitionList Page
     *
     * @return Redirect
     */
    public function execute()
    {
        try {
            $resultRedirect = $this->resultRedirectFactory->create();
            $newRequisitionList = true;
            $data = $this->getRequest()->getPostValue();
            $adminRequisitionListModel = $this->requisitionListAdminFactory->create();
            $allowRequisitionlistAdmin = $this->helper->getRequisitionlistAdmin();
            $bestSeller = $this->isBestSeller($data['best_seller']);
            $bestSellerStatus = false;
                        
            if (array_key_exists('entity_id', $data)) {
                $adminRequisitionListModel = $adminRequisitionListModel->load($data['entity_id']);
                $newRequisitionList = false;
                if (count($bestSeller) == 1 && isset($bestSeller[0])
                    && $bestSeller[0] != $data['entity_id']) {
                    $bestSellerStatus = true;
                }
            } else {
                if ($allowRequisitionlistAdmin <= count($adminRequisitionListModel->getCollection())) {
                    $this->messageManager->addErrorMessage(
                        __('Requisition list admin are allowed:'.$allowRequisitionlistAdmin.' or less than.')
                    );
                    return $resultRedirect->setPath('*/*/index', ['_current' => true]);
                }

                if ((count($bestSeller) == $data['best_seller'])
                && count($bestSeller) !=0) {
                    $bestSellerStatus = true;
                }
            }

            if ($bestSellerStatus) {
                $this->messageManager->addErrorMessage(__('Best seller requisition list admin is alreday exist.'));
                return $resultRedirect->setPath('*/*/index', ['_current' => true]);
            }

            if (isset($data['products']) && $data['products'] !='') {
                $productIds = explode('&', $data['products']);
                $selectedQtys = $this->getSelectedItem($productIds, $data['qty']);
                $selectedSkus = $this->getSelectedItem($productIds, $data['sku']);
                if ($this->validateQty($selectedQtys)) {
                    $this->messageManager->addErrorMessage(
                        __('Selected product with quantity is not allowed empty or 0.')
                    );
                    return $resultRedirect->setPath('*/*/index', ['_current' => true]);
                }
                if ($this->allowQty($selectedQtys)) {
                    $this->messageManager->addErrorMessage(
                        __('Allow product quantity minimum: '
                            .$this->quantityHelper->getMinimumCartQty().' and maximum: '
                            .$this->quantityHelper->getMaximumCartQty().'.')
                    );
                    return $resultRedirect->setPath('*/*/index', ['_current' => true]);
                }
            }
            $adminRequisitionListModel->setData('name', $data['name']);
            $adminRequisitionListModel->setData('description', $data['description']);
            $adminRequisitionListModel->setData('best_seller', $data['best_seller']);
            $adminRequisitionListModel->save();
            $requisitionListId = $adminRequisitionListModel->getEntityId();

            if (isset($data['products']) && $data['products'] !='') {
                if (!empty($requisitionListId) && $data['best_seller'] !=1) {
                    $ids = [];
                    $uncheck = false;
                    foreach ($selectedQtys as $key => $qty) {
                        $ids[] = $key;
                        $requisitionListItemModel = $this->requisitionListItemAdminFactory->create();
                        if ($itemId = $this->getItemId($requisitionListId, $key)) {
                            $requisitionListItemModel->load($itemId);
                            $uncheck = true;
                        }
                        $requisitionListItemModel->setData('requisition_list_id', $requisitionListId);
                        $requisitionListItemModel->setData('sku', $selectedSkus[$key]);
                        $requisitionListItemModel->setData('product_id', $key);
                        $requisitionListItemModel->setData('qty', $qty);
                        $requisitionListItemModel->save();
                    }
                    $this->unassignItem($requisitionListId, $ids);
                }
            }
              
            if ($newRequisitionList) {
                $this->messageManager->addSuccessMessage(__('The RequisitionList has been successfully created'));
            } else {
                $this->messageManager->addSuccessMessage(__('The RequisitionList details has been updated.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error while trying to save data'.$e->getMessage()));
        }
        return $resultRedirect->setPath('*/*/index', ['_current' => true]);
    }

     /**
      * Get Item Id
      *
      * @param int $requisitionListId
      * @param int $productId
      * @return int|null
      */
    protected function getItemId($requisitionListId, $productId)
    {
        $itemModel = $this->requisitionListItemAdminFactory->create();
        $itemId = $itemModel->getRequisitionListItemId($requisitionListId, $productId);
        if (!empty($itemId)) {
            return $itemId[0];
        }
    }

    /**
     * Unassigned Item
     *
     * @param int $requisitionListId
     * @param int $ids
     * @return
     */
    protected function unassignItem($requisitionListId, $ids)
    {
        $itemModel = $this->requisitionListItemAdminFactory->create();
        $unassignItem = $itemModel->getProductsByEntityId($requisitionListId, $ids);
        if (!empty($unassignItem) && !empty($ids)) {
            foreach ($unassignItem as $item) {
                $model = $this->requisitionListItemAdminFactory->create();
                $this->requisitionListItemResourceModel->load($model, $item);
                $this->requisitionListItemResourceModel->delete($model);
            }
        }
    }

    /**
     * Get Selected Item
     *
     * @param array $selectedItem
     * @param array $data
     * @return array
     */
    protected function getSelectedItem($selectedItem, $data)
    {
        $filteredArray = array_intersect_key($data, array_flip($selectedItem));
        return $filteredArray;
    }

    /**
     * Check Best Seller item
     *
     * @param int $bestSeller
     * @return int
     */
    public function isBestSeller($bestSeller)
    {
        $adminRequisitionListModel = $this->requisitionListAdminFactory->create();
        $adminRequisitionListModel = $adminRequisitionListModel->getBestSeller($bestSeller);
        return $adminRequisitionListModel;
    }

    /**
     * Validate Qty
     *
     * @param array $selectedQtys
     * @return boolean
     */
    public function validateQty($selectedQtys)
    {
        foreach ($selectedQtys as $key => $qty) {
            if (empty($qty) || $qty == 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Allow Qty
     *
     * @param array $selectedQtys
     * @return boolean
     */
    public function allowQty($selectedQtys)
    {
        $quantity = 0;
        foreach ($selectedQtys as $key => $qty) {
            $quantity = $quantity + $qty;
        }
        $quantityHelper = $this->quantityHelper;
        if ($quantity < $quantityHelper->getMinimumCartQty()) {
            return true;
        }
        if ($quantity > $quantityHelper->getMaximumCartQty()) {
            return true;
        }
        return false;
    }
}
