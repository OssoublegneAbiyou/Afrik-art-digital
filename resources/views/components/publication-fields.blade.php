@props(['model' => null, 'idPrefix' => 'pub'])

{{-- Statut de publication : maintenant, brouillon ou programmée (heure d'Abidjan). --}}
@php
    $status = old('status', $model?->publicationStatus() ?? 'publie');
    $scheduled = old('scheduled_at', $model?->isScheduled() ? $model->published_at->format('Y-m-d\TH:i') : '');
    $choices = [
        'publie' => ['Publier', $model?->isPublished() ? 'Reste en ligne.' : 'Visible tout de suite.'],
        'brouillon' => ['Brouillon', 'Visible par toi seul.'],
        'programme' => ['Programmer', 'Publiée automatiquement à la date choisie.'],
    ];
@endphp

<fieldset {{ $attributes->class('pub') }} x-data="{ status: @js($status) }">
    <legend class="pub__legend">Publication</legend>
    <div class="pub__choices">
        @foreach ($choices as $value => [$label, $hint])
            <label class="pub__choice">
                <input type="radio" name="status" value="{{ $value }}" x-model="status" @checked($status === $value)>
                <span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span>
            </label>
        @endforeach
    </div>
    <div class="pub__date" x-show="status === 'programme'" @if ($status !== 'programme') x-cloak @endif>
        <label for="{{ $idPrefix }}-date">Date et heure de publication <small>(heure d'Abidjan)</small></label>
        <input id="{{ $idPrefix }}-date" type="datetime-local" name="scheduled_at" value="{{ $scheduled }}"
            min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}" x-bind:required="status === 'programme'">
    </div>
</fieldset>
