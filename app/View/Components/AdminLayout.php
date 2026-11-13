<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Coquille de l'administration (/admin) : univers Pont, menu latéral (en haut sur mobile).
 *
 *   <x-admin-layout title="Membres" intro="Rôles et comptes du collectif.">
 *       <x-slot:actions><a class="adm-btn" href="…">Inviter</a></x-slot:actions>
 *       …
 *   </x-admin-layout>
 */
class AdminLayout extends Component
{
    public function __construct(
        public string $title = 'Administration',
        public ?string $intro = null,
    ) {
    }

    public function render(): View
    {
        return view('layouts.admin');
    }
}
