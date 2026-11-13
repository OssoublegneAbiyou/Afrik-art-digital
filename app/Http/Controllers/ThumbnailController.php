<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Illustration;
use App\Support\ImageThumbnail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Génère une miniature à la première demande puis la sert. Les fois suivantes,
 * les vues pointent directement vers le fichier dans /storage/thumbnails.
 */
class ThumbnailController extends Controller
{
    public function illustration(Illustration $illustration): Response
    {
        return $this->serve($illustration->image_path);
    }

    public function cover(Document $document): Response
    {
        return $this->serve($document->cover_image_path);
    }

    private function serve(string $source): Response
    {
        $path = ImageThumbnail::generate($source);

        // Génération impossible (GD absent, image illisible) : on renvoie vers l'original.
        if (! $path) {
            return redirect()->to(asset('storage/'.$source));
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
