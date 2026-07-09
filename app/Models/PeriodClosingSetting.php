<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodClosingSetting extends Model
{
    protected $fillable = [
        'closing_password',
        'viewer_user_id',
        'updated_by',
    ];

    protected $hidden = [
        'closing_password',
    ];

    public function hasClosingPassword(): bool
    {
        return !empty($this->attributes['closing_password'] ?? null);
    }

    public function verifyClosingPassword(string $password): bool
    {
        $hash = $this->attributes['closing_password'] ?? null;

        return $hash && \Illuminate\Support\Facades\Hash::check($password, $hash);
    }

    public function viewerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function instance(): self
    {
        return static::firstOrCreate([]);
    }
}
