<?php

namespace App\Traits;

use App\Models\Sma\People\Customer;
use App\Models\User;
use DateTimeInterface;
use App\Models\Sma\Order\AwardPoint;
use Illuminate\Support\Carbon;

trait HasAwardPoints
{
    public function awardPoints()
    {
        return $this->hasMany(AwardPoint::class);
    }

    public function getPointsAttribute()
    {
        return $this->points();
    }

    public function points($date = null)
    {
        $date = $date ?: Carbon::now();

        if(! $date instanceof DateTimeInterface)
        {
            $date = Carbon::create($date);
        }

        return floatval($this->awardPoints()->where('created_at', '<=', $date->format('Y-m-d H:i:s'))->sum('value'));
    }

    public function grantPoints($on_amount, array $data)
    {
        $loyalty = get_settings('loyalty');
        if($loyalty && $on_amount > 0) {
            if($this instanceof Customer && ($loyalty['customer']['spent'] ?? null) && ($loyalty['customer']['spent'] ?? null)) {
                $data['value'] = floor(($on_amount / $loyalty['customer']['spent']) * $loyalty['customer']['points']);
                $this->awardPoints()->updateOrCreate(['sale_id' => $data['sale_id'], 'user_id' => null, 'customer_id' => $this->id], $data);
            }elseif ($this instanceof User && ($loyalty['staff']['spent'] ?? null) && ($loyalty['staff']['spent'] ?? null)) {
                $data['value'] = floor(($on_amount / $loyalty['staff']['spent']) * $loyalty['staff']['points']);
                $this->awardPoints()->updateOrCreate(['sale_id' => $data['sale_id'], 'customer_id' => null, 'user_id' => $this->id], $data);
            }
        }
    }

    public function deletePoints($sale_id)
    {
        $this->awardPoints()->where('sale_id', $sale_id)->forceDelete();
    }
}
