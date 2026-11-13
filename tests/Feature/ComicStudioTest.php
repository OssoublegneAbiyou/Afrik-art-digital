<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Comic;
use App\Models\ComicChapter;
use App\Models\ComicPage;
use App\Models\PlatformSetting;
use App\Models\Report;
use App\Models\User;
use App\Notifications\MemberActivity;
use App\Support\ComicImages;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComicStudioTest extends TestCase
{
    use RefreshDatabase;

    private array $tmp = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Comme Storage::fake(), mais dans un dossier propre à ce processus (suites lancées en parallèle).
        foreach (['public', 'local'] as $disk) {
            $root = storage_path('framework/testing/disks/comic-studio-'.getmypid().'/'.$disk);
            (new \Illuminate\Filesystem\Filesystem)->deleteDirectory($root);
            Storage::set($disk, Storage::createLocalDriver(['root' => $root]));
        }
        // Par défaut : pas de validation avant publication (cas testés explicitement plus bas).
        PlatformSetting::updateModeration(false);
    }

    protected function tearDown(): void
    {
        foreach ($this->tmp as $file) {
            @unlink($file);
        }

        (new \Illuminate\Filesystem\Filesystem)->deleteDirectory(storage_path('framework/testing/disks/comic-studio-'.getmypid()));

        parent::tearDown();
    }

    // ---------- Aides ----------

    private function artist(string $name = 'Kassi'): User
    {
        $user = User::factory()->create(['name' => $name, 'account_type' => 'artist']);
        Artist::create(['user_id' => $user->id]);

        return $user;
    }

    private function admin(): User
    {
        return User::factory()->create(['name' => 'Admin', 'role' => User::ROLE_ADMIN, 'account_type' => 'visitor']);
    }

    /** Petite image PNG générée avec GD. */
    private function image(string $name, int $width = 40, int $height = 60): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 40, 90));
        imagefilledrectangle($image, 0, 0, (int) ($width / 2), (int) ($height / 2), imagecolorallocate($image, 20, 200, 220));
        $path = tempnam(sys_get_temp_dir(), 'bd').'.png';
        imagepng($image, $path, 9);
        imagedestroy($image);
        $this->tmp[] = $path;

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function createComic(User $user, string $type = Comic::ONE_SHOT, string $title = 'Nuit à Treichville'): Comic
    {
        $this->actingAs($user)->post(route('comics.studio.store'), [
            'type' => $type,
            'titre' => $title,
            'resume' => 'Une course-poursuite sous les néons.',
            'couverture' => $this->image('couverture.png', 60, 90),
            'format_lecture' => 'vertical',
            'sens_lecture' => 'ltr',
            'tags' => 'action, Abidjan',
            'rights' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();

        return Comic::withUnpublished()->where('titre', $title)->sole();
    }

    private function firstChapter(Comic $comic): ComicChapter
    {
        return $comic->allChapters()->firstOrFail();
    }

    private function upload(User $user, Comic $comic, ComicChapter $chapter, UploadedFile $file)
    {
        return $this->actingAs($user)->post(route('comics.studio.pages.store', [$comic, $chapter]), ['page' => $file], ['Accept' => 'application/json']);
    }

    private function publish(User $user, Comic $comic, ComicChapter $chapter, array $data = ['action' => 'publie'])
    {
        return $this->actingAs($user)->post(route('comics.studio.chapters.publish', [$comic, $chapter]), $data);
    }

    private function order(ComicChapter $chapter): array
    {
        return ComicPage::where('comic_chapter_id', $chapter->id)->orderBy('ordre')->pluck('nom_fichier')->all();
    }

    // ---------- Tests ----------

    public function test_natural_sort_puts_2_before_10(): void
    {
        $this->assertSame(['1', '2', '3', '10', '11'], array_values(ComicImages::naturalSort(['10', '2', '11', '1', '3'])));
        $this->assertSame(
            ['page 1.png', 'Page 2.png', 'page 10.png'],
            array_values(ComicImages::naturalSort(['page 10.png', 'Page 2.png', 'page 1.png'])),
        );

        $user = $this->artist();
        $comic = $this->createComic($user);
        $chapter = $this->firstChapter($comic);

        foreach (['10.png', '2.png', '11.png', '1.png', '3.png'] as $name) {
            $this->upload($user, $comic, $chapter, $this->image($name))->assertCreated();
        }

        $this->assertSame(['10.png', '2.png', '11.png', '1.png', '3.png'], $this->order($chapter));

        $this->actingAs($user)->postJson(route('comics.studio.pages.sort', [$comic, $chapter]))->assertOk();
        $this->assertSame(['1.png', '2.png', '3.png', '10.png', '11.png'], $this->order($chapter));
    }

    public function test_pages_can_be_reordered(): void
    {
        $user = $this->artist();
        $comic = $this->createComic($user);
        $chapter = $this->firstChapter($comic);

        foreach (['a.png', 'b.png', 'c.png'] as $name) {
            $this->upload($user, $comic, $chapter, $this->image($name))->assertCreated();
        }

        $ids = ComicPage::where('comic_chapter_id', $chapter->id)->orderBy('ordre')->pluck('id', 'nom_fichier');

        $this->actingAs($user)->putJson(route('comics.studio.pages.reorder', [$comic, $chapter]), [
            'pages' => [$ids['c.png'], $ids['a.png'], $ids['b.png']],
        ])->assertOk()->assertJsonPath('state.pages.0.nom', 'c.png');

        $this->assertSame(['c.png', 'a.png', 'b.png'], $this->order($chapter));

        // Liste incomplète ou étrangère : refusée.
        $this->actingAs($user)->putJson(route('comics.studio.pages.reorder', [$comic, $chapter]), ['pages' => [$ids['a.png']]])
            ->assertStatus(422);

        // Suppression : l'ordre est recompacté.
        $this->actingAs($user)->deleteJson(route('comics.studio.pages.destroy', [$comic, $chapter, $ids['c.png']]))->assertOk();
        $this->assertSame(['a.png', 'b.png'], $this->order($chapter));
        $this->assertSame([1, 2], ComicPage::where('comic_chapter_id', $chapter->id)->orderBy('ordre')->pluck('ordre')->all());

        // Double page et description.
        $this->actingAs($user)->patchJson(route('comics.studio.pages.update', [$comic, $chapter, $ids['a.png']]), ['double_page' => 1, 'alt' => 'Le héros saute'])
            ->assertOk()->assertJsonPath('page.double_page', true);
        $this->assertSame('Le héros saute', ComicPage::find($ids['a.png'])->alt);
    }

    public function test_one_shot_of_twenty_pages_is_created_and_published(): void
    {
        $user = $this->artist();

        $this->actingAs($user)->get(route('comics.studio.create'))->assertOk()
            ->assertSee('Je certifie être l’auteur de ces planches', false)
            ->assertSee(route('pages.rules'), false);

        // Sans certification : refusé.
        $this->actingAs($user)->post(route('comics.studio.store'), ['type' => 'one_shot', 'titre' => 'X', 'format_lecture' => 'vertical', 'sens_lecture' => 'ltr'])
            ->assertSessionHasErrors('rights');

        $comic = $this->createComic($user);
        $chapter = $this->firstChapter($comic);
        $this->assertTrue($comic->isOneShot());
        $this->assertTrue($comic->isDraft());
        $this->assertSame(1.0, (float) $chapter->numero);
        $this->assertStringEndsWith('.webp', $comic->couverture);
        Storage::disk('public')->assertExists($comic->couverture);

        foreach (range(1, 20) as $n) {
            $this->upload($user, $comic, $chapter, $this->image(sprintf('%d.png', $n)))->assertCreated()
                ->assertJsonPath('page.statut', ComicPage::READY);
        }

        $pages = ComicPage::where('comic_chapter_id', $chapter->id)->orderBy('ordre')->get();
        $this->assertCount(20, $pages);
        $this->assertSame(20, $chapter->fresh()->pages_count);
        $this->assertSame(array_map(fn ($n) => $n.'.png', range(1, 20)), $pages->pluck('nom_fichier')->all());
        $pages->each(function (ComicPage $page) {
            $this->assertTrue($page->isReady());
            Storage::disk('public')->assertExists([$page->lecture, $page->miniature]);
            $this->assertStringEndsWith('.webp', $page->lecture);
            $this->assertSame(40, $page->largeur);
            $this->assertNull($page->tranches);
            $this->assertGreaterThan(0, $page->taille_octets);
        });

        // Étapes de l'assistant : planches (sans parler de chapitre), puis publication.
        $this->actingAs($user)->get(route('comics.studio.chapters.pages', [$comic, $chapter]))->assertOk()
            ->assertSee('Planches')->assertDontSee('Chapitre 1');
        $this->actingAs($user)->get(route('comics.studio.chapters.publication', [$comic, $chapter]))->assertOk()
            ->assertSee('Publier maintenant')->assertSee('Programmer')->assertSee('Enregistrer en brouillon');

        $this->publish($user, $comic, $chapter)->assertSessionHasNoErrors()->assertRedirect(route('comics.studio.manage', $comic));

        $comic->refresh();
        $chapter->refresh();
        $this->assertTrue($chapter->isVisible());
        $this->assertTrue($comic->isVisible());
        $this->assertNotNull($comic->last_chapter_at);
        $this->assertNotNull($chapter->notified_at);
        $this->assertSame(1, Comic::withVisibleChapters()->count());

        // Tableau de bord : section « Mes récits dessinés ».
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Mes récits dessinés')->assertSee('Nouveau récit dessiné')->assertSee('Nuit à Treichville')
            ->assertSee('En ligne')->assertSee('Stockage des récits dessinés');

        $this->actingAs($user)->get(route('comics.studio.manage', $comic))->assertOk()
            ->assertSee('Statistiques')->assertDontSee('Ajouter un chapitre');
    }

    public function test_publication_requires_cover_and_ready_pages(): void
    {
        $user = $this->artist();
        $comic = $this->createComic($user);
        $chapter = $this->firstChapter($comic);

        $this->publish($user, $comic, $chapter)->assertSessionHasErrors('publication');
        $this->assertTrue($chapter->fresh()->isDraft());

        // Brouillon : toujours possible, pages conservées (envoi reprenable).
        $this->upload($user, $comic, $chapter, $this->image('1.png'))->assertCreated();
        $this->publish($user, $comic, $chapter, ['action' => 'brouillon'])->assertSessionHasNoErrors();
        $this->assertTrue($chapter->fresh()->isDraft());
        $this->assertSame(1, $chapter->pages()->count());
    }

    public function test_series_scheduled_chapter_is_released_on_time_and_followers_notified_once(): void
    {
        Settings::set('bd.fuseau_horaire', 'Europe/Paris');
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(10, 0));

        $user = $this->artist();
        $comic = $this->createComic($user, Comic::SERIES, 'Les Gbakas de minuit');
        $one = $this->firstChapter($comic);
        $this->upload($user, $comic, $one, $this->image('01.png'))->assertCreated();
        $this->publish($user, $comic, $one)->assertSessionHasNoErrors();
        $this->assertTrue($comic->fresh()->isVisible());

        $fan = User::factory()->create(['account_type' => 'visitor']);
        $comic->followers()->attach($fan->id);

        // Chapitre 2 : numéro proposé = dernier + 1.
        $this->actingAs($user)->get(route('comics.studio.chapters.create', $comic))->assertOk()->assertSee('value="2"', false);
        $this->actingAs($user)->post(route('comics.studio.chapters.store', $comic), ['numero' => '2', 'titre' => 'Le retour'])->assertRedirect();
        $this->actingAs($user)->post(route('comics.studio.chapters.store', $comic), ['numero' => '2'])->assertSessionHasErrors('numero');
        $this->actingAs($user)->post(route('comics.studio.chapters.store', $comic), ['numero' => '1,5'])->assertRedirect();
        $this->assertEqualsCanonicalizing([1.0, 1.5, 2.0], ComicChapter::withUnpublished()->where('comic_id', $comic->id)->pluck('numero')->map(fn ($n) => (float) $n)->all());

        $two = ComicChapter::withUnpublished()->where('comic_id', $comic->id)->where('numero', 2)->sole();
        $this->upload($user, $comic, $two, $this->image('01.png'))->assertCreated();

        // Programmé à 12 h, heure de Paris (UTC+1 en novembre) = 11 h UTC.
        $this->publish($user, $comic, $two, ['action' => 'programme', 'scheduled_at' => '2026-11-20T12:00'])->assertSessionHasNoErrors();
        $two->refresh();
        $this->assertTrue($two->isScheduled());
        $this->assertSame('2026-11-20 11:00:00', $two->published_at->utc()->format('Y-m-d H:i:s'));
        $this->assertCount(0, $fan->fresh()->notifications);

        // Date passée : refusée.
        $this->publish($user, $comic, $two, ['action' => 'programme', 'scheduled_at' => '2026-11-20T09:00'])->assertSessionHasErrors('scheduled_at');

        $this->artisan('comics:release-scheduled')->assertSuccessful();
        $this->assertFalse($two->fresh()->isVisible());
        $this->assertCount(0, $fan->fresh()->notifications);

        $this->travelTo(now()->setTime(11, 1));
        $this->artisan('comics:release-scheduled')->assertSuccessful();
        $this->artisan('comics:release-scheduled')->assertSuccessful();

        $this->assertTrue($two->fresh()->isVisible());
        $this->assertNotNull($two->fresh()->notified_at);
        $notifications = $fan->fresh()->notifications;
        $this->assertCount(1, $notifications);
        $this->assertSame(MemberActivity::COMIC_NEW_CHAPTER, $notifications->first()->data['kind']);
        $this->assertStringContainsString('Les Gbakas de minuit ch. 2', $notifications->first()->data['message']);
        $this->assertTrue($comic->fresh()->last_chapter_at->eq($two->fresh()->published_at));
        // L'auteur n'est pas notifié de son propre chapitre.
        $this->assertCount(0, $user->fresh()->notifications->where('data.kind', MemberActivity::COMIC_NEW_CHAPTER));

        // Gérer la série : liste des chapitres et suppression (fichiers compris).
        $this->actingAs($user)->get(route('comics.studio.manage', $comic))->assertOk()->assertSee('Le retour')->assertSee('Ajouter un chapitre');
        $page = $two->pages()->first();
        $this->actingAs($user)->delete(route('comics.studio.chapters.destroy', [$comic, $two]))->assertRedirect();
        $this->assertNull(ComicChapter::withUnpublished()->withTrashed()->find($two->id));
        Storage::disk('public')->assertMissing($page->lecture);
        Storage::disk('local')->assertMissing($page->original);
    }

    public function test_new_comic_follower_notification_factory(): void
    {
        $user = $this->artist();
        $comic = $this->createComic($user, Comic::SERIES);
        $fan = User::factory()->create(['name' => 'Awa', 'account_type' => 'visitor']);

        $user->notify(MemberActivity::newComicFollower($comic, $fan));

        $this->assertSame(MemberActivity::COMIC_NEW_FOLLOWER, $user->fresh()->notifications->first()->data['kind']);
        $this->assertStringContainsString('Awa suit désormais', $user->fresh()->notifications->first()->data['message']);
    }

    public function test_webtoon_page_taller_than_5000px_is_sliced(): void
    {
        $user = $this->artist();
        $comic = $this->createComic($user);
        $chapter = $this->firstChapter($comic);

        $this->upload($user, $comic, $chapter, $this->image('webtoon.png', 800, 12000))->assertCreated()
            ->assertJsonPath('page.statut', ComicPage::READY)
            ->assertJsonPath('page.tranches', 6);

        $page = $chapter->pages()->sole();        $this->assertSame(800, $page->largeur);
        $this->assertSame(12000, $page->hauteur);
        $this->assertCount(6, $page->tranches);
        $this->assertSame(12000, array_sum(array_column($page->tranches, 'hauteur')));
        $this->assertSame($page->tranches[0]['chemin'], $page->lecture);

        foreach ($page->tranches as $slice) {
            $this->assertSame(800, $slice['largeur']);
            $this->assertLessThanOrEqual(2000, $slice['hauteur']);
            Storage::disk('public')->assertExists($slice['chemin']);
            [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($slice['chemin']));
            $this->assertSame([800, $slice['hauteur']], [$w, $h]);
        }

        // Miniature : 300 px de large, pas plus haute que 2:3.
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($page->miniature));
        $this->assertSame(300, $w);
        $this->assertLessThanOrEqual(450, $h);
    }

    public function test_reading_width_and_watermark_settings_apply(): void
    {
        Settings::set('bd.filigrane', true);
        $user = $this->artist();
        $comic = $this->createComic($user);
        $chapter = $this->firstChapter($comic);

        $this->upload($user, $comic, $chapter, $this->image('large.png', 2000, 1000))->assertCreated();
        $page = $chapter->pages()->sole();
        $this->assertSame(1600, $page->largeur);
        $this->assertSame(800, $page->hauteur);
        $this->assertSame('@Kassi · ', mb_substr(ComicImages::watermarkText($comic), 0, 9));
    }

    public function test_invalid_files_are_refused(): void
    {
        $user = $this->artist();
        $comic = $this->createComic($user);
        $chapter = $this->firstChapter($comic);

        // Faux PNG (texte renommé) : le vrai type est vérifié.
        $path = tempnam(sys_get_temp_dir(), 'bd');
        file_put_contents($path, 'pas une image');
        $this->tmp[] = $path;
        $fake = new UploadedFile($path, '1.png', 'image/png', null, true);
        $this->upload($user, $comic, $chapter, $fake)->assertStatus(422)->assertJsonValidationErrors('page');

        Settings::set('bd.formats_acceptes', 'jpg');
        $this->upload($user, $comic, $chapter, $this->image('2.png'))->assertStatus(422);
        $this->assertSame(0, $chapter->pages()->count());

        // Fichier abîmé directement sur le disque : la préparation échoue proprement, en français.
        $page = ComicPage::create([
            'comic_chapter_id' => $chapter->id, 'ordre' => 1, 'original' => 'comics/originaux/x.png',
            'statut_traitement' => ComicPage::PENDING,
        ]);
        Storage::disk('local')->put('comics/originaux/x.png', 'abîmé');
        $this->assertFalse(ComicImages::process($page));
        $this->assertSame(ComicPage::FAILED, $page->fresh()->statut_traitement);
        $this->assertSame('Ce fichier n’est pas une image lisible.', $page->fresh()->erreur);
    }

    public function test_original_is_never_reachable_by_public_url(): void
    {
        $user = $this->artist();
        $comic = $this->createComic($user);
        $chapter = $this->firstChapter($comic);

        $response = $this->upload($user, $comic, $chapter, $this->image('1.png'))->assertCreated();
        $page = $chapter->pages()->sole();

        $this->assertStringStartsWith('comics/originaux/', $page->original);
        Storage::disk('local')->assertExists($page->original);
        Storage::disk('public')->assertMissing($page->original);
        $this->assertStringNotContainsString($page->original, $response->getContent());
        $this->assertStringNotContainsString('originaux', $this->actingAs($user)->get(route('comics.studio.chapters.pages', [$comic, $chapter]))->getContent());
        $this->assertContains($this->get('/storage/'.$page->original)->status(), [403, 404]);
    }

    public function test_comic_storage_quota_is_enforced_and_separate(): void
    {
        Settings::set('bd.stockage_max_go', 1);
        $user = $this->artist();
        $comic = $this->createComic($user);
        $chapter = $this->firstChapter($comic);

        ComicPage::create([
            'comic_chapter_id' => $chapter->id, 'ordre' => 1, 'original' => 'comics/originaux/vieux.png',
            'taille_octets' => 1024 ** 3 - 10, 'statut_traitement' => ComicPage::READY,
        ]);

        $this->upload($user, $comic, $chapter, $this->image('2.png'))->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'stockage des récits dessinés plein'));
        $this->assertSame(1, $chapter->pages()->count());
        $this->assertGreaterThanOrEqual(1024 ** 3 - 10, ComicImages::usedBytes($user));
        // Séparé du stockage des illustrations.
        $this->assertLessThan(1024 ** 2, $user->fresh()->storageUsedBytes());

        $this->actingAs($user)->get(route('dashboard'))->assertSee('sur 1,00 Go');

        // Limite de pages par chapitre.
        Settings::set('bd.stockage_max_go', 3);
        Settings::set('bd.pages_max_par_chapitre', 1);
        $this->upload($user, $comic, $chapter, $this->image('3.png'))->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, '1 planches au maximum') || str_contains($m, 'au maximum'));
    }

    public function test_validation_before_publication_then_approved_or_rejected_with_notification(): void
    {
        PlatformSetting::updateModeration(true);
        $admin = $this->admin();
        $user = $this->artist();
        $comic = $this->createComic($user, Comic::SERIES, 'Zaouli');
        $one = $this->firstChapter($comic);
        $this->upload($user, $comic, $one, $this->image('1.png'))->assertCreated();

        $this->publish($user, $comic, $one)->assertSessionHas('success', fn ($m) => str_contains($m, 'après validation'));
        $one->refresh();
        $comic->refresh();
        $this->assertTrue($one->isPendingReview());
        $this->assertTrue($comic->isPendingReview());
        $this->assertFalse($comic->isVisible());
        $this->assertSame(MemberActivity::TO_REVIEW, $admin->fresh()->notifications->first()->data['kind']);

        // File « À valider » : aperçu des planches en grille, compteur du menu.
        $this->actingAs($admin)->get(route('admin.moderation.index'))->assertOk()
            ->assertSee('Zaouli')->assertSee('Récit dessiné · Série · chapitre 1')->assertSee('Planches (1)')
            ->assertSee($one->pages()->first()->thumbnailUrl(), false);

        $this->actingAs($user)->post(route('admin.moderation.approve', ['comic_chapter', $one->id]))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.moderation.approve', ['comic_chapter', $one->id]))->assertRedirect();

        $this->assertTrue($one->fresh()->isVisible());
        $this->assertTrue($comic->fresh()->isVisible());
        $this->assertNotNull($comic->fresh()->last_chapter_at);
        $approved = $user->fresh()->notifications->firstWhere('data.kind', MemberActivity::APPROVED);
        $this->assertNotNull($approved);
        $this->assertStringContainsString('Zaouli', $approved->data['message']);
        $this->assertDatabaseHas('admin_logs', ['action' => 'oeuvre.validee']);

        // Chapitre 2 refusé avec motif.
        $this->actingAs($user)->post(route('comics.studio.chapters.store', $comic), ['numero' => '2'])->assertRedirect();
        $two = ComicChapter::withUnpublished()->where('comic_id', $comic->id)->where('numero', 2)->sole();
        $this->upload($user, $comic, $two, $this->image('1.png'))->assertCreated();
        $this->publish($user, $comic, $two)->assertSessionHasNoErrors();
        $this->assertTrue($two->fresh()->isPendingReview());
        $this->assertTrue($comic->fresh()->isVisible(), 'La série déjà en ligne le reste.');

        $this->actingAs($admin)->post(route('admin.moderation.reject', ['comic_chapter', $two->id]))->assertSessionHasErrors('note');
        $this->actingAs($admin)->post(route('admin.moderation.reject', ['comic_chapter', $two->id]), ['note' => 'Planches floues.'])->assertRedirect();
        $this->assertTrue($two->fresh()->isRejected());
        $rejected = $user->fresh()->notifications->firstWhere('data.kind', MemberActivity::REJECTED);
        $this->assertStringContainsString('Planches floues.', $rejected->data['message']);
        $this->assertDatabaseHas('admin_logs', ['action' => 'oeuvre.refusee']);

        // Programmé non validé à l'heure : l'auteur est prévenu une seule fois.
        $this->actingAs($user)->post(route('comics.studio.chapters.store', $comic), ['numero' => '3'])->assertRedirect();
        $three = ComicChapter::withUnpublished()->where('comic_id', $comic->id)->where('numero', 3)->sole();
        $this->upload($user, $comic, $three, $this->image('1.png'))->assertCreated();
        $at = now()->setTimezone('Africa/Abidjan')->addHour()->format('Y-m-d\TH:i');
        $this->publish($user, $comic, $three, ['action' => 'programme', 'scheduled_at' => $at])->assertSessionHasNoErrors();
        $this->assertTrue($three->fresh()->isPendingReview());
        $this->travel(2)->hours();
        $this->artisan('comics:release-scheduled');
        $this->artisan('comics:release-scheduled');
        $this->assertCount(1, $user->fresh()->notifications->where('data.kind', MemberActivity::COMIC_SCHEDULE_PENDING));
        $this->assertFalse($three->fresh()->isVisible());

        // bd.validation_avant_publication = non : publication directe malgré la règle générale.
        Settings::set('bd.validation_avant_publication', 'non');
        $this->assertSame(ComicChapter::MODERATION_APPROVED, ComicChapter::moderationStatusFor($user));
        Settings::set('bd.validation_avant_publication', 'oui');
        PlatformSetting::updateModeration(false);
        $this->assertSame(ComicChapter::MODERATION_PENDING, ComicChapter::moderationStatusFor($user));
        $this->assertSame(ComicChapter::MODERATION_APPROVED, ComicChapter::moderationStatusFor($admin));
    }

    public function test_reported_chapter_can_hide_the_whole_series(): void
    {
        $admin = $this->admin();
        $user = $this->artist();
        $comic = $this->createComic($user, Comic::SERIES, 'Masques');
        $one = $this->firstChapter($comic);
        $this->upload($user, $comic, $one, $this->image('1.png'))->assertCreated();
        $this->publish($user, $comic, $one)->assertSessionHasNoErrors();

        $report = new Report(['reason' => 'droits', 'status' => Report::OPEN]);
        $report->reportable()->associate($one);
        $report->save();

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee('Masques ch. 1')->assertSee('Toute la série');

        $this->actingAs($admin)->patch(route('admin.reports.resolve', $report), ['action' => 'remove', 'scope' => 'serie', 'note' => 'Copie.'])
            ->assertRedirect();

        $this->assertTrue($comic->fresh()->isRejected());
        $this->assertFalse($comic->fresh()->isVisible());
        $this->assertSame(0, Comic::count());
        $this->assertSame(Report::ACTIONED, $report->fresh()->status);
        $this->assertStringContainsString('masqué', $user->fresh()->notifications->firstWhere('data.kind', MemberActivity::REJECTED)->data['message']);
    }

    public function test_studio_is_reserved_to_the_owner(): void
    {
        $owner = $this->artist('Kassi');
        $other = $this->artist('Yao');
        $comic = $this->createComic($owner);
        $chapter = $this->firstChapter($comic);
        $this->upload($owner, $comic, $chapter, $this->image('1.png'))->assertCreated();
        $page = $chapter->pages()->sole();

        $this->actingAs($owner)->get(route('comics.studio.index'))->assertOk()->assertSee('Nuit à Treichville')->assertSee('Brouillon');
        $this->actingAs($owner)->get(route('comics.studio.edit', [$comic, 'chapitre' => $chapter->id]))->assertOk()
            ->assertSee('Enregistrer et passer aux pages');
        $this->actingAs($owner)->put(route('comics.studio.update', $comic), [
            'titre' => 'Nuit à Treichville', 'type' => 'serie', 'format_lecture' => 'pages', 'sens_lecture' => 'rtl', 'statut_serie' => 'en_pause',
        ])->assertRedirect(route('comics.studio.manage', $comic));
        $this->assertSame('rtl', $comic->fresh()->sens_lecture);
        $this->actingAs($owner)->getJson(route('comics.studio.pages.status', [$comic, $chapter]))->assertOk()->assertJsonPath('state.count', 1);

        // Brouillon : introuvable pour un autre membre.
        $this->actingAs($other)->get(route('comics.studio.manage', $comic))->assertNotFound();

        // En ligne : visible publiquement, mais pas modifiable par un autre.
        $this->publish($owner, $comic, $chapter)->assertSessionHasNoErrors();
        $this->actingAs($other)->get(route('comics.studio.manage', $comic))->assertForbidden();
        $this->actingAs($other)->get(route('comics.studio.chapters.pages', [$comic, $chapter]))->assertForbidden();
        $this->actingAs($other)->post(route('comics.studio.pages.store', [$comic, $chapter]), ['page' => $this->image('2.png')], ['Accept' => 'application/json'])->assertForbidden();
        $this->actingAs($other)->deleteJson(route('comics.studio.pages.destroy', [$comic, $chapter, $page]))->assertForbidden();
        $this->actingAs($other)->delete(route('comics.studio.destroy', $comic))->assertForbidden();
        $this->assertNotNull(Comic::find($comic->id));

        // Réservé aux comptes illustrateurs ; invités redirigés vers la connexion.
        $visitor = User::factory()->create(['account_type' => 'visitor']);
        $this->actingAs($visitor)->get(route('comics.studio.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('comics.studio.index'))->assertRedirect(route('login'));

        // Suppression par l'auteur : fichiers effacés.
        $this->actingAs($owner)->delete(route('comics.studio.destroy', $comic))->assertRedirect(route('comics.studio.index'));
        $this->assertNull(Comic::withUnpublished()->withTrashed()->find($comic->id));
        Storage::disk('local')->assertMissing($page->original);
        Storage::disk('public')->assertMissing([$page->lecture, $page->miniature, $comic->couverture]);
    }

    public function test_studio_is_hidden_when_feature_is_disabled(): void
    {
        $user = $this->artist();
        $comic = $this->createComic($user);

        Settings::set('bd.active', false);

        $this->actingAs($user)->get(route('comics.studio.index'))->assertNotFound();
        $this->actingAs($user)->get(route('comics.studio.create'))->assertNotFound();
        $this->actingAs($user)->get(route('comics.studio.manage', $comic))->assertNotFound();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('Mes récits dessinés');
    }
}
