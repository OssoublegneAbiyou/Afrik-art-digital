<?php

namespace Tests\Feature;

use App\Jobs\ProcessAnimation;
use App\Models\Animation;
use App\Models\Artist;
use App\Models\PlatformSetting;
use App\Models\Report;
use App\Models\User;
use App\Notifications\MemberActivity;
use App\Support\AnimationMedia;
use App\Support\Ffmpeg;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Espace « Mes animations », conversion ffmpeg (§12, §15) et modération.
 * Les tests de conversion utilisent le vrai ffmpeg local ; ils sont ignorés s'il est absent.
 */
class AnimationStudioTest extends TestCase
{
    use RefreshDatabase;

    private array $tmp = [];

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Comme Storage::fake(), mais dans un dossier propre à ce processus (suites lancées en parallèle).
        foreach (['public', 'local'] as $disk) {
            $root = storage_path('framework/testing/disks/animation-studio-'.getmypid().'/'.$disk);
            (new \Illuminate\Filesystem\Filesystem)->deleteDirectory($root);
            Storage::set($disk, Storage::createLocalDriver(['root' => $root]));
        }

        $this->tmpDir = storage_path('framework/testing/animation-videos-'.getmypid());
        @mkdir($this->tmpDir, 0775, true);

        PlatformSetting::updateModeration(false);
    }

    protected function tearDown(): void
    {
        $files = new \Illuminate\Filesystem\Filesystem;
        $files->deleteDirectory(storage_path('framework/testing/disks/animation-studio-'.getmypid()));
        $files->deleteDirectory($this->tmpDir);

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

    private function requireFfmpeg(): void
    {
        if (! Ffmpeg::available()) {
            $this->markTestSkipped('ffmpeg / ffprobe introuvables (config media.ffmpeg, media.ffprobe).');
        }
    }

    /** Petite vidéo générée par ffmpeg lui-même (mire + son sinusoïdal). */
    private function video(string $name, int $seconds = 2, bool $audio = true, string $size = '320x240'): UploadedFile
    {
        $path = $this->tmpDir.DIRECTORY_SEPARATOR.uniqid('v', true).'-'.$name;
        $args = ['-f', 'lavfi', '-i', 'testsrc=duration='.$seconds.':size='.$size.':rate=15'];

        if ($audio) {
            $args = [...$args, '-f', 'lavfi', '-i', 'sine=frequency=440:duration='.$seconds, '-shortest', '-c:a', 'aac'];
        }

        $args = str_ends_with($name, '.gif')
            ? [...$args, '-f', 'gif', $path]
            : [...$args, '-c:v', 'libx264', '-pix_fmt', 'yuv420p', $path];

        $result = Ffmpeg::run($args, 60);
        $this->assertTrue($result['ok'], 'Génération de la vidéo de test : '.$result['error']);

        $mime = match (pathinfo($name, PATHINFO_EXTENSION)) {
            'mov' => 'video/quicktime',
            'gif' => 'image/gif',
            default => 'video/mp4',
        };

        return new UploadedFile($path, $name, $mime, null, true);
    }

    private function upload(User $user, UploadedFile $file, array $data = [])
    {
        return $this->actingAs($user)->post(route('animations.studio.store'), [
            'titre' => 'Danse du masque',
            'description' => 'Un masque qui danse sous la pluie.',
            'mode' => Animation::LOOP,
            'tags' => 'boucle, Abidjan',
            'rights' => 1,
            'fichier' => $file,
            ...$data,
        ]);
    }

    private function latest(): Animation
    {
        return Animation::withUnpublished()->latest('id')->firstOrFail();
    }

    /** Animation déjà convertie, avec des chemins factices (tests sans ffmpeg). */
    private function readyAnimation(User $user, array $attributes = []): Animation
    {
        $animation = new Animation([
            'user_id' => $user->id,
            'titre' => 'Boucle du marché',
            'mode' => Animation::LOOP,
            'source' => 'animations/sources/'.$user->id.'/source.mp4',
            'source_nom' => 'marche.mp4',
            'mp4' => 'animations/1/abc.mp4',
            'poster' => 'animations/1/abc-poster.webp',
            'duree_s' => 4,
            'largeur' => 640,
            'hauteur' => 360,
            'taille_octets' => 1000,
            'statut_traitement' => Animation::READY,
            ...$attributes,
        ]);
        $animation->published_at = $attributes['published_at'] ?? null;
        $animation->moderation_status = $attributes['moderation_status'] ?? Animation::MODERATION_APPROVED;
        $animation->save();

        return $animation;
    }

    private function probe(string $publicPath): array
    {
        $probe = Ffmpeg::probe(Storage::disk('public')->path($publicPath));
        $this->assertNotNull($probe);

        return $probe;
    }

    // ---------- Conversion (vrai ffmpeg) ----------

    public function test_a_mov_clip_becomes_an_h264_mp4_with_poster_and_keeps_its_sound(): void
    {
        $this->requireFfmpeg();
        $user = $this->artist();

        $this->upload($user, $this->video('scene.mov', 3), ['mode' => Animation::CLIP])
            ->assertSessionHasNoErrors()->assertRedirect();

        $animation = $this->latest();
        $this->assertSame(Animation::READY, $animation->statut_traitement, (string) $animation->erreur);
        $this->assertStringEndsWith('.mp4', $animation->mp4);
        $this->assertStringStartsWith('animations/'.$animation->id.'/', $animation->mp4);
        Storage::disk('public')->assertExists([$animation->mp4, $animation->poster]);
        $this->assertStringEndsWith('.webp', $animation->poster);
        $this->assertSame('RIFF', substr(Storage::disk('public')->get($animation->poster), 0, 4));
        $this->assertEqualsWithDelta(3, $animation->duree_s, 0.3);
        $this->assertSame(320, $animation->largeur);
        $this->assertSame(240, $animation->hauteur);
        $this->assertNull($animation->webm, 'WebM désactivé par défaut.');

        $probe = $this->probe($animation->mp4);
        $this->assertSame('h264', $probe['video']['codec']);
        $this->assertTrue($probe['audio'], 'Le clip garde sa piste son.');
        $this->assertGreaterThan(0, $animation->taille_octets);

        // Fiche de gestion : prête, aperçu vidéo et choix du poster.
        $this->actingAs($user)->get(route('animations.studio.manage', $animation))
            ->assertOk()->assertSee('Prêt')->assertSee($animation->mp4Url())->assertSee('Utiliser cette image');
    }

    public function test_a_loop_has_no_audio_track(): void
    {
        $this->requireFfmpeg();
        $user = $this->artist();

        $this->upload($user, $this->video('boucle.mp4', 2, audio: true), ['mode' => Animation::LOOP])->assertSessionHasNoErrors();

        $animation = $this->latest();
        $this->assertTrue($animation->isReady(), (string) $animation->erreur);
        $probe = $this->probe($animation->mp4);
        $this->assertFalse($probe['audio'], 'Une boucle n’a pas de piste son.');
        $this->assertSame('h264', $probe['video']['codec']);
    }

    public function test_switching_a_loop_to_a_clip_converts_again_with_sound(): void
    {
        $this->requireFfmpeg();
        $user = $this->artist();
        $this->upload($user, $this->video('boucle.mp4', 2))->assertSessionHasNoErrors();
        $animation = $this->latest();
        $oldMp4 = $animation->mp4;

        $this->actingAs($user)->put(route('animations.studio.update', $animation), [
            'titre' => 'Danse du masque', 'mode' => Animation::CLIP,
        ])->assertSessionHasNoErrors()->assertRedirect(route('animations.studio.manage', $animation));

        $animation->refresh();
        $this->assertTrue($animation->isReady());
        $this->assertNotSame($oldMp4, $animation->mp4);
        Storage::disk('public')->assertMissing($oldMp4);
        $this->assertTrue($this->probe($animation->mp4)['audio']);
    }

    public function test_a_gif_becomes_a_lighter_mp4(): void
    {
        $this->requireFfmpeg();
        $user = $this->artist();
        $gif = $this->video('anim.gif', 3, audio: false, size: '320x240');
        $gifSize = filesize($gif->getRealPath());

        $this->upload($user, $gif)->assertSessionHasNoErrors();

        $animation = $this->latest();
        $this->assertTrue($animation->isReady(), (string) $animation->erreur);
        $mp4Size = Storage::disk('public')->size($animation->mp4);
        $this->assertLessThan($gifSize, $mp4Size, 'Le MP4 doit être plus léger que le GIF.');
        $this->assertSame('h264', $this->probe($animation->mp4)['video']['codec']);
        $this->assertNotNull($animation->poster);
    }

    public function test_a_too_long_video_is_refused_with_a_clear_message(): void
    {
        $this->requireFfmpeg();
        Settings::set('anim.duree_max_s', 1);
        $user = $this->artist();

        $this->upload($user, $this->video('long.mp4', 3))->assertSessionHasNoErrors();

        $animation = $this->latest();
        $this->assertSame(Animation::FAILED, $animation->statut_traitement);
        $this->assertStringContainsString('Vidéo trop longue', $animation->erreur);
        $this->assertStringContainsString('1 s', $animation->erreur);
        $this->assertNull($animation->mp4);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->actingAs($user)->get(route('animations.studio.manage', $animation))
            ->assertOk()->assertSee('Échec')->assertSee('Vidéo trop longue', false)->assertSee('Relancer la conversion');

        $this->actingAs($user)->getJson(route('animations.studio.status', $animation))
            ->assertOk()->assertJson(['statut' => Animation::FAILED, 'libelle' => 'Échec', 'pret' => false]);

        // Impossible de publier une vidéo en échec.
        $this->actingAs($user)->post(route('animations.studio.publish', $animation), ['action' => 'publie'])
            ->assertSessionHasErrors('publication');
        $this->assertTrue($animation->refresh()->isDraft());
    }

    public function test_a_non_video_file_is_refused(): void
    {
        $user = $this->artist();

        // Mauvaise extension : refusé dès l'envoi.
        $text = UploadedFile::fake()->createWithContent('notes.txt', 'Bonjour');
        $this->upload($user, $text)->assertSessionHasErrors(['fichier' => 'Format non accepté : envoie une vidéo MP4, MOV, WEBM ou GIF.']);
        $this->assertSame(0, Animation::withUnpublished()->count());

        // Image renommée en .mp4 : refusée par ffprobe, avec un message clair.
        $this->requireFfmpeg();
        $image = imagecreatetruecolor(64, 64);
        $path = $this->tmpDir.DIRECTORY_SEPARATOR.'faux.mp4';
        imagepng($image, $path);
        imagedestroy($image);

        $this->upload($user, new UploadedFile($path, 'faux.mp4', 'video/mp4', null, true))->assertSessionHasNoErrors();
        $animation = $this->latest();
        $this->assertSame(Animation::FAILED, $animation->statut_traitement);
        $this->assertMatchesRegularExpression('/image fixe|pas une vidéo lisible|Format non supporté/u', $animation->erreur);

        // Fichier texte renommé en .mov : idem.
        file_put_contents($path2 = $this->tmpDir.DIRECTORY_SEPARATOR.'faux.mov', str_repeat('pas une vidéo ', 50));
        $this->upload($user, new UploadedFile($path2, 'faux.mov', 'video/quicktime', null, true), ['titre' => 'Faux film'])->assertSessionHasNoErrors();
        $this->assertStringContainsString('n’est pas une vidéo lisible', $this->latest()->erreur);
    }

    public function test_sources_are_never_public(): void
    {
        $this->requireFfmpeg();
        $user = $this->artist();
        $this->upload($user, $this->video('scene.mp4', 2))->assertSessionHasNoErrors();
        $animation = $this->latest();

        Storage::disk(Animation::PRIVATE_DISK)->assertExists($animation->source);
        $this->assertStringStartsWith('animations/sources/', $animation->source);
        Storage::disk('public')->assertMissing($animation->source);
        // Aucun fichier du disque public n'est la source.
        foreach (Storage::disk('public')->allFiles() as $file) {
            $this->assertStringStartsWith('animations/'.$animation->id.'/', $file);
        }

        $this->assertContains($this->get('/storage/'.$animation->source)->status(), [403, 404]);
        $json = $this->actingAs($user)->getJson(route('animations.studio.status', $animation))->assertOk()->getContent();
        $this->assertStringNotContainsString(basename($animation->source), $json);
        $this->actingAs($user)->get(route('animations.studio.manage', $animation))
            ->assertOk()->assertDontSee(basename($animation->source));
    }

    public function test_poster_can_be_chosen_by_the_artist(): void
    {
        $this->requireFfmpeg();
        $user = $this->artist();
        $this->upload($user, $this->video('scene.mp4', 3))->assertSessionHasNoErrors();
        $animation = $this->latest();
        $oldPoster = $animation->poster;

        $this->actingAs($user)->post(route('animations.studio.poster', $animation), ['poster_seconde' => '2.5'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $animation->refresh();
        $this->assertEquals(2.5, $animation->poster_seconde);
        $this->assertNotSame($oldPoster, $animation->poster);
        Storage::disk('public')->assertExists($animation->poster);
        Storage::disk('public')->assertMissing($oldPoster);

        $this->actingAs($user)->post(route('animations.studio.poster', $animation), ['poster_seconde' => '99'])
            ->assertSessionHasErrors('poster_seconde');
    }

    public function test_deleting_an_animation_removes_its_files(): void
    {
        $this->requireFfmpeg();
        $user = $this->artist();
        $this->upload($user, $this->video('scene.mp4', 2))->assertSessionHasNoErrors();
        $animation = $this->latest();
        $files = [$animation->mp4, $animation->poster];
        $source = $animation->source;

        $this->actingAs($user)->delete(route('animations.studio.destroy', $animation))
            ->assertRedirect(route('animations.studio.index'));

        $this->assertSame(0, Animation::withUnpublished()->withTrashed()->count());
        Storage::disk('public')->assertMissing($files);
        Storage::disk(Animation::PRIVATE_DISK)->assertMissing($source);
    }

    public function test_conversion_runs_in_the_queue_with_a_fallback_when_no_worker_runs(): void
    {
        $this->requireFfmpeg();
        \Illuminate\Support\Facades\Queue::fake();
        $user = $this->artist();

        $this->upload($user, $this->video('scene.mp4', 2))->assertSessionHasNoErrors();
        $animation = $this->latest();
        \Illuminate\Support\Facades\Queue::assertPushed(ProcessAnimation::class, fn ($job) => $job->animationId === $animation->id);

        // Jamais pendant la requête d'envoi : encore « En file… ».
        $this->assertSame(Animation::QUEUED, $animation->statut_traitement);
        $this->actingAs($user)->getJson(route('animations.studio.status', $animation))
            ->assertOk()->assertJson(['statut' => Animation::QUEUED, 'libelle' => 'En file…', 'en_cours' => true]);
        $this->actingAs($user)->get(route('animations.studio.manage', $animation))->assertOk()->assertSee('En file…');

        // Aucun worker depuis plus de 2 minutes : la page d'état convertit (une seule fois).
        $this->travel(3)->minutes();
        $this->actingAs($user)->getJson(route('animations.studio.status', $animation))
            ->assertOk()->assertJson(['statut' => Animation::READY, 'libelle' => 'Prêt', 'pret' => true]);

        // Le job arrivé ensuite ne refait rien.
        $mp4 = $animation->refresh()->mp4;
        (new ProcessAnimation($animation->id))->handle();
        $this->assertSame($mp4, $animation->refresh()->mp4);

        $this->actingAs($user)->get(route('animations.studio.edit', $animation))->assertOk()
            ->assertSee('Remplacer la vidéo')->assertSee('scene.mp4');
    }

    // ---------- ffmpeg absent ----------

    public function test_without_ffmpeg_uploads_are_suspended_with_a_clear_message(): void
    {
        config(['media.ffmpeg' => 'C:/introuvable/ffmpeg.exe', 'media.ffprobe' => 'C:/introuvable/ffprobe.exe']);
        $this->assertFalse(Ffmpeg::available());

        $user = $this->artist();
        $ready = $this->readyAnimation($user, ['published_at' => now()->subDay()]);

        $this->actingAs($user)->get(route('animations.studio.create'))
            ->assertOk()->assertSee(Ffmpeg::UNAVAILABLE_MESSAGE, false)->assertDontSee('Envoyer la vidéo');
        $this->actingAs($user)->get(route('animations.studio.index'))
            ->assertOk()->assertSee(Ffmpeg::UNAVAILABLE_MESSAGE, false)->assertDontSee('+ Nouvelle animation');
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee(Ffmpeg::UNAVAILABLE_MESSAGE, false);

        // L'envoi est refusé, rien n'est enregistré.
        $file = UploadedFile::fake()->create('scene.mp4', 200, 'video/mp4');
        $this->upload($user, $file)->assertSessionHasErrors(['fichier' => Ffmpeg::UNAVAILABLE_MESSAGE]);
        $this->assertSame(1, Animation::withUnpublished()->count());
        $this->assertSame([], Storage::disk(Animation::PRIVATE_DISK)->allFiles());

        // Une animation en file au moment où ffmpeg disparaît : échec explicite, rien ne plante.
        $queued = $this->readyAnimation($user, ['titre' => 'En file', 'statut_traitement' => Animation::QUEUED, 'mp4' => null, 'poster' => null]);
        (new ProcessAnimation($queued->id))->handle();
        $this->assertSame(Animation::FAILED, $queued->refresh()->statut_traitement);
        $this->assertStringContainsString(Ffmpeg::UNAVAILABLE_MESSAGE, $queued->erreur);

        // L'animation déjà convertie reste gérable (texte modifiable) ; changer de mode est refusé.
        $this->actingAs($user)->get(route('animations.studio.manage', $ready))->assertOk()->assertSee('Prêt');
        $this->actingAs($user)->put(route('animations.studio.update', $ready), ['titre' => 'Nouveau titre', 'mode' => Animation::LOOP])
            ->assertSessionHasNoErrors();
        $this->assertSame('Nouveau titre', $ready->refresh()->titre);
        $this->actingAs($user)->put(route('animations.studio.update', $ready), ['titre' => 'Nouveau titre', 'mode' => Animation::CLIP])
            ->assertSessionHasErrors(['fichier' => Ffmpeg::UNAVAILABLE_MESSAGE]);

        // Avertissement dans l'administration.
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertSee('ffmpeg est introuvable', false);
        $this->actingAs($admin)->get(route('admin.settings.edit', ['onglet' => 'anim']))->assertOk()->assertSee('ffmpeg est introuvable', false);
    }

    public function test_admin_sees_no_warning_when_ffmpeg_is_present(): void
    {
        $this->requireFfmpeg();

        $this->actingAs($this->admin())->get(route('admin.index'))->assertOk()->assertDontSee('ffmpeg est introuvable', false);
    }

    // ---------- Règles, quota, accès ----------

    public function test_quota_is_enforced(): void
    {
        Settings::set('anim.stockage_max_go', 1);
        $user = $this->artist();
        $this->readyAnimation($user, ['taille_octets' => 1024 ** 3 - 1000]);

        $file = UploadedFile::fake()->create('scene.mp4', 10, 'video/mp4');
        $this->upload($user, $file)->assertSessionHasErrors('fichier');
        $this->assertStringContainsString('Stockage des animations plein', session('errors')->first('fichier'));
        $this->assertSame(1, Animation::withUnpublished()->count());

        // Barre de quota affichée.
        $this->actingAs($user)->get(route('animations.studio.index'))
            ->assertOk()->assertSee('Stockage des animations')->assertSee('aria-valuenow="100"', false);

        // Les animations d'un autre membre ne comptent pas.
        $other = $this->artist('Awa');
        $this->assertSame(0, AnimationMedia::usedBytes($other));
    }

    public function test_file_size_limit_is_enforced(): void
    {
        Settings::set('anim.taille_max_mo', 1);
        $user = $this->artist();

        $this->upload($user, UploadedFile::fake()->create('lourd.mp4', 2048, 'video/mp4'))
            ->assertSessionHasErrors(['fichier' => 'La vidéo ne doit pas dépasser 1 Mo.']);
    }

    public function test_rights_certification_is_required(): void
    {
        $user = $this->artist();

        $this->upload($user, UploadedFile::fake()->create('scene.mp4', 10, 'video/mp4'), ['rights' => null])
            ->assertSessionHasErrors('rights');
        $this->actingAs($user)->get(route('animations.studio.create'))->assertOk()->assertSee(route('pages.rules'), false);
    }

    public function test_only_the_owner_can_manage_an_animation(): void
    {
        $owner = $this->artist();
        $other = $this->artist('Awa');
        $draft = $this->readyAnimation($owner);
        $live = $this->readyAnimation($owner, ['titre' => 'En ligne', 'published_at' => now()->subDay()]);

        foreach ([$draft, $live] as $animation) {
            $this->actingAs($other)->get(route('animations.studio.manage', $animation))->assertStatus($animation->isVisible() ? 403 : 404);
            $this->actingAs($other)->get(route('animations.studio.edit', $animation))->assertStatus($animation->isVisible() ? 403 : 404);
            $this->actingAs($other)->getJson(route('animations.studio.status', $animation))->assertStatus($animation->isVisible() ? 403 : 404);
            $this->actingAs($other)->put(route('animations.studio.update', $animation), ['titre' => 'Volé', 'mode' => Animation::LOOP])->assertStatus($animation->isVisible() ? 403 : 404);
            $this->actingAs($other)->post(route('animations.studio.publish', $animation), ['action' => 'brouillon'])->assertStatus($animation->isVisible() ? 403 : 404);
            $this->actingAs($other)->delete(route('animations.studio.destroy', $animation))->assertStatus($animation->isVisible() ? 403 : 404);
        }

        $this->assertSame(2, Animation::withUnpublished()->count());
        $this->assertNotSame('Volé', $live->refresh()->titre);

        // Un compte non illustrateur n'a pas d'espace animations ; un visiteur est renvoyé à la connexion.
        $visitor = User::factory()->create(['account_type' => 'visitor']);
        $this->actingAs($visitor)->get(route('animations.studio.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('animations.studio.index'))->assertRedirect(route('login'));

        // L'auteur, lui, voit tout.
        $this->actingAs($owner)->get(route('animations.studio.manage', $draft))->assertOk()->assertSee('Boucle du marché');
        $this->actingAs($owner)->get(route('animations.studio.index'))->assertOk()->assertSee('Boucle du marché')->assertSee('En ligne');
    }

    public function test_inactive_animations_return_404(): void
    {
        Settings::set('anim.active', false);
        $user = $this->artist();
        $animation = $this->readyAnimation($user);

        $this->actingAs($user)->get(route('animations.studio.index'))->assertNotFound();
        $this->actingAs($user)->get(route('animations.studio.create'))->assertNotFound();
        $this->actingAs($user)->get(route('animations.studio.manage', $animation))->assertNotFound();
        $this->upload($user, UploadedFile::fake()->create('scene.mp4', 10, 'video/mp4'))->assertNotFound();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('Mes animations');
    }

    public function test_dashboard_shows_my_animations_section(): void
    {
        $user = $this->artist();
        $animation = $this->readyAnimation($user, ['published_at' => now()->subDay(), 'view_count' => 12]);
        $animation->likes()->create(['user_id' => $this->artist('Fan')->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Mes animations')->assertSee('+ Nouvelle animation')->assertSee('Boucle du marché')
            ->assertSee('Stockage des animations')->assertSee(route('animations.studio.manage', $animation), false);

        $this->actingAs($user)->get(route('animations.studio.manage', $animation))->assertOk()
            ->assertSee('Statistiques')->assertSee('12')->assertSee('En ligne depuis');
    }

    // ---------- Publication et modération ----------

    public function test_publish_schedule_and_draft(): void
    {
        $user = $this->artist();
        $animation = $this->readyAnimation($user);

        $this->actingAs($user)->post(route('animations.studio.publish', $animation), ['action' => 'publie'])
            ->assertRedirect(route('animations.studio.manage', $animation))->assertSessionHas('success', 'Ton animation est en ligne !');
        $this->assertTrue($animation->refresh()->isVisible());
        $this->assertTrue(Animation::query()->ready()->whereKey($animation->id)->exists());

        Settings::set('bd.fuseau_horaire', 'Africa/Abidjan');
        $when = now()->addDays(2)->setTimezone('Africa/Abidjan')->format('Y-m-d\TH:i');
        $this->actingAs($user)->post(route('animations.studio.publish', $animation), ['action' => 'programme', 'scheduled_at' => $when])
            ->assertSessionHasNoErrors();
        $this->assertTrue($animation->refresh()->isScheduled());
        $this->assertFalse(Animation::query()->whereKey($animation->id)->exists());

        $this->actingAs($user)->post(route('animations.studio.publish', $animation), ['action' => 'programme', 'scheduled_at' => now()->subHour()->format('Y-m-d\TH:i')])
            ->assertSessionHasErrors('scheduled_at');

        $this->actingAs($user)->post(route('animations.studio.publish', $animation), ['action' => 'brouillon'])->assertSessionHasNoErrors();
        $this->assertTrue($animation->refresh()->isDraft());

        // Pas de publication tant que la conversion n'est pas finie.
        $queued = $this->readyAnimation($user, ['titre' => 'En file', 'statut_traitement' => Animation::QUEUED, 'mp4' => null]);
        $this->actingAs($user)->post(route('animations.studio.publish', $queued), ['action' => 'publie'])->assertSessionHasErrors('publication');
        $this->assertTrue($queued->refresh()->isDraft());
    }

    public function test_moderation_queue_approval_and_rejection_notify_the_author(): void
    {
        PlatformSetting::updateModeration(true);
        $user = $this->artist();
        $admin = $this->admin();
        $animation = $this->readyAnimation($user, ['description' => 'Pluie sur Adjamé.']);

        $this->actingAs($user)->post(route('animations.studio.publish', $animation), ['action' => 'publie'])
            ->assertSessionHas('success', 'Ton animation est envoyée à l’équipe : elle sera en ligne après validation. Tu seras prévenu.');
        $animation->refresh();
        $this->assertTrue($animation->isPendingReview());
        $this->assertFalse($animation->isVisible());
        $this->assertTrue($admin->notifications()->where('data->kind', MemberActivity::TO_REVIEW)->exists());

        // File « À valider » : lecteur vidéo, pastille du menu, tableau de bord.
        $this->actingAs($admin)->get(route('admin.moderation.index'))->assertOk()
            ->assertSee('Boucle du marché')->assertSee('Animation · Boucle', false)
            ->assertSee($animation->mp4Url(), false)->assertSee('<video', false)
            ->assertSee(route('admin.moderation.approve', ['animation', $animation->id]), false);

        // Refus avec motif.
        $this->actingAs($admin)->post(route('admin.moderation.reject', ['animation', $animation->id]), ['note' => 'La musique n’est pas libre de droits.'])
            ->assertRedirect(route('admin.moderation.index'));
        $animation->refresh();
        $this->assertTrue($animation->isRejected());
        $notification = $user->notifications()->where('data->kind', MemberActivity::REJECTED)->sole();
        $this->assertSame(MemberActivity::REJECTED, $notification->data['kind']);
        $this->assertStringContainsString('La musique n’est pas libre de droits.', $notification->data['message']);
        $this->assertSame(route('animations.studio.manage', $animation), $notification->data['url']);
        $this->assertTrue(\App\Models\AdminLog::where('action', 'oeuvre.refusee')->exists());

        $this->actingAs($user)->get(route('animations.studio.manage', $animation))->assertOk()
            ->assertSee('Refusée ou masquée par l’équipe', false)->assertSee('La musique n’est pas libre de droits.', false);

        // Nouvelle soumission puis validation.
        $this->actingAs($user)->post(route('animations.studio.publish', $animation), ['action' => 'publie'])->assertSessionHasNoErrors();
        $this->assertTrue($animation->refresh()->isPendingReview());

        $this->actingAs($admin)->post(route('admin.moderation.approve', ['animation', $animation->id]))->assertRedirect(route('admin.moderation.index'));
        $animation->refresh();
        $this->assertTrue($animation->isVisible());
        $notification = $user->notifications()->where('data->kind', MemberActivity::APPROVED)->sole();
        $this->assertSame(MemberActivity::APPROVED, $notification->data['kind']);
        $this->assertStringContainsString('a été validée par l’équipe et est en ligne', $notification->data['message']);
        $this->actingAs($admin)->get(route('admin.moderation.index'))->assertOk()->assertSee('Rien à valider');
    }

    public function test_staff_publish_directly_even_with_moderation(): void
    {
        PlatformSetting::updateModeration(true);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'account_type' => 'artist']);
        Artist::create(['user_id' => $admin->id]);
        $animation = $this->readyAnimation($admin);

        $this->actingAs($admin)->post(route('animations.studio.publish', $animation), ['action' => 'publie'])->assertSessionHasNoErrors();
        $this->assertTrue($animation->refresh()->isVisible());
    }

    public function test_hide_from_reports(): void
    {
        $user = $this->artist();
        $admin = $this->admin();
        $animation = $this->readyAnimation($user, ['published_at' => now()->subDay()]);

        $report = new Report(['reason' => 'droits', 'message' => 'Copie d’un dessin animé.', 'status' => Report::OPEN]);
        $report->reportable()->associate($animation);
        $report->save();

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()
            ->assertSee('Boucle du marché')->assertSee('Animation · Boucle', false);

        $this->actingAs($admin)->patch(route('admin.reports.resolve', $report), ['action' => 'remove', 'note' => 'Reprend un personnage protégé.'])
            ->assertRedirect(route('admin.reports.index'));

        $animation->refresh();
        $this->assertTrue($animation->isRejected());
        $this->assertFalse($animation->isVisible());
        $this->assertSame(Report::ACTIONED, $report->refresh()->status);
        $this->assertStringContainsString('Reprend un personnage protégé.', $user->notifications()->where('data->kind', MemberActivity::REJECTED)->sole()->data['message']);
    }

    // ---------- §14 : cases animées dans un chapitre ----------

    private function comicChapter(User $user): array
    {
        $image = imagecreatetruecolor(60, 90);
        $cover = $this->tmpDir.DIRECTORY_SEPARATOR.'couverture.png';
        imagepng($image, $cover);
        imagedestroy($image);

        $this->actingAs($user)->post(route('comics.studio.store'), [
            'type' => \App\Models\Comic::ONE_SHOT, 'titre' => 'Nuit animée', 'format_lecture' => 'vertical', 'sens_lecture' => 'ltr',
            'couverture' => new UploadedFile($cover, 'couverture.png', 'image/png', null, true), 'rights' => 1,
        ])->assertSessionHasNoErrors();

        $comic = \App\Models\Comic::withUnpublished()->where('titre', 'Nuit animée')->sole();

        return [$comic, $comic->allChapters()->firstOrFail()];
    }

    public function test_a_short_video_becomes_an_animated_comic_page(): void
    {
        $this->requireFfmpeg();
        $user = $this->artist();
        [$comic, $chapter] = $this->comicChapter($user);

        $this->actingAs($user)->post(route('comics.studio.pages.store', [$comic, $chapter]), ['page' => $this->video('02-case.mp4', 2)], ['Accept' => 'application/json'])
            ->assertCreated();

        $page = \App\Models\ComicPage::where('comic_chapter_id', $chapter->id)->sole();
        $this->assertTrue($page->isReady(), (string) $page->erreur);
        $this->assertTrue($page->isAnimated());
        Storage::disk('public')->assertExists([$page->video, $page->lecture, $page->miniature]);
        $this->assertStringEndsWith('.mp4', $page->video);
        $this->assertStringEndsWith('.webp', $page->lecture);
        $this->assertFalse($this->probe($page->video)['audio'], 'Une case animée est muette.');
        $this->assertEqualsWithDelta(2, $page->duree_s, 0.3);
        $this->assertSame(320, $page->largeur);

        $this->actingAs($user)->getJson(route('comics.studio.pages.status', [$comic, $chapter]))
            ->assertOk()->assertJsonPath('state.pages.0.anime', true);
        $this->actingAs($user)->get(route('comics.studio.chapters.pages', [$comic, $chapter]))
            ->assertOk()->assertSee('Case animée')->assertSee('.mp4', false);

        // Remplacée par une image : la vidéo disparaît.
        $video = $page->video;
        $image = imagecreatetruecolor(40, 60);
        $png = $this->tmpDir.DIRECTORY_SEPARATOR.'page.png';
        imagepng($image, $png);
        $this->actingAs($user)->post(route('comics.studio.pages.replace', [$comic, $chapter, $page]), ['page' => new UploadedFile($png, 'page.png', 'image/png', null, true)], ['Accept' => 'application/json'])
            ->assertOk();
        $page->refresh();
        $this->assertFalse($page->isAnimated());
        Storage::disk('public')->assertMissing($video);
    }

    public function test_an_animated_comic_page_longer_than_15_seconds_is_refused(): void
    {
        $this->requireFfmpeg();
        $user = $this->artist();
        [$comic, $chapter] = $this->comicChapter($user);

        $this->actingAs($user)->post(route('comics.studio.pages.store', [$comic, $chapter]), ['page' => $this->video('longue.mp4', 17, audio: false, size: '160x120')], ['Accept' => 'application/json'])
            ->assertCreated();

        $page = \App\Models\ComicPage::where('comic_chapter_id', $chapter->id)->sole();
        $this->assertSame(\App\Models\ComicPage::FAILED, $page->statut_traitement);
        $this->assertStringContainsString('Page animée trop longue', $page->erreur);
        $this->assertStringContainsString('15 s', $page->erreur);
    }

    public function test_animated_comic_pages_are_refused_when_animations_are_off(): void
    {
        $user = $this->artist();
        [$comic, $chapter] = $this->comicChapter($user);
        Settings::set('anim.active', false);

        $this->actingAs($user)->post(route('comics.studio.pages.store', [$comic, $chapter]), ['page' => UploadedFile::fake()->create('case.mp4', 10, 'video/mp4')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('page');
        $this->actingAs($user)->get(route('comics.studio.chapters.pages', [$comic, $chapter]))
            ->assertOk()->assertDontSee('Case animée');
    }

    public function test_admin_menu_badge_counts_pending_animations(): void
    {
        $user = $this->artist();
        $this->readyAnimation($user, ['published_at' => now()->subHour(), 'moderation_status' => Animation::MODERATION_PENDING]);

        $this->actingAs($this->admin())->get(route('admin.index'))->assertOk()
            ->assertSeeInOrder(['À valider', '1'], false);
    }
}
