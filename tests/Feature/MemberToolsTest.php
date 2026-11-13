<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Document;
use App\Models\Illustration;
use App\Models\User;
use App\Models\Writer;
use App\Notifications\MemberActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ces tests portent sur d'autres fonctions : la validation avant publication est testée dans ModerationTest.
        \App\Models\PlatformSetting::updateModeration(false);

        Storage::fake('public');
        Storage::fake('local');
    }

    private function artist(string $name = 'Kassi'): array
    {
        $user = User::factory()->create(['name' => $name, 'account_type' => 'artist']);

        return [$user, Artist::create(['user_id' => $user->id])];
    }

    private function illustration(Artist $artist, string $title, array $attributes = []): Illustration
    {
        return Illustration::withoutEvents(fn () => Illustration::create($attributes + [
            'artist_id' => $artist->id, 'title' => $title, 'type' => 'couleur',
            'image_path' => 'illustrations/'.md5($title).'.jpg', 'file_size_bytes' => 1,
        ]));
    }

    public function test_drafts_and_scheduled_works_stay_private_until_published(): void
    {
        [$owner, $artist] = $this->artist();
        $visitor = User::factory()->create(['account_type' => 'visitor']);
        $this->illustration($artist, 'Œuvre en ligne');
        $draft = $this->illustration($artist, 'Brouillon secret', ['published_at' => null]);
        $scheduled = $this->illustration($artist, 'Surprise de demain', ['published_at' => now()->addDay()]);

        foreach ([route('public.illustrations'), route('public.artist', $artist), route('search', ['q' => 'secret']), route('public.index')] as $url) {
            $this->get($url)->assertOk()->assertDontSee('Brouillon secret')->assertDontSee('Surprise de demain');
        }

        $this->get(route('public.illustration', $draft))->assertNotFound();
        $this->actingAs($visitor)->get(route('public.illustration', $scheduled))->assertNotFound();
        $this->actingAs($visitor)->postJson(route('likes.toggle', ['illustration', $draft->id]))->assertNotFound();

        $this->actingAs($owner)->get(route('public.illustration', $draft))
            ->assertOk()->assertSee('Brouillon : cette page n\'est visible que par toi et l\'équipe.', false);
        $this->actingAs($owner)->get(route('dashboard'))
            ->assertSee('Brouillon secret')->assertSee('Programmée le');

        // Le lendemain, l'œuvre programmée apparaît d'elle-même.
        $this->travel(2)->days();
        auth()->logout();
        $this->get(route('public.illustrations'))->assertSee('Surprise de demain')->assertDontSee('Brouillon secret');
    }

    public function test_batch_upload_with_tags_and_draft_status(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        [$owner, $artist] = $this->artist();

        $this->actingAs($owner)->post(route('illustrations.store'), [
            'rights' => 1,
            'type' => 'croquis',
            'tags' => 'Manga, abidjan ; manga, nuit #été',
            'status' => 'brouillon',
            'images' => [
                UploadedFile::fake()->image('carnet_du-marche.jpg'),
                UploadedFile::fake()->image('lagune.png'),
                UploadedFile::fake()->image('IMG-2041.jpg'),
            ],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $works = Illustration::withUnpublished()->orderBy('id')->get();
        $this->assertSame(['Carnet du marche', 'Lagune', 'IMG 2041'], $works->pluck('title')->all());
        $this->assertSame(['manga', 'abidjan', 'nuit', 'été'], $works->first()->tags);
        $this->assertTrue($works->every->isDraft());
        $this->assertSame(0, Illustration::count());

        // Le quota compte aussi les brouillons.
        foreach (range(1, 17) as $i) {
            $this->illustration($artist, 'Remplissage '.$i);
        }
        $this->actingAs($owner)->post(route('illustrations.store'), [
            'rights' => 1,
            'type' => 'couleur',
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasErrors('quota');
    }

    public function test_owner_edits_an_illustration_and_schedules_it(): void
    {
        [$owner, $artist] = $this->artist();
        [$other] = $this->artist('Yao');
        $illustration = $this->illustration($artist, 'Ancien titre');

        $this->actingAs($other)->get(route('illustrations.edit', $illustration))->assertForbidden();
        $this->actingAs($other)->patch(route('illustrations.update', $illustration), ['full' => 1, 'title' => 'X', 'type' => 'couleur'])->assertForbidden();

        $this->actingAs($owner)->get(route('illustrations.edit', $illustration))->assertOk()->assertSee('Remplacer l\'image', false);

        $this->actingAs($owner)->patch(route('illustrations.update', $illustration), [
            'full' => 1, 'title' => 'Nouveau titre', 'type' => 'couverture', 'description' => "Encre et aquarelle.\nCarnet d'Adjamé.",
            'tags' => 'encre, aquarelle', 'status' => 'programme', 'scheduled_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('illustrations.edit', $illustration))->assertSessionHasNoErrors();

        $fresh = Illustration::withUnpublished()->find($illustration->id);
        $this->assertSame('Nouveau titre', $fresh->title);
        $this->assertSame(['encre', 'aquarelle'], $fresh->tags);
        $this->assertTrue($fresh->isScheduled());

        $this->actingAs($owner)->patch(route('illustrations.update', $illustration), [
            'full' => 1, 'title' => 'Nouveau titre', 'type' => 'couverture', 'status' => 'programme', 'scheduled_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('scheduled_at');

        $this->actingAs($owner)->patch(route('illustrations.update', $illustration), [
            'full' => 1, 'title' => 'Nouveau titre', 'type' => 'couverture', 'status' => 'publie', 'description' => 'Encre et aquarelle.', 'tags' => 'encre',
        ]);
        $this->get(route('public.illustration', $illustration))
            ->assertOk()->assertSee('Encre et aquarelle.')->assertSee('#encre')->assertSee(route('search', ['q' => 'encre']), false);
        $this->get(route('search', ['q' => 'encre']))->assertSee('Nouveau titre');
    }

    public function test_views_are_counted_once_per_visitor_and_not_for_the_owner(): void
    {
        [$owner, $artist] = $this->artist();
        $illustration = $this->illustration($artist, 'Lagune');
        $draft = $this->illustration($artist, 'Brouillon', ['published_at' => null]);

        $this->get(route('public.illustration', $illustration));
        $this->get(route('public.illustration', $illustration));
        $this->assertSame(1, $illustration->fresh()->view_count);

        $this->actingAs($owner)->get(route('public.illustration', $illustration));
        $this->actingAs($owner)->get(route('public.illustration', $draft));
        $this->assertSame(1, $illustration->fresh()->view_count);
        $this->assertSame(0, Illustration::withUnpublished()->find($draft->id)->view_count);

        $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertSee('👁 1', false);
    }

    public function test_follow_favorite_and_featured_notifications(): void
    {
        [$owner, $artist] = $this->artist();
        $illustration = $this->illustration($artist, 'Lagune');
        $fan = User::factory()->create(['name' => 'Ama', 'account_type' => 'visitor']);

        $this->actingAs($fan)->post(route('artists.follow', $artist));
        $this->actingAs($fan)->post(route('artists.follow', $artist));
        $this->actingAs($owner)->post(route('artists.follow', $artist));
        $this->actingAs($fan)->post(route('illustrations.favorite', $illustration));
        $this->actingAs($owner)->post(route('illustrations.favorite', $illustration));

        $kinds = $owner->fresh()->notifications->pluck('data.kind')->all();
        $this->assertSame(1, count(array_keys($kinds, MemberActivity::NEW_FOLLOWER)));
        $this->assertSame(1, count(array_keys($kinds, MemberActivity::FAVORITED)));

        // Rotation du jour : l'artiste (seul éligible) est prévenu une seule fois.
        $this->get(route('public.index'));
        $this->get(route('public.artists'));
        $this->assertSame(1, $owner->fresh()->notifications->where('data.kind', MemberActivity::FEATURED)->count());

        $this->actingAs($owner)->get(route('public.index'))
            ->assertSee('Notifications (3 non lues)', false)
            ->assertSee('class="w-bell__count"', false);

        $this->actingAs($owner)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Ama suit désormais ton studio.')
            ->assertSee('Ama a ajouté « Lagune » à ses favoris.')
            ->assertSee('Artiste du jour')
            ->assertSee('Nouveau');
        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());

        $this->actingAs($owner)->delete(route('notifications.clear'))->assertRedirect(route('notifications.index'));
        $this->assertSame(0, $owner->fresh()->notifications()->count());
    }

    public function test_admin_featured_selection_notifies_the_writer(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $writerUser = User::factory()->create(['account_type' => 'writer']);
        $writer = Writer::create(['user_id' => $writerUser->id]);

        // Planification pour aujourd'hui (date par défaut) : la plume est prévenue une seule fois.
        $this->actingAs($admin)->post(route('admin.featured.schedule'), ['writer_id' => $writer->id])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.featured.schedule'), ['writer_id' => $writer->id]);

        $notifications = $writerUser->fresh()->notifications;
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('Plume du jour', $notifications->first()->data['message']);
    }

    public function test_writer_saves_a_draft_text(): void
    {
        $user = User::factory()->create(['account_type' => 'writer']);
        $writer = Writer::create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('documents.store'), [
            'rights' => 1,
            'title' => 'Mon brouillon', 'genre' => 'conte', 'content' => 'Il était une fois.', 'status' => 'brouillon', 'from_editor' => 1,
            'cover_image' => UploadedFile::fake()->create('c.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        $document = Document::withUnpublished()->where('title', 'Mon brouillon')->sole();
        $this->assertTrue($document->isDraft());

        auth()->logout();
        $this->get(route('public.writer', $writer))->assertDontSee('Mon brouillon');
        $this->get(route('documents.read', $document))->assertNotFound();
        $this->actingAs($user)->get(route('documents.read', $document))->assertOk()->assertSee('visible que par toi');
        $this->actingAs($user)->get(route('dashboard'))->assertSee('Mon brouillon')->assertSee('Brouillon');
    }
}
