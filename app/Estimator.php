<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Estimator extends Model
{
    protected $fillable = [
        'name', 'avgAge', 'avgDailyIncomeInUSD', 'avgDailyIncomePopulation', 'periodType', 'timeToElapse', 'reportedCases', 'population', 'totalHospitalBeds'
    ];
}
