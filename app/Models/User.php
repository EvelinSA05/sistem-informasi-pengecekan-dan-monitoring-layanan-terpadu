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
class User extends Authenticatable
{
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
}
