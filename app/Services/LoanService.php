<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $loan = new Loan([
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'terms' => $terms,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);
        $loan->user()->associate($user);
        $loan->save();

        for ($monthNum = 1; $monthNum <= $terms; $monthNum++) {
            // TODO: need to implement pricing calculate here
            $scheduleRepaymentAmount = ceil($amount / $terms);
            $scheduleRepayment = $loan->scheduledRepayments()->create([
                'amount' => $scheduleRepaymentAmount,
                'outstanding_amount' => $scheduleRepaymentAmount,
                'currency_code' => $currencyCode,
                'due_date' => $processedAt instanceof Carbon
                    ? $processedAt->addMonths($monthNum)->format('Y-m-d')
                    : Carbon::createFromFormat('Y-m-d', $processedAt)->addMonths($monthNum)->format('Y-m-d'),
                'status' => \App\Models\ScheduledRepayment::STATUS_DUE
            ]);

            if ($scheduleRepayment) {
                Log::info('Store schedule repayment success!');
                Log::debug('Schedule repayment data: ' . json_encode($scheduleRepayment));
            } else {
                Log::error('Store schedule repayment was failed!');
            }
        }

        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment|Model
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        $receivedRepayment = $loan->receivedRepayments()->create([
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => Carbon::createFromFormat('Y-m-d', $receivedAt)->format('Y-m-d'),
        ]);
        if ($receivedRepayment) {
            Log::info('Store received repayment success!');
            Log::debug('Received repayment data: ' . json_encode($receivedRepayment));

            $loan->outstanding_amount = $loan->outstanding_amount - $amount;
            $loan->save();
        } else {
            Log::error('Store received repayment was failed!');
        }

        return $receivedRepayment;
    }
}
