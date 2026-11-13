<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Illustration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtistDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ces tests portent sur d'autres fonctions : la validation avant publication est testée dans ModerationTest.
        \App\Models\PlatformSetting::updateModeration(false);

        Storage::fake('public');

        // UploadedFile::fake()->image() a besoin de GD.
        if (str_contains($this->name(), 'required') && ! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }
    }

    public function test_artist_dashboard_uses_neo_world_with_own_title(): void
    {
        [$user, $artist] = $this->artist();
        $this->illustration($artist, null);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-world="neo"', false)
            ->assertSee('<title>Mon espace artiste | '.e(config('site.name')).'</title>', false)
            ->assertSee('Publier une œuvre')
            ->assertSee('1 œuvre sans type apparaît dans le filtre « Autre »')
            ->assertSee(route('illustrations.update', Illustration::first()), false)
            ->assertDontSee('Non précisé');
    }

    public function test_writer_and_admin_pages_use_new_header(): void
    {
        $writer = User::factory()->create(['account_type' => 'writer', 'is_admin' => true]);

        $this->actingAs($writer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<title>Mon espace écrivain | ', false)
            ->assertSee('class="w-header', false);

        $this->actingAs($writer)->get(route('admin.index'))
            ->assertOk()
            ->assertSee('<title>Tableau de bord · Administration | ', false)
            ->assertSee('class="w-header', false);

        $this->actingAs($writer)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('<title>Mon compte | ', false);
    }

    public function test_type_is_required_on_upload(): void
    {
        [$user] = $this->artist();

        $this->actingAs($user)
            ->post(route('illustrations.store'), [
            'rights' => 1,
                'title' => 'Sans type',
                'image' => UploadedFile::fake()->image('a.jpg'),
            ])
            ->assertSessionHasErrors('type');

        $this->actingAs($user)
            ->post(route('illustrations.store'), [
            'rights' => 1,
                'title' => 'Avec type',
                'type' => 'autre',
                'image' => UploadedFile::fake()->image('a.jpg'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('autre', Illustration::where('title', 'Avec type')->value('type'));
    }

    public function test_artist_can_reclassify_only_own_illustration(): void
    {
        [$user, $artist] = $this->artist();
        [$other] = $this->artist();
        $illustration = $this->illustration($artist, null);

        $this->actingAs($other)
            ->patch(route('illustrations.update', $illustration), ['type' => 'croquis'])
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('illustrations.update', $illustration), ['type' => 'croquis'])
            ->assertSessionHasNoErrors();

        $this->assertSame('croquis', $illustration->fresh()->type);
    }

    public function test_gallery_other_filter_includes_untyped_works(): void
    {
        [, $artist] = $this->artist();
        $this->illustration($artist, null, 'Ancienne œuvre');
        $this->illustration($artist, 'autre', 'Œuvre autre');
        $this->illustration($artist, 'croquis', 'Un croquis');

        $this->get(route('public.illustrations', ['type' => 'autre']))
            ->assertOk()
            ->assertSee('Ancienne œuvre')
            ->assertSee('Œuvre autre')
            ->assertDontSee('Un croquis');
    }

    public function test_genre_is_required_and_writer_can_set_it_later(): void
    {
        $user = User::factory()->create(['account_type' => 'writer']);
        $writer = \App\Models\Writer::create(['user_id' => $user->id]);
        $other = User::factory()->create(['account_type' => 'writer']);
        \App\Models\Writer::create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->post(route('documents.store'), [
            'rights' => 1,
                'title' => 'Sans genre',
                'document' => UploadedFile::fake()->create('r.txt', 1, 'text/plain'),
                'cover_image' => UploadedFile::fake()->image('c.jpg'),
            ])
            ->assertSessionHasErrors('genre');

        $this->actingAs($user)
            ->post(route('documents.store'), [
            'rights' => 1,
                'title' => 'Avec genre',
                'genre' => 'conte',
                'document' => UploadedFile::fake()->create('r.txt', 1, 'text/plain'),
                'cover_image' => UploadedFile::fake()->image('c.jpg'),
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('conte', \App\Models\Document::where('title', 'Avec genre')->value('genre'));

        $legacy = \App\Models\Document::create([
            'writer_id' => $writer->id, 'title' => 'Ancien récit', 'file_path' => 'f.txt', 'file_type' => 'txt',
            'file_size_bytes' => 1, 'cover_image_path' => 'c.jpg', 'cover_image_size_bytes' => 1,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('genreUpdateUrl', false);

        $this->actingAs($other)
            ->patch(route('documents.update', $legacy), ['genre' => 'thriller'])
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('documents.update', $legacy), ['genre' => 'thriller'])
            ->assertSessionHasNoErrors();
        $this->assertSame('thriller', $legacy->fresh()->genre);
    }

    private function artist(): array
    {
        $user = User::factory()->create(['account_type' => 'artist']);

        return [$user, Artist::create(['user_id' => $user->id])];
    }

    private function illustration(Artist $artist, ?string $type, string $title = 'Œuvre'): Illustration
    {
        return Illustration::create([
            'artist_id' => $artist->id,
            'title' => $title,
            'type' => $type,
            'image_path' => 'illustrations/x.jpg',
            'file_size_bytes' => 10,
        ]);
    }
}
