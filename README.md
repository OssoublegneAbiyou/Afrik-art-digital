# Afrik'art Digital

Plateforme creative construite avec Laravel, React, Vite et Tailwind CSS.

Le projet permet de gerer plusieurs univers :
- `illustrateur`
- `ecrivain`
- `visiteur`
- `admin`

On y trouve :
- une page d'accueil publique avec mise en avant quotidienne
- des profils publics artistes et ecrivains
- un espace createur pour publier des illustrations ou des oeuvres
- un espace visiteur avec favoris, suivis et bibliotheque
- un panneau admin pour les comptes, contenus et mises en avant
- une connexion classique par mail
- une connexion Google via OAuth

## Stack

- `PHP 8.4+`
- `Laravel 12`
- `React 19`
- `Vite 7`
- `Tailwind CSS`
- `MySQL`
- `Laravel Socialite` pour Google login
- `Alpine.js` pour les interactions Blade simples

## Prerequis

Installe ces outils avant de demarrer :

- `PHP 8.4+`
- `Composer`
- `Node.js 20+`
- `npm`
- `MySQL / MariaDB`

Sous Windows avec WAMP, ce projet a ete travaille avec :
- `PHP 8.4.15`
- `http://127.0.0.1:8000`

## Installation locale

### 1. Cloner le projet

```powershell
git clone <url-du-repo>
cd afrikart-digital
```

### 2. Installer les dependances PHP

```powershell
composer install
```

### 3. Installer les dependances front

```powershell
npm install
```

### 4. Creer le fichier d'environnement

```powershell
Copy-Item .env.example .env
```

### 5. Generer la cle Laravel

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe artisan key:generate
```

### 6. Configurer la base de donnees

Dans [`.env`](/c:/Users/cedric.ossoublegne/afrikart-digital/.env), renseigne :

```env
APP_NAME="Afrik'art Digital"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=afrikart_digital
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_DOMAIN=
```

### 7. Lancer les migrations

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe artisan migrate
```

### 8. Lier le stockage public

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe artisan storage:link
```

### 9. Lancer le projet

Terminal 1 :

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Terminal 2 :

```powershell
npm.cmd run dev
```

Ensuite ouvre :

```text
http://127.0.0.1:8000
```

## Build production local

```powershell
npm.cmd run build
```

## Comptes et roles

### 1. Illustrateur

Peut :
- gerer sa bio
- ajouter une banniere
- publier jusqu'a `20` illustrations
- partager ses reseaux
- avoir un profil public

### 2. Ecrivain

Peut :
- gerer sa bio
- ajouter une banniere
- publier des oeuvres en `pdf`, `txt`, `doc`, `docx`
- ajouter une illustration de couverture
- avoir un profil public

### 3. Visiteur

Peut :
- suivre des artistes
- ajouter des illustrations en favoris
- ajouter des livres en marque-page
- consulter sa bibliotheque personnelle

### 4. Admin

Peut :
- ouvrir le panneau admin
- gerer les comptes
- changer le type d'un utilisateur
- definir l'artiste du jour et l'ecrivain du jour
- supprimer des contenus

## Quotas et stockage

Chaque utilisateur dispose de :
- `1 Go` de stockage total

Regles actuelles :
- illustrateur : `20 illustrations maximum`
- ecrivain : documents `pdf`, `txt`, `doc`, `docx`
- bannieres comprises dans le calcul du stockage

## Connexion Google

Le projet utilise `Laravel Socialite`.

### Variables `.env`

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

### URL de retour Google en local

Dans Google Cloud Console, ajoute exactement :

```text
http://127.0.0.1:8000/auth/google/callback
```

### Important

En local, le callback Google passe en mode `stateless()` pour eviter les erreurs de session de type `InvalidStateException`.

## Ngrok pour tester en ligne

Si tu veux tester le site publiquement avant de prendre un hebergeur :

### 1. Lancer Laravel

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe artisan serve --host=0.0.0.0 --port=8000
```

### 2. Lancer Ngrok

```powershell
ngrok http 8000
```

### 3. Mettre a jour `.env`

Exemple :

```env
APP_URL=https://mon-url-ngrok.ngrok-free.app
GOOGLE_REDIRECT_URI=https://mon-url-ngrok.ngrok-free.app/auth/google/callback
```

Puis :

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe artisan config:clear
```

### 4. Mettre la meme URL dans Google Cloud Console

```text
https://mon-url-ngrok.ngrok-free.app/auth/google/callback
```

## Structure utile du projet

### Backend

- [`app/Http/Controllers`](/c:/Users/cedric.ossoublegne/afrikart-digital/app/Http/Controllers)
  Controleurs Laravel
- [`app/Models`](/c:/Users/cedric.ossoublegne/afrikart-digital/app/Models)
  Modeles Eloquent
- [`routes/web.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/routes/web.php)
  Routes principales
