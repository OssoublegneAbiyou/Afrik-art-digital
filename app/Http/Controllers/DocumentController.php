<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isWriter(), 403);

        $writer = $request->user()->writer()->firstOrCreate([]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'document' => ['required', 'file', 'mimes:pdf,txt,doc,docx', 'max:102400'],
            'cover_image' => ['required', 'image', 'max:5120'],
        ]);

        $documentSize = $request->file('document')->getSize();
        $coverSize = $request->file('cover_image')->getSize();
        $incomingSize = $documentSize + $coverSize;

        if (($request->user()->storageUsedBytes() + $incomingSize) > $request->user()->storageLimitBytes()) {
            return back()->withErrors([
                'storage' => 'Espace de stockage de 1 Go dépassé. Supprime des fichiers avant d’ajouter une nouvelle œuvre.',
            ]);
        }

        $documentPath = $request->file('document')->store('documents/files', 'public');
        $coverPath = $request->file('cover_image')->store('documents/covers', 'public');

        Document::create([
            'writer_id' => $writer->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $documentPath,
            'file_type' => strtolower($request->file('document')->getClientOriginalExtension()),
            'file_size_bytes' => $documentSize,
            'cover_image_path' => $coverPath,
            'cover_image_size_bytes' => $coverSize,
        ]);

        return back()->with('success', 'Œuvre littéraire ajoutée avec succès.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        if (auth()->user()?->isAdmin()) {
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            if (Storage::disk('public')->exists($document->cover_image_path)) {
                Storage::disk('public')->delete($document->cover_image_path);
            }

            $document->delete();

            return back()->with('success', 'Oeuvre supprimee avec succes.');
        }

        $writer = Writer::where('user_id', auth()->id())->first();

        if (! $writer || $document->writer_id !== $writer->id) {
            abort(403, 'Non autorisé.');
        }

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        if (Storage::disk('public')->exists($document->cover_image_path)) {
            Storage::disk('public')->delete($document->cover_image_path);
        }

        $document->delete();

        return back()->with('success', 'Œuvre supprimée avec succès.');
    }
}
