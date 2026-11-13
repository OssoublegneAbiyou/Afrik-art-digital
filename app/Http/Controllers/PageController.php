<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pages éditables (admin › Pages), rendues dans l'univers Pont.
 * Une page non publiée renvoie 404, sauf pour l'équipe (aperçu).
 */
class PageController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        abort_unless($page->publie || $request->user()?->isStaff(), 404);

        return view('public.page', ['page' => $page]);
    }
}
