<?php
namespace Bat\Customer\Model\Entity\Attribute\Source;

use Bat\CustomerConsentForm\Model\ConsentFormFactory;

class ConsentFormOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var ConsentFormFactory
     *
     */
    protected $consentformFactory;

    /**
     * Constructor
     *
     * @param ConsentFormFactory $consentformFactory
     */
    public function __construct(
        ConsentFormFactory $consentformFactory
    ) {
        $this->consentformFactory = $consentformFactory;
    }
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $this->_options = [];
        
        $data = $this->consentformFactory->create()->getCollection();
        foreach ($data as $consentdata) {
            $title = $consentdata['title'];
            $identifier = $consentdata['identifier'];
            $this->_options[] = ['label' => $title, 'value' => $identifier];
        }
        return $this->_options;
    }
}
