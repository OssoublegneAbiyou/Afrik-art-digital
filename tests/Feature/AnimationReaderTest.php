<?php

namespace Tests\Feature;

use App\Models\Animation;
use App\Models\Artist;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Report;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Animations, côté public : lecteur (boucle / clip), fiche, visibilité, filtre « Animé » de la galerie,
 * j'aime / favoris / signalement, vues, intégrations (profil, accueil, recherche, collection) et désactivation.
 * Les animations sont créées déjà « prêtes » avec des chemins factices : ffmpeg n'est pas nécessaire.
 */
class AnimationReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    // ---------- Lecteur ----------

    public function test_loop_player_autoplays_muted_in_a_loop_inline_with_its_poster(): void
    {
        $animation = $this->makeAnimation($this->makeArtist(), ['titre' => 'Taxi de nuit']);

        $this->get(route('animations.show', $animation))
            ->assertOk()
            ->assertSee('data-world="neo"', false)
            ->assertSee('data-animation-player', false)
            ->assertSee('data-mode="boucle"', false)
            ->assertSee('<video class="ap__video" autoplay muted loop playsinline preload="metadata"', false)
            ->assertSee('poster="'.$animation->posterUrl().'"', false)
            ->assertSee('<source src="'.$animation->mp4Url().'" type="video/mp4">', false)
            ->assertSee('data-view-url="'.route('animations.view', $animation).'"', false)
            ->assertSee('aria-label="Mettre l’animation en pause"', false)
            ->assertSee('style="--ap-ratio: 640 / 360;"', false)
            // Image de partage : le poster.
            ->assertSee('content="'.$animation->posterUrl().'"', false);
    }

    public function test_clip_player_never_autoplays_and_has_custom_accessible_controls(): void
    {
        $clip = $this->makeAnimation($this->makeArtist(), [
            'titre' => 'Le marché parle',
            'mode' => Animation::CLIP,
            'webm' => 'animations/9/video-v1.webm',
            'duree_s' => 42,
        ]);

        $response = $this->get(route('animations.show', $clip))->assertOk();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/<video class="ap__video" controls playsinline preload="metadata"\s+poster="[^"]+"/', $html);
        $this->assertStringNotContainsString('autoplay', $html);
        $this->assertStringNotContainsString('muted loop', $html);

        $response
            ->assertSee('data-mode="clip"', false)
            ->assertSee('type="video/webm; codecs=vp9"', false)
            ->assertSeeInOrder(['video/webm', 'video/mp4'], false)
            ->assertSee('aria-label="Lire le clip « Le marché parle »"', false)
            ->assertSee('data-ap-toggle aria-pressed="false" aria-label="Lecture"', false)
            ->assertSee('data-ap-seek', false)
            ->assertSee('aria-valuenow="0"', false)
            ->assertSee('aria-valuetext="0:00 sur 0:42"', false)
            ->assertSee('aria-label="Couper le son"', false)
            ->assertSee('aria-label="Plein écran"', false)
            ->assertSee('>Volume</label>', false)
            ->assertSee('Raccourcis : Espace (lecture/pause), ← → (5 s), M (muet), F (plein écran).');
    }

    public function test_show_page_presents_the_animation(): void
    {
        $author = $this->makeArtist('Awa Traoré');
        $animation = $this->makeAnimation($author, [
            'titre' => 'Taxi de nuit',
            'description' => 'Un woro-woro traverse le pont.',
            'genre' => 'fantasy',
            'tags' => ['taxi', 'abidjan'],
        ]);
        $other = $this->makeAnimation($author, ['titre' => 'Autre boucle du studio']);
        $foreign = $this->makeAnimation($this->makeArtist('Kofi'), ['titre' => 'Boucle d’un autre studio']);

        $this->get(route('animations.show', $animation))
            ->assertOk()
            ->assertSee('Taxi de nuit')
            ->assertSee('<span class="nb-byline__label">Une animation d’</span>', false)
            ->assertSee('<span class="nb-byline__name">Awa Traoré</span>', false)
            ->assertSee('href="'.route('public.artist', $author->artist).'"', false)
            ->assertSee(Genre::labelFor('fantasy'))
            ->assertSee('Un woro-woro traverse le pont.')
            ->assertSee('#taxi')
            ->assertSee('Boucle muette')
            ->assertSee('0:07')
            ->assertSee('Se connecter pour garder en favori')
            ->assertSee('J’aime')
            ->assertSee('Partager l’animation')
            ->assertSee('Signaler')
            ->assertSee('Du même studio')
            ->assertSee('Autre boucle du studio')
            ->assertSee('href="'.route('public.illustrations', ['format' => 'anime']).'"', false)
            ->assertDontSee('Boucle d’un autre studio');
    }

    public function test_sensitive_content_shows_a_warning_before_playing(): void
    {
        $author = $this->makeArtist();
        $animation = $this->makeAnimation($author, ['contenu_sensible' => true]);

        $this->get(route('animations.show', $animation))
            ->assertOk()
            ->assertSee('role="alertdialog"', false)
            ->assertSee('Cette animation contient des images qui peuvent heurter')
            ->assertSee(route('animations.show', [$animation, 'avertissement' => 'lu']), false)
            ->assertDontSee('data-animation-player', false);

        $this->get(route('animations.show', [$animation, 'avertissement' => 'lu']))
            ->assertOk()
            ->assertDontSee('role="alertdialog"', false)
            ->assertSee('data-animation-player', false);

        // L'auteur voit directement son animation.
        $this->actingAs($author)->get(route('animations.show', $animation))->assertSee('data-animation-player', false);

        // Dans les grilles : poster flouté, jamais de lecture.
        $this->get(route('public.illustrations', ['format' => 'anime']))
            ->assertSee('is-sensitive', false)
            ->assertSee('Contenu sensible')
            ->assertDontSee('data-animation-card ', false);
    }

    // ---------- Visibilité ----------

    public function test_unpublished_pending_rejected_or_unready_animations_are_hidden_but_previewable(): void
    {
        $author = $this->makeArtist();
        $staff = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $stranger = User::factory()->create();

        $hidden = [
            'brouillon' => $this->makeAnimation($author, ['titre' => 'Anim brouillon', 'published_at' => null]),
            'programmée' => $this->makeAnimation($author, ['titre' => 'Anim programmée', 'published_at' => now()->addDay()]),
            'en attente' => $this->makeAnimation($author, ['titre' => 'Anim en attente', 'moderation_status' => Animation::MODERATION_PENDING]),
            'refusée' => $this->makeAnimation($author, ['titre' => 'Anim refusée', 'moderation_status' => Animation::MODERATION_REJECTED, 'moderation_note' => 'Son trop fort']),
            'en conversion' => $this->makeAnimation($author, ['titre' => 'Anim en conversion', 'statut_traitement' => Animation::PROCESSING, 'mp4' => null, 'poster' => null]),
            'échec' => $this->makeAnimation($author, ['titre' => 'Anim en échec', 'statut_traitement' => Animation::FAILED, 'erreur' => 'Fichier abîmé.', 'mp4' => null]),
        ];

        foreach ($hidden as $label => $animation) {
            $this->assertFalse($animation->isVisible(), $label);
            $this->get(route('animations.show', $animation))->assertNotFound();
            $this->actingAs($stranger)->get(route('animations.show', $animation))->assertNotFound();
            $this->actingAs($author)->get(route('animations.show', $animation))
                ->assertOk()
                ->assertSee('class="preview-banner"', false)
                ->assertSee('<meta name="robots" content="noindex">', false)
                ->assertDontSee('data-view-url', false)
                ->assertDontSee('Partager l’animation');
            $this->actingAs($staff)->get(route('animations.show', $animation))->assertOk();
            auth()->logout();
        }

        $this->actingAs($author)->get(route('animations.show', $hidden['refusée']))->assertSee('Refusée par l’équipe (Son trop fort)');
        $this->actingAs($author)->get(route('animations.show', $hidden['en conversion']))
            ->assertSee('Conversion en cours')
            ->assertDontSee('data-animation-player', false);
        $this->actingAs($author)->get(route('animations.show', $hidden['échec']))->assertSee('Échec de la conversion : Fichier abîmé.');
        auth()->logout();

        // Rien de tout ça dans la galerie, le profil, la recherche ou l'accueil.
        $visible = $this->makeAnimation($author, ['titre' => 'Anim visible']);
        $pages = [
            route('public.illustrations', ['format' => 'anime']),
            route('public.illustrations'),
            route('public.artist', $author->artist),
            route('search', ['q' => 'Anim']),
            route('public.index'),
        ];
        foreach ($pages as $url) {
            $response = $this->get($url)->assertOk()->assertSee('Anim visible');
            foreach ($hidden as $animation) {
                $response->assertDontSee($animation->titre);
            }
        }

        // Ni j'aime, ni favori, ni signalement, ni vue sur une animation non visible.
        $this->actingAs($stranger)->postJson(route('likes.toggle', ['animation', $hidden['en conversion']->id]))->assertNotFound();
        $this->actingAs($stranger)->postJson(route('likes.toggle', ['animation', $hidden['brouillon']->id]))->assertNotFound();
        $this->actingAs($stranger)->post(route('animations.favorite', $hidden['en attente']))->assertNotFound();
        $this->post(route('reports.store', ['animation', $hidden['échec']->id]), ['reason' => 'spam'])->assertNotFound();
        $this->actingAs($author)->postJson(route('animations.view', $hidden['brouillon']))->assertOk()->assertJson(['view' => false]);
        $this->assertSame(0, Report::count());
        $this->assertSame(0, Favorite::count());
        $this->assertSame(0, (int) Animation::withUnpublished()->sum('view_count'));
    }

    // ---------- Galerie : filtre « Animé » ----------

    public function test_gallery_has_an_animated_filter_listing_ready_animations(): void
    {
        $author = $this->makeArtist('Awa Traoré');
        $older = $this->makeAnimation($author, ['titre' => 'Boucle ancienne', 'published_at' => now()->subDays(3)]);
        $this->makeAnimation($author, ['titre' => 'Clip récent', 'mode' => Animation::CLIP, 'published_at' => now()->subDay()]);

        // Galerie complète : bouton « Animé » avec le nombre réel, et section « Animations ».
        $this->get(route('public.illustrations'))
            ->assertOk()
            ->assertSee('name="format" value="anime"', false)
            ->assertSee('Animé')
            ->assertSee('Toutes les animations')
            ->assertSee('Clip récent');

        // Filtre actif : grille d'animations, plus récente d'abord.
        $this->get(route('public.illustrations', ['format' => 'anime']))
            ->assertOk()
            ->assertSee('<h2 id="nb-gal-grid-title" class="nb-h2">', false)
            ->assertSee('Animations')
            ->assertSee('2 animations')
            ->assertSeeInOrder(['Clip récent', 'Boucle ancienne'])
            ->assertSee('Animé · Boucle')
            ->assertSee('Animé · Clip')
            ->assertSee('class="an-card__play"', false)
            ->assertSee('data-animation-card muted loop playsinline preload="none"', false)
            ->assertSee('format=anime&amp;tri=populaire', false)
            ->assertSee(route('animations.show', $older), false)
            ->assertDontSee('Toutes les animations');

        // Tri « Populaire » : j'aime.
        $older->toggleLikeFor(User::factory()->create());
        $this->get(route('public.illustrations', ['format' => 'anime', 'tri' => 'populaire']))
            ->assertSeeInOrder(['Boucle ancienne', 'Clip récent']);

        // Aucune animation : pas de bouton « Animé ».
        Animation::withUnpublished()->delete();
        $this->get(route('public.illustrations'))->assertOk()->assertDontSee('name="format" value="anime"', false);
    }

    // ---------- J'aime, favoris, signalement ----------

    public function test_like_favorite_and_report(): void
    {
        $author = $this->makeArtist();
        $animation = $this->makeAnimation($author, ['titre' => 'Boucle aimée']);
        $fan = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // J'aime (type « animation »).
        $this->actingAs($fan)->postJson(route('likes.toggle', ['animation', $animation->id]))->assertOk()->assertJson(['liked' => true, 'count' => 1]);
        $this->assertDatabaseHas('likes', ['user_id' => $fan->id, 'likeable_type' => 'animation', 'likeable_id' => $animation->id]);
        $this->get(route('animations.show', $animation))->assertSee('Tu aimes')->assertSee(route('likes.toggle', ['animation', $animation->id]), false);
        $this->actingAs($fan)->postJson(route('likes.toggle', ['animation', $animation->id]))->assertJson(['liked' => false, 'count' => 0]);

        // Favoris (morph « animation »).
        $this->post(route('animations.favorite', $animation))->assertRedirect()->assertSessionHas('animation_status');
        $this->assertDatabaseHas('favorites', ['user_id' => $fan->id, 'favoritable_type' => 'animation', 'favoritable_id' => $animation->id]);
        $this->get(route('animations.show', $animation))->assertSee('Retirer des favoris')->assertSee('aria-pressed="true"', false);
        $this->postJson(route('animations.favorite', $animation))->assertJson(['favorite' => false]);
        $this->assertSame(0, Favorite::count());
        auth()->logout();
        $this->post(route('animations.favorite', $animation))->assertRedirect(route('login'));

        // Signalement (visiteur), l'équipe est prévenue.
        $this->get(route('animations.show', $animation))->assertSee(route('reports.store', ['animation', $animation->id]), false);
        $this->post(route('reports.store', ['animation', $animation->id]), ['reason' => 'droits', 'message' => 'Vidéo copiée.'])
            ->assertSessionHas('report_sent');
        $this->assertDatabaseHas('reports', ['reportable_type' => 'animation', 'reportable_id' => $animation->id, 'reason' => 'droits']);
        $this->assertStringContainsString('« Boucle aimée » a été signalé', $admin->notifications()->first()->data['message']);

        // L'auteur n'a pas de bouton « Signaler » ni « favori » sur sa propre animation.
        $this->flushSession();
        $this->actingAs($author)->get(route('animations.show', $animation))
            ->assertDontSee('Ajouter aux favoris')
            ->assertDontSee(route('reports.store', ['animation', $animation->id]), false);
    }

    // ---------- Vues ----------

    public function test_a_view_is_counted_once_per_visitor_per_day(): void
    {
        $author = $this->makeArtist();
        $animation = $this->makeAnimation($author);

        $this->postJson(route('animations.view', $animation))->assertOk()->assertJson(['view' => true]);
        $this->postJson(route('animations.view', $animation))->assertJson(['view' => false]);
        $this->assertSame(1, $animation->fresh()->view_count);

        // L'auteur ne compte pas.
        $this->actingAs($author)->postJson(route('animations.view', $animation))->assertJson(['view' => false]);
        // Un membre compte une fois.
        $this->actingAs(User::factory()->create())->postJson(route('animations.view', $animation))->assertJson(['view' => true]);
        $this->assertSame(2, $animation->fresh()->view_count);

        // Le lendemain, le même visiteur compte à nouveau.
        auth()->logout();
        $this->travel(1)->days();
        $this->postJson(route('animations.view', $animation))->assertJson(['view' => true]);
        $this->assertSame(3, $animation->fresh()->view_count);
        $this->get(route('animations.show', $animation))->assertSee('<dt>Vues</dt><dd>3</dd>', false);

        // Protégé par le jeton CSRF (comme comics.progress) et limité en débit.
        $route = app('router')->getRoutes()->getByName('animations.view');
        $this->assertSame(['POST'], array_values(array_diff($route->methods(), ['HEAD'])));
        $this->assertContains('throttle:30,1', $route->gatherMiddleware());
        $this->assertContains('web', $route->gatherMiddleware());
    }

    // ---------- Intégrations ----------

    public function test_profile_home_search_and_collection_integrations(): void
    {
        $author = $this->makeArtist('Awa Traoré');
        $loop = $this->makeAnimation($author, ['titre' => 'Taxi de nuit', 'description' => 'Un woro-woro sous la pluie.']);
        $clip = $this->makeAnimation($author, ['titre' => 'Le marché parle', 'mode' => Animation::CLIP, 'published_at' => now()->subDays(2)]);

        // Profil illustrateur : « Ses animations ».
        $this->get(route('public.artist', $author->artist))
            ->assertOk()
            ->assertSee('Ses animations')
            ->assertSee('2 animations')
            ->assertSee('Taxi de nuit')
            ->assertSee('Le marché parle');

        // Accueil : « Le fil » avec le type ANIMATION, la boucle tourne quand elle est visible.
        $this->get(route('public.index'))
            ->assertOk()
            ->assertSee('ANIMATION · Boucle')
            ->assertSee('ANIMATION · Clip')
            ->assertSee(route('animations.show', $loop), false)
            ->assertSee('data-animation-card muted loop playsinline', false);

        // Recherche globale : catégorie « Animations ».
        $this->get(route('search', ['q' => 'pluie']))
            ->assertOk()
            ->assertSee('id="animations"', false)
            ->assertSee('Taxi de nuit')
            ->assertDontSee('Le marché parle');
        $this->get(route('search', ['q' => 'Awa']))->assertSee('Le marché parle')->assertSee('href="#animations"', false);

        // Ma collection : animations favorites.
        $fan = User::factory()->create();
        $clip->favorites()->create(['user_id' => $fan->id]);
        $this->actingAs($fan)->get(route('collection'))
            ->assertOk()
            ->assertSee('pc-fav-anim-title', false)
            ->assertSee('Le marché parle')
            ->assertSee(route('animations.favorite', $clip), false)
            ->assertDontSee('Taxi de nuit');
    }

    // ---------- Désactivation ----------

    public function test_everything_is_hidden_when_animations_are_disabled(): void
    {
        $author = $this->makeArtist();
        $animation = $this->makeAnimation($author, ['titre' => 'Boucle cachée']);
        $animation->favorites()->create(['user_id' => $author->id]);
        $fan = User::factory()->create();

        Settings::set('anim.active', false);

        $this->get(route('animations.show', $animation))->assertNotFound();
        $this->postJson(route('animations.view', $animation))->assertNotFound();
        $this->actingAs($fan)->post(route('animations.favorite', $animation))->assertNotFound();
        $this->actingAs($fan)->postJson(route('likes.toggle', ['animation', $animation->id]))->assertNotFound();
        auth()->logout();
        $this->post(route('reports.store', ['animation', $animation->id]), ['reason' => 'spam'])->assertNotFound();

        $this->get(route('public.illustrations'))->assertOk()->assertDontSee('Boucle cachée')->assertDontSee('name="format" value="anime"', false);
        $this->get(route('public.illustrations', ['format' => 'anime']))->assertOk()->assertDontSee('Boucle cachée');
        $this->get(route('public.artist', $author->artist))->assertDontSee('Ses animations');
        $this->get(route('public.index'))->assertDontSee('Boucle cachée');
        $this->get(route('search', ['q' => 'cachée']))->assertDontSee('Boucle cachée');
        $this->actingAs($author)->get(route('collection'))->assertOk()->assertDontSee('Boucle cachée');
        $this->assertSame(0, $animation->fresh()->view_count);
    }

    // ---------- Données ----------

    private function makeArtist(string $name = 'Kofi Dessin'): User
    {
        $user = User::factory()->create(['account_type' => 'artist', 'name' => $name]);
        Artist::create(['user_id' => $user->id]);

        return $user->fresh('artist');
    }

    private function makeAnimation(User $author, array $attributes = []): Animation
    {
        $key = str()->random(8);

        return Animation::create(array_merge([
            'user_id' => $author->id,
            'titre' => 'Animation '.$key,
            'mode' => Animation::LOOP,
            'source' => 'animations-sources/'.$key.'.mov',
            'mp4' => 'animations/'.$key.'/video-v1.mp4',
            'poster' => 'animations/'.$key.'/poster-v1.webp',
            'duree_s' => 6.5,
            'largeur' => 640,
            'hauteur' => 360,
            'statut_traitement' => Animation::READY,
            'published_at' => now()->subHour(),
            'moderation_status' => Animation::MODERATION_APPROVED,
        ], $attributes));
    }
}
