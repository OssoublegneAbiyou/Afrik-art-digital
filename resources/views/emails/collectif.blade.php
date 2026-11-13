{{-- E-mail sobre du collectif (HTML). Données : $greeting, $lines[], $action [label, url]|null, $after[], $signature. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine ?? $signature }}</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f4f1fb;font-family:Arial,Helvetica,sans-serif;color:#2A2350;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px 24px;line-height:1.6;font-size:16px;">
        @if (! empty($greeting))
            <p style="margin:0 0 16px;font-size:18px;font-weight:bold;">{{ $greeting }}</p>
        @endif
        @foreach ($lines ?? [] as $line)
            <p style="margin:0 0 14px;white-space:pre-line;">{{ $line }}</p>
        @endforeach
        @if (! empty($action))
            <p style="margin:22px 0;">
                <a href="{{ $action['url'] }}" style="display:inline-block;padding:12px 22px;border-radius:999px;background:#2A2350;color:#ffffff;text-decoration:none;font-weight:bold;">{{ $action['label'] }}</a>
            </p>
            <p style="margin:0 0 14px;font-size:13px;color:#5b5480;">Si le bouton ne fonctionne pas, copie ce lien dans ton navigateur :<br><span style="word-break:break-all;">{{ $action['url'] }}</span></p>
        @endif
        @foreach ($after ?? [] as $line)
            <p style="margin:0 0 14px;white-space:pre-line;">{{ $line }}</p>
        @endforeach
        <p style="margin:22px 0 0;">{{ $signature }}</p>
    </div>
</body>
</html>
