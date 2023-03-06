<?php

declare(strict_types=1);

namespace Karimi\CommissionTask\Service;

class Math
{
    private $scale;
    private $withdrawPerWeek;

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
        $file = fopen(__DIR__ . '/../../input.csv', 'r');
        while (($transaction = fgetcsv($file)) !== false) {
            // Check which type of transaction it is? deposit or withdraw.
            if ($transaction[3] === 'deposit') {
                $fees[] = $this->feeFormater($this->deposit($transaction));
            } else {
                $fees[] = $this->feeFormater($this->withdraw($transaction));
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
        $rate = $this->checkRate($transaction);

        if ($transaction[2] === 'private') {
            $chargeStatus = $this->freeCharge($transaction);
            if ($chargeStatus['isFree']) {
                $commissionFee = 0;
            } else {
                // Private Clients, Commission fee - 0.3% from withdrawn amount.
                // 1000.00 EUR for a week (from Monday to Sunday) is free of charge
                $commissionFee = ($chargeStatus['amount'] / $rate) * 0.3 / 100;
            }
        } else {
            // Business Clients, Commission fee - 0.5% from withdrawn amount.
            $commissionFee = ($amount / $rate) * 0.5 / 100;
        }

        return $commissionFee;
    }

    public function checkRate($transaction)
    {
        $rate = 1;
        if ($transaction[5] != "EUR") {
            if ($transaction[5] == "JPY") {
                $rate = 129.53;
            } else {
                $rate = 1.1497;
            }
        }
        return $rate;
    }
    public function freeCharge($transaction)
    {
        $year = date('Y', strtotime($transaction[0]));
        $week = date('W', strtotime($transaction[0]));
        $rate = $this->checkRate($transaction);

        // $oldAmount = $this->withdrawPerWeek[$week][$transaction[1]];
        if (
            is_array($this->withdrawPerWeek) && array_key_exists($week, $this->withdrawPerWeek) &&
            is_array($this->withdrawPerWeek[$week]) && array_key_exists($transaction[1], $this->withdrawPerWeek[$week])
        ) {
            $oldAmount = array_sum($this->withdrawPerWeek[$week][$transaction[1]]);
            if (count($this->withdrawPerWeek[$week][$transaction[1]]) > 3) {
                return ["isFree" => false, "amount" => $transaction[4]];
            }
            // $this->withdrawPerWeek[$week][$transaction[1]] = $oldAmount + $transaction[4];
        } else {
            $oldAmount = 0;
        }

        $this->withdrawPerWeek[$week][$transaction[1]][] = $transaction[4] / $rate;
        $newAmount = array_sum($this->withdrawPerWeek[$week][$transaction[1]]);
        if ($newAmount > 1000) {
            if ($oldAmount >= 1000) {
                $exceeded = $transaction[4];
            } else {
                $exceeded = $newAmount - 1000;
            }

            return ["isFree" => false, "amount" => $exceeded];
        } else {
            return ["isFree" => true];
        }
    }
    public function feeFormater($fee)
    {
        return number_format((float)$fee, 2, '.', '');
    }
}
