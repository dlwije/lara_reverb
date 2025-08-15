<?php

namespace App\Traits;

trait Paginatable
{
    public function getPerPage()
    {
        return get_settings('rows_per_page') ?? 15;
    }

    public function onEachSide($count)
    {
        $this->onEachSide = $count;

        return $this;
    }
}
