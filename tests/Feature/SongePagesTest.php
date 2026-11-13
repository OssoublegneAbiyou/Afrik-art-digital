<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Models\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SongePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_writers_page_shows_the_genre_sky_and_real_counts(): void
    {
        $writer = $this->makeWriter(['bio' => 'Je raconte la lagune la nuit.'], 'Plume Active');
        $this->addDocument($writer, ['title' => 'Ombres', 'genre' => 'thriller']);
        $this->addDocument($writer, ['title' => 'Indice', 'genre' => 'thriller']);
        $this->addDocument($writer, ['title' => 'Sans ciel', 'genre' => null]);

        $this->get(route('public.writers'))
            ->assertOk()
            ->assertSee('data-world="songe"', false)
            ->assertSee('Chaque genre est')
            ->assertSee('devient une constellation.')
            ->assertSee('aria-pressed', false)
            ->assertSeeInOrder(['Plume du jour', 'Plume Active', 'Je raconte la lagune la nuit.', 'Visiter son univers'])
            ->assertSee('Les royaumes')
            ->assertSee('2 œuvres')
            ->assertSee('À ÉCRIRE')
            ->assertSee('Une place libre dans le ciel.')
            ->assertSee(route('public.about').'#rejoindre', false)
            ->assertSee('Ce ciel attend sa première histoire.')
            ->assertSee('Ombres')
            ->assertSee('Sans ciel')
            ->assertDontSee('Suivre cette plume');
    }

    public function test_featured_writer_excludes_admins_and_empty_profiles_and_never_invents_a_quote(): void
    {
        $admin = User::factory()->create(['name' => 'AdminAbiyou', 'account_type' => 'writer', 'is_admin' => true]);
        $this->addDocument(Writer::create(['user_id' => $admin->id]));
        $this->makeWriter([], 'Plume Vide');

        $this->get(route('public.writers'))->assertOk()->assertDontSee('Plume du jour');

        $this->addDocument($this->makeWriter([], 'Plume Muette'));

        $this->get(route('public.writers'))
            ->assertOk()
            ->assertSeeInOrder(['Plume du jour', 'Plume Muette'])
            ->assertDontSee('sg-featured__quote', false);
    }

    public function test_writer_profile_uses_main_genre_sky_and_keeps_features(): void
    {
        $writer = $this->makeWriter(['instagram' => '@lahlougo', 'bio' => 'Les ruelles parlent.']);
        $this->addDocument($writer, ['title' => 'Ombres', 'genre' => 'thriller', 'description' => 'Une enquête.']);

        $this->get(route('public.writer', $writer))
            ->assertOk()
            ->assertSee('data-world="songe"', false)
            ->assertSee('w-header--overlay', false)
            ->assertSee('Ciel : Thriller')
            ->assertSee('sg-eclipse', false)
            ->assertSee('« Les ruelles parlent. »', false)
            ->assertSee('href="https://instagram.com/lahlougo"', false)
            ->assertSee('Sa')
            ->assertSee('bibliothèque')
            ->assertSee('Commencer la lecture')
            ->assertDontSee('Suivre cette plume')
            ->assertDontSee('Ajouter un marque-page');

        $this->actingAs(User::factory()->create())
            ->get(route('public.writer', $writer))
            ->assertSee('Ajouter un marque-page')
            ->assertSee(route('documents.bookmark', Document::first()), false);
    }

    public function test_empty_bio_hint_is_only_shown_to_the_owner(): void
    {
        $writer = $this->makeWriter();

        $this->get(route('public.writer', $writer))->assertOk()->assertDontSee('Ajoute ta bio');
        $this->actingAs($writer->user)->get(route('public.writer', $writer))->assertOk()->assertSee('Ajoute ta bio');
    }

    public function test_reader_renders_text_with_dropcap_separators_and_next_work(): void
    {
        $writer = $this->makeWriter([], 'Awa Plume');
        Storage::disk('public')->put('documents/files/story.txt', "Premier paragraphe.\nVers suivant.\n\n* * *\n\nDeuxième partie.\n\n✦ ✦ ✦\n\nTroisième <b>partie</b>.");
        $document = $this->addDocument($writer, ['title' => 'Ombres', 'genre' => 'thriller']);
        $next = $this->addDocument($writer, ['title' => 'La suite', 'file_path' => 'documents/files/next.pdf', 'file_type' => 'pdf']);

        $response = $this->get(route('documents.read', $document))
            ->assertOk()
            ->assertSee('data-world="songe"', false)
            ->assertSee('THRILLER')
            ->assertDontSee('PARTIE')
            ->assertSee('environ 1 min de lecture')
            ->assertSee('class="sg-dropcap"', false)
            ->assertSee('Premier paragraphe.<br>Vers suivant.', false)
            ->assertSee('&lt;b&gt;partie&lt;/b&gt;', false)
            ->assertDontSee('* * *')
            ->assertDontSee('✦ ✦ ✦')
            ->assertSee('aad-reader-ambiance')
            ->assertSee('aad-reader-size')
            ->assertSee('Crépuscule')
            ->assertSee('Retour à la bibliothèque')
            ->assertSee('Œuvre suivante')
            ->assertSee(route('documents.read', $next), false)
            ->assertSee('Se connecter pour marquer')
            ->assertSee('Télécharger');

        $this->assertSame(2, substr_count($response->getContent(), 'role="separator"'));

        $this->actingAs(User::factory()->create())
            ->get(route('documents.read', $document))
            ->assertSee('Ajouter un marque-page');
    }

    public function test_reader_keeps_pdf_and_download_fallbacks(): void
    {
        $writer = $this->makeWriter();
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('documents/files/a.pdf', '%PDF-1.4');
        \Illuminate\Support\Facades\Storage::disk('public')->put('documents/files/a.doc', 'doc');
        $pdf = $this->addDocument($writer, ['file_path' => 'documents/files/a.pdf', 'file_type' => 'pdf']);
        $doc = $this->addDocument($writer, ['file_path' => 'documents/files/a.doc', 'file_type' => 'doc']);

        $this->get(route('documents.read', $pdf))->assertOk()->assertSee('<iframe', false)
            ->assertSee(route('documents.file', $pdf), false)
            ->assertDontSee('storage/documents/files', false);
        $this->get(route('documents.read', $doc))->assertOk()->assertSee('Lecture intégrée indisponible')->assertSee('Télécharger le document');
    }

    public function test_literary_works_page_is_in_the_songe_world(): void
    {
        $this->addDocument($this->makeWriter(), ['title' => 'Conte du soir', 'genre' => 'conte']);

        $this->get(route('public.literary-works'))
            ->assertOk()
            ->assertSee('data-world="songe"', false)
            ->assertSee('Toutes les œuvres')
            ->assertSee('Conte du soir')
            ->assertSee('1 œuvre');
    }

    private function makeWriter(array $attributes = [], ?string $name = null): Writer
    {
        $user = User::factory()->create(array_filter(['account_type' => 'writer', 'name' => $name]));

        return Writer::create(['user_id' => $user->id] + $attributes);
    }

    private function addDocument(Writer $writer, array $attributes = []): Document
    {
        return Document::create($attributes + [
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
