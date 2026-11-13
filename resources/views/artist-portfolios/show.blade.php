<x-world-layout
    world="neo"
    force-mode="nuit"
    :title="$artistProps['portfolio']['title'].' – '.$artistProps['artist']['name']"
    :description="$artistProps['portfolio']['description'] ?: 'Portfolio immersif '.\App\Support\Fr::de($artistProps['artist']['name']).' sur '.$collectif['nom'].'.'"
    :image="$artistProps['illustrations'][0]['thumbnailUrl'] ?? null"
>
    {{-- Portfolio immersif : toujours de nuit (palette Néo-Babi), rendu par React. --}}
    <div
        id="artist-immersive-root"
        class="nb-immersive"
        data-props='@json($artistProps)'
    ></div>

    <div class="nb-portfolio-share">
        <x-share-buttons class="w-container" :title="'« '.$artistProps['portfolio']['title'].' », portfolio immersif '.\App\Support\Fr::de($artistProps['artist']['name'])" label="Partager ce portfolio" />
    </div>
</x-world-layout>
