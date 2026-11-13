<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\RichText;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** Envoie un onglet complet : valeurs actuelles + modifications. */
    private function saveTab(User $user, string $tab, array $changes = [], array $files = [])
    {
        $data = ['_method' => 'PUT', 'onglet' => $tab];

        foreach (Settings::definitions() as $key => $definition) {
            $groups = \App\Http\Controllers\Admin\SettingsController::TABS[$tab]['groups'] ?? [];
            if (! in_array($definition['group'], $groups, true) || $definition['type'] === 'image') {
                continue;
            }
            $value = Settings::get($key);
            $data[str_replace('.', '__', $key)] = match ($definition['type']) {
                'json' => implode("\n", (array) $value),
                'boolean' => $value ? '1' : '0',
                default => $value,
            };
        }

        foreach ($changes as $key => $value) {
            $data[str_replace('.', '__', $key)] = $value;
        }

        return $this->actingAs($user)->post(route('admin.settings.update'), array_merge($data, $files));
    }

    public function test_only_admins_can_open_settings(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));

        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member)->get(route('admin.settings.edit'))->assertForbidden();

        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $this->actingAs($moderator)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($moderator)->put(route('admin.settings.update'), ['onglet' => 'identite', 'identite__nom' => 'Pirate'])->assertForbidden();

        $admin = $this->admin();
        foreach (array_keys(\App\Http\Controllers\Admin\SettingsController::TABS) as $tab) {
            $this->actingAs($admin)->get(route('admin.settings.edit', ['onglet' => $tab]))
                ->assertOk()
                ->assertSee('Réglages du collectif')
                ->assertSee('Enregistrer');
        }

        $this->actingAs($admin)->get(route('admin.settings.edit', ['onglet' => 'presentation']))
            ->assertSee('name="presentation__accueil_intro"', false)
            ->assertDontSee('accueil__artiste_du_jour_mode', false)
            ->assertSee('Voir sur le site ↗');
    }

    public function test_identity_and_contact_changes_are_visible_on_the_site_and_logged(): void
    {
        $admin = $this->admin();
        $this->get('/')->assertOk()->assertSee('là où le néon rêve');

        $this->saveTab($admin, 'identite', ['identite.nom' => 'Collectif Lagune', 'identite.slogan' => 'les néons de la lagune'])
            ->assertRedirect(route('admin.settings.edit', ['onglet' => 'identite']))
            ->assertSessionHas('status');

        $this->saveTab($admin, 'contact', ['contact.email' => 'Bonjour@Lagune.test', 'contact.afficher_email' => '1'])
            ->assertSessionHasNoErrors();

        $this->get('/')
            ->assertOk()
            ->assertSee('Collectif Lagune')
            ->assertSee('les néons de la lagune')
            ->assertSee('mailto:bonjour@lagune.test', false);

        $this->get(route('public.about'))->assertSee('mailto:bonjour@lagune.test', false);

        $log = AdminLog::where('action', 'reglage.modifie')->where('cible_id', 'identite.nom')->sole();
        $this->assertSame("Afrik'art Digital", $log->avant['valeur']);
        $this->assertSame('Collectif Lagune', $log->apres['valeur']);
        $this->assertSame($admin->id, $log->user_id);
        // Une entrée par réglage réellement modifié (ville, description… inchangés).
        $this->assertSame(0, AdminLog::where('cible_id', 'identite.ville')->count());

        // E-mail masqué publiquement.
        $this->saveTab($admin, 'contact', ['contact.afficher_email' => '0']);
        $this->get(route('public.about'))->assertDontSee('mailto:bonjour@lagune.test', false);
    }

    public function test_social_links_are_normalized_and_empty_ones_hidden(): void
    {
        $admin = $this->admin();

        $this->saveTab($admin, 'contact', [
            'reseaux.instagram' => '@lagune.collectif',
            'reseaux.tiktok' => '@lagune',
            'reseaux.youtube' => 'http://youtube.com/@lagune',
            'reseaux.facebook' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://instagram.com/lagune.collectif', Settings::get('reseaux.instagram'));
        $this->assertSame('https://www.tiktok.com/@lagune', Settings::get('reseaux.tiktok'));
        $this->assertSame('https://youtube.com/@lagune', Settings::get('reseaux.youtube'));

        $this->get('/')
            ->assertOk()
            ->assertSee('href="https://instagram.com/lagune.collectif"', false)
            ->assertSee('aria-label="Instagram (nouvel onglet)"', false)
            ->assertSee('aria-label="TikTok (nouvel onglet)"', false)
            ->assertDontSee('aria-label="Facebook (nouvel onglet)"', false)
            ->assertDontSee('aria-label="Behance (nouvel onglet)"', false);

        $this->saveTab($admin, 'contact', ['reseaux.site' => '@pas-un-site'])
            ->assertSessionHasErrors('reseaux__site');
    }

    public function test_empty_contact_hides_the_whole_block(): void
    {
        foreach (['contact.email', 'contact.telephone', 'contact.whatsapp', 'contact.adresse'] as $key) {
            Settings::set($key, null);
        }
        foreach (array_keys(Settings::definitions()) as $key) {
            if (str_starts_with($key, 'reseaux.')) {
                Settings::set($key, null);
            }
        }

        $this->get(route('public.about'))
            ->assertOk()
            ->assertDontSee('id="contact"', false)
            ->assertDontSee('bientôt disponibles')
            ->assertDontSee('#contact', false);

        Settings::set('contact.telephone', '+225 07 00 00 00 00');
        Settings::set('contact.whatsapp', '+225 05 11 22 33 44');
        Settings::set('contact.adresse', "Rue des Jardins\nCocody");

        $this->get(route('public.about'))
            ->assertSee('id="contact"', false)
            ->assertSee('href="tel:+2250700000000"', false)
            ->assertSee('https://wa.me/2250511223344', false)
            ->assertSee('Rue des Jardins<br />', false);
    }

    public function test_validation_errors_are_shown_under_fields(): void
    {
        $admin = $this->admin();

        $this->saveTab($admin, 'contact', ['contact.email' => 'pas-un-email', 'contact.telephone' => 'appelle-moi'])
            ->assertSessionHasErrors(['contact__email', 'contact__telephone']);

        $this->saveTab($admin, 'identite', ['identite.nom' => '', 'identite.annee_creation' => '1800'])
            ->assertSessionHasErrors(['identite__nom', 'identite__annee_creation']);

        $this->saveTab($admin, 'presentation', ['presentation.meta_description' => str_repeat('a', 161)])
            ->assertSessionHasErrors('presentation__meta_description');

        $this->followRedirects($this->saveTab($admin, 'contact', ['contact.email' => 'pas-un-email']))
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('id="reglage-contact__email-erreur"', false);

        $this->assertNull(Settings::get('contact.email'));
    }

    public function test_rich_text_is_sanitized(): void
    {
        $admin = $this->admin();

        $this->saveTab($admin, 'presentation', [
            'presentation.a_propos' => '<h2 style="color:red">Qui</h2><p onclick="alert(1)">Un <b>collectif</b> <a href="javascript:alert(1)">piège</a> '
                .'<a href="https://example.test" target="_blank">site</a></p><script>alert("x")</script><img src=x onerror=alert(1)>',
        ])->assertSessionHasNoErrors();

        $stored = Settings::get('presentation.a_propos');
        $this->assertSame('<h2>Qui</h2><p>Un <strong>collectif</strong> piège <a href="https://example.test">site</a></p>', $stored);

        $this->get(route('public.about'))
            ->assertOk()
            ->assertSee('<strong>collectif</strong>', false)
            ->assertDontSee('alert(', false)
            ->assertDontSee('onclick', false);

        $this->assertSame("<p>Ligne 1<br>\nLigne 2</p>\n<p>Paragraphe</p>", RichText::clean("Ligne 1\nLigne 2\n\nParagraphe"));
        $this->assertSame('<p>Été « ok »</p>', RichText::clean('<p>Été « ok »</p>'));
        $this->assertSame('<a href="mailto:a@b.test">écrire</a>', RichText::clean('<a href=" mailto:a@b.test" title="x">écrire</a>'));
        $this->assertSame('texte', RichText::clean('<div><span style="x">texte</span></div><!-- note -->'));
    }

    public function test_images_can_be_uploaded_replaced_and_removed(): void
    {
        $admin = $this->admin();

        $this->saveTab($admin, 'identite', [], ['identite__logo' => UploadedFile::fake()->image('logo.png', 200, 200)])
            ->assertSessionHasNoErrors();

        $first = Settings::get('identite.logo');
        $this->assertStringStartsWith('collectif/', $first);
        Storage::disk('public')->assertExists($first);

        $this->get('/')->assertSee('class="w-logo__img"', false)->assertSee($first, false);

        // Remplacer : l'ancien fichier est supprimé.
        $this->saveTab($admin, 'identite', [], ['identite__logo' => UploadedFile::fake()->image('logo2.webp', 100, 100)]);
        $second = Settings::get('identite.logo');
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        // SVG refusé.
        $this->saveTab($admin, 'identite', [], ['identite__favicon' => UploadedFile::fake()->create('icone.svg', 2, 'image/svg+xml')])
            ->assertSessionHasErrors('identite__favicon');

        // Retirer.
        $this->actingAs($admin)->put(route('admin.settings.update'), ['onglet' => 'identite', 'retirer' => 'identite__logo'])
            ->assertRedirect(route('admin.settings.edit', ['onglet' => 'identite']));
        $this->assertNull(Settings::get('identite.logo'));
        Storage::disk('public')->assertMissing($second);
        $this->get('/')->assertDontSee('w-logo__img', false);

        $this->assertSame(3, AdminLog::where('cible_id', 'identite.logo')->count());
    }

    public function test_noindex_when_indexing_is_disabled(): void
    {
        $this->get('/')->assertDontSee('<meta name="robots" content="noindex">', false);

        $this->saveTab($this->admin(), 'seo', ['seo.indexation' => '0', 'seo.suffixe_titre' => '· Lagune'])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/')->assertSee('<meta name="robots" content="noindex">', false);
        $this->get(route('public.about'))->assertSee('<title>À propos · Lagune</title>', false);
    }

    public function test_decor_texts_are_limited_and_used_on_the_site(): void
    {
        $admin = $this->admin();

        $this->saveTab($admin, 'decor', ['decor.bandeau_nuit' => "EXPO ZIGUEHI\n".str_repeat('X', 41)])
            ->assertSessionHasErrors('decor__bandeau_nuit');
        $this->saveTab($admin, 'decor', ['decor.panneau_directions' => "Un\nDeux"])
            ->assertSessionHasErrors('decor__panneau_directions');
        $this->assertContains('LIBÈRE TA CRÉATIVITÉ', Settings::get('decor.bandeau_nuit'));

        $this->saveTab($admin, 'decor', [
            'decor.bandeau_nuit' => "EXPO ZIGUEHI\n\n  CONCOURS 2026  ",
            'decor.ecran_drop' => 'VERNISSAGE · 19:00',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['EXPO ZIGUEHI', 'CONCOURS 2026'], Settings::get('decor.bandeau_nuit'));

        $this->get(route('public.illustrations'))->assertOk()->assertSee('EXPO ZIGUEHI')->assertDontSee('LIBÈRE TA CRÉATIVITÉ');
        $this->get(route('public.artists'))->assertOk()->assertSee('VERNISSAGE · 19:00')->assertSee('Yopougon · Street Art');
    }

    public function test_closed_membership_shows_message_without_form(): void
    {
        $this->get(route('public.about'))
            ->assertSee('name="site_web"', false)
            ->assertSee('tabindex="-1"', false);

        $this->saveTab($this->admin(), 'adhesion', [
            'adhesion.ouverte' => '0',
            'adhesion.message_ferme' => 'Revenez en janvier pour la prochaine session.',
        ])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get(route('public.about'))
            ->assertOk()
            ->assertSee('Revenez en janvier pour la prochaine session.')
            ->assertDontSee('action="'.route('join-requests.store').'"', false);
    }

    public function test_rules_tab_and_dashboard_themes(): void
    {
        $admin = $this->admin();

        $this->saveTab($admin, 'regles', ['regles.max_oeuvres_par_membre' => '35', 'regles.telechargement_recits' => 'interdit'])
            ->assertSessionHasNoErrors();
        $this->assertSame(35, Settings::get('regles.max_oeuvres_par_membre'));
        $this->assertSame('interdit', Settings::get('regles.telechargement_recits'));

        $this->saveTab($admin, 'regles', ['regles.telechargement_recits' => 'peut-etre'])
            ->assertSessionHasErrors('regles__telechargement_recits');

        $themes = PlatformSetting::dashboardThemes();
        $payload = ['_method' => 'PUT', 'onglet' => 'apparence', 'themes' => []];
        foreach ($themes as $type => $theme) {
            $payload['themes'][$type] = collect($theme)->only(['background', 'panel', 'text', 'accent'])->all();
        }
        $payload['themes']['artist']['accent'] = '#123456';

        $this->actingAs($admin)->post(route('admin.settings.update'), $payload)->assertSessionHasNoErrors();
        $this->assertSame('#123456', PlatformSetting::dashboardThemes()['artist']['accent']);
        $this->assertSame(1, AdminLog::where('cible_id', 'dashboard_themes')->count());

        $payload['themes']['artist']['background'] = 'red; background-image: url(https://evil.test)';
        $this->actingAs($admin)->post(route('admin.settings.update'), $payload)->assertSessionHasErrors('themes.artist.background');
    }
}
