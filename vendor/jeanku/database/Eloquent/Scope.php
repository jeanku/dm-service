<?php

namespace Jeanku\Database\Eloquent;

interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Jeanku\Database\Eloquent\Builder  $builder
     * @param  \Jeanku\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);
}
