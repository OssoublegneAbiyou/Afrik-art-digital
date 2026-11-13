<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Document;
use App\Models\FeaturedSelection;
use App\Models\Illustration;
use App\Models\PlatformSetting;
use App\Models\Report;
use App\Models\User;
use App\Models\Writer;
use App\Notifications\MemberActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->admin = User::factory()->create(['name' => 'Admin', 'is_admin' => true, 'account_type' => 'visitor']);
    }

    private function artist(string $name = 'Kassi'): array
    {
        $user = User::factory()->create(['name' => $name, 'account_type' => 'artist']);

        return [$user, Artist::create(['user_id' => $user->id])];
    }

    private function upload(User $user, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->post(route('illustrations.store'), $extra + [
            'title' => 'Héros masqué', 'type' => 'couleur', 'rights' => 1,
            'images' => [UploadedFile::fake()->create('heros.jpg', 20, 'image/jpeg')],
        ]);
    }

    public function test_upload_requires_rights_certification(): void
    {
        [$user] = $this->artist();

        $this->upload($user, ['rights' => null])->assertSessionHasErrors('rights');
        $this->assertSame(0, Illustration::withUnpublished()->count());

        $this->actingAs($user)->get(route('dashboard'))->assertSee('Je certifie être l\'auteur', false);
    }

    public function test_member_work_waits_for_approval_then_goes_live(): void
    {
        [$user] = $this->artist();

        $this->upload($user)->assertSessionHasNoErrors()->assertSessionHas('success', fn ($m) => str_contains($m, 'après validation'));
        $work = Illustration::withUnpublished()->sole();

        $this->assertTrue($work->isPendingReview());
        $this->assertNotNull($work->rights_certified_at);
        auth()->logout();
        $this->get(route('public.illustrations'))->assertDontSee('Héros masqué');
        $this->get(route('public.illustration', $work))->assertNotFound();
        $this->actingAs($user)->get(route('public.illustration', $work))->assertOk()->assertSee('En attente de validation');

        $this->assertSame(MemberActivity::TO_REVIEW, $this->admin->fresh()->notifications->first()->data['kind']);
        $this->actingAs($this->admin)->get(route('admin.moderation.index'))
            ->assertOk()->assertSee('1 œuvre en attente')->assertSee('Héros masqué')->assertSee('Droits certifiés par l’auteur');

        $this->actingAs($user)->post(route('admin.moderation.approve', ['illustration', $work->id]))->assertForbidden();
        $this->actingAs($this->admin)->post(route('admin.moderation.approve', ['illustration', $work->id]))->assertRedirect();

        auth()->logout();
        $this->get(route('public.illustrations'))->assertSee('Héros masqué');
        $this->assertSame(MemberActivity::APPROVED, $user->fresh()->notifications->first()->data['kind']);
    }

    public function test_rejected_work_shows_reason_and_can_be_resubmitted(): void
    {
        [$user] = $this->artist();
        $this->upload($user);
        $work = Illustration::withUnpublished()->sole();

        $this->actingAs($this->admin)->post(route('admin.moderation.reject', ['illustration', $work->id]), [])->assertSessionHasErrors('note');
        $this->actingAs($this->admin)->post(route('admin.moderation.reject', ['illustration', $work->id]), [
            'note' => 'L’image reprend des personnages protégés (DC Comics).',
        ])->assertRedirect();

        $work->refresh();
        $this->assertTrue($work->isRejected());
        $notification = $user->fresh()->notifications->first();
        $this->assertSame(MemberActivity::REJECTED, $notification->data['kind']);
        $this->assertStringContainsString('DC Comics', $notification->data['message']);
        $this->actingAs($user)->get(route('dashboard'))->assertSee('Refusée par l’équipe')->assertSee('Motif : L’image reprend');

        // L'auteur corrige : l'œuvre repart en validation.
        $this->actingAs($user)->patch(route('illustrations.update', $work), [
            'full' => 1, 'title' => 'Héroïne d’Abidjan', 'type' => 'couleur', 'status' => 'publie',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($work->fresh()->isPendingReview());
        $this->assertNull($work->fresh()->moderation_note);

        // Remplacer l'image exige une nouvelle certification.
        $this->actingAs($user)->patch(route('illustrations.update', $work), [
            'full' => 1, 'title' => 'Héroïne d’Abidjan', 'type' => 'couleur', 'status' => 'publie',
            'image' => UploadedFile::fake()->create('v2.jpg', 20, 'image/jpeg'),
        ])->assertSessionHasErrors('rights');
    }

    public function test_admins_publish_directly_and_setting_can_turn_moderation_off(): void
    {
        [$user, $artist] = $this->artist();
        $this->admin->update(['account_type' => 'artist']);
        Artist::create(['user_id' => $this->admin->id]);

        $this->upload($this->admin->fresh());
        $this->assertTrue(Illustration::withUnpublished()->sole()->isApproved());

        // Le réglage se modifie désormais dans l'écran Réglages du collectif.
        \App\Support\Settings::set('regles.validation_avant_publication', false);
        $this->assertFalse(PlatformSetting::moderationEnabled());
        $this->actingAs($this->admin)->get(route('admin.moderation.index'))->assertSee('La validation avant publication est désactivée');

        $this->upload($user, ['title' => 'Directe']);
        $this->assertTrue(Illustration::where('title', 'Directe')->exists());
    }

    public function test_writer_text_changes_go_back_to_review(): void
    {
        $user = User::factory()->create(['account_type' => 'writer']);
        $writer = Writer::create(['user_id' => $user->id]);
        $document = Document::create([
            'writer_id' => $writer->id, 'title' => 'Récit validé', 'genre' => 'conte', 'content' => 'Version 1.',
            'file_disk' => 'local', 'file_size_bytes' => 0, 'cover_image_path' => 'c.jpg', 'cover_image_size_bytes' => 1,
        ]);

        $this->actingAs($user)->put(route('documents.save', $document), [
            'title' => 'Récit validé', 'genre' => 'conte', 'content' => 'Version 1.', 'rights' => 1, 'status' => 'publie',
        ]);
        $this->assertTrue($document->fresh()->isApproved());

        $this->actingAs($user)->put(route('documents.save', $document), [
            'title' => 'Récit validé', 'genre' => 'conte', 'content' => 'Version 2, avec un ajout.', 'rights' => 1, 'status' => 'publie',
        ]);
        $this->assertTrue($document->fresh()->isPendingReview());
        auth()->logout();
        $this->get(route('documents.read', $document))->assertNotFound();
    }

    public function test_reports_are_sent_deduplicated_and_resolved(): void
    {
        [$owner, $artist] = $this->artist();
        $work = Illustration::create([
            'artist_id' => $artist->id, 'title' => 'Carte de vœux', 'type' => 'couverture',
            'image_path' => 'illustrations/x.jpg', 'file_size_bytes' => 1,
        ]);
        $reader = User::factory()->create(['account_type' => 'visitor']);

        $this->get(route('public.illustration', $work))->assertSee('Signaler')->assertSee('Droits d’auteur');
        $this->actingAs($owner)->get(route('public.illustration', $work))->assertDontSee('Pourquoi signaler');

        auth()->logout();
        $this->post(route('reports.store', ['illustration', $work->id]), ['reason' => 'autre'])->assertSessionHasErrorsIn('report', 'message');
        $this->post(route('reports.store', ['illustration', $work->id]), ['reason' => 'droits', 'message' => 'Visuel NSIA.', 'email' => 'temoin@example.com'])
            ->assertSessionHas('report_sent');
        $this->actingAs($reader)->post(route('reports.store', ['illustration', $work->id]), ['reason' => 'droits']);
        $this->actingAs($reader)->post(route('reports.store', ['illustration', $work->id]), ['reason' => 'droits']);

        $this->assertSame(2, Report::count());
        $this->assertSame(MemberActivity::REPORTED, $this->admin->fresh()->notifications->first()->data['kind']);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()->assertSee('1 œuvre signalée')->assertSee('Visuel NSIA.')->assertSee('temoin@example.com')->assertSee('2 signalements');

        $this->actingAs($this->admin)->patch(route('admin.reports.resolve', Report::first()), ['action' => 'remove', 'note' => 'Reprend un visuel de marque.']);

        $this->assertTrue($work->fresh()->isRejected());
        $this->assertSame(2, Report::where('status', Report::ACTIONED)->count());
        auth()->logout();
        $this->get(route('public.illustration', $work))->assertNotFound();
        $this->assertStringContainsString('visuel de marque', $owner->fresh()->notifications->first()->data['message']);

        // Une œuvre non visible ne peut pas être signalée.
        $this->post(route('reports.store', ['illustration', $work->id]), ['reason' => 'droits'])->assertNotFound();
    }

    public function test_report_can_be_dismissed(): void
    {
        [, $artist] = $this->artist();
        $work = Illustration::create(['artist_id' => $artist->id, 'title' => 'Lagune', 'type' => 'couleur', 'image_path' => 'i.jpg', 'file_size_bytes' => 1]);
        $this->post(route('reports.store', ['illustration', $work->id]), ['reason' => 'spam']);

        $this->actingAs($this->admin)->patch(route('admin.reports.resolve', Report::first()), ['action' => 'dismiss']);

        $this->assertSame(Report::DISMISSED, Report::first()->status);
        $this->assertTrue($work->fresh()->isVisible());
    }

    public function test_featured_profiles_can_be_planned_ahead(): void
    {
        [$user, $artist] = $this->artist();
        Illustration::create(['artist_id' => $artist->id, 'title' => 'Lagune', 'type' => 'couleur', 'image_path' => 'i.jpg', 'file_size_bytes' => 1]);
        [$other, $otherArtist] = $this->artist('Yao');
        Illustration::create(['artist_id' => $otherArtist->id, 'title' => 'Marché', 'type' => 'couleur', 'image_path' => 'm.jpg', 'file_size_bytes' => 1]);
        $day = today()->addDays(3);

        $this->actingAs($this->admin)->post(route('admin.featured.schedule'), ['date' => today()->addDays(61)->toDateString(), 'artist_id' => $artist->id])
            ->assertSessionHasErrors('date');
        $this->actingAs($this->admin)->post(route('admin.featured.schedule'), ['date' => $day->toDateString(), 'artist_id' => $artist->id])
            ->assertSessionHas('status');

        $this->assertTrue(FeaturedSelection::whereDate('featured_for', $day)->exists());
        $this->assertSame(0, $user->fresh()->notifications()->where('data->kind', MemberActivity::FEATURED)->count());
        $this->actingAs($this->admin)->get(route('admin.featured.index'))->assertSee('Planning')->assertSee('Artiste : Kassi');

        // Le jour venu, la sélection manuelle s'applique et l'artiste est prévenu.
        $this->travelTo($day->copy()->setTime(9, 0));
        auth()->logout();
        $this->get(route('public.artists'))->assertSee('Kassi');
        $this->assertSame(1, $user->fresh()->notifications->where('data.kind', MemberActivity::FEATURED)->count());

        $this->actingAs($this->admin)->delete(route('admin.featured.destroy', FeaturedSelection::first()))->assertRedirect();
        $this->assertSame(0, FeaturedSelection::count());
    }
}
