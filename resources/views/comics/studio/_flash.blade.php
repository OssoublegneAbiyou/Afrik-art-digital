@if (session('success'))
    <p class="nd-flash nd-flash--ok" role="status">{{ session('success') }}</p>
@endif
@if (session('error'))
    <p class="nd-flash nd-flash--error" role="alert">{{ session('error') }}</p>
@endif
@if ($errors->any())
    <div class="nd-flash nd-flash--error" role="alert" tabindex="-1" id="cs-errors">
        <p><strong>Impossible d’enregistrer :</strong></p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
