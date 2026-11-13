<x-world-layout world="pont" :title="$title.' · Administration'" robots-noindex>
    <div class="adm w-container">
        <nav class="adm__nav" aria-label="Administration">
            <p class="adm__nav-title">Administration</p>
            <ul class="adm__nav-list">
                @foreach ($adminMenu as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" class="adm__nav-link" @if ($item['active']) aria-current="page" @endif>
                            <span>{{ $item['label'] }}</span>
                            @if ($item['badge'] > 0)
                                <span class="adm__badge" aria-label="{{ $item['badge'] }} à traiter">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="adm__main">
            <header class="adm-head">
                <div>
                    <h1 class="adm-head__title">{{ $title }}</h1>
                    @if ($intro)
                        <p class="adm-head__intro">{{ $intro }}</p>
                    @endif
                </div>
                @isset($actions)
                    <div class="adm-head__actions">{{ $actions }}</div>
                @endisset
            </header>

            @if (session('status'))
                <p class="adm-flash adm-flash--ok" role="status">{{ session('status') }}</p>
            @endif
            @if (session('error'))
                <p class="adm-flash adm-flash--error" role="alert">{{ session('error') }}</p>
            @endif
            @if ($errors->any() && ! ($hideErrorSummary ?? false))
                <div class="adm-flash adm-flash--error" role="alert">
                    Certains champs sont à corriger : voir les messages sous les champs concernés.
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</x-world-layout>
