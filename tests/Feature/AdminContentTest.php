<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\Annonce;
use App\Models\Artist;
use App\Models\Document;
use App\Models\Illustration;
use App\Models\Page;
use App\Models\Report;
use App\Models\User;
use App\Models\Writer;
use App\Notifications\MemberActivity;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Administration des contenus : À valider, Signalements, Mises en avant, Pages, Annonces.
 */
class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->admin = User::factory()->create(['name' => 'Admin', 'role' => User::ROLE_ADMIN, 'account_type' => 'visitor']);
    }

    private function artist(string $name, string $role = User::ROLE_MEMBER, int $works = 1): Artist
    {
        $user = User::factory()->create(['name' => $name, 'account_type' => 'artist', 'role' => $role]);
        $artist = Artist::create(['user_id' => $user->id]);

        for ($i = 1; $i <= $works; $i++) {
            $this->illustration($artist, $name.' œuvre '.$i);
        }

        return $artist;
    }

    private function illustration(Artist $artist, string $title, array $extra = []): Illustration
    {
        return Illustration::create($extra + [
            'artist_id' => $artist->id, 'title' => $title, 'type' => 'couleur',
            'image_path' => 'illustrations/'.md5($title).'.jpg', 'file_size_bytes' => 1,
        ]);
    }

    // ---------- Accès ----------

    public function test_access_depends_on_role(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);

        foreach (['admin.moderation.index', 'admin.reports.index', 'admin.featured.index', 'admin.pages.index', 'admin.announcements.index'] as $route) {
            $this->actingAs($member)->get(route($route))->assertForbidden();
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }

        foreach (['admin.moderation.index', 'admin.reports.index', 'admin.featured.index'] as $route) {
            $this->actingAs($moderator)->get(route($route))->assertOk();
        }
        $this->actingAs($moderator)->get(route('admin.pages.index'))->assertForbidden();
        $this->actingAs($moderator)->get(route('admin.pages.edit', 'mentions-legales'))->assertForbidden();
        $this->actingAs($moderator)->get(route('admin.announcements.index'))->assertForbidden();
        $this->actingAs($moderator)->post(route('admin.announcements.store'), ['texte' => 'Test', 'actif' => 1])->assertForbidden();
        $this->assertSame(0, Annonce::count());
    }

    // ---------- À valider ----------

    public function test_validation_queue_lists_pending_works_and_publishes_them(): void
    {
        $artist = $this->artist('Kassi', works: 0);
        $pending = $this->illustration($artist, 'Héros masqué', ['moderation_status' => Illustration::MODERATION_PENDING]);
        $this->illustration($artist, 'Brouillon caché', ['moderation_status' => Illustration::MODERATION_PENDING, 'published_at' => null]);
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);

        $this->actingAs($moderator)->get(route('admin.moderation.index'))
            ->assertOk()->assertSee('Héros masqué')->assertSee('par Kassi')->assertDontSee('Brouillon caché')
            ->assertSee('Publier')->assertSee('Refuser');

        $this->actingAs($moderator)->post(route('admin.moderation.approve', ['illustration', $pending->id]))
            ->assertRedirect(route('admin.moderation.index'))->assertSessionHas('status');

        $this->assertTrue($pending->fresh()->isApproved());
        $this->assertSame(MemberActivity::APPROVED, $artist->user->fresh()->notifications->first()->data['kind']);
        $this->assertDatabaseHas('admin_logs', ['action' => 'oeuvre.validee', 'user_id' => $moderator->id, 'cible_id' => (string) $pending->id]);
        $this->get(route('public.illustrations'))->assertSee('Héros masqué');
    }

    public function test_rejection_requires_a_reason_sent_to_the_author(): void
    {
        $user = User::factory()->create(['name' => 'Awa', 'account_type' => 'writer']);
        $writer = Writer::create(['user_id' => $user->id]);
        $document = Document::create([
            'writer_id' => $writer->id, 'title' => 'Récit copié', 'genre' => 'conte', 'content' => 'Texte.',
            'file_disk' => 'local', 'file_size_bytes' => 0, 'cover_image_path' => 'c.jpg', 'cover_image_size_bytes' => 1,
            'moderation_status' => Document::MODERATION_PENDING,
        ]);

        $this->actingAs($this->admin)->from(route('admin.moderation.index'))
            ->post(route('admin.moderation.reject', ['document', $document->id]), ['note' => '', 'work' => 'document-'.$document->id])
            ->assertSessionHasErrors('note');
        $this->assertTrue($document->fresh()->isPendingReview());

        $this->actingAs($this->admin)->post(route('admin.moderation.reject', ['document', $document->id]), ['note' => 'Le texte reprend un roman publié.'])
            ->assertRedirect(route('admin.moderation.index'));

        $this->assertTrue($document->fresh()->isRejected());
        $notification = $user->fresh()->notifications->first();
        $this->assertSame(MemberActivity::REJECTED, $notification->data['kind']);
        $this->assertStringContainsString('roman publié', $notification->data['message']);
        $log = AdminLog::where('action', 'oeuvre.refusee')->sole();
        $this->assertSame('Le texte reprend un roman publié.', $log->apres['motif']);
    }

    // ---------- Signalements ----------

    public function test_reports_are_grouped_and_can_hide_the_work(): void
    {
        $artist = $this->artist('Kassi', works: 0);
        $work = $this->illustration($artist, 'Carte de vœux');
        $this->post(route('reports.store', ['illustration', $work->id]), ['reason' => 'droits', 'message' => 'Logo NSIA.'])->assertSessionHas('report_sent');
        $this->post(route('reports.store', ['illustration', $work->id]), ['reason' => 'contenu']);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()->assertSee('1 œuvre signalée')->assertSee('Carte de vœux')->assertSee('Logo NSIA.')->assertSee('2 signalements')
            ->assertSee('Contacter l’auteur')->assertSee('mailto:'.$artist->user->email, false);

        $this->actingAs($this->admin)->patch(route('admin.reports.resolve', Report::first()), ['action' => 'remove', 'note' => ''])
            ->assertSessionHasErrors('note');

        $this->actingAs($this->admin)->patch(route('admin.reports.resolve', Report::first()), ['action' => 'remove', 'note' => 'Reprend un logo de marque.'])
            ->assertRedirect(route('admin.reports.index'));

        $this->assertTrue($work->fresh()->isRejected());
        $this->assertSame(2, Report::where('status', Report::ACTIONED)->count());
        $this->assertStringContainsString('logo de marque', $artist->user->fresh()->notifications->first()->data['message']);
        $this->assertDatabaseHas('admin_logs', ['action' => 'signalement.oeuvre_retiree']);
        auth()->logout();
        $this->get(route('public.illustration', $work))->assertNotFound();
    }

    public function test_report_can_be_dismissed(): void
    {
        $artist = $this->artist('Yao', works: 0);
        $work = $this->illustration($artist, 'Lagune');
        $this->post(route('reports.store', ['illustration', $work->id]), ['reason' => 'spam']);
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);

        $this->actingAs($moderator)->patch(route('admin.reports.resolve', Report::first()), ['action' => 'dismiss'])
            ->assertRedirect(route('admin.reports.index'));

        $this->assertSame(Report::DISMISSED, Report::first()->status);
        $this->assertTrue($work->fresh()->isVisible());
        $this->assertDatabaseHas('admin_logs', ['action' => 'signalement.classe', 'user_id' => $moderator->id]);
        $this->actingAs($moderator)->get(route('admin.reports.index'))->assertSee('Aucun signalement en cours')->assertSee('Classé sans suite');
    }

    // ---------- Mises en avant ----------

    public function test_auto_rotation_skips_staff_empty_profiles_and_yesterdays_pick(): void
    {
        $ama = $this->artist('Ama');
        $kofi = $this->artist('Kofi');
        $this->artist('Moderatrice', User::ROLE_MODERATOR);
        $this->artist('Admin artiste', User::ROLE_ADMIN);
        $this->artist('Sans œuvre', works: 0);
        $hidden = $this->artist('Brouillons', works: 0);
        $this->illustration($hidden, 'Pas encore', ['published_at' => null]);

        $previous = null;
        for ($day = 0; $day < 6; $day++) {
            $this->travelTo(now()->addDays($day));
            $picked = $this->get(route('public.index'))->assertOk()->viewData('featuredArtist');

            $this->assertContains($picked->id, [$ama->id, $kofi->id], 'Seuls les membres ayant publié entrent dans la rotation.');
            $this->assertNotSame($previous, $picked->id, 'Jamais la même personne deux jours de suite.');
            // Stable pendant la journée.
            $this->assertSame($picked->id, $this->get(route('public.artists'))->viewData('featuredArtist')->id);
            $previous = $picked->id;
            $this->travelBack();
        }
    }

    public function test_manual_choice_falls_back_to_auto_without_published_work(): void
    {
        $ama = $this->artist('Ama');
        $kofi = $this->artist('Kofi');
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);

        $this->actingAs($moderator)->put(route('admin.featured.update'), ['artiste_mode' => 'manuel', 'artiste_id' => '', 'plume_mode' => 'auto'])
            ->assertSessionHasErrors('artiste_id');
        $this->actingAs($moderator)->put(route('admin.featured.update'), ['artiste_mode' => 'manuel', 'artiste_id' => $kofi->id, 'plume_mode' => 'auto'])
            ->assertRedirect(route('admin.featured.index'));

        $this->assertSame('manuel', Settings::get('accueil.artiste_du_jour_mode'));
        $this->assertSame($kofi->id, Settings::get('accueil.artiste_du_jour_id'));
        $this->assertDatabaseHas('admin_logs', ['action' => 'mise_en_avant.modifiee', 'user_id' => $moderator->id]);
        // Le membre choisi est prévenu tout de suite.
        $this->assertSame(1, $kofi->user->fresh()->notifications->where('data.kind', MemberActivity::FEATURED)->count());

        auth()->logout();
        $this->assertSame($kofi->id, $this->get(route('public.index'))->viewData('featuredArtist')->id);

        // Kofi n'a plus d'œuvre en ligne : retour au tirage automatique.
        Illustration::where('artist_id', $kofi->id)->update(['published_at' => null]);
        $this->assertSame($ama->id, $this->get(route('public.index'))->viewData('featuredArtist')->id);
    }

    public function test_dated_planning_takes_priority_over_manual_mode(): void
    {
        $ama = $this->artist('Ama');
        $kofi = $this->artist('Kofi');
        Settings::set('accueil.artiste_du_jour_mode', 'manuel');
        Settings::set('accueil.artiste_du_jour_id', $kofi->id);

        $this->actingAs($this->admin)->post(route('admin.featured.schedule'), ['date' => today()->toDateString(), 'artist_id' => $ama->id])
            ->assertSessionHas('status');

        auth()->logout();
        $this->assertSame($ama->id, $this->get(route('public.index'))->viewData('featuredArtist')->id);
    }

    public function test_pinned_works_come_first_in_order(): void
    {
        $artist = $this->artist('Kassi', works: 0);
        $works = collect(range(1, 10))->map(fn ($i) => $this->illustration($artist, 'Œuvre '.$i));
        $draft = $this->illustration($artist, 'Brouillon', ['published_at' => null]);

        $this->actingAs($this->admin)->put(route('admin.featured.pinned'), ['pinned' => [$works[6]->id, $draft->id]])
            ->assertSessionHasErrors('pinned');

        $pinned = [$works[6]->id, $works[2]->id, $works[8]->id];
        $this->actingAs($this->admin)->put(route('admin.featured.pinned'), ['pinned' => $pinned])
            ->assertRedirect(route('admin.featured.index'));
        $this->assertSame($pinned, Settings::get('accueil.oeuvres_epinglees'));
        $this->assertDatabaseHas('admin_logs', ['action' => 'mise_en_avant.modifiee', 'cible_id' => 'accueil.oeuvres_epinglees']);

        $this->actingAs($this->admin)->get(route('admin.featured.index'))->assertOk()->assertSee('Enregistrer l’ordre');

        auth()->logout();
        $home = $this->get(route('public.index'))->assertOk();
        $this->assertSame($pinned, $home->viewData('dailyIllustrations')->take(3)->pluck('id')->all());
        // Sans récit publié, le fil ne contient que les illustrations : les épinglées en tête.
        $this->assertSame($pinned, $home->viewData('feed')->take(3)->pluck('id')->all());

        $gallery = $this->get(route('public.illustrations'))->assertOk();
        $this->assertSame($pinned, collect($gallery->viewData('illustrations')->items())->take(3)->pluck('id')->all());

        // Une œuvre épinglée qui n'est plus publiée disparaît simplement du fil.
        $works[6]->update(['published_at' => null]);
        $this->assertSame([$works[2]->id, $works[8]->id], $this->get(route('public.index'))->viewData('dailyIllustrations')->take(2)->pluck('id')->all());
    }

    // ---------- Pages ----------

    public function test_pages_are_editable_and_listed_in_the_footer(): void
    {
        $this->get(route('public.legal'))->assertOk()->assertSee('Mentions légales')->assertSee('Propriété intellectuelle');
        $this->get(route('pages.rules'))->assertOk()->assertSee('Des œuvres originales');
        $this->get(route('pages.privacy'))->assertOk()->assertSee('Statistiques de vue');
        $this->get(route('pages.show', 'confidentialite'))->assertOk();

        $this->get(route('public.about'))
            ->assertSee('href="'.route('public.legal').'"', false)
            ->assertSee('href="'.route('pages.rules').'"', false)
            ->assertSee('href="'.route('pages.privacy').'"', false);

        $this->actingAs($this->admin)->get(route('admin.pages.index'))->assertOk()->assertSee('Règles de publication');
        $this->actingAs($this->admin)->get(route('admin.pages.edit', 'confidentialite'))->assertOk()->assertSee('Aperçu');

        $this->actingAs($this->admin)->put(route('admin.pages.update', 'confidentialite'), [
            'titre' => 'Vie privée', 'contenu' => '<h2>Nos données</h2><p onclick="x()">Rien de plus.<script>alert(1)</script></p>',
            'meta_description' => str_repeat('a', 161), 'publie' => 1,
        ])->assertSessionHasErrors('meta_description');

        $this->actingAs($this->admin)->put(route('admin.pages.update', 'confidentialite'), [
            'titre' => 'Vie privée', 'contenu' => '<h2>Nos données</h2><p onclick="x()">Rien de plus.<script>alert(1)</script></p>',
            'meta_description' => 'Ce que nous savons de vous.', 'publie' => 0,
        ])->assertRedirect(route('admin.pages.edit', 'confidentialite'));

        $page = Page::where('slug', 'confidentialite')->sole();
        $this->assertSame('Vie privée', $page->titre);
        $this->assertStringNotContainsString('script', $page->contenu);
        $this->assertStringNotContainsString('onclick', $page->contenu);
        $this->assertFalse($page->publie);
        $this->assertDatabaseHas('admin_logs', ['action' => 'page.modifiee', 'cible_id' => (string) $page->id]);

        // Non publiée : 404 pour le public, aperçu pour l'équipe, et plus de lien dans le pied de page.
        $this->actingAs($this->admin)->get(route('pages.privacy'))->assertOk()->assertSee('Nos données')->assertSee('pas publiée');
        auth()->logout();
        $this->get(route('pages.privacy'))->assertNotFound();
        $this->get(route('public.about'))
            ->assertDontSee('href="'.route('pages.privacy').'"', false)
            ->assertSee('href="'.route('public.legal').'"', false);
        $this->get(route('pages.show', 'inconnue'))->assertNotFound();
    }

    public function test_rights_checkbox_links_to_publication_rules(): void
    {
        $artist = $this->artist('Kassi', works: 0);

        $this->actingAs($artist->user)->get(route('dashboard'))
            ->assertOk()->assertSee('href="'.route('pages.rules').'"', false)->assertSee('Lire les règles de publication');
    }

    // ---------- Annonces ----------

    public function test_announcement_banner_follows_activity_and_dates(): void
    {
        $this->get(route('public.index'))->assertDontSee('aad-annonce__text', false);

        $this->actingAs($this->admin)->post(route('admin.announcements.store'), ['texte' => str_repeat('x', 141), 'actif' => 1])
            ->assertSessionHasErrors('texte');
        $this->actingAs($this->admin)->post(route('admin.announcements.store'), ['texte' => 'Lien bizarre', 'lien' => 'javascript:alert(1)', 'actif' => 1])
            ->assertSessionHasErrors('lien');
        $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'texte' => 'Exposition samedi au Plateau', 'lien' => '/a-propos', 'actif' => 1,
        ])->assertRedirect(route('admin.announcements.index'));
        $annonce = Annonce::sole();
        $this->assertDatabaseHas('admin_logs', ['action' => 'annonce.modifiee', 'cible_id' => (string) $annonce->id]);

        auth()->logout();
        $this->get(route('public.index'))->assertSee('Exposition samedi au Plateau')->assertSee('Fermer l’annonce')
            ->assertSee('data-annonce-id="'.$annonce->id.'"', false);
        $this->get(route('public.writers'))->assertSee('Exposition samedi au Plateau');

        // Désactivée : plus affichée.
        $this->actingAs($this->admin)->patch(route('admin.announcements.toggle', $annonce))->assertRedirect();
        auth()->logout();
        $this->get(route('public.index'))->assertDontSee('Exposition samedi au Plateau');

        // Expirée ou à venir : pas affichée ; dans la période : affichée.
        $annonce->refresh()->update(['actif' => true, 'fin' => now()->subHour()]);
        $this->get(route('public.index'))->assertDontSee('Exposition samedi au Plateau');
        $annonce->update(['debut' => now()->addDay(), 'fin' => null]);
        $this->get(route('public.index'))->assertDontSee('Exposition samedi au Plateau');
        $annonce->update(['debut' => now()->subDay(), 'fin' => now()->addDay()]);
        $this->get(route('public.index'))->assertSee('Exposition samedi au Plateau');

        // Une seule annonce à la fois : la plus récente.
        $this->travel(1)->minutes();
        Annonce::create(['texte' => 'Nouveau drop à midi', 'actif' => true]);
        $this->get(route('public.index'))->assertSee('Nouveau drop à midi')->assertDontSee('Exposition samedi au Plateau');

        $this->actingAs($this->admin)->get(route('admin.announcements.index'))->assertOk()->assertSee('Nouveau drop à midi');
        $this->actingAs($this->admin)->get(route('admin.announcements.index', ['modifier' => $annonce->id]))
            ->assertOk()->assertSee('Modifier l’annonce')->assertSee($annonce->fresh()->fin->format('Y-m-d\TH:i'));
        $this->actingAs($this->admin)->put(route('admin.announcements.update', $annonce), ['texte' => 'Expo reportée', 'actif' => 0])
            ->assertRedirect(route('admin.announcements.index'));
        $this->assertSame('Expo reportée', $annonce->fresh()->texte);
        $this->actingAs($this->admin)->delete(route('admin.announcements.destroy', $annonce))->assertRedirect();
        $this->assertSame(1, Annonce::count());
    }
}
