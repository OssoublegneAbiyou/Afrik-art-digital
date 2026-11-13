<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Pages de gestion (admin, compte, formulaire de portfolio) : même en-tête que le site public,
 * dans un univers clair (mode jour verrouillé) car leurs formulaires sont conçus sur fond clair.
 */
class AppLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public string $world = 'songe',
    ) {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
