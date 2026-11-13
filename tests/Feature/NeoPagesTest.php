<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\ArtistPortfolio;
use App\Models\Illustration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Univers « Néo-Babi » : liste des illustrateurs, galerie, profil, fiche œuvre, portfolio immersif.
 */
class NeoPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_artists_page_uses_neo_world_with_search_and_featured_artist(): void
    {
        $artist = $this->makeArtist(['bio' => 'Encre et néons.'], 'Néon Kassi');
        $this->addIllustration($artist, 'Meca', 'couleur');
        $this->makeArtist([], 'Sans Oeuvre');

        $this->get(route('public.artists'))
            ->assertOk()
            ->assertSee('data-world="neo"', false)
            ->assertSee('aria-label="Ambiance"', false)
            ->assertSee('Chaque enseigne,')
            ->assertSee('<label for="nb-artist-search">Rechercher un illustrateur</label>', false)
            ->assertSee('Toutes les enseignes')
            ->assertSee('Artiste du jour')
            ->assertSee('Entrer dans la boutique')
            ->assertSee('★ DU JOUR')
            ->assertSee('alt="Meca par Néon Kassi"', false)
            ->assertSee('1 illustration')
            ->assertSee('Sans Oeuvre')
            ->assertDontSee('(s)');
    }

    public function test_artists_page_hides_featured_blocks_when_nobody_is_eligible(): void
    {
        $this->makeArtist([], 'Profil Vide');

        $this->get(route('public.artists'))
            ->assertOk()
            ->assertSee('Profil Vide')
            ->assertDontSee('Entrer dans la boutique')
            ->assertDontSee('★ DU JOUR');
    }

    public function test_gallery_filters_by_type_with_pressed_buttons(): void
    {
        $artist = $this->makeArtist();
        $this->addIllustration($artist, 'Esquisse Plateau', 'croquis');
        $this->addIllustration($artist, 'Affiche Cocody', 'couverture');

        $this->get(route('public.illustrations'))
            ->assertOk()
            ->assertSee('data-world="neo"', false)
            ->assertSee('2 illustrations publiées')
            ->assertSee('Esquisse Plateau')
            ->assertSee('Affiche Cocody');

        $response = $this->get(route('public.illustrations', ['type' => 'croquis']))
            ->assertOk()
            ->assertSee('Esquisse Plateau')
            ->assertDontSee('Affiche Cocody')
            ->assertSee('2 illustrations publiées');

        $this->assertMatchesRegularExpression('/value="croquis"\s+class="[^"]*"\s+aria-pressed="true"/', $response->getContent());
        $this->assertMatchesRegularExpression('/value=""\s+class="[^"]*"\s+aria-pressed="false"/', $response->getContent());
    }

    public function test_artist_profile_shows_led_studio_and_real_counters(): void
    {
        $artist = $this->makeArtist(['instagram' => '@studio.babi'], 'Kassi');
        $this->addIllustration($artist, 'Meca', 'croquis');
        $portfolio = ArtistPortfolio::create(['artist_id' => $artist->id, 'title' => 'Nuit à Babi']);

        $this->get(route('public.artist', $artist))
            ->assertOk()
            ->assertSee('data-world="neo"', false)
            ->assertSee('STUDIO KASSI · ABIDJAN ●')
            ->assertSee('STUDIO · ABIDJAN')
            ->assertSee('href="https://instagram.com/studio.babi"', false)
            ->assertSee('Entrer dans la galerie privée')
            ->assertSee(route('artist-portfolios.show', $portfolio), false)
            ->assertSee('Toujours de nuit')
            ->assertSee('En vitrine')
            ->assertSee('Artiste du jour')
            ->assertDontSee('Ajoute ta bio depuis ton espace');
    }

    public function test_artist_profile_hides_empty_bio_instagram_and_badge_for_visitors(): void
    {
        $featured = $this->makeArtist([], 'Vedette');
        $this->addIllustration($featured, 'Drop', 'couleur');
        $quiet = $this->makeArtist([], 'Discret');

        $this->get(route('public.artist', $quiet))
            ->assertOk()
            ->assertDontSee('Ajoute ta bio depuis ton espace')
            ->assertDontSee('Instagram <span', false)
            ->assertDontSee('Artiste du jour')
            ->assertDontSee('Entrer dans la galerie privée');

        $this->actingAs($quiet->user)
            ->get(route('public.artist', $quiet))
            ->assertOk()
            ->assertSee('Ajoute ta bio depuis ton espace');
    }

    public function test_logged_in_visitor_can_follow_and_favorite_from_profile(): void
    {
        $artist = $this->makeArtist();
        $illustration = $this->addIllustration($artist, 'Meca', 'couleur');
        $visitor = User::factory()->create(['account_type' => 'artist']);

        $this->actingAs($visitor)
            ->get(route('public.artist', $artist))
            ->assertOk()
            ->assertSee('action="'.route('artists.follow', $artist).'"', false)
            ->assertSee('action="'.route('illustrations.favorite', $illustration).'"', false)
            ->assertSee('Ajouter aux favoris');

        $visitor->followedArtists()->attach($artist->id);
        $visitor->favoriteIllustrations()->attach($illustration->id);

        $this->actingAs($visitor)
            ->get(route('public.artist', $artist))
            ->assertSee('Ne plus suivre')
            ->assertSee('Retirer des favoris')
            ->assertSee('name="_method" value="DELETE"', false);
    }

    public function test_illustration_page_has_lightbox_alt_and_hides_empty_blocks(): void
    {
        $artist = $this->makeArtist([], 'Kassi');
        $illustration = $this->addIllustration($artist, 'Meca', 'couleur');

        $this->get(route('public.illustration', $illustration))
            ->assertOk()
            ->assertSee('data-world="neo"', false)
            ->assertSee('<title>Meca – Kassi | '.e(config('site.name')).'</title>', false)
            ->assertSee('alt="Meca par Kassi"', false)
            ->assertSee('<dialog id="nb-lightbox"', false)
            ->assertSee('Fermer')
            ->assertSee($illustration->imageUrl(), false)
            ->assertSee('作品 #'.str_pad((string) $illustration->id, 2, '0', STR_PAD_LEFT))
            ->assertSee('Se connecter pour ajouter aux favoris')
            ->assertDontSee('Voir en immersif')
            ->assertDontSee('Dans la même rue')
            ->assertDontSee('Suggestions')
            ->assertDontSee('Pas encore assez');
    }

    public function test_illustration_page_links_to_immersive_portfolio_and_neighbours(): void
    {
        $artist = $this->makeArtist([], 'Kassi');
        $first = $this->addIllustration($artist, 'Première', 'croquis');
        $second = $this->addIllustration($artist, 'Seconde', 'couleur');
        $other = $this->addIllustration($this->makeArtist([], 'Autre'), 'Ailleurs', 'couverture');
        $portfolio = ArtistPortfolio::create(['artist_id' => $artist->id, 'title' => 'Visite']);
        $portfolio->items()->create(['illustration_id' => $second->id, 'position' => 0]);

        $this->get(route('public.illustration', $second))
            ->assertOk()
            ->assertSee('Voir en immersif')
            ->assertSee(route('artist-portfolios.show', $portfolio), false)
            ->assertSee(route('public.illustration', $first), false)
            ->assertSee('ŒUVRE PRÉCÉDENTE')
            ->assertSee('Dans la même rue')
            ->assertSee('Suggestions')
            ->assertSee('Ailleurs');

        $this->assertNotNull($other);
    }

    public function test_immersive_portfolio_is_locked_in_night_mode(): void
    {
        $artist = $this->makeArtist();
        $illustration = $this->addIllustration($artist, 'Meca', 'couleur');
        $portfolio = ArtistPortfolio::create(['artist_id' => $artist->id, 'title' => 'Visite']);
        $portfolio->items()->create(['illustration_id' => $illustration->id, 'position' => 0]);

        $this->get(route('artist-portfolios.show', $portfolio))
            ->assertOk()
            ->assertSee('data-world="neo" data-mode="nuit"', false)
            ->assertSee('data-mode-locked="true"', false)
            ->assertDontSee('aria-label="Ambiance"', false)
            ->assertSee('id="artist-immersive-root"', false)
            ->assertSee('favoritesCount')
            ->assertDontSee('247');
    }

    private function makeArtist(array $attributes = [], ?string $name = null): Artist
    {
        $user = User::factory()->create(array_filter(['account_type' => 'artist', 'name' => $name]));

        return Artist::create(['user_id' => $user->id] + $attributes);
    }

    private function addIllustration(Artist $artist, string $title, ?string $type = null): Illustration
    {
        return Illustration::create([
            'artist_id' => $artist->id,
            'title' => $title,
            'type' => $type,
            'image_path' => 'illustrations/'.str()->slug($title).'.jpg',
            'file_size_bytes' => 1,
        ]);
    }
}
