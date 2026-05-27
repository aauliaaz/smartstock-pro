<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    public const ADMIN = 'ADM';
    public const MANAGER = 'MGR';
    public const STAFF = 'STF';
    public const VIEWER = 'VWR';

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
