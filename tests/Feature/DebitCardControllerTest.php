<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.debit-cards.index'));
        $response->assertOk();
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $debitCard = DebitCard::factory()->make();
        $debitCard->user()->associate(User::factory()->create())->save();
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.debit-cards.index'));
        $response->assertJsonCount(0);
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $this->actingAs($this->user);
        $response = $this->postJson(route('api.debit-cards.store'), [
            'type' => 'visa'
        ]);
        $response->assertStatus(HttpResponse::HTTP_CREATED);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.debit-cards.show', [
            'debitCard' => DebitCard::factory()->make()->user()->associate($this->user)->save()
        ]));
        $response->assertOk();
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.debit-cards.show', [
            'debitCard' => DebitCard::factory()->make()->user()->associate(User::factory()->create())->save()
        ]));
        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $mockDebitCard = DebitCard::factory()->make();
        $this->actingAs($this->user);
        $response = $this->putJson(route('api.debit-cards.update', [
            'debitCard' => $mockDebitCard->user()->associate($this->user)->save()
        ]), [
            'is_active' => true
        ]);
        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $this->actingAs($this->user);
        $response = $this->putJson(route('api.debit-cards.update', [
            'debitCard' => DebitCard::factory()->make()->user()->associate($this->user)->save()
        ]), [
            'is_active' => false
        ]);
        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'number',
            'type',
            'expiration_date',
            'is_active',
        ]);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
    }

    // Extra bonus for extra tests :)
}
