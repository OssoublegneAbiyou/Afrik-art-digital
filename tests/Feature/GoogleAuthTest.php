<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\AdminLog;
use App\Models\JoinRequest;
use App\Models\Page;
use App\Models\User;
use App\Notifications\JoinRequestAnswered;
use App\Notifications\MemberActivity;
use App\Support\GoogleAuth;
use App\Support\Settings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client-id.apps.googleusercontent.com',
            'services.google.client_secret' => 'test-secret-value',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    /**
     * Faux compte Google renvoyé par Socialite.
     */
    private function fakeGoogle(array $overrides = [], bool $verified = true): GoogleUser
    {
        $data = array_merge([
            'id' => 'google-123',
            'name' => 'Awa Koné',
            'email' => 'awa@gmail.com',
            'avatar' => 'https://lh3.googleusercontent.com/a/photo-awa',
        ], $overrides);

        $googleUser = (new GoogleUser)
            ->setRaw(['sub' => $data['id'], 'email' => $data['email'], 'email_verified' => $verified, 'name' => $data['name']])
            ->map($data);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        return $googleUser;
    }

    private function googleCallback(array $query = [])
    {
        return $this->get(route('auth.google.callback', $query + ['code' => 'abc', 'state' => 'xyz']));
    }

    // ---------- §9 du brief ----------

    public function test_new_email_creates_a_visitor_account_and_returns_to_the_original_page(): void
    {
        $this->fakeGoogle();

        // Invitation « Connecte-toi pour suivre » : /login?retour=… puis Google.
        $this->get(route('login', ['retour' => '/illustrateurs?page=2']))->assertOk()->assertSee('Continuer avec Google');

        $this->googleCallback()->assertRedirect(url('/illustrateurs?page=2'))
            ->assertSessionHas(GoogleAuth::WELCOME_SESSION_KEY, GoogleAuthController::MSG_WELCOME);

        $user = User::where('email', 'awa@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('visitor', $user->account_type);
        $this->assertTrue($user->account_type_selected);
        $this->assertSame(User::ROLE_MEMBER, $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertNull($user->password);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame('awa@gmail.com', $user->google_email);
        $this->assertSame('https://lh3.googleusercontent.com/a/photo-awa', $user->avatar_url);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('Awa Koné', $user->name);
        $this->assertNull($user->artist);
        $this->assertNull($user->writer);

        $log = AdminLog::where('action', 'compte.cree')->sole();
        $this->assertSame('google', $log->apres['via']);
        $this->assertSame((string) $user->id, $log->cible_id);

        // Pas d'étape « type de compte » : le tableau de bord visiteur s'ouvre directement, avec l'encart d'adhésion.
        $this->get(route('dashboard'))->assertOk()->assertSee('joinUrl', false);
        $this->get(route('account-type.edit'))->assertRedirect(route('dashboard'));
    }

    public function test_welcome_message_is_shown_once_on_the_return_page(): void
    {
        $this->fakeGoogle();

        $this->googleCallback()->assertRedirect(url('/'));
        $this->get(route('public.index'))->assertOk()->assertSee('Ton compte visiteur est prêt');
        $this->get(route('public.index'))->assertOk()->assertDontSee('Ton compte visiteur est prêt');
    }

    public function test_existing_illustrator_is_linked_without_changing_role_or_type(): void
    {
        $this->fakeGoogle(['email' => 'awa@gmail.com']);
        $member = User::factory()->create([
            'email' => 'Awa@Gmail.com', 'account_type' => 'artist', 'role' => User::ROLE_MODERATOR, 'email_verified_at' => null,
        ]);
        $member->artist()->create([]);

        $this->googleCallback()->assertRedirect();

        $this->assertAuthenticatedAs($member);
        $this->assertSame(1, User::count());
        $member->refresh();
        $this->assertSame('artist', $member->account_type);
        $this->assertSame(User::ROLE_MODERATOR, $member->role);
        $this->assertSame('google-123', $member->google_id);
        $this->assertNotNull($member->email_verified_at);
        $this->assertNotNull($member->password);
        $this->assertSame(0, AdminLog::where('action', 'compte.cree')->count());
    }

    public function test_known_google_id_logs_in_directly(): void
    {
        $this->fakeGoogle(['email' => 'autre-adresse@gmail.com']);
        $user = User::factory()->create(['email' => 'awa@example.com', 'account_type' => 'visitor']);
        $user->forceFill(['google_id' => 'google-123', 'avatar_url' => 'https://example.com/ma-photo.jpg'])->save();

        $this->googleCallback()->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
        // Une photo déjà présente n'est pas remplacée.
        $this->assertSame('https://example.com/ma-photo.jpg', $user->fresh()->avatar_url);
    }

    public function test_unverified_google_email_is_refused(): void
    {
        $this->fakeGoogle([], verified: false);

        $this->googleCallback()->assertRedirect(route('login'))->assertSessionHasErrors(['google' => GoogleAuthController::MSG_UNVERIFIED]);

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_deactivated_account_is_refused(): void
    {
        $this->fakeGoogle();
        $user = User::factory()->create(['email' => 'awa@gmail.com', 'account_type' => 'visitor', 'deactivated_at' => now()]);

        $this->googleCallback()->assertRedirect(route('login'))->assertSessionHasErrors(['google' => EnsureAccountIsActive::MESSAGE]);
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);

        // Déjà relié : même refus.
        $user->forceFill(['google_id' => 'google-123'])->save();
        $this->googleCallback()->assertSessionHasErrors(['google' => EnsureAccountIsActive::MESSAGE]);
        $this->assertGuest();
    }

    public function test_signup_closed_refuses_new_accounts_but_accepts_existing_ones(): void
    {
        Settings::set('auth.google_inscription', false);
        $this->fakeGoogle(['email' => 'nouveau@gmail.com']);

        $this->googleCallback()->assertRedirect(route('login'))->assertSessionHasErrors(['google' => GoogleAuthController::MSG_SIGNUP_CLOSED]);
        $this->assertGuest();
        $this->assertSame(0, User::count());

        $existing = User::factory()->create(['email' => 'nouveau@gmail.com', 'account_type' => 'writer']);
        $this->googleCallback()->assertRedirect();
        $this->assertAuthenticatedAs($existing);
    }

    public function test_missing_keys_hide_every_button_without_errors(): void
    {
        config(['services.google.client_secret' => null]);
        $this->assertFalse(GoogleAuth::enabled());

        $this->get(route('login'))->assertOk()
            ->assertDontSee('Continuer avec Google')
            ->assertSee('Les comptes sont créés par l', false);
        $this->get(route('auth.google'))->assertRedirect(route('login'));
        $this->get(route('auth.google.callback', ['code' => 'x']))->assertRedirect(route('login'));

        $user = User::factory()->create(['account_type' => 'visitor']);
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertDontSee('Relier mon compte Google');
        $this->actingAs($user)->get(route('google.link'))->assertRedirect(route('profile.edit'));

        // Réglage « auth.google_actif » décoché : même résultat, même avec les clés.
        config(['services.google.client_secret' => 'test-secret-value']);
        Settings::set('auth.google_actif', false);
        $this->assertFalse(GoogleAuth::enabled());
        auth()->logout();
        $this->get(route('login'))->assertOk()->assertDontSee('Continuer avec Google');
    }

    public function test_external_return_paths_are_ignored(): void
    {
        foreach (['https://site-externe.com', '//site-externe.com', '/\\site-externe.com', 'javascript:alert(1)', '/javascript:alert(1)', 'site-externe.com', '/a//b'] as $bad) {
            $this->assertNull(GoogleAuth::safePath($bad), $bad);
        }
        $this->assertSame('/recits/12?page=3', GoogleAuth::safePath('/recits/12?page=3'));
        $this->assertNull(GoogleAuth::safePath('/login?retour=/x'));

        $this->fakeGoogle();
        $this->get(route('login', ['retour' => 'https://site-externe.com']))->assertOk()
            ->assertDontSee('site-externe.com');
        $this->googleCallback()->assertRedirect(url('/'));

        auth()->logout();
        $this->get(route('auth.google', ['retour' => '//site-externe.com']));
        $this->assertFalse(session()->has('url.intended') && str_contains(session('url.intended'), 'site-externe'));
    }

    public function test_unlinking_without_a_password_is_refused(): void
    {
        $user = User::factory()->create(['account_type' => 'visitor', 'password' => null]);
        $user->forceFill(['google_id' => 'google-123'])->save();

        $this->actingAs($user)->delete(route('google.unlink'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('google_error', GoogleAuthController::MSG_SET_PASSWORD_FIRST);
        $this->assertSame('google-123', $user->fresh()->google_id);

        // Avec un mot de passe : délié.
        $other = User::factory()->create(['account_type' => 'visitor']);
        $other->forceFill(['google_id' => 'google-456', 'avatar_url' => 'https://lh3.googleusercontent.com/a/x'])->save();
        $this->actingAs($other)->delete(route('google.unlink'))->assertSessionHas('google_status');
        $this->assertNull($other->fresh()->google_id);
        $this->assertNull($other->fresh()->avatar_url);
    }

    public function test_accepting_a_join_request_from_an_existing_visitor_upgrades_the_same_account(): void
    {
        Notification::fake();
        $this->fakeGoogle(['email' => 'awa@gmail.com']);
        $this->googleCallback();
        $visitor = User::where('email', 'awa@gmail.com')->sole();
        auth()->logout();

        $request = JoinRequest::create([
            'name' => 'Awa Koné', 'email' => 'AWA@gmail.com', 'discipline' => 'illustration',
            'portfolio_url' => 'https://instagram.com/awa', 'message' => 'Je dessine.',
        ]);
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR, 'account_type' => 'artist']);

        $this->actingAs($moderator)->post(route('admin.join-requests.accept', $request), ['account_type' => 'artist'])
            ->assertSessionHas('status');

        $this->assertSame(2, User::count());
        $visitor->refresh();
        $this->assertSame('artist', $visitor->account_type);
        $this->assertSame(User::ROLE_MEMBER, $visitor->role);
        $this->assertNotNull($visitor->artist);
        $this->assertSame($visitor->id, $request->fresh()->user_id);

        Notification::assertSentTo($visitor, MemberActivity::class);
        Notification::assertSentOnDemand(JoinRequestAnswered::class, function (JoinRequestAnswered $notification, $channels, $notifiable) {
            $mail = $notification->toMail($notifiable);
            $text = view($mail->view[1], $mail->viewData)->render();

            return $notification->accepted && ! $notification->hasPassword && str_contains($text, 'Continuer avec Google');
        });
        $this->assertSame(1, AdminLog::where('action', 'compte.type_modifie')->count());
    }

    // ---------- Compléments ----------

    public function test_redirect_asks_google_for_basic_scopes_and_remembers_the_page(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('scopes')->once()->with(['openid', 'email', 'profile'])->andReturnSelf();
        $provider->shouldReceive('with')->once()->with(['prompt' => 'select_account'])->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google', ['retour' => '/ecrivains']))->assertRedirect('https://accounts.google.com/o/oauth2/auth');
        $this->assertSame(url('/ecrivains'), session('url.intended'));
    }

    public function test_email_login_returns_to_the_original_page(): void
    {
        $user = User::factory()->create(['account_type' => 'visitor']);

        $this->get(route('login', ['retour' => '/ecrivains']))->assertOk();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(url('/ecrivains'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_cancelled_consent_returns_to_login(): void
    {
        $this->googleCallback(['error' => 'access_denied'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['google' => GoogleAuthController::MSG_CANCELLED]);
        $this->assertGuest();
    }

    public function test_invalid_state_returns_to_login_with_a_message(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->googleCallback()->assertRedirect(route('login'))->assertSessionHasErrors(['google' => GoogleAuthController::MSG_FAILED]);
        $this->assertGuest();

        $this->get(route('login'))->assertSee(GoogleAuthController::MSG_FAILED);
    }

    public function test_logged_in_member_can_link_google_from_my_account(): void
    {
        $user = User::factory()->create(['email' => 'awa@example.com', 'account_type' => 'writer']);

        $this->fakeGoogle(['email' => 'awa.perso@gmail.com']);

        $this->actingAs($user)->get(route('google.link'))->assertRedirect('https://accounts.google.com/o/oauth2/auth');
        $this->assertSame($user->id, session(GoogleAuth::LINK_SESSION_KEY));

        $this->actingAs($user)->googleCallback()->assertRedirect(route('profile.edit'))->assertSessionHas('google_status');

        $user->refresh();
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame('awa.perso@gmail.com', $user->google_email);
        $this->assertSame('awa@example.com', $user->email);
        $this->assertSame('writer', $user->account_type);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('awa.perso@gmail.com')->assertSee('Délier mon compte Google');
    }

    public function test_linking_a_google_account_already_used_by_someone_else_is_refused(): void
    {
        $owner = User::factory()->create(['account_type' => 'visitor']);
        $owner->forceFill(['google_id' => 'google-123'])->save();
        $user = User::factory()->create(['account_type' => 'visitor']);
        $this->fakeGoogle();

        $this->actingAs($user)->withSession([GoogleAuth::LINK_SESSION_KEY => $user->id])
            ->get(route('auth.google.callback', ['code' => 'abc']))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('google_error', GoogleAuthController::MSG_ALREADY_LINKED);

        $this->assertNull($user->fresh()->google_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_email_login_on_a_google_only_account_explains_instead_of_crashing(): void
    {
        $user = User::factory()->create(['email' => 'awa@gmail.com', 'account_type' => 'visitor', 'password' => null]);

        $this->from(route('login'))->post(route('login'), ['email' => 'AWA@gmail.com', 'password' => 'nimporte'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Ce compte se connecte avec Google.']);
        $this->assertGuest();
        $this->assertNull($user->fresh()->password);
    }

    public function test_google_only_account_can_set_a_password_and_delete_itself_with_its_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'awa@gmail.com', 'account_type' => 'visitor', 'password' => null]);
        $user->forceFill(['google_id' => 'google-123'])->save();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()
            ->assertSee('Définir un mot de passe')
            ->assertDontSee('Mot de passe actuel')
            ->assertSee('Supprimer mon compte');

        $this->actingAs($user)->from(route('profile.edit'))->post(route('password.set-link'))
            ->assertSessionHas('status', 'password-link-sent');
        Notification::assertSentTo($user, ResetPassword::class);

        // Formulaire direct : l'ancien mot de passe n'est pas demandé.
        $this->actingAs($user)->from(route('profile.edit'))->put(route('password.update'), [
            'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->password);

        // Suppression d'un compte sans mot de passe : l'adresse e-mail confirme.
        $other = User::factory()->create(['email' => 'kofi@gmail.com', 'account_type' => 'visitor', 'password' => null]);
        $this->actingAs($other)->from(route('profile.edit'))->delete(route('profile.destroy'), ['confirm_email' => 'autre@gmail.com'])
            ->assertSessionHasErrorsIn('userDeletion', 'confirm_email');
        $this->assertNotNull($other->fresh());

        $this->actingAs($other)->delete(route('profile.destroy'), ['confirm_email' => 'Kofi@Gmail.com'])->assertRedirect('/');
        $this->assertNull($other->fresh());
    }

    public function test_a_visitor_cannot_give_himself_a_creator_type(): void
    {
        $this->fakeGoogle();
        $this->googleCallback();
        $user = User::where('email', 'awa@gmail.com')->sole();

        $this->actingAs($user)->patch(route('account-type.update'), ['account_type' => 'artist'])->assertRedirect(route('dashboard'));
        $this->assertSame('visitor', $user->fresh()->account_type);
        $this->assertNull($user->fresh()->artist);
    }

    public function test_guest_invitations_point_to_login_with_the_current_page(): void
    {
        $artist = User::factory()->create(['account_type' => 'artist'])->artist()->create([]);

        $this->get(route('public.artist', $artist))->assertOk()
            ->assertSee(e(route('login', ['retour' => '/artist/'.$artist->id])), false);
    }

    public function test_connection_settings_tab_shows_configuration_without_the_secret(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'account_type' => 'artist']);

        $this->actingAs($admin)->get(route('admin.settings.edit', ['onglet' => 'connexion']))->assertOk()
            ->assertSee('ID client')
            ->assertSee('configuré')
            ->assertSee('auth__google_actif', false)
            ->assertSee('auth__google_inscription', false)
            ->assertDontSee('test-secret-value');

        config(['services.google.client_secret' => '']);
        $this->actingAs($admin)->get(route('admin.settings.edit', ['onglet' => 'connexion']))->assertOk()->assertSee('manquant');
    }

    public function test_admin_members_can_be_filtered_by_google_connection(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'account_type' => 'artist', 'name' => 'Admin Zéro']);
        $google = User::factory()->create(['name' => 'Awa Google', 'account_type' => 'visitor']);
        $google->forceFill(['google_id' => 'google-123'])->save();
        User::factory()->create(['name' => 'Kofi Classique', 'account_type' => 'artist']);

        $this->actingAs($admin)->get(route('admin.members.index', ['google' => 'oui']))->assertOk()
            ->assertSee('Awa Google')->assertDontSee('Kofi Classique');
        $this->actingAs($admin)->get(route('admin.members.index', ['google' => 'non']))->assertOk()
            ->assertSee('Kofi Classique')->assertDontSee('Awa Google');
    }

    public function test_privacy_page_mentions_google_login(): void
    {
        // Page d'origine intacte : section insérée avant « Vos droits ».
        $content = Page::where('slug', 'confidentialite')->value('contenu');
        $this->assertStringContainsString("<h2>Connexion avec Google</h2>\n<p>Si tu choisis de te connecter avec Google", $content);
        $this->assertLessThan(strpos($content, 'Vos droits'), strpos($content, 'Connexion avec Google'));

        // Page modifiée par l'équipe : le paragraphe est ajouté à la fin, le texte n'est pas remplacé.
        $migration = require database_path('migrations/2026_11_17_100010_add_google_to_privacy_page.php');
        $migration->down();
        Page::where('slug', 'confidentialite')->update(['contenu' => '<p>Texte réécrit par l’équipe.</p>']);
        $migration->up();
        $content = Page::where('slug', 'confidentialite')->value('contenu');
        $this->assertStringStartsWith('<p>Texte réécrit par l’équipe.</p>', $content);
        $this->assertStringEndsWith('Tu peux délier ton compte Google à tout moment depuis « Mon compte ».</p>', $content);

        // Relancée : rien n'est ajouté deux fois.
        $migration->up();
        $this->assertSame(1, substr_count(Page::where('slug', 'confidentialite')->value('contenu'), 'Connexion avec Google'));
    }

    public function test_join_form_is_prefilled_from_query_parameters(): void
    {
        $this->get(route('public.about', ['nom' => 'Awa Koné', 'email' => 'awa@gmail.com']))->assertOk()
            ->assertSee('value="Awa Koné"', false)
            ->assertSee('value="awa@gmail.com"', false);
    }
}
