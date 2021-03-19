<?php

namespace Database\Factories;

use App\Models\DebitCardTransaction;
use App\Models\Loan;
use App\Models\ReceivedRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceivedRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReceivedRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'loan_id' => fn () => Loan::factory()->create(),
            'amount' => $this->faker->randomNumber(),
            'currency_code' => DebitCardTransaction::CURRENCIES,
            'received_at' => $this->faker->dateTimeBetween('-24 month'),
        ];
    }
}
