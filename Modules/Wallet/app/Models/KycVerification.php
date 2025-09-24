<?php

namespace Botble\Wallet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Wallet\Database\Factories\KycVerificationFactory;

class KycVerification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id', 'tier', 'status', 'submitted_at', 'verified_at',
        'verified_by', 'document_paths', 'rejection_reason'
    ];

    protected $casts = [
        'document_paths' => 'array',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
