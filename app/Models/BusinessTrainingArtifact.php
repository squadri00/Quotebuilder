<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A business's own copy of a Super Admin training build sheet, installed
 * automatically alongside a specific product — see TemplateCloner::clone().
 * A private copy, not a live link: editing or deleting the master
 * TrainingArtifact later never changes what a business already has. Tied
 * 1:1 to the business's own product (product_id is unique), and
 * cascade-deletes with it, so removing or replacing that product always
 * removes its tutorial too, however the product gets deleted.
 */
class BusinessTrainingArtifact extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'product_id',
        'source_artifact_id',
        'title',
        'html',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(TrainingArtifact::class, 'source_artifact_id');
    }
}
