<?php

namespace Tests\Feature;

use App\Models\Dealer;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PromoTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function dealer(): User
    {
        $u = User::factory()->create(['role' => 'dealer']);
        Dealer::query()->create(['user_id' => $u->id, 'business_name' => 'Negozio', 'registry_completed_at' => now()]);

        return $u;
    }

    public function test_manager_publishes_a_promo_that_ends_after_the_chosen_duration(): void
    {
        Storage::fake('uploads');
        $this->travelTo('2026-11-10 18:42:00');

        $this->actingAs($this->manager())->post('/manager/promo', [
            'name' => 'Lavatrice compatta',
            'price' => '249.90',
            'duration_hours' => 48,
            'photo' => UploadedFile::fake()->image('lavatrice.jpg', 600, 600),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $promo = Promo::query()->sole();
        $this->assertSame('2026-11-12 18:42:00', $promo->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('Offerta 48h', $promo->label());
        Storage::disk('uploads')->assertExists($promo->photo_path);
    }

    public function test_only_the_allowed_durations_are_accepted(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->post('/manager/promo', ['name' => 'X', 'price' => 10, 'duration_hours' => 36])
            ->assertSessionHasErrors('duration_hours');
        $this->actingAs($manager)->post('/manager/promo', ['name' => 'X', 'price' => 10, 'duration_hours' => 168])
            ->assertSessionHasNoErrors();
        $this->assertSame('Offerta 1 settimana', Promo::query()->sole()->label());
    }

    public function test_promo_is_visible_with_countdown_until_it_expires_then_disappears(): void
    {
        $this->travelTo('2026-11-10 10:00:00');
        $this->actingAs($this->manager())->post('/manager/promo', ['name' => 'Offerta lampo', 'price' => 99, 'duration_hours' => 24]);
        $dealer = $this->dealer();

        $this->actingAs($dealer)->get('/dealer?scheda=promo')
            ->assertOk()->assertSee('Offerta lampo')->assertSee('99,00 €')
            ->assertSee('data-ends-at="2026-11-11T10:00:00+01:00"', false)
            ->assertSee('Ordina adesso');

        $this->travelTo('2026-11-11 09:59:59');
        $this->actingAs($dealer)->get('/dealer?scheda=promo')->assertSee('Offerta lampo');

        $this->travelTo('2026-11-11 10:00:00');
        $this->actingAs($dealer)->get('/dealer?scheda=promo')->assertOk()->assertDontSee('Offerta lampo');
    }

    public function test_manager_can_close_a_promo_early(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->post('/manager/promo', ['name' => 'Da chiudere', 'price' => 50, 'duration_hours' => 72]);
        $promo = Promo::query()->sole();

        $this->travel(1)->hours();
        $this->actingAs($manager)->post("/manager/promo/{$promo->id}/chiudi")->assertRedirect();

        $this->assertFalse($promo->fresh()->isActive());
        $this->actingAs($this->dealer())->get('/dealer?scheda=promo')->assertDontSee('Da chiudere');
    }

    public function test_dealers_cannot_publish_promos(): void
    {
        $this->actingAs($this->dealer())->post('/manager/promo', ['name' => 'X', 'price' => 10, 'duration_hours' => 24])->assertForbidden();
        $this->assertSame(0, Promo::query()->count());
    }

    public function test_showcase_accepts_only_sharers_links(): void
    {
        $manager = $this->manager();
        $products = fn (string $url) => ['products' => [1 => ['name' => 'Lavatrice', 'url' => $url], 2 => ['name' => null, 'url' => null], 3 => ['name' => null, 'url' => null]]];

        $this->actingAs($manager)->post('/manager/catalogo-sharers', $products('https://altro-sito.example/prodotto'))
            ->assertSessionHasErrors('products.1.url');
        $this->actingAs($manager)->post('/manager/catalogo-sharers', $products('https://www.sharers.it/lavatrice'))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->dealer())->get('/dealer?scheda=promo')
            ->assertSee('Lavatrice')->assertSee('https://www.sharers.it/lavatrice', false);
    }
}
