<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Models\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_txt_document_can_be_read_on_site(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'account_type' => 'writer',
        ]);
        $writer = Writer::create([
            'user_id' => $user->id,
            'bio' => 'Auteur test',
        ]);

        Storage::disk('public')->put('documents/files/story.txt', "Chapitre 1\n\nLe texte est lisible directement.");
        Storage::disk('public')->put('documents/covers/story.jpg', 'fake-image');

        $document = Document::create([
            'writer_id' => $writer->id,
            'title' => 'Histoire test',
            'description' => 'Une histoire courte.',
            'file_path' => 'documents/files/story.txt',
            'file_type' => 'txt',
            'file_size_bytes' => 42,
            'cover_image_path' => 'documents/covers/story.jpg',
            'cover_image_size_bytes' => 10,
        ]);

        $response = $this->get(route('documents.read', $document));

        $response
            ->assertOk()
            ->assertSee('Histoire test')
            ->assertSee('Le texte est lisible directement.');
    }
}
