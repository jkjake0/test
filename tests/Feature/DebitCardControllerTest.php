<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $baseUrl = '/api';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        $expectListCount = 10;
        $expectStructure = [
            '*' => [
                'id',
                'number',
                'type',
                'expiration_date',
                'is_active',
            ]
        ];

        DebitCard::factory($expectListCount)->create()->each(function ($debitCard) {
            $debitCard->disabled_at = null; // Hack factory for active all cards expected
            $debitCard->user_id = $this->user->id;
            $debitCard->save();
        });

        // Hack factory or can get expected here
        // $expectListCount for active debit card
        /*
        $expectListCount = DebitCard::with(['user'])->whereHas('user', function ($query) {
            return $query->where('id', $this->user->id);
        })->active()->count();
        */

        $this->actingAs($this->user);
        $response = $this->getJson(url("{$this->baseUrl}/debit-cards"));

        // Expected
        $response->assertOk();
        $response->assertJsonStructure($expectStructure);
        $response->assertJsonCount($expectListCount);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $randomDebitCard = rand(1, 10);
        $expectListCount = 0;

        $otherUser = User::factory()->create();
        DebitCard::factory($randomDebitCard)->create()->each(function ($debitCard) use($otherUser) {
            $debitCard->user()->associate($otherUser)->save();
        });

        $this->actingAs($this->user);
        $response = $this->getJson(url("{$this->baseUrl}/debit-cards"));

        // Expected
        $response->assertOk();
        $response->assertJsonCount($expectListCount);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $expectStatus = HttpResponse::HTTP_CREATED;
        $expectDebitCardData = [
            'type' => 'visa'
        ];

        $this->actingAs($this->user);
        $response = $this->postJson(url("{$this->baseUrl}/debit-cards"), $expectDebitCardData);

        // Expected
        $response->assertStatus($expectStatus);
        $response->assertJson($expectDebitCardData);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $this->actingAs($this->user);
        $debitCard = DebitCard::factory()->make();
        $debitCard->user()->associate($this->user)->save();

        $expectDebitCardData = $debitCard->only([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);

        $response = $this->getJson(url("{$this->baseUrl}/debit-cards", [
            'debitCard' => $debitCard
        ]));

        // Expected
        $response->assertOk();
        $response->assertExactJson($expectDebitCardData);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $expectStatus = HttpResponse::HTTP_FORBIDDEN;

        $this->actingAs($this->user);
        $response = $this->getJson(url("{$this->baseUrl}/debit-cards", [
            'debitCard' => DebitCard::factory()->make()->user()->associate(User::factory()->create())->save()
        ]));

        // Expected
        $response->assertStatus($expectStatus);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $expectStructure = [
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ];

        $mockDebitCard = DebitCard::factory()->make();
        $mockDebitCard->user()->associate($this->user)->save();
        $this->actingAs($this->user);

        $response = $this->putJson(url("{$this->baseUrl}/debit-cards", [
            'debitCard' => $mockDebitCard
        ]), [
            'is_active' => true
        ]);

        // Expected
        $response->assertOk();
        $response->assertJsonStructure($expectStructure);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $dataDeactivateCard = [
            'is_active' => false
        ];
        $debitCard = DebitCard::factory()->make();
        $debitCard->user()->associate($this->user)->save();
        $expectStructure = [
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ];

        $this->actingAs($this->user);
        $response = $this->putJson(url("{$this->baseUrl}/debit-cards", [
            'debitCard' => $debitCard
        ]), $dataDeactivateCard);

        // Expected
        $response->assertOk();
        $response->assertJsonStructure($expectStructure);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $wrongData = [
            'wrong-key' => 'wrong-value'
        ];
        $debitCard = DebitCard::factory()->make();
        $debitCard->user()->associate($this->user)->save();
        $expectStatus = HttpResponse::HTTP_UNPROCESSABLE_ENTITY;

        $this->actingAs($this->user);
        $response = $this->putJson(url("{$this->baseUrl}/debit-cards", [
            'debitCard' => $debitCard
        ]), $wrongData);

        // Expected
        $response->assertStatus($expectStatus);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $this->actingAs($this->user);
        $debitCard = DebitCard::factory()->make();
        $debitCard->user()->associate($this->user)->save();
        $expectDeleteStatus = HttpResponse::HTTP_NO_CONTENT;
        $expectTryToGetAfterDeleteStatus = HttpResponse::HTTP_NOT_FOUND;

        // try to delete it
        $response = $this->deleteJson(url("{$this->baseUrl}/debit-cards", [
            'debitCard' => $debitCard
        ]));
        $response->assertStatus($expectDeleteStatus);

        // try get it again
        $response = $this->getJson(url("{$this->baseUrl}/debit-cards", [
            'debitCard' => $debitCard
        ]));
        $response->assertStatus($expectTryToGetAfterDeleteStatus);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $this->actingAs($this->user);
        $debitCard = DebitCard::factory()->make();
        $debitCardTransaction = DebitCardTransaction::factory()->make();
        $debitCard->user()->associate($this->user)->save();
        $debitCard->debitCardTransactions()->create($debitCardTransaction->toArray());

        $expectDeleteStatus = HttpResponse::HTTP_FORBIDDEN;
        $expectGetSingleDebitCardAfterDeleteCallFailed = $debitCard->only([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);

        // try to delete it
        $response = $this->deleteJson(url("{$this->baseUrl}/debit-cards", [
            'debitCard' => $debitCard
        ]));
        $response->assertStatus($expectDeleteStatus);

        // try get it again
        $response = $this->getJson(url("{$this->baseUrl}/debit-cards", [
            'debitCard' => $debitCard
        ]));
        $response->assertOk();
        $response->assertExactJson($expectGetSingleDebitCardAfterDeleteCallFailed);
    }

    // Extra bonus for extra tests :)
}
