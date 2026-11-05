<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// #[Fillable(['name', 'email', 'password'])]
// #[Hidden(['password', 'remember_token'])]
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $username
 * @property string $password
 * @property string $role
 * @property string|null $nip
 * @property string|null $bidang
 * @property string $status
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'nip',
        'bidang',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a tester.
     */
    public function isTester(): bool
    {
        return $this->role === 'tester';
    }

    // Relasi ke checking_periods
    // Satu user (Admin) dapat membuat banyak periode
    public function checkingPeriods()
    {
        return $this->hasMany(CheckingPeriod::class, 'created_by');
    }

    // Relasi ke period_testers
    // Satu user (Tester) dapat terlibat dalam banyak periode
    public function periodTesters()
    {
        return $this->hasMany(PeriodTester::class, 'tester_id');
    }

    // Relasi ke period_services
    // Satu user (Tester) dapat menangani banyak layanan
    public function periodServices()
    {
        return $this->hasMany(PeriodService::class, 'tester_id');
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\CustomResetPassword($token));
    }

    /**
     * Override markEmailAsVerified agar aman karena tabel users tidak menggunakan kolom email_verified_at.
     */
    public function markEmailAsVerified(): bool
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'email_verified_at')) {
            return $this->forceFill([
                'email_verified_at' => $this->freshTimestamp(),
            ])->save();
        }

        return true;
    }
}
