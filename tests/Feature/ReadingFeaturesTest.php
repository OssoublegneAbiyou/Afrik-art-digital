<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\ReadingProgress;
use App\Models\User;
use App\Models\Writer;
use App\Support\ReaderText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ReadingFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private const STORY = "# Chapitre 1 — La lagune\n\nLa pluie tombait sur Adjamé.\n\n* * *\n\nKoffi remonta le col.\n\nChapitre 2\n\nLe gbaka de minuit passa.\n\nPartie de pêche au lever du jour.";

    protected function setUp(): void
    {
        parent::setUp();

        // Ces tests portent sur d'autres fonctions : la validation avant publication est testée dans ModerationTest.
        \App\Models\PlatformSetting::updateModeration(false);

        Storage::fake('public');
        Storage::fake('local');
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

    public function test_text_is_split_into_chapters_paragraphs_and_pauses(): void
    {
        $parsed = ReaderText::parse(self::STORY);

        $this->assertCount(2, $parsed['chapters']);
        $this->assertSame('Chapitre 1 — La lagune', $parsed['chapters'][0]['title']);
        $this->assertSame(['p', 'sep', 'p'], array_column($parsed['chapters'][0]['blocks'], 'type'));
        $this->assertSame('Chapitre 2', $parsed['chapters'][1]['title']);
        // Une phrase terminée par un point n'est pas un titre de chapitre.
        $this->assertSame('Partie de pêche au lever du jour.', $parsed['chapters'][1]['blocks'][1]['lines'][0]);
        // Seuls les mots des paragraphes comptent (pas les « # », « * * * » ni les titres).
        $this->assertSame(21, $parsed['words']);

        $plain = ReaderText::parse("Un seul paragraphe.\nSur deux lignes.");
        $this->assertCount(1, $plain['chapters']);
        $this->assertNull($plain['chapters'][0]['title']);
        $this->assertSame(['Un seul paragraphe.', 'Sur deux lignes.'], $plain['chapters'][0]['blocks'][0]['lines']);
    }

    public function test_docx_headings_become_chapters(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('Extension zip non disponible.');
        }

        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<w:document><w:body>'
            .'<w:p><w:pPr><w:pStyle w:val="Titre1"/></w:pPr><w:r><w:t>Prologue</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t xml:space="preserve">Il était </w:t></w:r><w:r><w:t>une fois &amp; ailleurs.</w:t></w:r></w:p>'
            .'</w:body></w:document>');
        $zip->close();

        $this->assertSame("# Prologue\n\nIl était une fois & ailleurs.", ReaderText::fromDocx($path));
        unlink($path);
    }

    public function test_writer_publishes_from_the_editor_without_a_file(): void
    {
        [$user] = $this->writer();
        $visitor = User::factory()->create(['account_type' => 'visitor']);

        $this->actingAs($visitor)->get(route('documents.create'))->assertForbidden();
        $this->actingAs($user)->get(route('documents.create'))->assertOk()->assertSee('Nouveau chapitre')->assertSee('Autoriser le téléchargement');

        $this->actingAs($user)->post(route('documents.store'), [
            'rights' => 1,
            'title' => 'Sans rien', 'genre' => 'conte', 'from_editor' => 1, 'cover_image' => UploadedFile::fake()->create('c.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasErrors('content');

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'rights' => 1,
            'title' => 'Le gbaka de minuit', 'genre' => 'thriller', 'content' => self::STORY, 'from_editor' => 1,
            'allow_download' => '0', 'cover_image' => UploadedFile::fake()->create('c.jpg', 10, 'image/jpeg'),
        ]);

        $document = Document::where('title', 'Le gbaka de minuit')->sole();
        $response->assertRedirect(route('documents.read', $document));
        $this->assertNull($document->file_path);
        $this->assertFalse($document->allow_download);

        // Vu par une lectrice (l'autrice, elle, peut toujours télécharger son œuvre).
        $this->actingAs($visitor)->get(route('documents.read', $document))
            ->assertOk()
            ->assertSee('Sommaire')
            ->assertSee('id="chapitre-2"', false)
            ->assertSee('class="sg-dropcap"', false)
            ->assertSee('2 chapitres')
            ->assertSee('Lecture en ligne uniquement')
            ->assertDontSee(route('documents.download', $document), false);
    }

    public function test_only_the_author_edits_and_text_takes_priority_over_pdf(): void
    {
        [$user, $writer] = $this->writer();
        [$other] = $this->writer();
        Storage::disk('local')->put('documents/files/livre.pdf', '%PDF-1.4');
        $document = $this->document($writer, ['content' => null, 'file_path' => 'documents/files/livre.pdf', 'file_type' => 'pdf', 'file_size_bytes' => 8]);

        $this->get(route('documents.read', $document))->assertSee('<iframe', false)->assertSee('publié en PDF');
        $this->get(route('documents.file', $document))->assertOk()->assertHeader('content-disposition');

        $this->actingAs($other)->get(route('documents.edit', $document))->assertForbidden();
        $this->actingAs($other)->put(route('documents.save', $document), ['title' => 'X', 'genre' => 'conte'])->assertForbidden();

        $this->actingAs($user)->get(route('documents.edit', $document))->assertOk()->assertSee('publié en PDF');
        $this->actingAs($user)->put(route('documents.save', $document), [
            'rights' => 1,
            'title' => 'Ombres sur la lagune', 'genre' => 'thriller', 'content' => self::STORY, 'allow_download' => '1',
        ])->assertRedirect(route('documents.edit', $document));

        $this->get(route('documents.read', $document))
            ->assertDontSee('<iframe', false)
            ->assertSee('La pluie tombait sur Adjamé.')
            ->assertSee(route('documents.download', $document), false);
    }

    public function test_downloads_are_controlled_and_counted(): void
    {
        [$author, $writer] = $this->writer();
        $reader = User::factory()->create(['account_type' => 'visitor']);
        Storage::disk('local')->put('documents/files/livre.pdf', '%PDF-1.4');
        $withFile = $this->document($writer, ['file_path' => 'documents/files/livre.pdf', 'file_type' => 'pdf', 'file_size_bytes' => 8]);
        $textOnly = $this->document($writer, ['title' => 'Texte seul']);

        $this->get(route('documents.download', $withFile))->assertOk()->assertDownload('ombres-sur-la-lagune.pdf');
        $this->actingAs($reader)->get(route('documents.download', $withFile))->assertOk();
        $this->actingAs($author)->get(route('documents.download', $withFile))->assertOk();
        $this->assertSame(2, $withFile->fresh()->download_count);

        $response = $this->get(route('documents.download', $textOnly));
        $response->assertOk()->assertDownload('texte-seul.txt');
        $this->assertStringContainsString('La pluie tombait sur Adjamé.', $response->streamedContent());

        $withFile->update(['allow_download' => false]);
        $this->actingAs($reader)->get(route('documents.download', $withFile))->assertForbidden();
        $this->actingAs($author)->get(route('documents.download', $withFile))->assertOk();
        $this->assertSame(2, $withFile->fresh()->download_count);
    }

    public function test_new_files_are_stored_privately(): void
    {
        [$user] = $this->writer();

        $this->actingAs($user)->post(route('documents.store'), [
            'rights' => 1,
            'title' => 'Manuscrit', 'genre' => 'poesie',
            'document' => UploadedFile::fake()->create('m.pdf', 20, 'application/pdf'),
            'cover_image' => UploadedFile::fake()->create('c.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        $document = Document::where('title', 'Manuscrit')->sole();
        $this->assertSame('local', $document->file_disk);
        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
        $this->assertTrue($document->allow_download);
    }

    public function test_existing_public_files_can_be_secured(): void
    {
        [, $writer] = $this->writer();
        Storage::disk('public')->put('documents/files/ancien.pdf', '%PDF-1.4');
        $document = $this->document($writer, ['file_path' => 'documents/files/ancien.pdf', 'file_disk' => 'public', 'file_type' => 'pdf']);

        $this->artisan('documents:secure')->assertSuccessful();

        $this->assertSame('local', $document->fresh()->file_disk);
        Storage::disk('local')->assertExists('documents/files/ancien.pdf');
        Storage::disk('public')->assertMissing('documents/files/ancien.pdf');
    }

    public function test_reading_progress_is_saved_and_offered_back(): void
    {
        [, $writer] = $this->writer();
        $document = $this->document($writer);
        $reader = User::factory()->create(['account_type' => 'visitor']);

        $this->postJson(route('documents.progress', $document), ['progress' => 0.4])->assertUnauthorized();

        $this->actingAs($reader)->postJson(route('documents.progress', $document), ['progress' => 1.5])->assertUnprocessable();
        $this->actingAs($reader)->postJson(route('documents.progress', $document), ['progress' => 0.42, 'chapter' => 2])->assertOk();
        $this->actingAs($reader)->postJson(route('documents.progress', $document), ['progress' => 0.45, 'chapter' => 2])->assertOk();

        $this->assertSame(1, ReadingProgress::count());
        $this->assertSame(0.45, ReadingProgress::sole()->progress);

        $this->actingAs($reader)->get(route('documents.read', $document))
            ->assertOk()
            ->assertSee('Ta lecture s\'était arrêtée', false)
            ->assertSee(\Illuminate\Support\Js::from([
                'id' => $document->id,
                'progressUrl' => route('documents.progress', $document),
                'saved' => ['progress' => 0.45, 'chapter' => 2],
            ])->toHtml(), false);

        $reader->bookmarkedDocuments()->attach($document);
        $this->actingAs($reader)->get(route('collection'))->assertSee('Lu à 45 %')->assertSee('Reprendre la lecture');
    }
}
