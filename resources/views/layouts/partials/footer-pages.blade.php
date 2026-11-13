{{-- Pages publiées (admin › Pages), liste mise en cache et vidée à chaque enregistrement. --}}
@foreach (\App\Models\Page::footerLinks() as $footerPage)
    <a href="{{ $footerPage['url'] }}">{{ $footerPage['titre'] }}</a>
@endforeach
