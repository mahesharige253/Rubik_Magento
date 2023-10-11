<?php
namespace Bat\RequisitionList\Helper;

/**
 * @class Data
 *
 * Helper class for new products
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * Requisition List Customer config path
     */
    public const REQUISITIONLIST_ADMIN_PATH = 'requisitionlist_bat/requisitionlist/requisitionlist_admin';
    
    /**
     * Requisition List customer config path
     */
    public const REQUISITIONLIST_CUSTOMER_PATH = 'requisitionlist_bat/requisitionlist/requisitionlist_customer';

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var getScopeConfig
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_backendUrl = $backendUrl;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     * Get products tab Url in admin
     *
     * @return string
     */
    public function getProductsGridUrl()
    {
        return $this->_backendUrl->getUrl('requisitionlist/requisitionlist/products', ['_current' => true]);
    }

    /**
     * Get Requisitionlist Admin allow count
     *
     * @return string
     */
    public function getRequisitionlistAdmin()
    {
        return $this->getConfig(self::REQUISITIONLIST_ADMIN_PATH);
    }

    /**
     * Get Requisitionlist Customer allow count
     *
     * @return string
     */
    public function getRequisitionlistCustomer()
    {
        return $this->getConfig(self::REQUISITIONLIST_CUSTOMER_PATH);
    }

    /**
     * Get Config path
     *
     * @param string $path
     * @return string|int
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
