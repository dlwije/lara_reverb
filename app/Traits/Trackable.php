<?php

namespace App\Traits;

use App\Models\Sma\Product\Track;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

trait Trackable
{
    public function tracks()
    {
        return $this->morphMany(Track::class, 'trackable');
    }

    public function getBalanceAttribute()
    {
        return $this->balance();
    }

    public function balance($date = null)
    {
        $date = $date ?: Carbon::now();

        if (! $date instanceof DateTimeInterface) {
            $date = Carbon::create($date);
        }

        return floatval($this->tracks()->where('created_at', '<=', $date->format('Y-m-d H:i:s'))->sum('value'));
    }

    public function increaseBalance($balance = 0, $arguments = [])
    {
        return $this->createTrack($balance, $arguments);
    }

    public function decreaseBalance($balance = 0, $arguments = [])
    {
        return $this->createTrack((0 - $balance), $arguments);
    }

    public function mutateTracking($balance = 0, $arguments = [])
    {
        return $this->createTrack($balance, $arguments);
    }

    public function clearBalance($newAmount = null, $arguments = [])
    {
        $this->tracks()->delete();

        if (! is_null($newAmount)) {
            $this->createTrack($newAmount, $arguments);
        }

        return true;
    }

    public function setBalance($newAmount, $arguments = [])
    {
        $currentValue = $this->balance;

        if ($deltaValue = $newAmount - $currentValue) {
            return $this->createTrack($deltaValue, $arguments);
        }
    }

    public function hasBalance($balance = 0.01)
    {
        return $this->balance > 0 && $this->balance >= $balance;
    }

    public function hasNoBalance()
    {
        return $this->balance <= 0;
    }

    protected function createTrack($value, $arguments = [])
    {
        $reference = Arr::get($arguments, 'reference');

        $createArguments = collect([
            'value'       => $value,
            'description' => Arr::get($arguments, 'description'),
        ])->when($reference, function ($collection) use ($reference) {
            return $collection
                ->put('reference_id', $reference->getKey())
                ->put('reference_type', $reference->getMorphClass());
        })->toArray();

        return $this->tracks()->create($createArguments);
    }

    public function scopeWhereHasBalance($query)
    {
        return $query->where(function ($query) {
            return $query->whereHas('tracks', function ($query) {
                return $query->select('trackable_id')->groupBy('trackable_id')->havingRaw('SUM(value) > 0');
            });
        });
    }

    public function scopeWhereHasNoBalance($query)
    {
        return $query->where(function ($query) {
            return $query->whereHas('tracks', function ($query) {
                return $query->select('trackable_id')->groupBy('trackable_id')->havingRaw('SUM(value) <= 0');
            })->orWhereDoesntHave('tracks');
        });
    }

    public function scopeWhereHasBalanceAbove($query, $value = 0)
    {
        return $query->where(function ($query) use ($value) {
            return $query->whereHas('tracks', function ($query) use ($value) {
                return $query->select('trackable_id')->groupBy('trackable_id')->havingRaw('SUM(value) > ' . $value);
            });
        });
    }

    public function scopeWhereHasBalanceBelow($query, $value = 0)
    {
        return $query->where(function ($query) use ($value) {
            return $query->whereHas('tracks', function ($query) use ($value) {
                return $query->select('trackable_id')->groupBy('trackable_id')->havingRaw('SUM(value) <= ' . $value);
            })->orWhereDoesntHave('tracks');
        });
    }
}
