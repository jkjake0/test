<?php

namespace App\Services;

use App\Models\Loan;
use App\Contracts\LoanService as LoanServiceContract;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoanService implements LoanServiceContract
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
     * @return Loan|null
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): ?Loan
    {
        DB::beginTransaction();
        try {
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
            Log::info('Store loan success!');
            Log::debug('Schedule repayment data: ' . json_encode($loan));

            // First idea
            // But it's seem it can not pass unit test
            $remainder = $amount % $terms;
            $third = floor($amount / $terms);
            $lastBit = $third + $remainder;
            for ($monthNum = 1; $monthNum <= $terms; $monthNum++) {
                if ($terms == $monthNum) {
                    $third = $lastBit;
                }
                $scheduleRepayment = $loan->scheduledRepayments()->create([
                    'amount' => $third,
                    'outstanding_amount' => $third,
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

            DB::commit();
            return $loan;
        } catch (\Exception $e) {
            DB::rollback();
            Log::warning('Error in createLoan transaction !');
            Log::error($e->getMessage());
            return null;
        }
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment|Model|null
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt)
    {
        DB::beginTransaction();
        try {
            $receivedRepayment = $loan->receivedRepayments()->create([
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);
            if ($receivedRepayment) {
                Log::info('Store received repayment success!');
                Log::debug('Received repayment data: ' . json_encode($receivedRepayment));

                $scheduleRepayments = $loan->scheduledRepayments()
                    ->where('status', ScheduledRepayment::STATUS_DUE)
                    ->orderBy('due_date')->get();

                self::recursiveScheduleRepaymentUpdate($scheduleRepayments, $amount);
                $loan->outstanding_amount = $loan->scheduledRepayments()
                    ->where('status', '<>', Loan::STATUS_REPAID)->sum('outstanding_amount');
                if ($loan->outstanding_amount == 0) {
                    $loan->status = Loan::STATUS_REPAID;
                }
                $loan->save();
            } else {
                Log::error('Store received repayment was failed!');
            }

            DB::commit();
            return $receivedRepayment;
        } catch (\Exception $e) {
            DB::rollback();
            Log::warning('Error in repayLoan transaction !');
            Log::error($e->getMessage());
            return null;
        }
    }

    /**
     * @param $scheduleRepayments
     * @param $amount
     * @return false|null
     */
    protected function recursiveScheduleRepaymentUpdate($scheduleRepayments, $amount): ?bool
    {
        if ($scheduleRepayments->count() == 0) return null;
        $firstScheduleRepayment = $scheduleRepayments->first();
        if ($firstScheduleRepayment->outstanding_amount < $amount) {
            $firstScheduleRepayment->status = ScheduledRepayment::STATUS_REPAID;
            $firstScheduleRepayment->outstanding_amount = 0;
            if ($firstScheduleRepayment->save()) {
                return self::recursiveScheduleRepaymentUpdate(
                    $scheduleRepayments->where('id', '<>', $firstScheduleRepayment->id),
                    $amount - $firstScheduleRepayment->amount
                );
            }
            return false;
        } elseif ($firstScheduleRepayment->outstanding_amount == $amount) {
            $firstScheduleRepayment->status = ScheduledRepayment::STATUS_REPAID;
            $firstScheduleRepayment->outstanding_amount = 0;
            return $firstScheduleRepayment->save();
        } else {
            $firstScheduleRepayment->outstanding_amount = $firstScheduleRepayment->outstanding_amount - $amount;
            $firstScheduleRepayment->status = ScheduledRepayment::STATUS_PARTIAL;
            return $firstScheduleRepayment->save();
        }
    }
}
