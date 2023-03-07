<?php

declare(strict_types=1);

namespace Karimi\CommissionTask\Tests\Service;

use PHPUnit\Framework\TestCase;
use Karimi\CommissionTask\Service\Commission;

class CommissionTest extends TestCase
{
    /**
     * @var Commission
     */
    private $commission;

    public function setUp()
    {
        $response = file_get_contents('https://developers.paysera.com/tasks/api/currency-exchange-rates');
        $apiRates = json_decode($response);

        $this->commission = new Commission($apiRates);
    }
    
    /**
     * Testing the commission fee calculation.
     *
     * @return void
     */
    public function testCommissionFeeCalc()
    {
        $path = __DIR__ . '/../../input.csv';
        // Expected results for "JPY": 130.869977, "USD": 1.129031.
        $expectation = [
            '0.60',
            '3.00',
            '0.00',
            '0.06',
            '1.50',
            '0.00',
            '0.70',
            '0.30',
            '0.30',
            '3.00',
            '0.00',
            '0.00',
            '8608'
        ];
        $this->assertEquals(
            $expectation,
            $this->commission->commissionFeeCalc($path)
        );
    }
}
