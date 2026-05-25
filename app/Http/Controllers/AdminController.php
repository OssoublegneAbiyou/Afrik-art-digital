<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Document;
use App\Models\FeaturedSelection;
use App\Models\Illustration;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $users = User::with(['artist.illustrations', 'writer.documents'])
            ->latest()
            ->get();

        $artists = Artist::with(['user', 'illustrations'])->latest()->get();
        $writers = Writer::with(['user', 'documents'])->latest()->get();
        $latestIllustrations = Illustration::with('artist.user')->latest()->take(8)->get();
        $latestDocuments = Document::with('writer.user')->latest()->take(8)->get();
        $todayFeatured = FeaturedSelection::with(['artist.user', 'writer.user'])
            ->whereDate('featured_for', today())
            ->first();

        return view('admin.index', [
            'stats' => [
                'users' => $users->count(),
                'artists' => $users->where('account_type', 'artist')->count(),
                'writers' => $users->where('account_type', 'writer')->count(),
                'visitors' => $users->where('account_type', 'visitor')->count(),
                'admins' => $users->where('is_admin', true)->count(),
                'illustrations' => $artists->sum(fn (Artist $artist) => $artist->illustrations->count()),
                'documents' => $writers->sum(fn (Writer $writer) => $writer->documents->count()),
                'storageUsedGb' => number_format($users->sum(fn (User $user) => $user->storageUsedBytes()) / 1024 / 1024 / 1024, 2),
            ],
            'users' => $users,
            'artists' => $artists,
            'writers' => $writers,
            'latestIllustrations' => $latestIllustrations,
            'latestDocuments' => $latestDocuments,
            'todayFeatured' => $todayFeatured,
            'dashboardThemes' => PlatformSetting::dashboardThemes(),
            'platformOptions' => [
                'storagePerAccount' => '1 Go',
                'artistLimit' => '20 illustrations',
                'writerFormats' => 'PDF, TXT, DOC, DOCX',
                'illustrationFormats' => 'Images JPG, PNG, WEBP',
            ],
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'account_type' => ['required', 'in:artist,writer,visitor'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => now(),
            'account_type' => $data['account_type'],
            'account_type_selected' => true,
            'is_admin' => (bool) ($data['is_admin'] ?? false),
            'password' => Hash::make($data['password']),
        ]);

        if ($user->isWriter()) {
            $user->writer()->firstOrCreate([]);
        } elseif ($user->isArtist()) {
            $user->artist()->firstOrCreate([]);
        }

        return back()->with('success', 'Compte créé avec succès.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'account_type' => ['required', 'in:artist,writer,visitor'],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'account_type' => $data['account_type'],
            'is_admin' => (bool) ($data['is_admin'] ?? false),
        ]);

        if ($user->isWriter()) {
            $user->writer()->firstOrCreate([]);
        } elseif ($user->isArtist()) {
            $user->artist()->firstOrCreate([]);
        }

        return back()->with('success', 'Compte mis à jour avec succès.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte admin.']);
        }

        $this->deleteUserFiles($user);

        $user->delete();

        return back()->with('success', 'Compte supprimé avec succès.');
    }

    public function updateFeatured(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'artist_id' => ['nullable', 'exists:artists,id'],
            'writer_id' => ['nullable', 'exists:writers,id'],
        ]);

        FeaturedSelection::updateOrCreate(
            ['featured_for' => today()->toDateString()],
            [
                'artist_id' => $data['artist_id'] ?? null,
                'writer_id' => $data['writer_id'] ?? null,
            ]
        );

        return back()->with('success', 'Mise en avant du jour mise à jour.');
    }

    public function updateDashboardThemes(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $types = array_keys(PlatformSetting::defaultDashboardThemes());

        $rules = [];
        foreach ($types as $type) {
            $rules["themes.$type.background"] = ['required', 'string', 'max:255'];
            $rules["themes.$type.panel"] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
            $rules["themes.$type.text"] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
            $rules["themes.$type.accent"] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }

        $data = $request->validate($rules);
        $current = PlatformSetting::dashboardThemes();

        foreach ($types as $type) {
            $current[$type] = array_merge($current[$type], $data['themes'][$type]);
        }

        PlatformSetting::updateDashboardThemes($current);

        return back()->with('success', 'Couleurs des dashboards mises à jour.');
    }

    private function deleteUserFiles(User $user): void
    {
        $user->loadMissing([
            'artist.illustrations',
            'artist.portfolios.items',
            'writer.documents',
        ]);

        $paths = [];

        if ($user->artist) {
            $paths[] = $user->artist->banner_path;

            foreach ($user->artist->illustrations as $illustration) {
                $paths[] = $illustration->image_path;
            }

            foreach ($user->artist->portfolios as $portfolio) {
                foreach ($portfolio->items as $item) {
                    $paths[] = $item->guide_audio_path;
                    $paths[] = $item->custom_music_path;
                }
            }
        }

        if ($user->writer) {
            $paths[] = $user->writer->banner_path;

            foreach ($user->writer->documents as $document) {
                $paths[] = $document->file_path;
                $paths[] = $document->cover_image_path;
            }
        }

        foreach (array_filter(array_unique($paths)) as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
