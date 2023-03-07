<?php

declare(strict_types=1);

namespace Karimi\CommissionTask\Service;

class Math
{
    private $scale;
    private $withdrawPerWeek;
    private $latestWithdraw = [];

    public function __construct(int $scale)
    {
        $this->scale = $scale;
    }

    public function add(string $leftOperand, string $rightOperand): string
    {
        return bcadd($leftOperand, $rightOperand, $this->scale);
    }

    /**
     * Main function for commission fee calculation.
     *
     * @param string $fileUrl
     */
    public function commissionFeeCalc($fileUrl): array
    {
        $fees = [];
        // Load data from the CSV file and loop over each line.
        $file = fopen($fileUrl, 'r');
        while (($transaction = fgetcsv($file)) !== false) {
            // Check which type of transaction it is? deposit or withdraw.
            if ($transaction[3] === 'deposit') {
                $fees[] = $this->feeFormater($this->deposit($transaction), $transaction[5]);
            } else {
                $fees[] = $this->feeFormater($this->withdraw($transaction), $transaction[5]);
            }
        }

        return $fees;
    }

    /**
     * Calcuate the the deposit fee.
     */
    public function deposit(array $transaction): float
    {
        // All deposits are charged 0.03% of deposit amount.
        $amount = $transaction[4];
        $commissionFee = $amount * 0.03 / 100;

        return $commissionFee;
    }

    /**
     * Calcuate the the withdraw fee.
     */
    public function withdraw(array $transaction): float
    {
        // There are different calculation rules for withdraw of private and business clients.
        $amount = $transaction[4];
        $rate = $this->getRate($transaction[5]);

        if ($transaction[2] === 'private') {
            $chargeStatus = $this->freeCharge($transaction);
            if ($chargeStatus['isFree']) {
                $commissionFee = 0;
            } else {
                // Private Clients, Commission fee - 0.3% from withdrawn amount.
                // Rate for this case already applied in free charge function.
                $commissionFee = $chargeStatus['amount'] * 0.3 / 100;
            }
        } else {
            // Business Clients, Commission fee - 0.5% from withdrawn amount.
            $commissionFee = ($amount / $rate) * 0.5 / 100;
        }

        return $commissionFee;
    }

    /**
     * Find the rate to convert values to EUR.
     *
     * @param array $transaction
     */
    public function getRate($currency): float
    {
        $rate = 1;
        if ($currency !== 'EUR') {
            if ($currency === 'JPY') {
                $rate = 129.53;
            } elseif ($currency === 'USD') {
                $rate = 1.1497;
            }
        }

        return $rate;
    }

    /**
     * Check that if the amount is free of charge or not.
     * If not, how much exceeded the 1000, and should be charged.
     *
     * @param array $transaction
     */
    public function freeCharge($transaction): array
    {
        $week = date('W', strtotime($transaction[0]));
        $rate = $this->getRate($transaction[5]);

        // Check the time differentiate between the dates to detemine if same week is in different years.
        if (array_key_exists($transaction[1], $this->latestWithdraw)) {
            $earlier = date('Y', strtotime($this->latestWithdraw[$transaction[1]]));
            $later = date('Y', strtotime($transaction[0]));

            // Check for the last transaction for the same client if is in less than a week.
            $date1 = date_create($this->latestWithdraw[$transaction[1]]);
            $date2 = date_create($transaction[0]);
            $diff = date_diff($date1, $date2);

            // If the year changed but the week is same, reset it.
            if ($earlier !== $later && $diff->format('%a') > 7) {
                $this->withdrawPerWeek[$week][$transaction[1]] = [];
            }
        }

        // Check if specific client has history for specific week.
        if (
            is_array($this->withdrawPerWeek)
            && array_key_exists($week, $this->withdrawPerWeek)
            && is_array($this->withdrawPerWeek[$week])
            && array_key_exists($transaction[1], $this->withdrawPerWeek[$week])
        ) {
            $oldAmount = array_sum($this->withdrawPerWeek[$week][$transaction[1]]);

            // If number of withdraw is more than 3, then it is not free.
            if (count($this->withdrawPerWeek[$week][$transaction[1]]) > 3) {
                return ['isFree' => false, 'amount' => $transaction[4]];
            }
        } else {
            $oldAmount = 0;
        }

        // Caclulation to find out how much of amount exceeded based on EUR.
        $this->withdrawPerWeek[$week][$transaction[1]][] = $transaction[4] / $rate;
        $newAmount = array_sum($this->withdrawPerWeek[$week][$transaction[1]]);
        $this->latestWithdraw[$transaction[1]] = $transaction[0];
        $exceeded = 0;
        if ($newAmount > 1000) {
            if ($oldAmount >= 1000) {
                $exceeded = $transaction[4] / $rate;
            } else {
                $exceeded = $newAmount - 1000;
            }

            return ['isFree' => false, 'amount' => $exceeded];
        } else {
            return ['isFree' => true];
        }
    }

    /**
     * Formatting the final fee value to be with 2 deciaml.
     *
     * @param float $fee
     */
    public function feeFormater($fee, $currency): string
    {
        // Return back the fee from EUR to it's original currency and format to decimal.
        $fee *= $this->getRate($currency);

        return number_format((float) $this->round_up($fee), 2, '.', '');
    }

    /**
     * Rounds up a float to a specified number of decimal places.
     *
     * @param float $value
     */
    public function round_up($value): float
    {
        $places = 0;
        if ($value >= 10) {
            $places = 0;
        } elseif ($value >= 0.10) {
            $places = 1;
        } elseif ($places < 0.10) {
            $places = 2;
        }
        $mult = pow(10, $places);

        return ceil($value * $mult) / $mult;
    }
}
