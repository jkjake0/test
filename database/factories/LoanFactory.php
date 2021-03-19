<?php

namespace Database\Factories;

use App\Models\DebitCardTransaction;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

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
            $status = Loan::STATUS_DUE;
        } elseif ($outstandingAmount == $amount) {
            $status = Loan::STATUS_REPAID;
        } else {
            $status = Loan::STATUS_REPAID;
            $outstandingAmount = $amount;
        }
        return [
            'user_id' => fn () => User::factory()->create(),
            'amount' => $amount,
            'terms' => rand(3, 6),
            'outstanding_amount' => $outstandingAmount,
            'currency_code' => $this->faker->randomElement(DebitCardTransaction::CURRENCIES),
            'processed_at' => $this->faker->date(Loan::DATE_FORMAT),
            'status' => $status,
        ];
    }
}