- [`routes/auth.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/routes/auth.php)
  Routes d'authentification

### Frontend

- [`resources/js/pages/PublicHome.jsx`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/js/pages/PublicHome.jsx)
  Page d'accueil React
- [`resources/js/pages/Dashboard.jsx`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/js/pages/Dashboard.jsx)
  Espaces illustrateur, ecrivain et visiteur
- [`resources/css/app.css`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/css/app.css)
  Styles globaux

### Vues Blade

- [`resources/views/public`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/public)
  Pages publiques
- [`resources/views/auth`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/auth)
  Login, inscription, choix du type
- [`resources/views/admin/index.blade.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/admin/index.blade.php)
  Panneau admin

## Ce qu'il faut modifier si tu veux personnaliser l'app

### Modifier la page d'accueil

- [`resources/js/pages/PublicHome.jsx`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/js/pages/PublicHome.jsx)
- [`resources/views/public/index.blade.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/public/index.blade.php)

### Modifier les dashboards

- [`resources/js/pages/Dashboard.jsx`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/js/pages/Dashboard.jsx)

### Modifier la navigation

- [`resources/views/layouts/navigation.blade.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/layouts/navigation.blade.php)

### Modifier les pages publiques

- [`resources/views/public/artist-show.blade.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/public/artist-show.blade.php)
- [`resources/views/public/writer-show.blade.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/public/writer-show.blade.php)
- [`resources/views/public/artists.blade.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/public/artists.blade.php)
- [`resources/views/public/writers.blade.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/public/writers.blade.php)

### Modifier l'authentification

- [`resources/views/auth/login.blade.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/auth/login.blade.php)
- [`resources/views/auth/register.blade.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/auth/register.blade.php)
- [`resources/views/auth/account-type.blade.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/resources/views/auth/account-type.blade.php)
- [`app/Http/Controllers/Auth/GoogleAuthController.php`](/c:/Users/cedric.ossoublegne/afrikart-digital/app/Http/Controllers/Auth/GoogleAuthController.php)

## Fonctions principales deja implementees

- page d'accueil dynamique
- artiste du jour / ecrivain du jour
- listes publiques des illustrateurs et ecrivains
- profils publics
- bannieres de profil
- favoris d'illustrations
- suivi d'artistes
- marque-pages de livres
- mini lecteur pour les livres
- espace visiteur
- panneau admin
- connexion Google

## Commentaires utiles pour les futurs devs

### 1. Encodage

Quelques anciens fichiers ont eu des problemes d'encodage UTF-8.  
Si un texte s'affiche mal, verifie d'abord l'encodage du fichier avant de faire un gros patch.

### 2. Stockage

Les uploads utilisent le disque `public`.  
Ne pas oublier :

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe artisan storage:link
```

### 3. Google OAuth

Si Google renvoie une erreur :
- verifier `APP_URL`
- verifier `GOOGLE_REDIRECT_URI`
- verifier la meme URL dans Google Cloud Console
- relancer :

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe artisan config:clear
```

### 4. Vite

Si un changement React ne s'affiche pas :

```powershell
npm.cmd run dev
```

ou

```powershell
npm.cmd run build
```

### 5. Cache Blade

Si une vue Blade semble ne pas se mettre a jour :

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe artisan view:clear
```

## Tests utiles

### Lancer les tests Laravel

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe artisan test
```

### Verifier une syntaxe PHP

```powershell
C:\wamp64\bin\php\php8.4.15\php.exe -l app\Http\Controllers\Auth\GoogleAuthController.php
```

## Deploiement

Avant mise en ligne :

- passer `APP_ENV=production`
- passer `APP_DEBUG=false`
- configurer la base de donnees prod
- configurer le stockage
- configurer Google OAuth avec l'URL de production
- lancer :

```powershell
composer install --no-dev --optimize-autoloader
npm.cmd run build
C:\wamp64\bin\php\php8.4.15\php.exe artisan migrate --force
C:\wamp64\bin\php\php8.4.15\php.exe artisan config:cache
C:\wamp64\bin\php\php8.4.15\php.exe artisan route:cache
```

## Resume rapide pour installer

```powershell
composer install
npm install
Copy-Item .env.example .env
C:\wamp64\bin\php\php8.4.15\php.exe artisan key:generate
C:\wamp64\bin\php\php8.4.15\php.exe artisan migrate
C:\wamp64\bin\php\php8.4.15\php.exe artisan storage:link
npm.cmd run dev
C:\wamp64\bin\php\php8.4.15\php.exe artisan serve --host=127.0.0.1 --port=8000
```

## Licence

Projet applicatif prive `Afrik'art Digital`.
#   A f r i k - a r t - d i g i t a l  
 #   A f r i k - a r t - d i g i t a l  
 #   A f r i k - a r t - d i g i t a l  
 #   A f r i k - a r t - d i g i t a l  
 