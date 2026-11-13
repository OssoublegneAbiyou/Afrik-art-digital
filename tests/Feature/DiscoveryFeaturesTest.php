<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Document;
use App\Models\Illustration;
use App\Models\User;
use App\Models\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function artist(string $name = 'Kassi'): Artist
    {
        $user = User::factory()->create(['name' => $name, 'account_type' => 'artist']);

        return Artist::create(['user_id' => $user->id]);
    }

    private function illustration(Artist $artist, string $title, ?string $type = 'couleur', ?string $createdAt = null): Illustration
    {
        $illustration = Illustration::withoutEvents(fn () => Illustration::create([
            'artist_id' => $artist->id, 'title' => $title, 'type' => $type, 'image_path' => 'illustrations/'.md5($title).'.jpg', 'file_size_bytes' => 1,
        ]));

        if ($createdAt) {
            $illustration->forceFill(['created_at' => $createdAt])->save();
        }

        return $illustration;
    }

    private function document(string $title, string $writerName = 'Awa Plume', ?string $description = null): Document
    {
        $user = User::factory()->create(['name' => $writerName, 'account_type' => 'writer']);
        $writer = Writer::create(['user_id' => $user->id]);

        return Document::withoutEvents(fn () => Document::create([
            'writer_id' => $writer->id, 'title' => $title, 'description' => $description, 'genre' => 'conte',
            'file_path' => 'f.txt', 'file_type' => 'txt', 'file_size_bytes' => 1, 'cover_image_path' => 'c.jpg', 'cover_image_size_bytes' => 1,
        ]));
    }

    public function test_likes_require_login_and_toggle_with_real_count(): void
    {
        $illustration = $this->illustration($this->artist(), 'Lagune');
        $document = $this->document('Le gbaka');
        $ama = User::factory()->create(['account_type' => 'visitor']);
        $koffi = User::factory()->create(['account_type' => 'visitor']);

        $this->post(route('likes.toggle', ['illustration', $illustration->id]))->assertRedirect(route('login'));

        $this->actingAs($ama)->postJson(route('likes.toggle', ['illustration', $illustration->id]))
            ->assertOk()->assertExactJson(['liked' => true, 'count' => 1]);
        $this->actingAs($koffi)->postJson(route('likes.toggle', ['illustration', $illustration->id]))
            ->assertExactJson(['liked' => true, 'count' => 2]);
        $this->actingAs($ama)->postJson(route('likes.toggle', ['illustration', $illustration->id]))
            ->assertExactJson(['liked' => false, 'count' => 1]);

        // Sans JavaScript : simple redirection.
        $this->actingAs($ama)->from(route('documents.read', $document))
            ->post(route('likes.toggle', ['document', $document->id]))
            ->assertRedirect(route('documents.read', $document));
        $this->assertSame(1, $document->likes()->count());

        $this->actingAs($koffi)->get(route('public.illustration', $illustration))
            ->assertOk()->assertSee('aria-pressed="true"', false)->assertSee('Tu aimes');
        $this->get(route('documents.read', $document))->assertOk()->assertSee('J’aime');

        $this->actingAs($ama)->postJson('/jaime/user/1')->assertNotFound();
        $this->actingAs($ama)->postJson(route('likes.toggle', ['illustration', 9999]))->assertNotFound();
    }

    public function test_gallery_is_paginated(): void
    {
        $artist = $this->artist();
        foreach (range(1, 30) as $i) {
            $this->illustration($artist, sprintf('Œuvre %02d', $i), 'couleur', now()->subMinutes(100 - $i)->toDateTimeString());
        }

        $this->get(route('public.illustrations'))
            ->assertOk()
            ->assertSee('30 illustrations')
            ->assertSee('page 1 sur 2')
            ->assertSee('Œuvre 30')
            ->assertDontSee('Œuvre 06')
            ->assertSee('rel="next"', false);

        $this->get(route('public.illustrations', ['page' => 2]))
            ->assertOk()
            ->assertSee('Œuvre 06')
            ->assertDontSee('Œuvre 30');
    }

    public function test_gallery_sorts_by_popularity_and_keeps_filters(): void
    {
        $artist = $this->artist();
        $old = $this->illustration($artist, 'Ancienne aimée', 'croquis', now()->subYear()->toDateTimeString());
        $this->illustration($artist, 'Toute neuve', 'croquis');
        $this->illustration($artist, 'Sans type', null);
        foreach (User::factory()->count(3)->create() as $user) {
            $old->toggleLikeFor($user);
        }

        $this->get(route('public.illustrations', ['type' => 'croquis']))
            ->assertSeeInOrder(['Toute neuve', 'Ancienne aimée']);

        $this->get(route('public.illustrations', ['type' => 'croquis', 'tri' => 'populaire']))
            ->assertSeeInOrder(['Ancienne aimée', 'Toute neuve'])
            ->assertSee('name="tri" value="populaire"', false)
            ->assertDontSee('Sans type');

        $this->get(route('public.illustrations', ['type' => 'autre']))
            ->assertSee('Sans type')
            ->assertDontSee('Toute neuve');
    }

    public function test_global_search_finds_works_texts_artists_and_writers(): void
    {
        $artist = $this->artist('Lagune Studio');
        $this->illustration($artist, 'Nuit sur la lagune');
        $this->illustration($this->artist('Yao'), 'Marché de Treichville');
        $this->document('Ombres', 'Awa Plume', 'Une enquête au bord de la lagune.');
        $this->document('Le baobab', 'Konan');

        $this->get(route('search', ['q' => 'lagune']))
            ->assertOk()
            ->assertSee('Nuit sur la lagune')
            ->assertSee('Ombres')
            ->assertSee('Lagune Studio')
            ->assertDontSee('Marché de Treichville')
            ->assertDontSee('Le baobab');

        $this->get(route('search', ['q' => 'Konan']))->assertSee('Le baobab')->assertSee('Écrivains');
        $this->get(route('search', ['q' => 'zzz']))->assertSee('Aucun résultat pour « zzz »');
        $this->get(route('search', ['q' => 'a']))->assertSee('Tape au moins deux caractères.');
        $this->get(route('search', ['q' => '%%']))->assertOk()->assertSee('Aucun résultat');

        $this->get(route('public.index'))->assertSee('href="'.route('search').'"', false);
    }
}
