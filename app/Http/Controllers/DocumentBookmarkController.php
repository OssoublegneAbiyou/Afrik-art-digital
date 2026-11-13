<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentBookmarkController extends Controller
{
    public function store(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $request->user()->bookmarkedDocuments()->syncWithoutDetaching([$document->id]);

        return back()->with('success', 'Livre ajouté à vos marque-pages.');
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $request->user()->bookmarkedDocuments()->detach($document->id);

        return back()->with('success', 'Livre retiré de vos marque-pages.');
    }
}
