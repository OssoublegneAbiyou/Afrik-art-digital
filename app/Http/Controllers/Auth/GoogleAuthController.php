<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\User;
use App\Support\AdminLogger;
use App\Support\GoogleAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * « Continuer avec Google » : connexion, création d'un compte visiteur, liaison depuis « Mon compte ».
 *
 * Google ne donne jamais de rôle ni de type créateur : un compte créé ici est toujours
 * un visiteur (type « visitor », rôle « membre »). Les jetons d'accès Google ne sont pas conservés.
 */
class GoogleAuthController extends Controller
{
    public const MSG_CANCELLED = 'Connexion avec Google annulée.';
    public const MSG_FAILED = 'La connexion avec Google a échoué, réessaie.';
    public const MSG_UNVERIFIED = 'Ton adresse Google n’est pas vérifiée : vérifie-la chez Google, puis réessaie.';
    public const MSG_SIGNUP_CLOSED = 'Les inscriptions avec Google sont fermées pour le moment.';
    public const MSG_ALREADY_LINKED = 'Ce compte Google est déjà relié à un autre compte.';
    public const MSG_OTHER_GOOGLE = 'Ce compte est déjà relié à un autre compte Google. Connecte-toi avec ton e-mail et ton mot de passe.';
    public const MSG_WELCOME = 'Bienvenue sur Afrik’art Digital ! Ton compte visiteur est prêt : tu peux aimer, suivre et garder tes lectures.';
    public const MSG_SET_PASSWORD_FIRST = 'Définis d’abord un mot de passe : sans lui, tu ne pourrais plus te connecter.';

    /**
     * Départ vers Google (visiteur non connecté).
     */
    public function redirect(Request $request): RedirectResponse|SymfonyRedirect
    {
        if (! GoogleAuth::enabled()) {
            return redirect()->route('login');
        }

        $request->session()->forget(GoogleAuth::LINK_SESSION_KEY);

        // Page d'origine : ?retour= (chemin interne), sinon celle déjà mémorisée, sinon la page précédente.
        if ($request->filled('retour')) {
            GoogleAuth::rememberReturn($request->query('retour'));
        } elseif (! $request->session()->has('url.intended')) {
            $previous = url()->previous();
            if (Str::startsWith($previous, url('/'))) {
                GoogleAuth::rememberReturn(Str::after($previous, rtrim(url('/'), '/')) ?: '/');
            }
        }

        return $this->toGoogle();
    }

    /**
     * Départ vers Google depuis « Mon compte » (relier un compte Google).
     */
    public function linkRedirect(Request $request): RedirectResponse|SymfonyRedirect
    {
        if (! GoogleAuth::enabled()) {
            return redirect()->route('profile.edit')->with('google_error', 'La connexion avec Google n’est pas disponible pour le moment.');
        }

        $request->session()->put(GoogleAuth::LINK_SESSION_KEY, $request->user()->id);

        return $this->toGoogle();
    }

    /**
     * Retour de Google (seule adresse autorisée chez Google : /auth/google/callback).
     */
    public function callback(Request $request): RedirectResponse
    {
        $linkUserId = $request->session()->pull(GoogleAuth::LINK_SESSION_KEY);
        $linking = $linkUserId !== null && Auth::check() && (int) Auth::id() === (int) $linkUserId;

        // Déjà connecté sans demande de liaison : rien à faire.
        if (! $linking && Auth::check()) {
            return redirect()->route('dashboard');
        }

        if (! GoogleAuth::enabled()) {
            return $this->fail($linking, self::MSG_FAILED);
        }

        if ($request->filled('error')) {
            return $this->fail($linking, $request->query('error') === 'access_denied' ? self::MSG_CANCELLED : self::MSG_FAILED);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            Log::info('Google callback failed', ['error' => class_basename($exception)]);

            return $this->fail($linking, self::MSG_FAILED);
        }

        $googleId = (string) $googleUser->getId();
        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if ($googleId === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail($linking, self::MSG_FAILED);
        }

        if (! $this->emailVerified($googleUser)) {
            return $this->fail($linking, self::MSG_UNVERIFIED);
        }

        return $linking
            ? $this->link($request->user(), $googleUser, $googleId, $email)
            : $this->login($request, $googleUser, $googleId, $email);
    }

