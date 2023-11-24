<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceWorker extends Model
{
    use HasFactory;

    protected $table = 'service_workers';

    public $timestamps = false;

    protected $fillable = ['service_id', 'service_worker_id'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceWorker()
    {
        return $this->belongsTo(User::class);
    }
}
