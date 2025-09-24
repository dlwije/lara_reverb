<?php

namespace Botble\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Wallet\Database\Factories\DisputeEvidenceFactory;

class DisputeEvidence extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'dispute_id', 'file_path', 'file_name', 'description'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime'
    ];

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }
}
