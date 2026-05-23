<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'google_avatar',
        'account_type',
        'account_type_selected',
        'is_admin',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'account_type_selected' => 'boolean',
            'is_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function artist()
    {
        return $this->hasOne(Artist::class);
    }

    public function writer()
    {
        return $this->hasOne(Writer::class);
    }

    public function isArtist(): bool
    {
        return $this->account_type === 'artist';
    }

    public function isWriter(): bool
    {
        return $this->account_type === 'writer';
    }

    public function isVisitor(): bool
    {
        return $this->account_type === 'visitor';
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function storageLimitBytes(): int
    {
        return 1024 * 1024 * 1024;
    }

    public function followedArtists()
    {
        return $this->belongsToMany(Artist::class, 'artist_follows')->withTimestamps();
    }

    public function favoriteIllustrations()
    {
        return $this->belongsToMany(Illustration::class, 'illustration_favorites')->withTimestamps();
    }

    public function bookmarkedDocuments()
    {
        return $this->belongsToMany(Document::class, 'document_bookmarks')->withTimestamps();
    }

    public function storageUsedBytes(): int
    {
        $artistStorage = $this->artist
            ? $this->artist->illustrations->sum(function (Illustration $illustration) {
                if ($illustration->file_size_bytes) {
                    return (int) $illustration->file_size_bytes;
                }

                return Storage::disk('public')->exists($illustration->image_path)
                    ? Storage::disk('public')->size($illustration->image_path)
                    : 0;
            })
            : 0;

        $artistStorage += $this->artist ? (int) $this->artist->banner_size_bytes : 0;

        $writerStorage = $this->writer
            ? $this->writer->documents->sum(function (Document $document) {
                return (int) $document->file_size_bytes + (int) $document->cover_image_size_bytes;
            })
            : 0;

        $writerStorage += $this->writer ? (int) $this->writer->banner_size_bytes : 0;

        return $artistStorage + $writerStorage;
    }
}
