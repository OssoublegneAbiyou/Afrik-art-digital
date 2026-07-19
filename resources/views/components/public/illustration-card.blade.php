@props(['illustration'])

<a href="{{ route('public.illustration', $illustration) }}" class="group block overflow-hidden rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
    <div class="overflow-hidden rounded-2xl bg-[#efe4d6]">
        <img
            src="{{ asset('storage/' . $illustration->image_path) }}"
            alt="{{ $illustration->title }}"
            class="aspect-square w-full bg-[#efe4d6] object-contain transition duration-300 group-hover:scale-[1.03]"
            loading="lazy"
        >
    </div>
    <div class="mt-4">
        <h3 class="text-base font-semibold text-[#201a16] transition group-hover:text-[#9a4f2c]">{{ $illustration->title }}</h3>
        <p class="mt-2 text-sm font-semibold text-[#9a4f2c]">{{ $illustration->artist?->user?->name ?? 'Artiste' }}</p>
    </div>
    <span class="mt-4 inline-flex rounded-full bg-[#201a16] px-4 py-2 text-xs font-semibold text-white">
        Voir l'œuvre
    </span>
</a>
