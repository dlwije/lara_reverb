<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Spatie\SchemalessAttributes\Casts\SchemalessAttributes;

trait HasSchemalessAttributes
{
    public function initializeHasSchemalessAttributes()
    {
        $this->casts['extra_attributes'] = SchemalessAttributes::class;
    }

    public function saveWithAttributes(array $data)
    {
        $this->fill($data);
        $this->extra_attributes = $data['extra_attributes'];
        $this->save();

        return $this;
    }

    public function scopeWithExtraAttributes(): Builder
    {
        return $this->extra_attributes->modelScope();
    }
}
