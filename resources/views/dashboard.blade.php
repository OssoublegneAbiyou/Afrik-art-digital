{{-- Espaces écrivain et visiteur (composant React). L'espace artiste a sa propre vue : dashboard/artist.blade.php. --}}
<x-world-layout
    world="songe"
    force-mode="jour"
    :title="$dashboardProps['accountType'] === 'writer' ? 'Mon espace écrivain' : 'Mon espace'"
    description="Ton espace personnel sur Afrik'art Digital."
    robots-noindex
>
    <div
        id="dashboard-root"
        data-props='@json($dashboardProps)'
    ></div>
</x-world-layout>
