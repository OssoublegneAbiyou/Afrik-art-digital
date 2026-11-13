<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Document;
use App\Models\Illustration;
use App\Models\JoinRequest;
use App\Models\User;
use App\Models\Writer;
use App\Notifications\NewJoinRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VisitorFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function validJoin(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ama Koné',
            'email' => 'Ama@Example.com ',
            'discipline' => 'illustration',
            'portfolio_url' => 'https://instagram.com/ama',
            'message' => 'Je dessine des mangas inspirés de Treichville.',
        ], $overrides);
    }

    public function test_about_page_shows_join_form(): void
    {
        $this->get(route('public.about'))
            ->assertOk()
            ->assertSee('action="'.route('join-requests.store').'"', false)
            ->assertSee('name="portfolio_url"', false)
            ->assertDontSee('bientôt ouvertes');
    }

    public function test_join_request_is_stored_and_admins_are_notified(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->post(route('join-requests.store'), $this->validJoin())
            ->assertRedirect(route('public.about').'#rejoindre')
            ->assertSessionHas('join_success');

        $request = JoinRequest::sole();
        $this->assertSame('ama@example.com', $request->email);
        $this->assertNull($request->handled_at);
        Notification::assertSentTo($admin, NewJoinRequest::class);

        $this->followingRedirects()->post(route('join-requests.store'), $this->validJoin(['email' => 'b@example.com']))
            ->assertSee('Merci, ta demande est bien arrivée.');
    }

    public function test_join_request_validation_and_honeypot(): void
    {
        $this->post(route('join-requests.store'), $this->validJoin(['portfolio_url' => 'instagram ama', 'discipline' => 'danse']))
            ->assertSessionHasErrorsIn('join', ['portfolio_url', 'discipline']);

        $this->post(route('join-requests.store'), $this->validJoin(['site_web' => 'http://spam.test']))
            ->assertSessionHas('join_success');

        $this->assertSame(0, JoinRequest::count());
    }

    public function test_admin_handles_requests_and_prefills_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'account_type' => 'visitor']);
        $visitor = User::factory()->create(['account_type' => 'visitor']);
        $request = JoinRequest::create($this->validJoin(['email' => 'ama@example.com']));

        Notification::fake();

        $this->actingAs($admin)->get(route('admin.join-requests.index'))
            ->assertOk()
            ->assertSee('Nouvelles&nbsp;·&nbsp;1', false)
            ->assertSee('Ama Koné');

        $this->actingAs($admin)->get(route('admin.join-requests.show', $request))
            ->assertOk()
            ->assertSee('https://instagram.com/ama')
            ->assertSee('<option value="artist" selected', false);

        $this->actingAs($visitor)->post(route('admin.join-requests.accept', $request), ['account_type' => 'artist'])->assertForbidden();

        $this->actingAs($admin)->post(route('admin.join-requests.accept', $request), ['account_type' => 'artist'])->assertRedirect();
        $this->assertSame(JoinRequest::STATUS_ACCEPTED, $request->fresh()->statut);
        $this->assertTrue(User::where('email', 'ama@example.com')->where('account_type', 'artist')->exists());

        // Créer le compte du candidat depuis « Membres » clôt automatiquement sa demande.
        $other = JoinRequest::create($this->validJoin(['email' => 'new@example.com']));
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'name' => 'Nouvelle', 'email' => 'new@example.com', 'account_type' => 'artist', 'role' => 'membre',
        ])->assertSessionHasNoErrors();
        $this->assertSame(JoinRequest::STATUS_ACCEPTED, $other->fresh()->statut);
    }

    public function test_collection_groups_favorites_bookmarks_and_follows(): void
    {
        $this->get(route('collection'))->assertRedirect(route('login'));

        $artistUser = User::factory()->create(['name' => 'Kassi', 'account_type' => 'artist']);
        $artist = Artist::create(['user_id' => $artistUser->id]);
        $illustration = Illustration::create(['artist_id' => $artist->id, 'title' => 'Lagune', 'type' => 'couleur', 'image_path' => 'i.jpg', 'file_size_bytes' => 1]);
        $writerUser = User::factory()->create(['name' => 'Awa', 'account_type' => 'writer']);
        $writer = Writer::create(['user_id' => $writerUser->id]);
        $document = Document::create(['writer_id' => $writer->id, 'title' => 'Le gbaka', 'genre' => 'conte', 'file_path' => 'f.txt', 'file_type' => 'txt', 'file_size_bytes' => 1, 'cover_image_path' => 'c.jpg', 'cover_image_size_bytes' => 1]);

        // Un écrivain (pas seulement un visiteur) retrouve aussi sa collection.
        $reader = User::factory()->create(['account_type' => 'writer']);
        $reader->favoriteIllustrations()->attach($illustration);
        $reader->bookmarkedDocuments()->attach($document);
        $reader->followedArtists()->attach($artist);

        $this->actingAs($reader)->get(route('collection'))
            ->assertOk()
            ->assertSee('<title>Ma collection | ', false)
            ->assertSee('Lagune')
            ->assertSee('Le gbaka')
            ->assertSee('Kassi')
            ->assertSee(route('illustrations.unfavorite', $illustration), false)
            ->assertSee(route('documents.unbookmark', $document), false)
            ->assertSee(route('artists.unfollow', $artist), false);

        $this->actingAs($reader)->get(route('public.index'))->assertSee(route('collection'), false);
    }

    public function test_share_buttons_on_public_pages(): void
    {
        $user = User::factory()->create(['name' => 'Awa', 'account_type' => 'artist']);
        $artist = Artist::create(['user_id' => $user->id]);
        $illustration = Illustration::create(['artist_id' => $artist->id, 'title' => 'Lagune', 'type' => 'couleur', 'image_path' => 'i.jpg', 'file_size_bytes' => 1]);
        $url = route('public.illustration', $illustration);

        $this->get($url)
            ->assertOk()
            ->assertSee('https://wa.me/?text=', false)
            ->assertSee(rawurlencode($url), false)
            ->assertSee('https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url), false)
            ->assertSee('Copier le lien')
            ->assertSee('d’Awa');

        $this->get(route('public.artist', $artist))->assertSee('Partager le studio');
    }
}
