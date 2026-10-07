<?php

namespace App;

use InvalidArgumentException;

class CreditCardInstallmentCalculator
{
    /**
     * @return list<array{count: int, total: float, installment: float, fee: float, has_fee: bool}>
     */
    public function options(float $price): array
    {
        return collect(range(1, $this->maximumFor($price)))
            ->map(fn (int $installments): array => $this->for($price, $installments))
            ->all();
    }

    public function maximumFor(float $price): int
    {
        $maximumInstallments = (int) config('asaas.credit_card_max_installments', 12);
        $smallPurchaseMaximum = (float) config('asaas.credit_card_small_purchase_maximum', 100);
        $smallPurchaseInstallments = (int) config('asaas.credit_card_small_purchase_installments', 5);

        if ($price <= $smallPurchaseMaximum) {
            return min($smallPurchaseInstallments, $maximumInstallments);
        }

        return $maximumInstallments;
    }

    /**
     * @return array{count: int, total: float, installment: float, fee: float, has_fee: bool}
     */
    public function for(float $price, int $installments): array
    {
        $maximumInstallments = $this->maximumFor($price);

        if ($installments < 1 || $installments > $maximumInstallments) {
            throw new InvalidArgumentException("Este valor permite no máximo {$maximumInstallments} parcelas.");
        }

        $freeInstallmentLimit = (int) config('asaas.credit_card_free_installments', 5);
        $priceInCents = (int) round($price * 100, 0, PHP_ROUND_HALF_UP);
        $feeInCents = 0;

        if ($installments > $freeInstallmentLimit) {
            $percentageInBasisPoints = (int) round((float) config('asaas.credit_card_fee_percentage', 3.49) * 100, 0, PHP_ROUND_HALF_UP);
            $fixedFeeInCents = (int) round((float) config('asaas.credit_card_fixed_fee', 0.49) * 100, 0, PHP_ROUND_HALF_UP);
            $feeInCents = (int) round(($priceInCents * $percentageInBasisPoints) / 10000, 0, PHP_ROUND_HALF_UP) + $fixedFeeInCents;
        }

        $totalInCents = $priceInCents + $feeInCents;
        $fee = $feeInCents / 100;
        $total = $totalInCents / 100;

        return [
            'count' => $installments,
            'total' => $total,
            'installment' => round($total / $installments, 2),
            'fee' => $fee,
            'has_fee' => $fee > 0,
        ];
    }
}
