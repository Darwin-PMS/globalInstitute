<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
        'phone',
        'avatar',
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
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'role' => 'string',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(\App\Models\Student::class);
    }

    /**
     * The student profile for this login.
     *
     * A login owns at most one student record in practice; `students()` stays
     * for the historical has-many callers.
     */
    public function student(): HasOne
    {
        return $this->hasOne(\App\Models\Student::class);
    }

    /**
     * The trainer profile for this login, if the account teaches.
     */
    public function trainer(): HasOne
    {
        return $this->hasOne(\App\Models\Trainer::class);
    }

    /**
     * The single source of truth for "is this account an administrator".
     *
     * `is_admin` is the authoritative flag: it is set explicitly by the admin
     * seeder and by admin management. The legacy `role` column is not usable on
     * its own because it was created with a default of 'admin', so every new
     * account would otherwise inherit administrator rights.
     */
    public function isAdministrator(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Effective role for this account, resolved server-side.
     * Presentation code should use this rather than inferring from relations.
     */
    public function learningRole(): string
    {
        if ($this->isAdministrator()) {
            return 'admin';
        }

        return $this->trainer ? 'teacher' : 'student';
    }
}
