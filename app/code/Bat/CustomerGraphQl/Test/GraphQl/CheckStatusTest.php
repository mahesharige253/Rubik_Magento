<?php
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Tests\GraphQl;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class CustomerIsMobileAvailableTest
 * Bat\CustomerGraphQl\Tests\GraphQl
 */
class CheckStatusTest extends GraphQlAbstract
{
    public function testCustomerFound()
    {
        $query
        = <<<QUERY
        {
            getCustomerApplicationStatus(mobilenumber: "010 1919 1919")
            {
                heading
                message
                call_center_number
                rejected_fields
            }
        }
        QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertIsArray($response['getCustomerApplicationStatus']);
        $this->assertNotEmpty($response['getCustomerApplicationStatus']);
        $isMobileAvailable = $response['getCustomerApplicationStatus'];
        $this->assertArrayHasKey('heading', $isMobileAvailable);
        $this->assertNotEmpty($isMobileAvailable['heading']);
        $this->assertArrayHasKey('message', $isMobileAvailable);
        $this->assertNotEmpty($isMobileAvailable['message']);
    }

    public function testCustomerNotFound()
    {
        $query
        = <<<QUERY
        {
            getCustomerApplicationStatus(mobilenumber: "010 1234 1234")
            {
                heading
                message
                call_center_number
                rejected_fields
            }
        }
        QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertIsArray($response['getCustomerApplicationStatus']);
        $this->assertNotEmpty($response['getCustomerApplicationStatus']);
        $customerStatus = $response['getCustomerApplicationStatus'];
        $this->assertArrayHasKey('heading', $customerStatus);
        $this->assertNotEmpty($customerStatus['heading']);
        $this->assertArrayHasKey('message', $customerStatus);
    }
}
