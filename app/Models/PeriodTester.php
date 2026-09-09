<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodTester extends Model
{
    protected $table = 'period_testers';

    protected $fillable = [
        'period_id',
        'tester_id',
    ];

    public $timestamps = false;

    public function period()
    {
        return $this->belongsTo(CheckingPeriod::class, 'period_id');
    }

    public function tester()
    {
        return $this->belongsTo(User::class, 'tester_id');
    }
}
