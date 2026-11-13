<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\Artist;
use App\Models\Illustration;
use App\Models\JoinRequest;
use App\Models\User;
use App\Notifications\AccountInvitation;
use App\Notifications\JoinRequestAnswered;
use App\Notifications\NewJoinRequest;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminCommunityTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = User::ROLE_MEMBER, array $extra = []): User
    {
        return User::factory()->create(['role' => $role, 'account_type' => 'artist'] + $extra);
    }

    private function join(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ama Koné',
            'email' => 'ama@example.com',
            'discipline' => 'illustration',
            'portfolio_url' => 'https://instagram.com/ama',
            'message' => 'Je dessine Treichville la nuit.',
        ], $overrides);
    }

    // ---------- Rôles et accès ----------

    public function test_member_gets_styled_403_on_admin(): void
    {
        $this->actingAs($this->user())->get('/admin')
            ->assertForbidden()
            ->assertSee('Cette porte reste fermée.');
    }

    public function test_moderator_sees_dashboard_and_requests_but_not_members_or_log(): void
    {
        $moderator = $this->user(User::ROLE_MODERATOR);

        $this->actingAs($moderator)->get(route('admin.index'))
            ->assertOk()
            ->assertSee('À traiter')
            ->assertDontSee('Dernières modifications');
        $this->actingAs($moderator)->get(route('admin.join-requests.index'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.members.index'))->assertForbidden();
        $this->actingAs($moderator)->get(route('admin.logs.index'))->assertForbidden();
        $this->actingAs($moderator)->patch(route('admin.members.role', $moderator), ['role' => 'admin'])->assertForbidden();
        $this->assertSame(User::ROLE_MODERATOR, $moderator->fresh()->role);
    }

    public function test_admin_sees_every_community_screen(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        JoinRequest::create($this->join());

        $this->actingAs($admin)->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Nouvelles demandes d’adhésion')
            ->assertSee('Dernières modifications')
            ->assertSee(route('admin.join-requests.index'), false);
        $this->actingAs($admin)->get(route('admin.members.index'))->assertOk()->assertSee($admin->email);
        $this->actingAs($admin)->get(route('admin.join-requests.index'))->assertOk()->assertSee('Ama Koné');
        $this->actingAs($admin)->get(route('admin.logs.index'))->assertOk();
    }

    public function test_make_admin_command(): void
    {
        $user = $this->user();

        $this->artisan('collectif:make-admin', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->fresh()->isAdmin());
        $this->assertTrue((bool) $user->fresh()->is_admin);

        $this->artisan('collectif:make-admin', ['email' => 'inconnu@example.com'])->assertFailed();
    }

    // ---------- Membres ----------

    public function test_admin_can_search_accounts_and_see_works(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, ['name' => 'Admin']);
        $awa = $this->user(User::ROLE_MEMBER, ['name' => 'Awa Bamba']);
        $this->user(User::ROLE_MEMBER, ['name' => 'Kassi']);
        $artist = Artist::create(['user_id' => $awa->id]);
        Illustration::create([
            'artist_id' => $artist->id, 'title' => 'Héros', 'type' => 'couleur', 'image_path' => 'h.jpg', 'file_size_bytes' => 1,
            'published_at' => now(), 'moderation_status' => Illustration::MODERATION_PENDING,
        ]);

        $this->actingAs($admin)->get(route('admin.members.index', ['q' => 'awa']))
            ->assertOk()
            ->assertSee('Rechercher un compte')
            ->assertSee('Awa Bamba')
            ->assertDontSee('Kassi')
            ->assertSee('1 illustration')
            ->assertSee('1 à valider')
            ->assertSee(route('public.artist', $artist), false);

        $this->actingAs($admin)->get(route('admin.members.index', ['role' => 'admin']))
            ->assertSee('Admin')->assertDontSee('Awa Bamba');
    }

    public function test_admin_changes_role_and_it_is_logged(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $member = $this->user();

        $this->actingAs($admin)->patch(route('admin.members.role', $member), ['role' => 'moderateur'])
            ->assertSessionHas('status');
        $this->assertSame(User::ROLE_MODERATOR, $member->fresh()->role);

        $log = AdminLog::where('action', 'role.modifie')->sole();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(['role' => 'membre'], $log->avant);
        $this->assertSame(['role' => 'moderateur'], $log->apres);
    }

    public function test_last_active_admin_is_protected(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->patch(route('admin.members.role', $admin), ['role' => 'membre'])
            ->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->isAdmin());

        // Un second admin désactivé ne compte pas : le premier reste le dernier admin actif.
        $other = $this->user(User::ROLE_ADMIN, ['deactivated_at' => now()]);
        $this->actingAs($admin)->patch(route('admin.members.role', $admin), ['role' => 'moderateur'])->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->isAdmin());

        // Réactivé, il peut prendre le relais.
        $this->actingAs($admin)->post(route('admin.members.reactivate', $other))->assertSessionHas('status');
        $this->actingAs($admin)->patch(route('admin.members.role', $admin), ['role' => 'moderateur'])->assertSessionHas('status');
        $this->assertSame(User::ROLE_MODERATOR, $admin->fresh()->role);

        // $other est maintenant le dernier : ni rétrogradation, ni désactivation, ni suppression (par personne).
        $this->actingAs($other->fresh())->patch(route('admin.members.role', $other), ['role' => 'membre'])->assertSessionHas('error');
        $this->assertTrue($other->fresh()->isAdmin());
        $this->assertSame(0, AdminLog::where('action', 'compte.supprime')->count());
    }

    public function test_admin_cannot_deactivate_or_delete_last_admin(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $second = $this->user(User::ROLE_ADMIN);

        // Deux admins : on peut en désactiver un…
        $this->actingAs($admin)->post(route('admin.members.deactivate', $second))->assertSessionHas('status');
        $this->assertTrue($second->fresh()->isDeactivated());

        // … mais pas soi-même, ni supprimer son propre compte.
        $this->actingAs($admin)->post(route('admin.members.deactivate', $admin))->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('admin.members.destroy', $admin))->assertSessionHas('error');
        $this->assertModelExists($admin);
        $this->assertFalse($admin->fresh()->isDeactivated());
    }

    public function test_admin_creates_account_with_password_link_and_deletes_it(): void
    {
        Notification::fake();
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->post(route('admin.members.store'), [
            'name' => 'Koffi', 'email' => ' Koffi@Example.com', 'account_type' => 'writer', 'role' => 'moderateur',
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $koffi = User::where('email', 'koffi@example.com')->sole();
        $this->assertSame(User::ROLE_MODERATOR, $koffi->role);
        $this->assertNotNull($koffi->email_verified_at);
        $this->assertNotNull($koffi->writer);
        Notification::assertSentTo($koffi, AccountInvitation::class, function (AccountInvitation $notification) use ($koffi) {
            $mail = $notification->toMail($koffi);
            $html = view($mail->view[0], $mail->viewData)->render();

            return str_contains($html, route('password.reset', ['token' => $notification->token, 'email' => $koffi->email]))
                && ! str_contains(mb_strtolower($html), 'mot de passe :');
        });

        // Doublon d'adresse refusé avec un message clair.
        $this->actingAs($admin)->post(route('admin.members.store'), [
            'name' => 'Koffi 2', 'email' => 'koffi@example.com', 'account_type' => 'writer', 'role' => 'membre',
        ])->assertSessionHasErrorsIn('create', ['email']);

        $this->actingAs($admin)->delete(route('admin.members.destroy', $koffi))->assertRedirect(route('admin.members.index'));
        $this->assertModelMissing($koffi);
        $this->assertSame(['compte.cree', 'compte.supprime'], AdminLog::orderBy('id')->pluck('action')->all());
    }

    // ---------- Comptes désactivés ----------

    public function test_deactivated_account_cannot_log_in_and_open_session_is_closed(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $member = $this->user();

        $this->actingAs($admin)->post(route('admin.members.deactivate', $member))->assertSessionHas('status');
        $this->assertSame('compte.desactive', AdminLog::latest('id')->value('action'));

        // Session déjà ouverte : déconnectée à la requête suivante.
        $this->actingAs($member->fresh())->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Ce compte est désactivé. Contactez l’équipe du collectif.']);
        $this->assertGuest();

        // Nouvelle connexion refusée, même avec le bon mot de passe.
        $this->post(route('login'), ['email' => $member->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Ce compte est désactivé. Contactez l’équipe du collectif.']);
        $this->assertGuest();

        // Réactivé : la connexion fonctionne.
        $this->actingAs($admin)->post(route('admin.members.reactivate', $member));
        auth()->logout();
        $this->post(route('login'), ['email' => $member->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($member->fresh());
    }

    // ---------- Demandes d'adhésion : côté public ----------

    public function test_join_request_notifies_team_and_notification_email(): void
    {
        Notification::fake();
        $moderator = $this->user(User::ROLE_MODERATOR);
        $member = $this->user();
        Settings::set('adhesion.email_notification', 'equipe@collectif.test');
        Settings::set('adhesion.message_confirmation', 'Bien reçu, à très vite !');

        $this->post(route('join-requests.store'), $this->join())
            ->assertRedirect(route('public.about').'#rejoindre')
            ->assertSessionHas('join_success', 'Bien reçu, à très vite !');

        $request = JoinRequest::sole();
        $this->assertSame(JoinRequest::STATUS_NEW, $request->statut);
        $this->assertSame(1, JoinRequest::pendingCount());

        Notification::assertSentTo($moderator, NewJoinRequest::class, fn ($n, $channels) => $channels === ['database']);
        Notification::assertNotSentTo($member, NewJoinRequest::class);
        Notification::assertSentOnDemand(NewJoinRequest::class, function ($notification, $channels, AnonymousNotifiable $notifiable) {
            return $notifiable->routes['mail'] === 'equipe@collectif.test' && $channels === ['mail'];
        });
    }

    public function test_join_honeypot_pretends_success_without_saving(): void
    {
        Notification::fake();

        $this->post(route('join-requests.store'), $this->join(['site_web' => 'https://spam.test']))
            ->assertRedirect(route('public.about').'#rejoindre')
            ->assertSessionHas('join_success');

        $this->assertSame(0, JoinRequest::count());
        Notification::assertNothingSent();
    }

    public function test_join_is_refused_when_closed(): void
    {
        Settings::set('adhesion.ouverte', false);
        Settings::set('adhesion.message_ferme', 'Fermé jusqu’à la prochaine expo.');

        $this->post(route('join-requests.store'), $this->join())
            ->assertRedirect(route('public.about').'#rejoindre')
            ->assertSessionHasErrorsIn('join', ['adhesion' => 'Fermé jusqu’à la prochaine expo.'])
            ->assertSessionMissing('join_success');

        $this->assertSame(0, JoinRequest::count());
    }

    public function test_join_is_throttled_to_three_per_hour(): void
    {
        Notification::fake();

        foreach (range(1, 3) as $i) {
            $this->post(route('join-requests.store'), $this->join(['email' => "ama{$i}@example.com"]))->assertRedirect();
        }

        $this->post(route('join-requests.store'), $this->join(['email' => 'ama4@example.com']))->assertStatus(429);
        $this->assertSame(3, JoinRequest::count());
    }

    // ---------- Demandes d'adhésion : côté admin ----------

    public function test_accepting_creates_account_and_sends_password_link(): void
    {
        Notification::fake();
        $moderator = $this->user(User::ROLE_MODERATOR);
        $request = JoinRequest::create($this->join(['discipline' => 'ecriture']));

        $this->actingAs($moderator)->get(route('admin.join-requests.show', $request))
            ->assertOk()
            ->assertSee('<option value="writer" selected', false);

        $this->actingAs($moderator)->post(route('admin.join-requests.accept', $request), ['account_type' => 'writer'])
            ->assertRedirect(route('admin.join-requests.show', $request))
            ->assertSessionHas('status');

        $user = User::where('email', 'ama@example.com')->sole();
        $this->assertSame('writer', $user->account_type);
        $this->assertSame(User::ROLE_MEMBER, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->writer);

        $request->refresh();
        $this->assertSame(JoinRequest::STATUS_ACCEPTED, $request->statut);
        $this->assertSame($moderator->id, $request->traite_par);
        $this->assertSame($user->id, $request->user_id);
        $this->assertNotNull($request->traite_le);
        $this->assertSame(0, JoinRequest::pendingCount());

        Notification::assertSentTo($user, AccountInvitation::class, function (AccountInvitation $notification) use ($user) {
            $mail = $notification->toMail($user);
            $text = view($mail->view[1], $mail->viewData)->render();

            return $notification->fromJoinRequest
                && str_contains($text, '/reset-password/'.$notification->token)
                && str_contains($text, "Afrik'art Digital")
                && ! str_contains($text, 'password=');
        });

        $this->assertTrue(AdminLog::where('action', 'demande.acceptee')->where('user_id', $moderator->id)->exists());

        // Une seconde décision est refusée.
        $this->actingAs($moderator)->post(route('admin.join-requests.refuse', $request))->assertSessionHas('error');
        $this->assertSame(JoinRequest::STATUS_ACCEPTED, $request->fresh()->statut);
    }

    public function test_accepting_with_existing_email_links_without_recreating(): void
    {
        Notification::fake();
        $moderator = $this->user(User::ROLE_MODERATOR);
        $existing = User::factory()->create(['email' => 'ama@example.com', 'account_type' => 'visitor']);
        $request = JoinRequest::create($this->join());

        $this->actingAs($moderator)->post(route('admin.join-requests.accept', $request), ['account_type' => 'artist'])
            ->assertSessionHas('status');

        $this->assertSame(1, User::where('email', 'ama@example.com')->count());
        $this->assertSame($existing->id, $request->fresh()->user_id);
        $this->assertSame('artist', $existing->fresh()->account_type);
        Notification::assertNotSentTo($existing, AccountInvitation::class);
        Notification::assertSentOnDemand(JoinRequestAnswered::class, fn ($n) => $n->accepted);
    }

    public function test_refusing_sends_optional_message(): void
    {
        Notification::fake();
        $moderator = $this->user(User::ROLE_MODERATOR);
        $request = JoinRequest::create($this->join());

        $this->actingAs($moderator)->post(route('admin.join-requests.refuse', $request), [
            'motif_refus' => 'Reviens nous voir avec plus de planches !', 'prevenir' => '1',
        ])->assertSessionHas('status');

        $request->refresh();
        $this->assertSame(JoinRequest::STATUS_REFUSED, $request->statut);
        $this->assertSame('Reviens nous voir avec plus de planches !', $request->motif_refus);
        $this->assertFalse(User::where('email', 'ama@example.com')->exists());

        Notification::assertSentOnDemand(JoinRequestAnswered::class, function (JoinRequestAnswered $notification, $channels, $notifiable) {
            $mail = $notification->toMail($notifiable);
            $text = view($mail->view[1], $mail->viewData)->render();

            return $notifiable->routes['mail'] === 'ama@example.com'
                && ! $notification->accepted
                && str_contains($text, 'Reviens nous voir avec plus de planches !');
        });

        // Refus silencieux : aucun e-mail.
        $silent = JoinRequest::create($this->join(['email' => 'b@example.com']));
        $this->actingAs($moderator)->post(route('admin.join-requests.refuse', $silent), ['prevenir' => '0']);
        $this->assertSame(JoinRequest::STATUS_REFUSED, $silent->fresh()->statut);
        Notification::assertSentOnDemandTimes(JoinRequestAnswered::class, 1);

        $this->assertSame(2, AdminLog::where('action', 'demande.refusee')->count());
    }

    public function test_internal_note_and_status_filter(): void
    {
        $moderator = $this->user(User::ROLE_MODERATOR);
        $new = JoinRequest::create($this->join(['name' => 'Nouvelle Personne']));
        JoinRequest::create($this->join(['name' => 'Déjà Refusée', 'email' => 'r@example.com', 'statut' => JoinRequest::STATUS_REFUSED]));

        $this->actingAs($moderator)->get(route('admin.join-requests.index'))
            ->assertSee('Nouvelle Personne')->assertDontSee('Déjà Refusée');
        $this->actingAs($moderator)->get(route('admin.join-requests.index', ['statut' => 'refusee']))
            ->assertSee('Déjà Refusée')->assertDontSee('Nouvelle Personne');

        $this->actingAs($moderator)->patch(route('admin.join-requests.note', $new), ['note_interne' => 'Portfolio très solide.'])
            ->assertSessionHas('status');
        $this->assertSame('Portfolio très solide.', $new->fresh()->note_interne);
        $this->actingAs($moderator)->get(route('admin.join-requests.show', $new))->assertSee('Portfolio très solide.');
    }

    // ---------- Journal ----------

    public function test_log_is_filled_and_readable(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, ['name' => 'Fatou']);
        $member = $this->user(User::ROLE_MEMBER, ['name' => 'Yao']);

        $this->actingAs($admin)->patch(route('admin.members.role', $member), ['role' => 'moderateur']);
        $this->actingAs($admin)->post(route('admin.members.deactivate', $member));
        \App\Support\AdminLogger::log('reglage.modifie', 'setting', 'decor.bandeau_nuit', ['valeur' => ['A', 'B']], ['valeur' => str_repeat('x', 400)]);

        $this->actingAs($admin)->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('Rôle modifié')
            ->assertSee('Compte désactivé')
            ->assertSee('Réglage decor.bandeau_nuit')
            ->assertSee('membre')
            ->assertSee('moderateur')
            ->assertSee('Fatou')
            ->assertSee('"A"')
            ->assertDontSee(str_repeat('x', 400));

        $this->actingAs($admin)->get(route('admin.logs.index', ['action' => 'role.modifie']))
            ->assertSee('<strong>Rôle modifié</strong>', false)->assertDontSee('<strong>Compte désactivé</strong>', false);

        $this->actingAs($admin)->get(route('admin.logs.index', ['du' => now()->addDay()->toDateString()]))
            ->assertSee('Aucune entrée ne correspond.');

        // Le tableau de bord montre les dernières entrées aux admins.
        $this->actingAs($admin)->get(route('admin.index'))->assertSee('Compte désactivé');
    }
}
