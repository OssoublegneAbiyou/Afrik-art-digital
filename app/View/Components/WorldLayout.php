<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Layout des trois univers : « pont » (accueil, connexion, erreurs),
 * « neo » (illustrateurs) et « songe » (écrivains).
 */
class WorldLayout extends Component
{
    public const DEFAULT_MODES = [
        'pont' => 'nuit',
        'neo' => 'nuit',
        'songe' => 'jour',
    ];

    public string $defaultMode;

    public function __construct(
        public string $world = 'pont',
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        // Force un mode et masque le bouton Jour/Nuit (ex. portfolio immersif toujours de nuit).
        public ?string $forceMode = null,
        // En-tête posé en superposition sur le hero (ciel des écrivains, profil auteur).
        public bool $overlayHeader = false,
        public bool $robotsNoindex = false,
    ) {
        $this->world = array_key_exists($world, self::DEFAULT_MODES) ? $world : 'pont';
        $this->defaultMode = $forceMode ?: self::DEFAULT_MODES[$this->world];
    }

    public function hasModeToggle(): bool
    {
        return $this->world !== 'pont' && $this->forceMode === null;
    }

    public function render(): View
    {
        return view('layouts.world');
    }
}
