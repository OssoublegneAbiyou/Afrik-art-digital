<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Comic;
use App\Models\ComicChapter;
use App\Models\ComicPage;
use App\Models\ComicReadingProgress;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Report;
use App\Models\User;
use App\Notifications\MemberActivity;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Récits dessinés, côté lecteur : liste, fiche, lecteur, progression, vues, suivre / favoris / j'aime,
 * signalement et intégrations publiques (menu, recherche, collection, profil, accueil).
 */
class ComicReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    // ---------- Liste ----------

    public function test_list_shows_visible_comics_in_neo_world_with_badges(): void
    {
        $author = $this->makeArtist('Awa Traoré');
        $series = $this->makeComic($author, ['titre' => 'Les Masques de Babi', 'contenu_sensible' => true]);
        $this->addChapter($series, 1);
        $oneShot = $this->makeComic($author, ['titre' => 'Nuit à Treichville', 'type' => Comic::ONE_SHOT, 'last_chapter_at' => now()->subDays(20)]);
        $this->addChapter($oneShot, 1);
        $draft = $this->makeComic($author, ['titre' => 'Brouillon secret', 'published_at' => null]);
        $this->addChapter($draft, 1);
        $noChapter = $this->makeComic($author, ['titre' => 'Sans chapitre visible']);
        $this->addChapter($noChapter, 1, 2, ['published_at' => null]);

        $this->get(route('comics.index'))
            ->assertOk()
            ->assertSee('data-world="neo"', false)
            ->assertSee('RÉCITS DESSINÉS')
            ->assertSee('漫画')
            ->assertSee('BD · MANGA · WEBTOON')
            ->assertSee('class="bd-hero__signs" aria-hidden="true"', false)
            ->assertSee('Les Masques de Babi')
            ->assertSee('Nuit à Treichville')
            ->assertSee('NOUVEAU CHAPITRE')
            ->assertSee('Contenu sensible')
            ->assertSee('alt="Couverture de « Les Masques de Babi » par Awa Traoré"', false)
            ->assertSee('2 récits publiés')
            ->assertDontSee('Brouillon secret')
            ->assertDontSee('Sans chapitre visible')
            ->assertDontSee('(s)');
    }

    public function test_list_filters_and_sorts(): void
    {
        $author = $this->makeArtist();
        $running = $this->makeComic($author, ['titre' => 'Série en cours', 'genre' => 'fantasy', 'published_at' => now()->subDays(3), 'last_chapter_at' => now()->subDays(3)]);
        $this->addChapter($running, 1);
        $done = $this->makeComic($author, ['titre' => 'Série terminée', 'statut_serie' => 'terminee', 'genre' => 'thriller', 'published_at' => now()->subDays(2), 'last_chapter_at' => now()->subDays(10)]);
        $this->addChapter($done, 1);
        $short = $this->makeComic($author, ['titre' => 'Histoire courte', 'type' => Comic::ONE_SHOT, 'published_at' => now()->subDay(), 'last_chapter_at' => now()->subDays(30)]);
        $this->addChapter($short, 1);

        $this->get(route('comics.index', ['type' => 'one-shot']))
            ->assertOk()->assertSee('Histoire courte')->assertDontSee('Série en cours')->assertDontSee('Série terminée')
            ->assertSee('aria-pressed="true"', false);
        $this->get(route('comics.index', ['type' => 'serie']))
            ->assertSee('Série en cours')->assertSee('Série terminée')->assertDontSee('Histoire courte');
        $this->get(route('comics.index', ['type' => 'terminee']))
            ->assertSee('Série terminée')->assertDontSee('Série en cours');
        $this->get(route('comics.index', ['genre' => 'fantasy']))
            ->assertSee('Série en cours')->assertDontSee('Série terminée')->assertSee(Genre::labelFor('fantasy'));
        // Genre inconnu : ignoré.
        $this->get(route('comics.index', ['genre' => 'inexistant']))->assertOk()->assertSee('Série terminée');

        // Récents (date de publication), Mis à jour (dernier chapitre), Populaires (j'aime).
        $this->get(route('comics.index'))->assertSeeInOrder(['Histoire courte', 'Série terminée', 'Série en cours']);
        $this->get(route('comics.index', ['tri' => 'maj']))->assertSeeInOrder(['Série en cours', 'Série terminée', 'Histoire courte']);
        foreach ([User::factory()->create(), User::factory()->create()] as $fan) {
            $done->toggleLikeFor($fan);
        }
        $this->get(route('comics.index', ['tri' => 'populaire']))->assertSeeInOrder(['Série terminée', 'Histoire courte']);
    }

    public function test_list_is_paginated_by_24_with_french_pager(): void
    {
        $author = $this->makeArtist();
        for ($i = 1; $i <= 25; $i++) {
            $comic = $this->makeComic($author, ['titre' => 'Récit numéro '.$i, 'published_at' => now()->subMinutes(100 - $i)]);
            $this->addChapter($comic, 1, 1);
        }

        $this->get(route('comics.index'))
            ->assertOk()
            ->assertSee('page 1 sur 2')
            ->assertSee('Suivant')
            ->assertSee('Récit numéro 25')
            ->assertDontSee('Récit numéro 1<', false);
        $this->get(route('comics.index', ['page' => 2]))->assertSee('Récit numéro 1');
    }

    // ---------- Fiche ----------

    public function test_show_page_presents_the_comic_and_its_chapters(): void
    {
        $author = $this->makeArtist('Awa Traoré');
        $comic = $this->makeComic($author, ['titre' => 'Les Masques de Babi', 'genre' => 'fantasy', 'resume' => 'Un masque, une ville, une nuit.', 'tags' => ['masques', 'abidjan']]);
        $this->addChapter($comic, 1, 3, ['titre' => 'Le réveil']);
        $this->addChapter($comic, 2.5, 2, ['titre' => 'Bonus']);
        $other = $this->makeComic($author, ['titre' => 'Autre récit du studio']);
        $this->addChapter($other, 1);

        $this->get(route('comics.show', $comic))
            ->assertOk()
            ->assertSee('Les Masques de Babi')
            ->assertSee('href="'.route('public.artist', $author->artist).'"', false)
            ->assertSee(Genre::labelFor('fantasy'))
            ->assertSee('En cours')
            ->assertSee('Un masque, une ville, une nuit.')
            ->assertSee('#masques')
            ->assertSee('Commencer la lecture')
            ->assertSee('href="'.route('comics.read', [$comic, '1']).'"', false)
            ->assertSee('Se connecter pour suivre')
            ->assertSee('J’aime')
            ->assertSee('Partager le récit')
            ->assertSee('Signaler')
            ->assertSee('Ch. 1')
            ->assertSee('Le réveil')
            ->assertSee('Ch. 2,5')
            ->assertSee(route('comics.read', [$comic, '2.5']), false)
            ->assertSee('3 pages')
            ->assertSee('Du même studio')
            ->assertSee('Autre récit du studio');

        // Le bouton « Signaler » est masqué pour l'auteur.
        $this->actingAs($author)->get(route('comics.show', $comic))->assertOk()->assertDontSee('Signaler');
    }

    public function test_draft_chapter_is_hidden_from_public_but_visible_to_author_and_staff(): void
    {
        $author = $this->makeArtist();
        $comic = $this->makeComic($author, ['titre' => 'Série test']);
        $this->addChapter($comic, 1);
        $this->addChapter($comic, 2, 2, ['published_at' => null, 'titre' => 'Chapitre en brouillon']);
        $this->addChapter($comic, 3, 2, ['published_at' => now()->addDay(), 'titre' => 'Chapitre programmé']);

        $this->get(route('comics.show', $comic))->assertOk()->assertDontSee('Chapitre en brouillon')->assertDontSee('Chapitre programmé');
        $this->get(route('comics.read', [$comic, '2']))->assertNotFound();
        $this->get(route('comics.read', [$comic, '3']))->assertNotFound();
        // Le chapitre 1 n'annonce pas de suivant.
        $this->get(route('comics.read', [$comic, '1']))->assertOk()->assertSee('Prochain chapitre bientôt')->assertDontSee('Chapitre suivant');

        $this->actingAs($author)->get(route('comics.show', $comic))->assertOk()->assertSee('Chapitre en brouillon')->assertSee('Brouillon');
        $this->actingAs($author)->get(route('comics.read', [$comic, '2']))->assertOk()->assertSee('Aperçu');

        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $this->actingAs($moderator)->get(route('comics.read', [$comic, '3']))->assertOk();

        // Récit entièrement en brouillon : 404 pour le public, aperçu pour l'auteur.
        $draft = $this->makeComic($author, ['titre' => 'Fiche brouillon', 'published_at' => null]);
        $this->addChapter($draft, 1);
        auth()->logout();
        $this->get(route('comics.show', $draft))->assertNotFound();
        $this->actingAs($author)->get(route('comics.show', $draft))->assertOk()->assertSee('visible que par toi');
    }

    // ---------- Lecteur ----------

    public function test_reader_renders_pages_with_alt_live_region_mode_and_direction(): void
    {
        $author = $this->makeArtist();
        $comic = $this->makeComic($author, ['titre' => 'Les Masques de Babi']);
        $chapter = $this->addChapter($comic, 1, 5, ['note_auteur' => 'Merci de me lire !']);
        $chapter->pages()->where('ordre', 2)->update(['alt' => 'Kofi court sous la pluie']);
        $this->addChapter($comic, 2, 1, ['titre' => 'La suite']);

        $response = $this->get(route('comics.read', [$comic, '1']))
            ->assertOk()
            ->assertSee('data-comic-reader', false)
            ->assertSee('data-mode="vertical"', false)
            ->assertSee('data-dir="ltr"', false)
            ->assertSee('alt="Page 1 du chapitre 1 de Les Masques de Babi"', false)
            ->assertSee('alt="Kofi court sous la pluie"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('Page 1 sur 5')
            ->assertSee('width="800" height="1200"', false)
            ->assertSee('decoding="async"', false)
            ->assertSee('aria-label="Réglages de lecture"', false)
            ->assertSee('aria-label="Page suivante"', false)
            ->assertSee('Merci de me lire !')
            ->assertSee('Chapitre suivant')
            ->assertSee('La suite')
            ->assertDontSee('data-world="neo" data-mode="nuit" class="w-', false);

        // Les 3 premières pages se chargent tout de suite, les suivantes en différé.
        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'loading="lazy"'));

        // Manga page par page : mode et sens transmis.
        $manga = $this->makeComic($author, ['titre' => 'Manga test', 'format_lecture' => 'pages', 'sens_lecture' => 'rtl', 'statut_serie' => 'terminee']);
        $this->addChapter($manga, 1, 2);
        $this->get(route('comics.read', [$manga, '1']))
            ->assertOk()
            ->assertSee('data-mode="pages"', false)
            ->assertSee('data-dir="rtl"', false)
            ->assertSee('Lecture de droite à gauche')
            ->assertSee('Fin — merci d’avoir lu !');
    }

    public function test_reader_stacks_slices_and_supports_bonus_chapter_numbers(): void
    {
        $author = $this->makeArtist();
        $comic = $this->makeComic($author, ['titre' => 'Webtoon']);
        $this->addChapter($comic, 1, 1);
        $bonus = $this->addChapter($comic, 2.5, 0);
        ComicPage::create([
            'comic_chapter_id' => $bonus->id, 'ordre' => 1, 'original' => 'comics/originaux/tall.png', 'lecture' => 'comics/lecture/tall-v1.webp',
            'largeur' => 800, 'hauteur' => 4000, 'statut_traitement' => ComicPage::READY,
            'tranches' => [
                ['chemin' => 'comics/lecture/tall-v1-1.webp', 'largeur' => 800, 'hauteur' => 2000],
                ['chemin' => 'comics/lecture/tall-v1-2.webp', 'largeur' => 800, 'hauteur' => 2000],
            ],
        ]);

        $this->get(route('comics.read', [$comic, '2.5']))
            ->assertOk()
            ->assertSee('Chapitre 2,5')
            ->assertSee('tall-v1-1.webp')
            ->assertSee('tall-v1-2.webp')
            ->assertSee('alt="Page 1 du chapitre 2,5 de Webtoon"', false)
            ->assertDontSee('comics/originaux', false);

        $this->get('/recits-dessines/'.$comic->slug.'/lire/9')->assertNotFound();
    }

    public function test_pages_that_are_not_ready_are_never_shown(): void
    {
        $author = $this->makeArtist();
        $comic = $this->makeComic($author);
        $chapter = $this->addChapter($comic, 1, 2);
        ComicPage::create(['comic_chapter_id' => $chapter->id, 'ordre' => 3, 'original' => 'comics/originaux/p3.png', 'lecture' => 'comics/lecture/en-attente.webp', 'statut_traitement' => ComicPage::PENDING]);
        ComicPage::create(['comic_chapter_id' => $chapter->id, 'ordre' => 4, 'original' => 'comics/originaux/p4.png', 'lecture' => 'comics/lecture/en-erreur.webp', 'statut_traitement' => ComicPage::FAILED]);

        $this->get(route('comics.read', [$comic, '1']))
            ->assertOk()
            ->assertSee('Page 1 sur 2')
            ->assertDontSee('en-attente.webp')
            ->assertDontSee('en-erreur.webp');

        $this->get(route('comics.show', $comic))->assertSee('2 pages');

        $this->actingAs($author)->get(route('comics.read', [$comic, '1']))
            ->assertOk()
            ->assertDontSee('en-attente.webp')
            ->assertSee('2 pages encore en préparation');
    }

    public function test_sensitive_content_shows_a_warning_before_reading(): void
    {
        $comic = $this->makeComic($this->makeArtist(), ['contenu_sensible' => true]);
        $this->addChapter($comic, 1);

        $this->get(route('comics.read', [$comic, '1']))
            ->assertOk()
            ->assertSee('role="alertdialog"', false)
            ->assertSee('Ce récit contient des scènes qui peuvent heurter');
        $this->get(route('comics.read', [$comic, '1', 'avertissement' => 'lu']))
            ->assertOk()
            ->assertDontSee('role="alertdialog"', false);
    }

    // ---------- Progression ----------

    public function test_progress_is_saved_and_resume_is_offered(): void
    {
        $author = $this->makeArtist();
        $comic = $this->makeComic($author, ['titre' => 'Série suivie']);
        $first = $this->addChapter($comic, 1, 10);
        $second = $this->addChapter($comic, 2, 4);
        $reader = User::factory()->create();

        $this->actingAs($reader)
            ->postJson(route('comics.progress'), ['chapitre' => $first->id, 'page' => 4, 'pourcentage' => 40])
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertDatabaseHas('comic_reading_progress', ['user_id' => $reader->id, 'comic_chapter_id' => $first->id, 'page_ordre' => 4, 'pourcentage' => 40]);

        $this->get(route('comics.show', $comic))
            ->assertSee('Reprendre ch. 1, p. 4')
            ->assertSee('Lu à 40 %')
            ->assertSee(route('comics.read', [$comic, '1', 'page' => 4]), false);
        $this->get(route('comics.read', [$comic, '1']))->assertSee('Page 4 sur 10');

        // Envoi de fermeture (sendBeacon) : formulaire classique.
        $this->post(route('comics.progress'), ['chapitre' => $first->id, 'page' => 10, 'pourcentage' => 100])->assertOk();
        $this->get(route('comics.show', $comic))
            ->assertSee('✓')
            ->assertSee('Continuer : ch. 2');

        // Validation stricte.
        $this->postJson(route('comics.progress'), ['chapitre' => $first->id, 'page' => 0, 'pourcentage' => 140])->assertUnprocessable();
        $draft = $this->addChapter($comic, 3, 1, ['published_at' => null]);
        $this->postJson(route('comics.progress'), ['chapitre' => $draft->id, 'page' => 1, 'pourcentage' => 10])->assertNotFound();

        // Visiteur : rien en base (localStorage côté navigateur).
        auth()->logout();
        $this->postJson(route('comics.progress'), ['chapitre' => $second->id, 'page' => 1, 'pourcentage' => 25])->assertOk()->assertJson(['saved' => false]);
        $this->assertSame(1, ComicReadingProgress::count());
    }

    public function test_a_view_is_counted_once_per_visitor_per_day_after_three_pages_or_twenty_percent(): void
    {
        $author = $this->makeArtist();
        $comic = $this->makeComic($author);
        $chapter = $this->addChapter($comic, 1, 20);

        $this->postJson(route('comics.progress'), ['chapitre' => $chapter->id, 'page' => 2, 'pourcentage' => 10])->assertJson(['view' => false]);
        $this->assertSame(0, $chapter->fresh()->view_count);

        $this->postJson(route('comics.progress'), ['chapitre' => $chapter->id, 'page' => 3, 'pourcentage' => 15])->assertJson(['view' => true]);
        $this->postJson(route('comics.progress'), ['chapitre' => $chapter->id, 'page' => 8, 'pourcentage' => 40])->assertJson(['view' => false]);
        $this->assertSame(1, ComicChapter::withUnpublished()->find($chapter->id)->view_count);
        $this->assertSame(1, Comic::withUnpublished()->find($comic->id)->view_count);

        // L'auteur ne compte pas ses propres lectures.
        $this->actingAs($author)->postJson(route('comics.progress'), ['chapitre' => $chapter->id, 'page' => 5, 'pourcentage' => 25]);
        $this->assertSame(1, $chapter->fresh()->view_count);

        // Le lendemain, le même visiteur compte à nouveau.
        auth()->logout();
        $this->travel(1)->days();
        $this->postJson(route('comics.progress'), ['chapitre' => $chapter->id, 'page' => 1, 'pourcentage' => 20])->assertJson(['view' => true]);
        $this->assertSame(2, $chapter->fresh()->view_count);
    }

    // ---------- Suivre, favoris, j'aime ----------

    public function test_follow_favorite_and_like(): void
    {
        $author = $this->makeArtist();
        $comic = $this->makeComic($author, ['titre' => 'Série à suivre']);
        $this->addChapter($comic, 1);
        $fan = User::factory()->create(['name' => 'Fatou']);

        $this->post(route('comics.follow', $comic))->assertRedirect(route('login'));

        $this->actingAs($fan)->postJson(route('comics.follow', $comic))->assertOk()->assertJson(['following' => true, 'count' => 1]);
        $notification = $author->notifications()->first();
        $this->assertSame(MemberActivity::COMIC_NEW_FOLLOWER, $notification->data['kind']);
        $this->assertStringContainsString('Fatou suit désormais « Série à suivre »', $notification->data['message']);
        $this->get(route('comics.show', $comic))->assertSee('Ne plus suivre')->assertSee('aria-pressed="true"', false);

        $this->actingAs($fan)->post(route('comics.follow', $comic))->assertRedirect()->assertSessionHas('comic_status');
        $this->assertSame(0, $comic->followers()->count());
        $this->assertSame(1, $author->notifications()->count());

        // Favoris (morph « comic »).
        $this->actingAs($fan)->post(route('comics.favorite', $comic));
        $this->assertDatabaseHas('favorites', ['user_id' => $fan->id, 'favoritable_type' => 'comic', 'favoritable_id' => $comic->id]);
        $this->get(route('comics.show', $comic))->assertSee('Retirer des favoris');
        $this->actingAs($fan)->postJson(route('comics.favorite', $comic))->assertJson(['favorite' => false]);
        $this->assertSame(0, Favorite::count());

        // J'aime.
        $this->actingAs($fan)->postJson(route('likes.toggle', ['comic', $comic->id]))->assertOk()->assertJson(['liked' => true, 'count' => 1]);
        $this->actingAs($fan)->postJson(route('likes.toggle', ['comic', $comic->id]))->assertJson(['liked' => false, 'count' => 0]);

        // L'auteur ne s'abonne pas à sa propre série.
        $this->actingAs($author)->postJson(route('comics.follow', $comic))->assertJson(['following' => false]);
        $this->assertSame(0, $comic->followers()->count());

        // Récit non visible : ni like ni suivi pour les autres.
        $draft = $this->makeComic($author, ['published_at' => null]);
        $this->actingAs($fan)->postJson(route('likes.toggle', ['comic', $draft->id]))->assertNotFound();
        $this->actingAs($fan)->postJson(route('comics.follow', $draft))->assertNotFound();
    }

    // ---------- Signalement ----------

    public function test_comics_and_chapters_can_be_reported(): void
    {
        $author = $this->makeArtist();
        $comic = $this->makeComic($author, ['titre' => 'Signalé']);
        $chapter = $this->addChapter($comic, 1);
        $this->addChapter($comic, 2);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->post(route('reports.store', ['comic', $comic->id]), ['reason' => 'droits', 'message' => 'Planches copiées.'])->assertSessionHas('report_sent');
        $this->post(route('reports.store', ['comic_chapter', $chapter->id]), ['reason' => 'contenu']);

        $this->assertDatabaseHas('reports', ['reportable_type' => 'comic', 'reportable_id' => $comic->id, 'reason' => 'droits']);
        $this->assertDatabaseHas('reports', ['reportable_type' => 'comic_chapter', 'reportable_id' => $chapter->id, 'reason' => 'contenu']);
        $this->assertSame(2, $admin->notifications()->count());
        $messages = $admin->notifications()->get()->pluck('data.message')->implode("\n");
        $this->assertStringContainsString('« Signalé » a été signalé : droits d’auteur', $messages);
        $this->assertStringContainsString('« Signalé ch. 1 » a été signalé', $messages);

        // Le lecteur propose de signaler le chapitre.
        $this->flushSession();
        $this->get(route('comics.read', [$comic, '1']))->assertSee(route('reports.store', ['comic_chapter', $chapter->id]), false);

        $draft = $this->addChapter($comic, 3, 1, ['published_at' => null]);
        $this->post(route('reports.store', ['comic_chapter', $draft->id]), ['reason' => 'spam'])->assertNotFound();
        $this->assertSame(2, Report::count());
    }

    // ---------- Intégrations ----------

    public function test_menu_search_collection_profile_and_home_integrations(): void
    {
        $author = $this->makeArtist('Awa Traoré');
        $webtoon = $this->makeComic($author, ['titre' => 'Les Masques de Babi', 'resume' => 'Une nuit de carnaval.']);
        $first = $this->addChapter($webtoon, 1, 10);
        $this->addChapter($webtoon, 2, 3);
        $manga = $this->makeComic($author, ['titre' => 'Lames du Plateau', 'format_lecture' => 'pages', 'sens_lecture' => 'rtl']);
        $this->addChapter($manga, 1);

        // Menu Néo : entre Galerie et Écrivains.
        $this->get(route('public.illustrations'))->assertSeeInOrder(['Galerie', 'Récits dessinés', 'Écrivains']);

        // Recherche globale.
        $this->get(route('search', ['q' => 'carnaval']))
            ->assertOk()
            ->assertSee('id="recits-dessines"', false)
            ->assertSee('Les Masques de Babi')
            ->assertDontSee('Lames du Plateau');
        $this->get(route('search', ['q' => 'Awa']))->assertSee('Lames du Plateau');

        // Profil illustrateur.
        $this->get(route('public.artist', $author->artist))->assertOk()->assertSee('Ses récits dessinés')->assertSee('Lames du Plateau');

        // Accueil : nouveaux chapitres dans « Le fil » avec le type BD / MANGA / WEBTOON.
        $this->get(route('public.index'))
            ->assertOk()
            ->assertSee('Les Masques de Babi — ch. 2')
            ->assertSee('WEBTOON · Nouveau chapitre')
            ->assertSee('MANGA')
            ->assertSee(route('comics.read', [$webtoon, '2']), false);

        // Ma collection : séries suivies avec reprise, favoris.
        $fan = User::factory()->create();
        $webtoon->followers()->attach($fan->id);
        $manga->favorites()->create(['user_id' => $fan->id]);
        ComicReadingProgress::create(['user_id' => $fan->id, 'comic_chapter_id' => $first->id, 'page_ordre' => 6, 'pourcentage' => 60]);

        $this->actingAs($fan)->get(route('collection'))
            ->assertOk()
            ->assertSee('Séries suivies')
            ->assertSee('Les Masques de Babi')
            ->assertSee('Reprendre ch. 1, p. 6')
            ->assertSee('Lames du Plateau')
            ->assertSee('Retirer des favoris');
    }

    public function test_everything_is_hidden_when_comics_are_disabled(): void
    {
        $author = $this->makeArtist();
        $comic = $this->makeComic($author, ['titre' => 'Récit caché']);
        $chapter = $this->addChapter($comic, 1);
        Settings::set('bd.active', false);

        $this->get(route('comics.index'))->assertNotFound();
        $this->get(route('comics.show', $comic))->assertNotFound();
        $this->get(route('comics.read', [$comic, '1']))->assertNotFound();
        $this->postJson(route('comics.progress'), ['chapitre' => $chapter->id, 'page' => 3, 'pourcentage' => 30])->assertNotFound();
        $this->actingAs(User::factory()->create())->post(route('comics.follow', $comic))->assertNotFound();

        $this->get(route('public.illustrations'))->assertOk()->assertDontSee('Récits dessinés');
        $this->get(route('search', ['q' => 'caché']))->assertDontSee('Récit caché');
        $this->get(route('public.artist', $author->artist))->assertDontSee('Ses récits dessinés');
        $this->get(route('collection'))->assertOk()->assertDontSee('Séries suivies');
    }

    // ---------- Données ----------

    private function makeArtist(string $name = 'Kofi Dessin'): User
    {
        $user = User::factory()->create(['account_type' => 'artist', 'name' => $name]);
        Artist::create(['user_id' => $user->id]);

        return $user->fresh('artist');
    }

    private function makeComic(User $author, array $attributes = []): Comic
    {
        return Comic::create(array_merge([
            'user_id' => $author->id,
            'type' => Comic::SERIES,
            'titre' => 'Récit '.str()->random(6),
            'couverture' => 'comics/couvertures/'.str()->random(8).'.webp',
            'format_lecture' => 'vertical',
            'sens_lecture' => 'ltr',
            'statut_serie' => 'en_cours',
            'published_at' => now()->subDay(),
            'moderation_status' => Comic::MODERATION_APPROVED,
            'last_chapter_at' => now()->subDay(),
        ], $attributes));
    }

    private function addChapter(Comic $comic, float $numero, int $pages = 3, array $attributes = []): ComicChapter
    {
        $chapter = ComicChapter::create(array_merge([
            'comic_id' => $comic->id,
            'numero' => $numero,
            'pages_count' => $pages,
            'published_at' => now()->subHours(2),
            'moderation_status' => ComicChapter::MODERATION_APPROVED,
        ], $attributes));

        for ($i = 1; $i <= $pages; $i++) {
            ComicPage::create([
                'comic_chapter_id' => $chapter->id,
                'ordre' => $i,
                'original' => 'comics/originaux/'.$chapter->id.'-'.$i.'.png',
                'lecture' => 'comics/lecture/'.$chapter->id.'-'.$i.'-v1.webp',
                'miniature' => 'comics/miniatures/'.$chapter->id.'-'.$i.'-v1.webp',
                'largeur' => 800,
                'hauteur' => 1200,
                'statut_traitement' => ComicPage::READY,
            ]);
        }

        return ComicChapter::withUnpublished()->find($chapter->id);
    }
}
