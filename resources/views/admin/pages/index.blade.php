<x-admin-layout title="Pages" intro="Pages légales et règles du collectif. Les pages publiées apparaissent automatiquement dans le pied de page.">
    <section class="adm-card" aria-labelledby="pages-title">
        <h2 id="pages-title" class="sr-only">Liste des pages</h2>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th scope="col">Page</th>
                        <th scope="col">État</th>
                        <th scope="col">Mise à jour</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pages as $page)
                        <tr>
                            <td>
                                <strong>{{ str_replace('{nom}', $collectif['nom'] ?? config('site.name'), $page->titre) }}</strong>
                                <br><span class="adm-muted">{{ parse_url($page->url(), PHP_URL_PATH) }}</span>
                            </td>
                            <td>
                                <span class="adm-tag {{ $page->publie ? 'adm-tag--ok' : 'adm-tag--off' }}">{{ $page->publie ? 'Publiée' : 'Non publiée' }}</span>
                            </td>
                            <td>{{ $page->updated_at?->locale('fr')->translatedFormat('j F Y') }}</td>
                            <td>
                                <div class="adm-row">
                                    <a href="{{ route('admin.pages.edit', $page) }}" class="adm-btn adm-btn--sm">Modifier<span class="sr-only"> « {{ $page->titre }} »</span></a>
                                    <a href="{{ $page->url() }}" class="adm-link" target="_blank" rel="noopener">Voir<span class="sr-only"> « {{ $page->titre }} » (nouvel onglet)</span> ↗</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
