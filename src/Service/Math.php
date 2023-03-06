<?php

declare(strict_types=1);

namespace Karimi\CommissionTask\Service;

class Math
{
    private $scale;

    public function __construct(int $scale)
    {
        // var_dump('sdfdsfsd');
        $this->commissionFeeCalc();
        $this->scale = $scale;
    }

    public function add(string $leftOperand, string $rightOperand): string
    {
        return bcadd($leftOperand, $rightOperand, $this->scale);
    }

    // Main function for commission fee calculation.
    public function commissionFeeCalc()
    {
        $fees = [];
        $file = fopen(__DIR__.'/../../input.csv', 'r');
        while (($transaction = fgetcsv($file)) !== false) {
            // Check which type of transaction it is? deposit or withdraw.
            if ($transaction[3] === 'deposit') {
                $fees[] = $this->deposit($transaction);
            } else {
                $fees[] = $this->withdraw($transaction);
            }
        }
        var_dump($fees);
        return 0;
    }

    public function deposit(array $transaction): float
    {
        // All deposits are charged 0.03% of deposit amount.
        $amount = $transaction[4];
        $commissionFee = $amount * 0.03 / 100;

        return $commissionFee;
    }

    public function withdraw(array $transaction): float
    {
        // There are different calculation rules for withdraw of private and business clients.
        $amount = $transaction[4];
        if ($transaction[2] === 'private') {
            // Private Clients, Commission fee - 0.3% from withdrawn amount.
            // 1000.00 EUR for a week (from Monday to Sunday) is free of charge

            $commissionFee = $amount * 0.3 / 100;
        } else {
            // Business Clients, Commission fee - 0.5% from withdrawn amount.
            $commissionFee = $amount * 0.5 / 100;
        }

        return $commissionFee;
    }
}
