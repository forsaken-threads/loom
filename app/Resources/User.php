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
     * Get the Loom resources that this resource is connected to and
     * that will be publicly exposed by Loom
     *
     * @return array
     */
    public function getConnectableResources()
    {
        return [];
    }

    /**
     * Get the Quality Control object for the Loom resource
     *
     * @return QualityControl
     */
    public function getQualityControl()
    {
        return new QualityControl([
        ]);
    }
}
