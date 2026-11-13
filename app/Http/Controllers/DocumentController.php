<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Genre;
use App\Models\ReadingProgress;
use App\Models\Writer;
use App\Notifications\MemberActivity;
use App\Support\AdminNotifier;
use App\Support\PlatformRules;
use App\Support\ReaderText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    // Environ 1 Mo de texte (un long roman tient en 500 000 caractères).
    public const CONTENT_MAX = 1_000_000;

    public function read(Document $document): View
    {
        $document->load('writer.user');
        \App\Support\ViewCounter::record($document);

        $user = auth()->user();
        $isBookmarked = $user
            ? $user->bookmarkedDocuments()->where('document_id', $document->id)->exists()
            : false;
        $isOwner = $document->isOwnedBy($user);

        $parsed = ReaderText::parse($document->readableText());
        $savedProgress = $user
            ? ReadingProgress::where('user_id', $user->id)->where('document_id', $document->id)->first()
            : null;

        return view('documents.read', [
            'document' => $document,
            'writer' => $document->writer,
            'coverUrl' => $document->coverUrl(),
            'chapters' => $parsed['chapters'],
            'isBookmarked' => $isBookmarked,
            'isOwner' => $isOwner,
            'canDownload' => $document->isDownloadable() && $document->canBeDownloadedBy($user),
            'pdfUrl' => $document->isPdf() && $document->hasFile() ? route('documents.file', $document) : null,
            'savedProgress' => $savedProgress,
            // Temps de lecture estimé : 230 mots par minute.
            'readingMinutes' => $parsed['words'] > 0 ? max(1, (int) ceil($parsed['words'] / 230)) : null,
            'nextDocument' => Document::where('writer_id', $document->writer_id)
                ->where('id', '>', $document->id)
                ->orderBy('id')
                ->first(),
        ]);
    }

    // ---------- Éditeur ----------

    public function create(Request $request): View
    {
        abort_unless($request->user()->isWriter(), 403);

        return view('documents.editor', ['document' => null, 'initialContent' => '', 'genres' => Genre::options()]);
    }

    public function edit(Request $request, Document $document): View
    {
        abort_unless($document->isOwnedBy($request->user()), 403);

        return view('documents.editor', [
            'document' => $document,
            'genres' => Genre::options($document->genre),
            // Si l'auteur n'a pas encore écrit de version texte, on lui propose celle extraite de son TXT / DOCX.
            'initialContent' => (string) $document->extractableText(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isWriter(), 403);

        $writer = $request->user()->writer()->firstOrCreate([]);
        $data = $this->validateWork($request, null);

        $documentSize = $request->file('document')?->getSize() ?? 0;
        $coverSize = $request->file('cover_image')->getSize();

        if (($request->user()->storageUsedBytes() + $documentSize + $coverSize) > $request->user()->storageLimitBytes()) {
            return back()->withInput()->withErrors([
                'storage' => 'Espace de stockage de '.PlatformRules::storageLabel()." dépassé. Supprime des fichiers avant d'ajouter une nouvelle œuvre.",
            ]);
        }

        $document = Document::create([
            'writer_id' => $writer->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'genre' => $data['genre'],
            'content' => $this->cleanContent($data['content'] ?? null),
            // Hors mode « au choix de l'auteur », la case n'est pas proposée : on garde la valeur par défaut.
            'allow_download' => PlatformRules::authorChoosesDownload() ? $request->boolean('allow_download', true) : true,
            'cover_image_path' => $request->file('cover_image')->store('documents/covers', 'public'),
            'cover_image_size_bytes' => $coverSize,
            'published_at' => Document::publicationDateFor($data['status'], $data['scheduled_at'] ?? null),
            'moderation_status' => Document::moderationStatusFor($request->user()),
            'rights_certified_at' => now(),
        ] + $this->storeFile($request));

        if ($document->isPendingReview() && ! $document->isDraft()) {
            AdminNotifier::send(MemberActivity::toReview($request->user(), 1, 'texte'));
        }

        $message = match (true) {
            $document->isDraft() => 'Brouillon enregistré : il n’est visible que par toi.',
            $document->isPendingReview() => 'Œuvre enregistrée : elle sera visible après validation par l’équipe.',
            $document->isScheduled() => 'Publication programmée : '.mb_strtolower($document->publicationLabel()).'.',
            default => 'Ton œuvre est publiée.',
        };

        if ($request->boolean('from_editor')) {
            return $document->isVisible()
                ? redirect()->route('documents.read', $document)->with('success', $message)
                : redirect()->route('documents.edit', $document)->with('success', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Enregistrement complet depuis l'éditeur (texte, informations, fichier, téléchargement).
     */
    public function save(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->isOwnedBy($request->user()), 403);

        $data = $this->validateWork($request, $document);

        $attributes = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'genre' => $data['genre'],
            'content' => $this->cleanContent($data['content'] ?? null),
            'published_at' => Document::publicationDateFor($data['status'], $data['scheduled_at'] ?? null, $document->published_at),
        ];

        // La case « Autoriser le téléchargement » n'existe qu'en mode « au choix de l'auteur ».
        if (PlatformRules::authorChoosesDownload()) {
            $attributes['allow_download'] = $request->boolean('allow_download');
        }

        if ($request->hasFile('cover_image')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->cover_image_path);
            \App\Support\ImageThumbnail::delete($document->cover_image_path);
            $attributes['cover_image_path'] = $request->file('cover_image')->store('documents/covers', 'public');
            $attributes['cover_image_size_bytes'] = $request->file('cover_image')->getSize();
            \App\Support\ImageThumbnail::generate($attributes['cover_image_path']);
        }

        if ($request->hasFile('document')) {
            if (filled($document->file_path)) {
                $document->fileDisk()->delete($document->file_path);
            }
            $attributes += $this->storeFile($request);
        } elseif ($request->boolean('remove_file') && filled($attributes['content'])) {
            $document->fileDisk()->delete((string) $document->file_path);
            $attributes += ['file_path' => null, 'file_type' => null, 'file_size_bytes' => 0];
        }

        // Texte, fichier ou couverture modifiés, ou œuvre refusée que l'auteur corrige : retour en validation.
        $contentChanged = $attributes['content'] !== $document->content
            || $request->hasFile('document')
            || $request->hasFile('cover_image');
        $resubmitted = ($contentChanged || $document->isRejected())
            && Document::moderationStatusFor($request->user()) === Document::MODERATION_PENDING;

        if ($resubmitted) {
            $attributes['moderation_status'] = Document::MODERATION_PENDING;
            $attributes['moderation_note'] = null;
            $attributes['rights_certified_at'] = now();
        }

        $wasDraft = $document->isDraft();
        $document->update($attributes);
        $leftDraft = $wasDraft && ! $document->isDraft() && $document->isPendingReview();

        if (($resubmitted || $leftDraft) && ! $document->isDraft()) {
            AdminNotifier::send(MemberActivity::toReview($request->user(), 1, 'texte'));
        }

        return redirect()->route('documents.edit', $document)->with('success', $resubmitted
            ? 'Modifications enregistrées : l’œuvre sera de nouveau visible après validation par l’équipe.'
            : 'Modifications enregistrées.');
    }

    /**
     * Choix rapide du genre depuis l'espace écrivain.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->isOwnedBy($request->user()), 403, 'Non autorisé.');

        $data = $request->validate([
            'genre' => ['required', Genre::rule($document->genre)],
        ], [
            'genre.in' => 'Choisis un genre proposé dans la liste.',
        ]);

        $document->update($data);

        return back()->with('success', '« '.$document->title.' » rejoint le ciel '.$document->genreLabel().'.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        abort_unless(auth()->user()?->isStaff() || $document->isOwnedBy(auth()->user()), 403, 'Non autorisé.');

        $document->deleteFiles();
        $document->delete();

        return back()->with('success', 'Œuvre supprimée avec succès.');
    }

    // ---------- Fichier et téléchargement ----------

    /**
     * Téléchargement contrôlé : respecte le choix de l'auteur et compte les téléchargements des lecteurs.
     */
    public function download(Request $request, Document $document): Response
    {
        $user = $request->user();
        $privileged = $document->isOwnedBy($user) || $user?->isStaff();

        abort_unless($document->canBeDownloadedBy($user), 403, PlatformRules::downloadMode() === PlatformRules::DOWNLOAD_FORBIDDEN
            ? 'Le téléchargement des récits n’est pas proposé sur le site.'
            : 'L’auteur ne propose pas le téléchargement de cette œuvre.');
        abort_unless($document->isDownloadable(), 404);

        if (! $privileged) {
            // Sans toucher à updated_at : un téléchargement n'est pas une modification de l'œuvre.
            DB::table('documents')->where('id', $document->id)->increment('download_count');
        }

        if ($document->hasFile()) {
            return $document->fileDisk()->download($document->file_path, $document->downloadFileName());
        }

        return response()->streamDownload(
            fn () => print($document->title."\n\n".$document->content),
            $document->downloadFileName(),
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }

    /**
     * Fichier affiché dans la page (PDF sans version texte). Ce n'est pas un téléchargement : pas de compteur.
     */
    public function file(Document $document): Response
    {
        abort_unless($document->hasFile(), 404);

        return $document->fileDisk()->response($document->file_path, $document->downloadFileName(), [
            'Content-Disposition' => 'inline; filename="'.$document->downloadFileName().'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    // ---------- Reprise de lecture ----------

    public function progress(Request $request, Document $document): JsonResponse
    {
        $data = $request->validate([
            'progress' => ['required', 'numeric', 'between:0,1'],
            'chapter' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        ReadingProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'document_id' => $document->id],
            ['progress' => round((float) $data['progress'], 4), 'chapter' => $data['chapter'] ?? null],
        );

        return response()->json(['saved' => true]);
    }

    // ---------- Outils ----------

    private function validateWork(Request $request, ?Document $document): array
    {
        $hasExistingFile = $document?->hasFile() ?? false;

        // Sans choix explicite (formulaire rapide de l'espace écrivain) : publication immédiate.
        if (blank($request->input('status'))) {
            $request->merge(['status' => Document::STATUS_PUBLISHED]);
        }

        return $request->validate([
            'status' => ['required', Rule::in([Document::STATUS_PUBLISHED, Document::STATUS_DRAFT, Document::STATUS_SCHEDULED])],
            'scheduled_at' => ['nullable', 'required_if:status,'.Document::STATUS_SCHEDULED, 'date', 'after:now'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'genre' => ['required', Genre::rule($document?->genre)],
            'content' => [$hasExistingFile ? 'nullable' : 'required_without:document', 'nullable', 'string', 'max:'.self::CONTENT_MAX],
            'document' => ['nullable', 'file', 'mimes:pdf,txt,doc,docx', 'max:102400'],
            'cover_image' => [$document ? 'nullable' : 'required', 'image', 'max:5120'],
            'allow_download' => ['nullable', 'boolean'],
            'rights' => ['accepted'],
        ], [
            'rights.accepted' => 'Coche la case pour certifier que tu es l’auteur de ce texte et que tu as le droit de le publier.',
            'genre.required' => 'Choisis le genre de l’œuvre : il place ton récit dans le bon ciel.',
            'genre.in' => 'Choisis un genre proposé dans la liste.',
            'content.required_without' => 'Écris ou colle ton texte, ou joins un fichier (PDF, TXT, DOC, DOCX).',
            'content.max' => 'Le texte est trop long pour une seule œuvre : découpe-le en plusieurs parties.',
            'cover_image.required' => 'Ajoute une image de couverture.',
            'scheduled_at.required_if' => 'Choisis la date de publication.',
            'scheduled_at.after' => 'La date de publication doit être dans le futur.',
        ]);
    }

    /**
     * Nouveau manuscrit : stocké sur le disque privé, servi uniquement par le site.
     */
    private function storeFile(Request $request): array
    {
        if (! $request->hasFile('document')) {
            return ['file_path' => null, 'file_disk' => Document::PRIVATE_DISK, 'file_type' => null, 'file_size_bytes' => 0];
        }

        $file = $request->file('document');

        return [
            'file_path' => $file->store('documents/files', Document::PRIVATE_DISK),
            'file_disk' => Document::PRIVATE_DISK,
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'file_size_bytes' => $file->getSize(),
        ];
    }

    private function cleanContent(?string $content): ?string
    {
        $content = trim(str_replace(["\r\n", "\r"], "\n", (string) $content));

        return $content === '' ? null : $content;
    }
}
