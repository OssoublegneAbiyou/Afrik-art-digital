<?php

/*
| Outils de conversion vidéo (animations). Vides : « ffmpeg » / « ffprobe » cherchés dans le PATH du serveur.
| Sans ffmpeg, l'envoi d'animations est suspendu avec un message clair (le reste du site fonctionne).
*/
return [
    'ffmpeg' => env('FFMPEG_PATH') ?: 'ffmpeg',
    'ffprobe' => env('FFPROBE_PATH') ?: 'ffprobe',

    // Durée maximale d'une conversion (secondes) avant abandon.
    'timeout' => (int) env('FFMPEG_TIMEOUT', 600),

    // WebM VP9 en plus du MP4 H.264 : désactivé par défaut. Le MP4 H.264 est lu par tous les navigateurs
    // (iPhone compris) ; l'encodage VP9 est 5 à 20 fois plus lent et doublerait le stockage, pour un gain
    // de poids modeste sur des vidéos de quelques secondes. À activer seulement sur un serveur rapide.
    'webm' => (bool) env('ANIMATION_WEBM', false),
];
