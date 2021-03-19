<?php

namespace Database\Factories;

use App\Models\DebitCardTransaction;
use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $amount = $this->faker->randomNumber();
        $outstandingAmount = $this->faker->randomNumber();
        if ($outstandingAmount < $amount) {
            $status = ScheduledRepayment::STATUS_PARTIAL;
        } elseif ($outstandingAmount == $amount) {
            $status = ScheduledRepayment::STATUS_REPAID;
        } else {
            $status = ScheduledRepayment::STATUS_REPAID;
            $outstandingAmount = $amount;
        }
        return [
            'loan_id' => fn () => Loan::factory()->create(),
            'amount' => $amount,
            'outstanding_amount' => $outstandingAmount,
            'currency_code' => DebitCardTransaction::CURRENCIES,
            'due_date' => $this->faker->date(ScheduledRepayment::DATE_FORMAT),
            'status' => $status,
        ];
    }
}
