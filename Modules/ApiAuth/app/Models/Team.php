<?php

namespace Modules\ApiAuth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mpociot\Teamwork\TeamworkTeam;

// use Modules\ApiAuth\Database\Factories\TeamFactory;

class Team extends TeamworkTeam
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['owner_id','name', 'team_type'];

    // protected static function newFactory(): TeamFactory
    // {
    //     // return TeamFactory::new();
    // }
}
