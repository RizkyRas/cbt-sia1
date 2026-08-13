<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationRecord extends Model
{
    protected $table = 'migrations';

    public $timestamps = false;

    protected $guarded = [];
}