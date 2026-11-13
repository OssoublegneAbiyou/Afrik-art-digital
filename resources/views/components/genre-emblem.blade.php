@props(['genre'])

{{-- Emblèmes de genre « Songe » : trait seul, couleur héritée (currentColor), décoratifs.
     Le dessin vient de la table genres (SVG assaini : formes simples seulement) ;
     genre inconnu, non précisé ou sans emblème : livre ouvert. --}}
@php($emblem = \App\Models\Genre::emblemFor(is_string($genre) ? $genre : null))
<svg viewBox="{{ $emblem['viewBox'] }}" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" {{ $attributes }}>{!! $emblem['inner'] !!}</svg>
