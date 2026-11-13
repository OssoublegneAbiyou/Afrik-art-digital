<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Document;
use App\Models\Illustration;
use App\Models\User;
use App\Models\Writer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PontPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_home_renders_in_blade_with_pont_world_and_dynamic_data(): void
    {
        $artistUser = User::factory()->create(['account_type' => 'artist', 'name' => 'Trait Nocturne']);
        $artist = Artist::create(['user_id' => $artistUser->id]);
        Illustration::create([
            'artist_id' => $artist->id,
            'title' => 'Lagune bleue',
            'image_path' => 'illustrations/lagune.jpg',
            'file_size_bytes' => 10,
        ]);

        $writerUser = User::factory()->create(['account_type' => 'writer', 'name' => 'Plume Lunaire']);
        $writer = Writer::create(['user_id' => $writerUser->id]);
        Document::create([
            'writer_id' => $writer->id,
            'title' => 'Le dernier gbaka',
            'description' => 'Une nuit, le dernier gbaka ne s’arrêta pas.',
            'genre' => 'conte',
            'file_path' => 'documents/files/story.txt',
            'file_type' => 'txt',
            'file_size_bytes' => 1,
            'cover_image_path' => 'documents/covers/story.jpg',
            'cover_image_size_bytes' => 1,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-world="pont"', false)
            ->assertDontSee('home-root', false)
            ->assertSee('og:image', false)
            ->assertSee('/miniatures/illustrations/', false)
            ->assertDontSee('storage/illustrations/lagune.jpg', false)
            ->assertSee('id="apropos"', false)
            ->assertSee('Lagune bleue')
            ->assertSee('Le dernier gbaka')
            ->assertSee('Une nuit, le dernier gbaka')
            ->assertSee('Trait Nocturne')
            ->assertSee('Plume Lunaire')
            ->assertSee('Artiste du jour')
            ->assertSee('Plume du jour')
            ->assertSee('Demander à rejoindre')
            ->assertSee(route('public.about').'#rejoindre', false)
            ->assertSee('バビの夜')
            ->assertSee('GBAKA LINE 09');
    }

    public function test_home_hides_featured_cards_when_no_profile(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Artiste du jour')
            ->assertDontSee('Plume du jour')
            ->assertSee('Le fil est encore silencieux');
    }

    public function test_home_uses_collective_text_from_admin(): void
    {
        \App\Models\PlatformSetting::updateCollectiveText('Nous sommes un collectif de test.');

        $this->get('/')->assertOk()->assertSee('Nous sommes un collectif de test.');
        // La page À propos a son propre texte (réglage presentation.a_propos).
        Settings::set('presentation.a_propos', '<p>Notre histoire de test.</p>');
        $this->get(route('public.about'))->assertOk()->assertSee('Notre histoire de test.');
    }

    public function test_about_page_is_in_pont_world_with_join_and_contact(): void
    {
        Settings::set('contact.email', 'contact@example.test');

        $this->get(route('public.about'))
            ->assertOk()
            ->assertSee('data-world="pont"', false)
            ->assertSee('id="rejoindre"', false)
            ->assertSee('id="contact"', false)
            ->assertSee('mailto:contact@example.test', false)
            ->assertSee('action="'.route('join-requests.store').'"', false)
            ->assertSee('Rejoindre le collectif');
    }

    public function test_about_page_without_contact_hides_the_contact_block(): void
    {
        foreach (['contact.email', 'contact.telephone', 'contact.whatsapp', 'contact.adresse', 'reseaux.instagram', 'reseaux.facebook'] as $key) {
            Settings::set($key, null);
        }

        $this->get(route('public.about'))
            ->assertOk()
            ->assertDontSee('mailto:', false)
            ->assertDontSee('id="contact"', false)
            ->assertDontSee('bientôt disponibles');
    }

    public function test_login_page_follows_pont_design_and_keeps_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Accès à votre espace')
            ->assertSee('Retour au site')
            ->assertSee("Retour à l'accueil", false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('<label', false)
            ->assertSee('Mot de passe oublié ?')
            ->assertSee('Se connecter')
            ->assertSee('Les comptes sont créés par l', false)
            ->assertSee('Demander à rejoindre le collectif')
            ->assertSee(route('public.about').'#rejoindre', false)
            ->assertSee('ようこそ');
    }

    public function test_login_still_authenticates(): void
    {
        $user = User::factory()->create(['account_type' => 'visitor']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_password_page_renders_in_french(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Envoyer le lien')
            ->assertSee('name="email"', false);
    }

    public function test_custom_404_page(): void
    {
        $this->get('/une-page-qui-nexiste-pas')
            ->assertNotFound()
            ->assertSee('Cette ruelle ne mène nulle part.')
            ->assertSee('Page introuvable')
            ->assertSee('迷子')
            ->assertSee('Reprendre le chemin')
            ->assertSee('href="/illustrateurs"', false)
            ->assertSee('href="/ecrivains"', false)
            ->assertSee('Retour à l\'accueil', false);
    }

    public function test_500_page_is_standalone(): void
    {
        $html = view('errors.500')->render();

        $this->assertStringContainsString('Coupure de courant dans le quartier.', $html);
        $this->assertStringContainsString('500', $html);
        $this->assertStringContainsString('<style>', $html);
        $this->assertStringContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('/build/', $html);
        $this->assertStringNotContainsString('@vite', $html);
        $this->assertStringNotContainsString('127.0.0.1:5173', $html);
        $this->assertStringContainsString('href="/illustrateurs"', $html);
    }

    public function test_all_error_pages_render(): void
    {
        foreach (['403', '404', '419', '429', '500', '503'] as $code) {
            $html = view('errors.'.$code)->render();
            $this->assertStringContainsString('Reprendre le chemin', $html, "errors.$code");
            $this->assertStringContainsString("Afrik'art Digital", $html, "errors.$code");
        }
    }
}
