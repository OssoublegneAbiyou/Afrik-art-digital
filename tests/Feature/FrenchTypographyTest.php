<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Illustration;
use App\Models\User;
use App\Support\Fr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrenchTypographyTest extends TestCase
{
    use RefreshDatabase;

    public function test_de_is_elided_before_a_vowel(): void
    {
        $this->assertSame('d’Awa Artist', Fr::de('Awa Artist'));
        $this->assertSame('d’Émile', Fr::de('Émile'));
        $this->assertSame('d’Yao', Fr::de('Yao'));
        $this->assertSame('de Kassi', Fr::de('Kassi'));
        $this->assertSame('de Hamed', Fr::de('Hamed'));
        $this->assertSame('d’', Fr::deArticle('Ama'));
        $this->assertSame('de', Fr::deArticle('Tchonté'));
    }

    public function test_counts_use_real_singular_and_plural(): void
    {
        $this->assertSame('0 illustration', Fr::count(0, 'illustration'));
        $this->assertSame('1 illustration', Fr::count(1, 'illustration'));
        $this->assertSame('16 illustrations', Fr::count(16, 'illustration'));
    }

    public function test_artwork_page_elides_artist_name(): void
    {
        $user = User::factory()->create(['name' => 'Awa Artist', 'account_type' => 'artist']);
        $artist = Artist::create(['user_id' => $user->id]);
        $illustration = Illustration::create([
            'artist_id' => $artist->id, 'title' => 'Lagune', 'type' => 'couleur',
            'image_path' => 'illustrations/x.jpg', 'file_size_bytes' => 1,
        ]);

        $this->get(route('public.illustration', $illustration))
            ->assertOk()
            ->assertSee('Une œuvre d’')
            ->assertDontSee('Une œuvre de');
    }

    public function test_no_parenthesised_plurals_or_missing_spaces_in_templates(): void
    {
        $files = array_merge(
            glob(resource_path('views/{,*/,*/*/}*.blade.php'), GLOB_BRACE),
            glob(resource_path('js/pages/*.jsx'))
        );

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/[a-zé]\(s\)/u', $content, basename($file).' contient un pluriel « (s) ».');
            $this->assertStringNotContainsString('Stockage:', $content, basename($file));
        }
    }
}
