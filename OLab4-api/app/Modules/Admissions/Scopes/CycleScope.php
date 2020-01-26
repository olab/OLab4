<?php

namespace Entrada\Modules\Admissions\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Entrada\Modules\Admissions\Models\Entrada\Cycle;


class CycleScope implements Scope {

    public function apply (Builder $builder, Model $model) {

        $cycle = Cycle::cycleFromRequest();

        $builder->where($model->getTable().'.cycle_id', '=', $cycle);
    }
}