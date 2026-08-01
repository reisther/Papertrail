<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAccountRecovery extends Model
{
    protected $guarded = [];

    protected $hidden = ['reset_token_hash'];

    protected function casts(): array
    {
        return [
            'document_delete_after' => 'datetime',
            'document_deleted_at' => 'datetime',
            'reset_token_expires_at' => 'datetime',
            'reset_token_used_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function administrator()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
