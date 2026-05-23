<?php

declare(strict_types=1);

function loadEnvFile(string $path): array
{
    if (! file_exists($path)) {
        fwrite(STDERR, "Unable to read .env\n");
        exit(1);
    }

    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#') || ! str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value);

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $values[$key] = $value;
    }

    return $values;
}

$config = loadEnvFile(__DIR__ . '/../.env');

$required = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];

foreach ($required as $key) {
    if (! array_key_exists($key, $config)) {
        fwrite(STDERR, "Missing {$key} in .env\n");
        exit(1);
    }
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['DB_HOST'],
    $config['DB_PORT'],
    $config['DB_DATABASE']
);

$pdo = new PDO($dsn, $config['DB_USERNAME'], (string) $config['DB_PASSWORD'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$command = $argv[1] ?? 'list';

function upsertUser(PDO $pdo, array $user): void
{
    $select = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $select->execute(['email' => $user['email']]);
    $existingId = $select->fetchColumn();

    $payload = [
        'name' => $user['name'],
        'email' => $user['email'],
        'password' => password_hash($user['password'], PASSWORD_BCRYPT),
        'account_type' => $user['account_type'],
        'account_type_selected' => 1,
        'is_admin' => $user['is_admin'],
        'email_verified_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if ($existingId !== false) {
        $payload['id'] = (int) $existingId;

        $update = $pdo->prepare(
            'UPDATE users
             SET name = :name,
                 email = :email,
                 password = :password,
                 account_type = :account_type,
                 account_type_selected = :account_type_selected,
                 is_admin = :is_admin,
                 email_verified_at = :email_verified_at,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $update->execute($payload);
        $userId = (int) $existingId;
    } else {
        $payload['created_at'] = date('Y-m-d H:i:s');
        $insert = $pdo->prepare(
            'INSERT INTO users
             (name, email, password, account_type, account_type_selected, is_admin, email_verified_at, created_at, updated_at)
             VALUES
             (:name, :email, :password, :account_type, :account_type_selected, :is_admin, :email_verified_at, :created_at, :updated_at)'
        );
        $insert->execute($payload);
        $userId = (int) $pdo->lastInsertId();
    }

    if ($user['account_type'] === 'artist') {
        $artist = $pdo->prepare('SELECT id FROM artists WHERE user_id = :user_id LIMIT 1');
        $artist->execute(['user_id' => $userId]);

        if ($artist->fetchColumn() === false) {
            $insertArtist = $pdo->prepare(
                'INSERT INTO artists (user_id, bio, banner_path, banner_size_bytes, instagram, twitter, behance, website, created_at, updated_at)
                 VALUES (:user_id, :bio, NULL, 0, NULL, NULL, NULL, NULL, :created_at, :updated_at)'
            );
            $insertArtist->execute([
                'user_id' => $userId,
                'bio' => $user['bio'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    if ($user['account_type'] === 'writer') {
        $writer = $pdo->prepare('SELECT id FROM writers WHERE user_id = :user_id LIMIT 1');
        $writer->execute(['user_id' => $userId]);

        if ($writer->fetchColumn() === false) {
            $insertWriter = $pdo->prepare(
                'INSERT INTO writers (user_id, bio, banner_path, banner_size_bytes, instagram, facebook, website, created_at, updated_at)
                 VALUES (:user_id, :bio, NULL, 0, NULL, NULL, NULL, :created_at, :updated_at)'
            );
            $insertWriter->execute([
                'user_id' => $userId,
                'bio' => $user['bio'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}

if ($command === 'list') {
    $rows = $pdo->query('SELECT id, name, email, account_type, account_type_selected, is_admin FROM users ORDER BY id')->fetchAll();

    foreach ($rows as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    exit(0);
}

if ($command === 'seed-test-users') {
    $accounts = [
        [
            'name' => 'Ced Admin',
            'email' => 'ced@afrikart.local',
            'password' => 'Ced12345!',
            'account_type' => 'artist',
            'is_admin' => 1,
            'bio' => 'Compte admin local pour tester toutes les pages sur ngrok.',
        ],
        [
            'name' => 'Awa Artist',
            'email' => 'artist@afrikart.local',
            'password' => 'Artist12345!',
            'account_type' => 'artist',
            'is_admin' => 0,
            'bio' => 'Compte artiste local pour tester la galerie et le dashboard artiste.',
        ],
        [
            'name' => 'Moussa Writer',
            'email' => 'writer@afrikart.local',
            'password' => 'Writer12345!',
            'account_type' => 'writer',
            'is_admin' => 0,
            'bio' => 'Compte ecrivain local pour tester les pages auteur et documents.',
        ],
        [
            'name' => 'Nina Visitor',
            'email' => 'visitor@afrikart.local',
            'password' => 'Visitor12345!',
            'account_type' => 'visitor',
            'is_admin' => 0,
            'bio' => 'Compte visiteur local pour tester les favoris, suivis et marque-pages.',
        ],
    ];

    foreach ($accounts as $account) {
        upsertUser($pdo, $account);
    }

    echo "Seeded local test accounts.\n";
    exit(0);
}

if ($command === 'fix-ced') {
    $select = $pdo->prepare('SELECT id FROM users WHERE name = :name OR email = :email ORDER BY id ASC LIMIT 1');
    $select->execute([
        'name' => 'ced',
        'email' => 'superabiyou@gmail.com',
    ]);

    $cedId = $select->fetchColumn();

    if ($cedId === false) {
        fwrite(STDERR, "Ced account not found.\n");
        exit(1);
    }

    $update = $pdo->prepare(
        'UPDATE users
         SET password = :password,
             account_type = :account_type,
             account_type_selected = 1,
             email_verified_at = :email_verified_at,
             updated_at = :updated_at
         WHERE id = :id'
    );

    $update->execute([
        'id' => (int) $cedId,
        'password' => password_hash('CedLocal123!', PASSWORD_BCRYPT),
        'account_type' => 'artist',
        'email_verified_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $artist = $pdo->prepare('SELECT id FROM artists WHERE user_id = :user_id LIMIT 1');
    $artist->execute(['user_id' => (int) $cedId]);

    if ($artist->fetchColumn() === false) {
        $insertArtist = $pdo->prepare(
            'INSERT INTO artists (user_id, bio, banner_path, banner_size_bytes, instagram, twitter, behance, website, created_at, updated_at)
             VALUES (:user_id, :bio, NULL, 0, NULL, NULL, NULL, NULL, :created_at, :updated_at)'
        );
        $insertArtist->execute([
            'user_id' => (int) $cedId,
            'bio' => 'Compte Ced remis en etat pour les tests ngrok.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    $verify = $pdo->prepare('SELECT id, name, email, account_type, account_type_selected, is_admin FROM users WHERE id = :id');
    $verify->execute(['id' => (int) $cedId]);

    echo json_encode($verify->fetch(), JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

fwrite(STDERR, "Unknown command: {$command}\n");
exit(1);
