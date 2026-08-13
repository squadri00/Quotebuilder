<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Super Admin's private reference library — training notes, help docs,
 * anything authored as a standalone HTML page (pasted directly, or
 * uploaded as an .html file) and stored so it renders back exactly as
 * authored. Never business-scoped and never shown outside the admin
 * guard — see SuperAdmin\TrainingArtifactController.
 *
 * Optionally tagged with a real Industry (the same list Templates use)
 * so build sheets and other industry-specific references group under
 * the industry they're about — e.g. a Cabinet & Millwork build sheet
 * groups with any other Cabinet & Millwork reference material. Leave
 * industry_id null for general material that isn't about one industry.
 */
class TrainingArtifact extends Model
{
    /** @use HasFactory<\Database\Factories\TrainingArtifactFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'industry_id',
        'html',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
