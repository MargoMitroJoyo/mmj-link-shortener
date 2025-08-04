<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Link extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'url',
        'description',
        'status',
        'expired_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPublished()
    {
        return $this->status;
    }

    public function isExpired()
    {
        return $this->expired_at && now()->gte($this->expired_at);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->user_id = Auth::user()->id;
        });
    }
}
