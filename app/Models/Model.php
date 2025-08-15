<?php

namespace App\Models;

use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class Model extends EloquentModel
{
    use HasFactory;
    use LogsActivity;
    use Paginatable;
    use SoftDeletes;

    public $casts = ['extra_attributes' => 'array'];

    public static $hasReference = false;

    public static $hasRegister = false;

    public static $hasStore = false;

    public static $hasSku = false;

    public static $hasUser = false;

    public static $userRecords = false;

    protected $guarded = [];

    protected static $logAttributesToIgnore = ['team_id'];

    protected static $logOnlyDirty = true;

    protected $setHash = false;

    protected static $submitEmptyLogs = false;

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function getExtraAttributesAttribute()
    {
        return SchemalessAttributes::createForModel($this, 'extra_attributes');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($this->getRouteKeyName(), $value)->withTrashed()->first();
    }

    public function scopeOfCompany($query, $company = null)
    {
        return $query->where('company_id', $company ?: optional(auth()->user())->company_id);
    }

    public function scopeWithExtraAttributes(): Builder
    {
        return SchemalessAttributes::scopeWithSchemalessAttributes('extra_attributes');
    }

    public function scopeTrashed($query, $value)
    {
        if(in_array($value, ['with', 'only'])) {
            return $query->{$value . 'Trashed'}();
        }

        return $query;
    }

    protected static function booted()
    {
        static::addGlobalScope('of_company', function (Builder $builder) {
            $table = with(new static)->getTable();
            $builder->where($table . '.company_id', get_company_id());
        });

        if(static::$userRecords) {
            $user = auth()->user();
            if($user && ! $user->hasRole('Super Admin') && $user->cant('read-all')) {
                static::addGlobalScope('mine', fn ($q) => $q->where('user_id', $user->id));
            }
        }

        static::creating(function ($model) {
            if(! $model->company_id) {
                $model->company_id = get_company_id();
            }
            if ($model::$hasReference && ! $model->reference) {
                $model->reference = get_reference($model);
            }
            if ($model::$hasRegister && ! $model->register_id) {
                $model->register_id = session('open_register_id') ?: auth()->user()?->openedRegister?->id;
            }
            if ($model::$hasSku && ! $model->sku) {
                $model->sku = ulid();
            }
            if ($model::$hasStore && ! $model->store_id) {
                $model->store_id = session('selected_store_id', config('app.default_store_id'));
            }
            if ($model->setHash && ! $model->hash) {
                $model->hash = uuid4();
            }
            if ($model::$hasUser && ! $model->user_id) {
                $model->user_id = auth()->id();
            }
        });
    }
}
