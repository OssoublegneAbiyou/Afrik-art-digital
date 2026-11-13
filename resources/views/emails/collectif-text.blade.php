@if (! empty($greeting)){!! $greeting !!}

@endif
@foreach ($lines ?? [] as $line){!! $line !!}

@endforeach
@if (! empty($action)){!! $action['label'] !!} : {!! $action['url'] !!}

@endif
@foreach ($after ?? [] as $line){!! $line !!}

@endforeach
{!! $signature !!}
