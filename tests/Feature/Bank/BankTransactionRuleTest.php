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

namespace Tests\Feature\Bank;

use App\Factory\BankIntegrationFactory;
use App\Factory\BankTransactionFactory;
use App\Models\BankIntegration;
use App\Models\BankTransaction;
use App\Models\BankTransactionRule;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\MockAccountData;
use Tests\TestCase;

class BankTransactionRuleTest extends TestCase
{

    use DatabaseTransactions;
    use MockAccountData;

    protected function setUp() :void
    {
        parent::setUp();

        $this->makeTestData();

        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->withoutExceptionHandling();
    }

    public function testValidationContainsRule()
    {
        //[{"search_key":"description","operator":"contains","value":"hello"}]


        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'category_id' =>$this->expense_category->id,
            'rules' => [
                [
                    'search_key' => 'description',
                    'operator' => 'contains',
                    'value' => 'hello',
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => 'HellO ThErE CowBoY',
            'base_type' => 'DEBIT',
            'amount' => 100
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNotNull($bt->expense_id);
        $this->assertNotNull($bt->expense->category_id);
        $this->assertNotNull($bt->expense->vendor_id);
        

    }


    public function testUpdateValidationRules()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'amount',
                    'operator' => '<=',
                    'value' => 100,
                ]
            ]
        ]);


        $data = [
            "applies_to" => "DEBIT", 
            "archived_at" => 0, 
            "auto_convert" => False, 
            "category_id" => $this->expense_category->hashed_id, 
            "is_deleted" => False, 
            "isChanged" => True, 
            "matches_on_all" => True, 
            "name" => "TEST 22", 
            "updated_at" => 1669060432, 
            "vendor_id" => $this->vendor->hashed_id
            ];

        $response = null;


        try {
            $response = $this->withHeaders([
                'X-API-SECRET' => config('ninja.api_secret'),
                'X-API-TOKEN' => $this->token,
            ])->putJson('/api/v1/bank_transaction_rules/'. $br->hashed_id, $data);

        } catch (ValidationException $e) {
            $message = json_decode($e->validator->getMessageBag(), 1);
            nlog($message);
        }

        if($response){
            $arr = $response->json();

            $response->assertStatus(200);      
        }

    }

    public function testMatchingBankTransactionExpenseAmountLessThanEqualTo()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'amount',
                    'operator' => '<=',
                    'value' => 100,
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => '',
            'base_type' => 'DEBIT',
            'amount' => 100
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNotNull($bt->expense_id);
    }


    public function testMatchingBankTransactionExpenseAmountLessThan()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'amount',
                    'operator' => '<',
                    'value' => 100,
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => '',
            'base_type' => 'DEBIT',
            'amount' => 99
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNotNull($bt->expense_id);
    }

    public function testMatchingBankTransactionExpenseAmountGreaterThan()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'amount',
                    'operator' => '>',
                    'value' => 100,
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => '',
            'base_type' => 'DEBIT',
            'amount' => 101
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNotNull($bt->expense_id);
    }


    public function testMatchingBankTransactionExpenseAmountMiss()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'amount',
                    'operator' => '=',
                    'value' => 100,
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => '',
            'base_type' => 'DEBIT',
            'amount' => 101
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNull($bt->expense_id);
    }

    public function testMatchingBankTransactionExpenseAmount()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'amount',
                    'operator' => '=',
                    'value' => 100,
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => '',
            'base_type' => 'DEBIT',
            'amount' => 100
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNotNull($bt->expense_id);
    }


    public function testMatchingBankTransactionExpenseIsEmpty()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'description',
                    'operator' => 'is_empty',
                    'value' => '',
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => '',
            'base_type' => 'DEBIT',
            'amount' => 100
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNotNull($bt->expense_id);
    }

    public function testMatchingBankTransactionExpenseIsEmptyMiss()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'description',
                    'operator' => 'is_empty',
                    'value' => '',
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => 'asdadsa',
            'base_type' => 'DEBIT',
            'amount' => 100
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNull($bt->expense_id);
    }


    public function testMatchingBankTransactionExpenseStartsWithMiss()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'description',
                    'operator' => 'starts_with',
                    'value' => 'chesst',
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => 'ChESSSty coughs are terrible',
            'base_type' => 'DEBIT',
            'amount' => 100
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNull($bt->expense_id);
    }



    public function testMatchingBankTransactionExpenseStartsWith()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'description',
                    'operator' => 'starts_with',
                    'value' => 'chess',
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => 'ChESSSty coughs are terrible',
            'base_type' => 'DEBIT',
            'amount' => 100
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNotNull($bt->expense_id);
    }


  public function testMatchingBankTransactionExpenseContainsMiss()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'description',
                    'operator' => 'contains',
                    'value' => 'asdddfd',
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => 'Something asd bizarre',
            'base_type' => 'DEBIT',
            'amount' => 100
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNull($bt->expense_id);
    }


    public function testMatchingBankTransactionExpenseContains()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'description',
                    'operator' => 'contains',
                    'value' => 'asd',
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => 'Something asd bizarre',
            'base_type' => 'DEBIT',
            'amount' => 100
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNotNull($bt->expense_id);
    }

    public function testMatchingBankTransactionExpenseMiss()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'description',
                    'operator' => 'is',
                    'value' => 'wallaby',
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => 'Wall',
            'base_type' => 'DEBIT',
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNull($bt->expense_id);
    }

    public function testMatchingBankTransactionExpense()
    {

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'DEBIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'description',
                    'operator' => 'is',
                    'value' => 'wallaby',
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => 'WallABy',
            'base_type' => 'DEBIT',
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertNotNull($bt->expense_id);
    }


    public function testMatchingBankTransactionInvoice()
    {

        $this->invoice->number = "MUHMUH";
        $this->invoice->save();

        $br = BankTransactionRule::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'matches_on_all' => false,
            'auto_convert' => true,
            'applies_to' => 'CREDIT',
            'client_id' => $this->client->id,
            'vendor_id' => $this->vendor->id,
            'rules' => [
                [
                    'search_key' => 'description',
                    'operator' => 'is',
                    'value' => 'MUHMUH',
                ]
            ]
        ]);

        $bi = BankIntegration::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
        ]);

        $bt = BankTransaction::factory()->create([
            'bank_integration_id' => $bi->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'description' => 'MUHMUH',
            'base_type' => 'CREDIT',
            'amount' => 100
        ]);
    

        $bt->service()->processRules();

        $bt = $bt->fresh();

        $this->assertEquals(BankTransaction::STATUS_MATCHED, $bt->status_id);
    }



}