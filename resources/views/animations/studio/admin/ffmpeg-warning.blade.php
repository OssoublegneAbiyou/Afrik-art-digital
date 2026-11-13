{{-- Avertissement de l'administration : ffmpeg absent du serveur (animations activées). --}}
@if (\App\Support\AnimationMedia::enabled() && ! \App\Support\Ffmpeg::available())
    <div class="adm-flash adm-flash--error as-adm-warning" role="alert">
        <p><strong>Animations : ffmpeg est introuvable sur ce serveur.</strong></p>
        <p>
            L’envoi de nouvelles animations est suspendu (les membres voient : « {{ \App\Support\Ffmpeg::UNAVAILABLE_MESSAGE }} »).
            Les animations déjà converties restent visibles.
            Installez ffmpeg et ffprobe sur le serveur, ou indiquez leur chemin dans le fichier .env (FFMPEG_PATH, FFPROBE_PATH).
            La vérification est refaite toutes les 10 minutes.
        </p>
    </div>
@endif
