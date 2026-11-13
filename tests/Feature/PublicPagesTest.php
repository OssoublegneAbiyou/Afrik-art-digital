<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\ArtistPortfolio;
use App\Models\Document;
use App\Models\Illustration;
use App\Models\User;
use App\Models\Writer;
use App\Support\SocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_social_handles_become_external_urls(): void
    {
        $this->assertSame('https://instagram.com/lahlougo', SocialLink::url('instagram', 'lahlougo'));
        $this->assertSame('https://instagram.com/lahlougo', SocialLink::url('instagram', '@lahlougo'));
        $this->assertSame('https://instagram.com/lahlougo', SocialLink::url('instagram', 'instagram.com/lahlougo'));
        $this->assertSame('https://www.behance.net/x', SocialLink::url('behance', 'https://www.behance.net/x'));
        $this->assertNull(SocialLink::url('website', 'monsite'));
        $this->assertNull(SocialLink::url('instagram', '  '));
    }

    public function test_writer_profile_links_instagram_handle_externally(): void
    {
        $writer = $this->makeWriter(['instagram' => 'lahlougo']);

        $this->get(route('public.writer', $writer))
            ->assertOk()
            ->assertSee('href="https://instagram.com/lahlougo"', false)
            ->assertSee('<title>'.e($writer->user->name).' — Écrivain | '.e(config('site.name')).'</title>', false);
    }

    public function test_admin_and_empty_profiles_are_not_featured_of_the_day(): void
    {
        $admin = User::factory()->create(['name' => 'AdminAbiyou', 'account_type' => 'writer', 'is_admin' => true]);
        $adminWriter = Writer::create(['user_id' => $admin->id]);
        $this->addDocument($adminWriter);
        $empty = User::factory()->create(['name' => 'Plume Vide', 'account_type' => 'writer']);
        Writer::create(['user_id' => $empty->id]);

        $this->get(route('public.writers'))
            ->assertOk()
            ->assertDontSee('Plume du jour');

        $active = $this->makeWriter([], 'Plume Active');
        $this->addDocument($active);

        $this->get(route('public.writers'))
            ->assertOk()
            ->assertSee('Plume du jour')
            ->assertSeeInOrder(['Plume du jour', 'Plume Active']);
    }

    public function test_public_pages_render(): void
    {
        $artist = $this->makeArtist();
        $illustration = Illustration::create([
            'artist_id' => $artist->id,
            'title' => 'Meca',
            'image_path' => 'illustrations/meca.jpg',
            'file_size_bytes' => 10,
        ]);
        $portfolio = ArtistPortfolio::create(['artist_id' => $artist->id, 'title' => 'Visite']);
        $portfolio->items()->create(['illustration_id' => $illustration->id, 'position' => 0]);

        $this->get('/')->assertOk()->assertSee('og:image', false);
        $this->get(route('public.artists'))->assertOk()->assertSee('1 illustration')->assertDontSee('(s)');
        $this->get(route('public.artist', $artist))->assertOk();
        $this->get(route('public.illustration', $illustration))
            ->assertOk()
            ->assertSee('<title>Meca – '.e($artist->user->name).' | '.e(config('site.name')).'</title>', false);
        $this->get(route('artist-portfolios.show', $portfolio))
            ->assertOk()
            ->assertDontSee('247')
            ->assertSee('followersCount');
        $this->get(route('public.illustrations'))->assertOk();
        $this->get(route('public.literary-works'))->assertOk();
        $this->get(route('public.about'))->assertOk()->assertSee('Rejoindre le collectif');
        $this->get(route('login'))->assertOk()->assertSee('Accès à votre espace')->assertSee('Retour à l\'accueil', false);
    }

    public function test_missing_page_uses_branded_404(): void
    {
        $this->get('/writer/lahlougo')
            ->assertNotFound()
            ->assertSee('Page introuvable')
            ->assertSee('Retour à l\'accueil', false);
    }

    public function test_thumbnail_is_generated_on_upload(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $image = imagecreatetruecolor(1800, 3200);
        ob_start();
        imagejpeg($image);
        Storage::disk('public')->put('illustrations/big.jpg', ob_get_clean());

        $illustration = Illustration::create([
            'artist_id' => $this->makeArtist()->id,
            'title' => 'Grande',
            'image_path' => 'illustrations/big.jpg',
            'file_size_bytes' => 10,
        ]);

        $thumbnail = \App\Support\ImageThumbnail::pathFor('illustrations/big.jpg');
        Storage::disk('public')->assertExists($thumbnail);
        [$width, , $type] = getimagesizefromstring(Storage::disk('public')->get($thumbnail));
        $this->assertSame(600, $width);
        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertStringContainsString('/storage/'.$thumbnail, $illustration->thumbnailUrl());
    }

    public function test_missing_thumbnail_is_created_on_first_request(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        // Œuvre publiée avant la mise en place des miniatures : aucune miniature sur le disque.
        $illustration = Illustration::withoutEvents(fn () => Illustration::create([
            'artist_id' => $this->makeArtist()->id,
            'title' => 'Ancienne',
            'image_path' => 'illustrations/old.jpg',
            'file_size_bytes' => 10,
        ]));
        $image = imagecreatetruecolor(2480, 3508);
        ob_start();
        imagejpeg($image);
        Storage::disk('public')->put('illustrations/old.jpg', ob_get_clean());

        // Les grilles pointent vers la route de génération, jamais vers l'original.
        $url = $illustration->thumbnailUrl();
        $this->assertSame(route('thumbnails.illustration', $illustration), $url);
        $this->get(route('public.illustrations'))->assertSee($url, false)->assertDontSee('storage/illustrations/old.jpg', false);

        $response = $this->get($url);
        $response->assertOk();
        [$width] = getimagesize($response->getFile()->getPathname());
        $this->assertSame(600, $width);

        // Ensuite, l'URL est celle du fichier statique.
        $this->assertStringContainsString('/storage/thumbnails/', $illustration->thumbnailUrl());
    }

    private function makeArtist(): Artist
    {
        $user = User::factory()->create(['account_type' => 'artist']);

        return Artist::create(['user_id' => $user->id]);
    }

    private function makeWriter(array $attributes = [], ?string $name = null): Writer
    {
        $user = User::factory()->create(array_filter(['account_type' => 'writer', 'name' => $name]));

        return Writer::create(['user_id' => $user->id] + $attributes);
    }

    private function addDocument(Writer $writer): Document
    {
        return Document::create([
            'writer_id' => $writer->id,
            'title' => 'Récit',
            'file_path' => 'documents/files/story.txt',
            'file_type' => 'txt',
            'file_size_bytes' => 1,
            'cover_image_path' => 'documents/covers/story.jpg',
            'cover_image_size_bytes' => 1,
        ]);
    }
}