    /**
     * Délier le compte Google (seulement si le compte a un mot de passe).
     */
    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->google_id === null) {
            return redirect()->route('profile.edit');
        }

        if ($user->password === null) {
            return redirect()->route('profile.edit')->with('google_error', self::MSG_SET_PASSWORD_FIRST);
        }

        // La photo venue de Google part avec la liaison.
        $user->forceFill([
            'google_id' => null,
            'google_email' => null,
            'avatar_url' => str_contains((string) $user->avatar_url, 'googleusercontent.com') ? null : $user->avatar_url,
        ])->save();

        return redirect()->route('profile.edit')->with('google_status', 'Ton compte Google est délié. Connecte-toi désormais avec ton e-mail et ton mot de passe.');
    }

    // ---------- Interne ----------

    private function toGoogle(): SymfonyRedirect
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    private function login(Request $request, SocialiteUser $googleUser, string $googleId, string $email): RedirectResponse
    {
        $created = false;

        // a. Compte déjà relié à ce compte Google.
        $user = User::where('google_id', $googleId)->first();

        // b. Même adresse e-mail (sans tenir compte de la casse) : on relie.
        if ($user === null) {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if ($user !== null) {
                if ($user->isDeactivated()) {
                    return $this->fail(false, EnsureAccountIsActive::MESSAGE);
                }

                if ($user->google_id !== null) {
                    return $this->fail(false, self::MSG_OTHER_GOOGLE);
                }

                $user->forceFill([
                    'google_id' => $googleId,
                    'google_email' => $email,
                    'avatar_url' => $user->avatar_url ?: $this->avatar($googleUser),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }
        }

        // c. Nouveau compte visiteur (si les inscriptions via Google sont ouvertes).
        if ($user === null) {
            if (! GoogleAuth::registrationOpen()) {
                return $this->fail(false, self::MSG_SIGNUP_CLOSED);
            }

            $user = new User([
                'name' => $this->name($googleUser, $email),
                'email' => $email,
                'password' => null,
                'account_type' => 'visitor',
                'account_type_selected' => true,
                'role' => User::ROLE_MEMBER,
            ]);
            $user->forceFill([
                'google_id' => $googleId,
                'google_email' => $email,
                'avatar_url' => $this->avatar($googleUser),
                'email_verified_at' => now(),
            ])->save();

            $created = true;
        }

        if ($user->isDeactivated()) {
            return $this->fail(false, EnsureAccountIsActive::MESSAGE);
        }

        // Ancien compte Google resté sur l'étape « type de compte » : visiteur d'office
        // (les types illustrateur et écrivain ne sont attribués que par l'équipe).
        if (! $user->account_type_selected) {
            $hasStudio = $user->artist()->exists() || $user->writer()->exists();
            $user->forceFill(array_filter([
                'account_type' => $hasStudio ? null : 'visitor',
                'account_type_selected' => true,
            ], fn ($value) => $value !== null))->save();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        if ($created) {
            AdminLogger::log('compte.cree', $user, null, null, [
                'nom' => $user->name, 'email' => $user->email, 'type' => $user->account_type, 'role' => $user->role, 'via' => 'google',
            ]);
            $request->session()->flash(GoogleAuth::WELCOME_SESSION_KEY, self::MSG_WELCOME);
        }

        return redirect()->intended(route('public.index', absolute: false));
    }

    private function link(User $user, SocialiteUser $googleUser, string $googleId, string $email): RedirectResponse
    {
        $owner = User::where('google_id', $googleId)->first();

        if ($owner !== null && ! $owner->is($user)) {
            return $this->fail(true, self::MSG_ALREADY_LINKED);
        }

        $user->forceFill([
            'google_id' => $googleId,
            'google_email' => $email,
            'avatar_url' => $user->avatar_url ?: $this->avatar($googleUser),
        ])->save();

        return redirect()->route('profile.edit')->with('google_status', 'Ton compte est maintenant relié à Google ('.$email.').');
    }

    private function fail(bool $linking, string $message): RedirectResponse
    {
        return $linking
            ? redirect()->route('profile.edit')->with('google_error', $message)
            : redirect()->route('login')->withErrors(['google' => $message]);
    }

    private function emailVerified(SocialiteUser $googleUser): bool
    {
        $raw = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : ($googleUser->user ?? []);
        $verified = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return $verified === true || $verified === 'true' || $verified === 1 || $verified === '1';
    }

    private function name(SocialiteUser $googleUser, string $email): string
    {
        $name = trim((string) ($googleUser->getName() ?: $googleUser->getNickname()));

        return Str::limit($name !== '' ? $name : Str::before($email, '@'), 120, '');
    }

    private function avatar(SocialiteUser $googleUser): ?string
    {
        $url = (string) $googleUser->getAvatar();

        return str_starts_with($url, 'https://') && strlen($url) <= 1000 ? $url : null;
    }
}
