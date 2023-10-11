<?php
namespace Bat\BannerApi\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\BannerApi\Model\Resolver\DataProvider\BannerData;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Banner implements ResolverInterface
{
    /**
     * @var BannerData
     */
    private $bannerData;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @inheritdoc
     */
    public function __construct(
        BannerData $bannerData,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->bannerData = $bannerData;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $enabledStatus = $this->_scopeConfig->getValue("bannerslider/general/enable");
        if ($enabledStatus) {
            $BannerDataResult = $this->bannerData->getRecords();
            if (count($BannerDataResult) > 0) {
                $BannerData = [];
                $imgUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                foreach ($BannerDataResult as $data) {
                    $arr = [
                        'image_name' => base64_encode($imgUrl . $data['imagename']),
                        'banner_title' => $data['title'],
                        'button_title' => $data['buttontitle'],
                        'url_key' => $data['url_key'],
                        'position' => $data['position']
                    ];
                    $BannerData[] = $arr;
                }
                return $BannerData;
            } else {
                throw new GraphQlNoSuchEntityException(__('There is no homepage banner data'));

            }
        } else {
            throw new GraphQlNoSuchEntityException(__('Home page banner is Disabled in admin'));
        }
    }
}
