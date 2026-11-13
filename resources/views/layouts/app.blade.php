<x-world-layout :world="$world" force-mode="jour" :title="$title" :description="$description" :image="$image" robots-noindex>
    <div class="font-sans text-[#17110d]">
        {{ $slot }}
    </div>
</x-world-layout>
