<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BankTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'transaction_id' => $this->faker->randomNumber(9, true) ,
            'amount' => $this->faker->randomFloat(2,10,10000) ,
            'currency_id' => '1',
            'account_type' => 'creditCard',
            'category_id' => 1,
            'category_type' => 'Random' ,
            'date' => $this->faker->date('Y-m-d') ,
            'bank_account_id' => 1 ,
            'description' =>$this->faker->words(5, true) ,
            'status_id'=> 1
        ];
    }
}
