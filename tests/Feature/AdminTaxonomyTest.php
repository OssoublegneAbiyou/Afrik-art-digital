<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\Artist;
use App\Models\Document;
use App\Models\Genre;
use App\Models\Illustration;
use App\Models\IllustrationType;
use App\Models\User;
use App\Models\Writer;
use App\Support\Settings;
use App\Support\SvgSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    private const STORY = "# Chapitre 1\n\nLa pluie tombait sur Adjamé.";

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\PlatformSetting::updateModeration(false);
        Storage::fake('public');
        Storage::fake('local');
    }

    // ---------- Accès ----------

    public function test_only_admins_can_manage_types_and_genres(): void
    {
        $this->get(route('admin.taxonomy.index'))->assertRedirect(route('login'));

        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member)->get(route('admin.taxonomy.index'))->assertForbidden();

        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $this->actingAs($moderator)->get(route('admin.taxonomy.index'))->assertForbidden();
        $this->actingAs($moderator)->post(route('admin.taxonomy.types.store'), ['nom' => 'Manga'])->assertForbidden();
        $this->assertDatabaseMissing('illustration_types', ['nom' => 'Manga']);

        $this->actingAs($this->admin())->get(route('admin.taxonomy.index'))
            ->assertOk()
            ->assertSee('Types &amp; genres', false)
            ->assertSee('Croquis')
            ->assertSee('Science-fiction')
            ->assertSee(route('admin.taxonomy.genres.create'), false);

        $this->actingAs($this->admin())->get(route('admin.taxonomy.genres.create'))->assertOk()->assertSee('Nouveau genre');
        $this->actingAs($this->admin())->get(route('admin.taxonomy.genres.edit', Genre::where('slug', 'fantasy')->first()))
            ->assertOk()
            ->assertSee('Adresse figée')
            ->assertSee('#C9B8FF')
            ->assertSee('<path d="M60 26 V12 l11 4 -11 4"/>', false);
    }

    public function test_initial_lists_match_the_previous_values(): void
    {
        $this->assertSame(['croquis' => 'Croquis', 'couleur' => 'Couleur', 'couverture' => 'Couverture', 'autre' => 'Autre'], IllustrationType::options());
        $this->assertSame(['fantasy', 'science-fiction', 'thriller', 'conte', 'poesie'], array_keys(Genre::options()));

        $fantasy = \App\Support\SongeSky::get('fantasy');
        $this->assertSame('linear-gradient(160deg,#F5D9FF,#B79CF0 50%,#6B52B8)', $fantasy['sky_day']);
        $this->assertSame('#C9B8FF', $fantasy['glow']);
        $this->assertSame('Tous', \App\Support\SongeSky::get(null)['label']);

        // Emblème d'origine restitué à l'identique.
        $this->assertStringContainsString('<ellipse cx="60" cy="62" rx="44" ry="11" transform="rotate(-18 60 62)"/>', Genre::emblemFor('science-fiction')['inner']);
        $this->assertStringContainsString('stroke-dasharray="2 6"', Genre::emblemFor('thriller')['inner']);
    }

    // ---------- Genres ----------

    public function test_added_genre_is_offered_to_writers_and_in_filters(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.taxonomy.genres.store'), $this->genrePayload(['nom' => 'Horreur']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $genre = Genre::where('slug', 'horreur')->firstOrFail();
        $this->assertSame('#4A0E0E', $genre->ciel_jour);
        $this->assertSame(6, $genre->ordre);
        $this->assertTrue(AdminLog::where('action', 'genre.cree')->exists());

        [$author, $writer] = $this->writer();

        $this->actingAs($author)->get(route('dashboard'))->assertOk()->assertSee('Horreur');
        $this->actingAs($author)->get(route('documents.create'))->assertOk()->assertSee('value="horreur"', false);

        $this->actingAs($author)->post(route('documents.store'), [
            'title' => 'La maison du marigot', 'genre' => 'horreur', 'content' => self::STORY, 'rights' => '1',
            'cover_image' => UploadedFile::fake()->create('c.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        $this->assertSame('horreur', Document::firstOrFail()->genre);

        $this->get(route('public.writers'))->assertOk()
            ->assertSee('Horreur')
            ->assertSee('Six ciels')
            ->assertSee('Une maison qui grince');
        $this->get(route('public.literary-works'))->assertOk()->assertSee('Horreur');
        $this->get(route('documents.read', Document::firstOrFail()))->assertOk()->assertSee('HORREUR');
    }

    public function test_used_genre_cannot_be_deleted_but_can_be_deactivated(): void
    {
        $admin = $this->admin();
        [$author, $writer] = $this->writer();
        $document = $this->document($writer, ['genre' => 'thriller']);
        $thriller = Genre::where('slug', 'thriller')->firstOrFail();

        $this->actingAs($admin)->from(route('admin.taxonomy.genres.edit', $thriller))
            ->delete(route('admin.taxonomy.genres.destroy', $thriller))
            ->assertRedirect(route('admin.taxonomy.genres.edit', $thriller))
            ->assertSessionHas('error', 'Ce genre est utilisé par 1 récit : désactivez-le plutôt.');
        $this->assertDatabaseHas('genres', ['slug' => 'thriller']);

        $this->actingAs($admin)->put(route('admin.taxonomy.genres.update', $thriller), $this->genrePayload([
            'nom' => 'Thriller', 'actif' => '0',
            'ciel_jour' => $thriller->ciel_jour, 'ciel_nuit' => $thriller->ciel_nuit, 'emblem' => $thriller->emblem,
        ]))->assertSessionHasNoErrors();

        $this->assertFalse($thriller->fresh()->actif);
        $this->assertSame('genre.modifie', AdminLog::latest('id')->first()->action);
        $this->assertSame('thriller', $thriller->fresh()->slug, 'le slug est figé');
        $this->assertArrayNotHasKey('thriller', Genre::options());

        // Plus proposé à l'envoi…
        $this->actingAs($author)->post(route('documents.store'), [
            'title' => 'Nouveau', 'genre' => 'thriller', 'content' => self::STORY, 'rights' => '1',
            'cover_image' => UploadedFile::fake()->create('c.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasErrors('genre');
        $this->actingAs($author)->get(route('documents.create'))->assertDontSee('value="thriller"', false);

        // … mais l'œuvre existante reste lisible avec son libellé, et son auteur peut la garder dans ce genre.
        $this->get(route('documents.read', $document))->assertOk()->assertSee('THRILLER');
        $this->actingAs($author)->get(route('documents.edit', $document))->assertOk()->assertSee('value="thriller"', false);
        $this->actingAs($author)->patch(route('documents.update', $document), ['genre' => 'thriller'])->assertSessionHasNoErrors();
        $this->get(route('public.writers'))->assertOk()->assertSee('Ombres sur la lagune')->assertDontSee('aria-label="Thriller :', false);

        // Un genre inutilisé se supprime.
        $conte = Genre::where('slug', 'conte')->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.taxonomy.genres.destroy', $conte))->assertRedirect(route('admin.taxonomy.index'));
        $this->assertDatabaseMissing('genres', ['slug' => 'conte']);
        $this->assertTrue(AdminLog::where('action', 'genre.supprime')->exists());
    }

    public function test_genre_colors_are_validated(): void
    {
        $this->actingAs($this->admin())->post(route('admin.taxonomy.genres.store'), $this->genrePayload([
            'encre_jour' => 'red', 'ciel_nuit' => 'url(javascript:alert(1))',
        ]))->assertSessionHasErrors(['encre_jour', 'ciel_nuit']);

        $this->assertDatabaseMissing('genres', ['slug' => 'horreur']);
    }

    public function test_malicious_svg_emblem_is_cleaned(): void
    {
        $evil = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 100" onload="alert(1)">'
            .'<script>alert(2)</script>'
            .'<style>svg{background:url(https://evil.test)}</style>'
            .'<foreignObject width="100" height="100"><div xmlns="http://www.w3.org/1999/xhtml">pirate</div></foreignObject>'
            .'<a href="javascript:alert(3)"><circle cx="5" cy="5" r="5"/></a>'
            .'<use xlink:href="#x"/>'
            .'<circle cx="50" cy="50" r="20" onclick="alert(4)" fill="url(#g)" stroke="#FF0000"/>'
            .'<g transform="translate(2 2)"><path d="M10 10 L 90 90" style="stroke:red"/></g>'
            .'</svg>';

        $clean = SvgSanitizer::clean($evil);

        foreach (['script', 'alert', 'onload', 'onclick', 'foreignObject', 'pirate', 'javascript', 'href', 'style', 'url(', '<a', '<use'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $clean, $forbidden);
        }
        $this->assertStringContainsString('viewBox="0 0 100 100"', $clean);
        $this->assertStringContainsString('<circle cx="50" cy="50" r="20" stroke="#FF0000"/>', $clean);
        $this->assertStringContainsString('<g transform="translate(2 2)"><path d="M10 10 L 90 90"/></g>', $clean);
        $this->assertNull(SvgSanitizer::clean('<script>alert(1)</script>'));
        $this->assertNull(SvgSanitizer::clean('<!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><svg><text>&e;</text></svg>'));

        // Par l'admin : enregistré assaini.
        $this->actingAs($this->admin())->post(route('admin.taxonomy.genres.store'), $this->genrePayload(['emblem' => $evil]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'ont été retirés'));
        $stored = Genre::where('slug', 'horreur')->firstOrFail()->emblem;
        $this->assertStringNotContainsStringIgnoringCase('script', $stored);
        $this->assertStringContainsString('<circle cx="50" cy="50" r="20"', $stored);

        // Même écrit directement en base, il est assaini à l'affichage.
        Genre::where('slug', 'horreur')->update(['emblem' => $evil]);
        Genre::flushCache();
        $this->get(route('public.writers'))->assertOk()
            ->assertDontSee('alert(2)', false)
            ->assertDontSee('onload="alert', false)
            ->assertDontSee('foreignObject', false);

        // Aperçu de l'admin : même nettoyage.
        $this->actingAs($this->admin())->postJson(route('admin.taxonomy.genres.preview'), ['emblem' => $evil])
            ->assertOk()
            ->assertJson(['valid' => true])
            ->assertJsonMissing(['svg' => 'script']);

        // Sans forme utilisable : refusé, et sans emblème : livre ouvert.
        $this->actingAs($this->admin())->post(route('admin.taxonomy.genres.store'), $this->genrePayload(['nom' => 'Romance', 'emblem' => '<script>x</script>']))
            ->assertSessionHasErrors('emblem');
        $this->assertSame(Genre::BOOK_EMBLEM, Genre::emblemFor('inconnu')['inner']);
    }

    // ---------- Types ----------

    public function test_types_admin_and_other_filter(): void
    {
        $admin = $this->admin();
        [$user, $artist] = $this->artist();
        $croquis = $this->illustration($artist, 'croquis', 'Esquisse du Plateau');
        $this->illustration($artist, 'manga', 'Planche inconnue');
        $this->illustration($artist, null, 'Sans type');
        $this->illustration($artist, 'couleur', 'Aquarelle');

        // Type inconnu ou absent → « Autre ».
        $this->get(route('public.illustrations', ['type' => 'autre']))->assertOk()
            ->assertSee('Planche inconnue')->assertSee('Sans type')->assertDontSee('Esquisse du Plateau');
        $this->get(route('public.illustrations', ['type' => 'croquis']))->assertOk()
            ->assertSee('Esquisse du Plateau')->assertDontSee('Planche inconnue');

        // Ajout d'un type : proposé à l'envoi et en filtre.
        $this->actingAs($admin)->post(route('admin.taxonomy.types.store'), ['nom' => 'Bande dessinée', 'form_id' => 'type-new'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('illustration_types', ['slug' => 'bande-dessinee', 'actif' => true, 'ordre' => 4]);
        $this->assertTrue(AdminLog::where('action', 'type.cree')->exists());
        $this->actingAs($user)->get(route('dashboard'))->assertSee('value="bande-dessinee"', false);
        $this->get(route('public.illustrations'))->assertSee('Bande dessinée');

        // « Autre » est réservé.
        $this->actingAs($admin)->post(route('admin.taxonomy.types.store'), ['nom' => 'Autre'])->assertSessionHasErrors('nom');

        // Type utilisé : suppression refusée, désactivation possible.
        $type = IllustrationType::where('slug', 'croquis')->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.taxonomy.types.destroy', $type))
            ->assertSessionHas('error', 'Ce type est utilisé par 1 illustration : désactivez-le plutôt.');
        $this->actingAs($admin)->put(route('admin.taxonomy.types.update', $type), ['nom' => 'Croquis', 'ordre' => 1, 'actif' => '0'])
            ->assertSessionHasNoErrors();
        $this->assertSame('type.modifie', AdminLog::latest('id')->first()->action);

        $this->get(route('public.illustrations', ['type' => 'autre']))->assertSee('Esquisse du Plateau');
        $this->get(route('public.illustrations'))->assertDontSee('value="croquis"', false)->assertSee('value="autre"', false);
        $this->assertSame('Croquis', $croquis->fresh()->typeLabel(), 'le libellé reste sur l’œuvre');
        $this->assertArrayNotHasKey('croquis', IllustrationType::options());

        $this->actingAs($user)->post(route('illustrations.store'), [
            'rights' => 1, 'type' => 'croquis', 'images' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertSessionHasErrors('type');

        // Monter / descendre.
        $couverture = IllustrationType::where('slug', 'couverture')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.taxonomy.types.move', $couverture), ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['croquis', 'couverture', 'couleur', 'bande-dessinee'], IllustrationType::query()->ordonne()->pluck('slug')->all());

        // Type inutilisé : supprimé.
        $this->actingAs($admin)->delete(route('admin.taxonomy.types.destroy', IllustrationType::where('slug', 'bande-dessinee')->first()))
            ->assertSessionHas('status');
        $this->assertDatabaseMissing('illustration_types', ['slug' => 'bande-dessinee']);
    }

    // ---------- Règles ----------

    public function test_quota_and_batch_size_follow_settings(): void
    {
        [$user, $artist] = $this->artist();
        $this->illustration($artist, 'couleur');

        Settings::set('regles.images_par_envoi', 2);
        Settings::set('regles.max_oeuvres_par_membre', 3);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('jusqu\'à 2 à la fois', false)
            ->assertSee('/ 3', false);

        $this->actingAs($user)->post(route('illustrations.store'), [
            'rights' => 1, 'type' => 'couleur',
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')],
        ])->assertSessionHasErrors(['images' => 'Tu peux envoyer jusqu’à 2 images à la fois.']);

        Settings::set('regles.images_par_envoi', 10);
        $this->actingAs($user)->post(route('illustrations.store'), [
            'rights' => 1, 'type' => 'couleur',
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')],
        ])->assertSessionHasErrors(['quota' => 'Il te reste 2 places sur 3 : retire des images de ton envoi ou supprime des œuvres.']);

        $this->actingAs($user)->post(route('illustrations.store'), [
            'rights' => 1, 'type' => 'couleur',
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasNoErrors();
        $this->assertSame(3, Illustration::count());
    }

    public function test_image_size_follows_setting(): void
    {
        [$user] = $this->artist();
        Settings::set('regles.taille_max_image_mo', 1);

        $this->actingAs($user)->get(route('dashboard'))->assertSee('1 Mo max chacune');

        $this->actingAs($user)->post(route('illustrations.store'), [
            'rights' => 1, 'type' => 'couleur', 'images' => [UploadedFile::fake()->image('lourde.jpg')->size(1500)],
        ])->assertSessionHasErrors(['images.0' => 'Chaque image doit faire 1 Mo au maximum.']);

        Settings::set('regles.taille_max_image_mo', 2);
        $this->actingAs($user)->post(route('illustrations.store'), [
            'rights' => 1, 'type' => 'couleur', 'images' => [UploadedFile::fake()->image('lourde.jpg')->size(1500)],
        ])->assertSessionHasNoErrors();
    }

    public function test_storage_limit_label_follows_setting(): void
    {
        [$author] = $this->writer();
        Settings::set('regles.stockage_max_go', 3);

        $this->assertSame(3 * 1024 ** 3, $author->storageLimitBytes());
        $this->actingAs($author)->get(route('dashboard'))->assertOk()->assertSee('3 Go');
    }

    public function test_download_rule_allowed_forbidden_or_author_choice(): void
    {
        [$author, $writer] = $this->writer();
        $reader = User::factory()->create(['account_type' => 'visitor']);
        $open = $this->document($writer, ['title' => 'Ouvert', 'allow_download' => true]);
        $closed = $this->document($writer, ['title' => 'Fermé', 'allow_download' => false]);

        // Au choix de l'auteur (comportement actuel).
        Settings::set('regles.telechargement_recits', 'choix_auteur');
        $this->actingAs($reader)->get(route('documents.download', $open))->assertOk();
        $this->actingAs($reader)->get(route('documents.download', $closed))->assertForbidden();
        $this->actingAs($author)->get(route('documents.edit', $closed))->assertSee('name="allow_download"', false);

        // Toujours autorisé.
        Settings::set('regles.telechargement_recits', 'autorise');
        $this->actingAs($reader)->get(route('documents.download', $closed))->assertOk();
        $this->actingAs($author)->get(route('documents.edit', $closed))->assertDontSee('name="allow_download"', false);

        // Toujours interdit, sauf pour l'auteur et l'équipe.
        Settings::set('regles.telechargement_recits', 'interdit');
        $this->actingAs($reader)->get(route('documents.download', $open))->assertForbidden();
        $this->actingAs($author)->get(route('documents.download', $open))->assertOk();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_MODERATOR]))->get(route('documents.download', $open))->assertOk();
        $this->actingAs($author)->get(route('documents.edit', $open))->assertDontSee('name="allow_download"', false);

        // Hors mode « au choix », enregistrer depuis l'éditeur ne touche pas au choix de l'auteur.
        $this->actingAs($author)->post(route('documents.save', $open), [
            'title' => 'Ouvert', 'genre' => 'thriller', 'content' => self::STORY, 'rights' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($open->fresh()->allow_download);
    }

    // ---------- Outils ----------

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function genrePayload(array $overrides = []): array
    {
        return $overrides + [
            'nom' => 'Horreur',
            'phrase_accroche' => 'Une maison qui grince, une porte qui s’ouvre seule.',
            'description' => 'Frissons et ombres.',
            'ciel_jour' => '#4a0e0e',
            'encre_jour' => '#FFE3D4',
            'ciel_nuit' => '#120404',
            'encre_nuit' => '#FFD9C7',
            'lueur_nuit' => '#FF5A5A',
            'emblem' => '<svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="30"/></svg>',
            'actif' => '1',
        ];
    }

    private function artist(): array
    {
        $user = User::factory()->create(['account_type' => 'artist']);

        return [$user, Artist::create(['user_id' => $user->id])];
    }

    private function illustration(Artist $artist, ?string $type, string $title = 'Œuvre'): Illustration
    {
        return Illustration::create([
            'artist_id' => $artist->id, 'title' => $title, 'type' => $type,
            'image_path' => 'illustrations/x.jpg', 'file_size_bytes' => 10,
        ]);
    }

    private function writer(): array
    {
        $user = User::factory()->create(['name' => 'Awa Plume', 'account_type' => 'writer']);

        return [$user, Writer::create(['user_id' => $user->id])];
    }

    private function document(Writer $writer, array $attributes = []): Document
    {
        return Document::create($attributes + [
            'writer_id' => $writer->id, 'title' => 'Ombres sur la lagune', 'genre' => 'thriller',
            'content' => self::STORY, 'file_disk' => 'local', 'file_size_bytes' => 0,
            'cover_image_path' => 'documents/covers/c.jpg', 'cover_image_size_bytes' => 1,
        ]);
    }
}
