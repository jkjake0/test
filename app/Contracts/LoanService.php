<?php

namespace App\Contracts;

use App\Models\Loan;
use App\Models\User;

/**
 * Interface LoanService
 * @package App\Contracts
 */
interface LoanService {
    /**
     * @param User $user
     * @param int $amount
     * @param string $currencyCode
     * @param int $terms
     * @param string $processedAt
     * @return Loan|null
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): ?Loan;

    /**
     * @param Loan $loan
     * @param int $amount
     * @param string $currencyCode
     * @param string $receivedAt
     * @return mixed
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt);
}
