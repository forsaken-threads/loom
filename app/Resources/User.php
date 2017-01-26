<?php

namespace App\Resources;

use App\Traits\Weavable;
use App\Loom\QualityControl;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable,
        Weavable;

    // Webstuhl resources use UUIDs for their primary key
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * @return QualityControl
     */
    public function getQualityControl()
    {
        return new QualityControl([
        ]);
    }
}
