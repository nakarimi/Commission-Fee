<?php

declare(strict_types=1);

namespace Karimi\CommissionTask\Tests\Service;

use PHPUnit\Framework\TestCase;
use Karimi\CommissionTask\Service\Math;

class MathTest extends TestCase
{
    /**
     * @var Math
     */
    private $math;

    public function setUp()
    {
        $this->math = new Math(2);
    }

    /**
     * @param string $leftOperand
     * @param string $rightOperand
     * @param string $expectation
     *
     * @dataProvider dataProviderForAddTesting
     */
    public function testAdd(string $leftOperand, string $rightOperand, string $expectation)
    {
        $this->assertEquals(
            $expectation,
            $this->math->add($leftOperand, $rightOperand)
        );
    }
    public function testCommissionFeeCalc()
    {
        $path = __DIR__ . '/../../input.csv';
        $expectation = ['0.60', '3.00', '0.00', '0.06', '1.50', '0.00', '0.70', '0.30', '0.30', '3.00', '0.00', '0.00', '8612'];
        $this->assertEquals(
            $expectation,
            $this->math->commissionFeeCalc($path)
        );
    }

    public function dataProviderForAddTesting(): array
    {
        return [
            'add 2 natural numbers' => ['1', '2', '3'],
            'add negative number to a positive' => ['-1', '2', '1'],
            'add natural number to a float' => ['1', '1.05123', '2.05'],
        ];
    }
}
