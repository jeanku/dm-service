<?php

namespace Jeanku\Database\Eloquent;

class SoftDeletingScope implements Scope
{


    /**
     * builder添加的拓展方法
     *
     * @var array
     */
    protected $extensions = ['ForceDelete', 'Restore', 'WithTrashed', 'OnlyTrashed'];


    /**
     * 添加软删除过滤条件.
     *
     * @param  $builder
     * @param  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where($model->getQualifiedStatusColumn(), '!=', $this->getInvalidStatus($builder));
    }

    /**
     * 加载拓展方法.
     *
     * @param $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {                                                        //覆盖删除方法，达到软删除效果
            $column = $this->getStatusColumn($builder);
            $deletedAtColumn = $this->getDeletedAtColumn($builder);
            $data = array(
                $column => $this->getInvalidStatus($builder),
            );
            $deletedAtColumn && $data[$deletedAtColumn] = $builder->getModel()->freshTimestampString();         // 更新删除时间
            return $builder->update($data) ? true : false;
        });
    }

    /**
     * 获取删除列名
     *
     * @author gaojian
     * @date   2017-10-26
     * @param  $builder
     * @return string
     */
    protected function getStatusColumn(Builder $builder)
    {
        $join = $builder->getQuery()->joins;
        if ($join && count($join) > 0) {
            return $builder->getModel()->getQualifiedStatusColumn();
        } else {
            return $builder->getModel()->getStatusColumn();
        }
    }

    /**
     * 获取删除时间列名
     *
     * @author gaojian
     * @date   2017-10-26
     * @param  $builder
     * @return string
     */
    protected function getDeletedAtColumn(Builder $builder)
    {
        $join = $builder->getQuery()->joins;
        if ($join && count($join) > 0) {
            return $builder->getModel()->getQualifiedDeletedAtColumn();
        } else {
            return $builder->getModel()->getDeletedAtColumn();
        }
    }

    /**
     * 获取表示无效的值
     *
     * @author gaojian
     * @date   2017-10-26
     * @param  $builder
     * @return string
     */
    protected function getInvalidStatus(Builder $builder)
    {
        return $builder->getModel()->getInvalidStatus();
    }


    /**
     * 增加强制删除方法
     *
     * @author gaojian
     * @date   2017-10-26
     * @param  $builder
     * @return void
     */
    protected function addForceDelete(Builder $builder)
    {
        $builder->macro('forceDelete', function (Builder $builder) {
            return $builder->getQuery()->delete();
        });
    }

    /**
     * 增加恢复方法
     *
     * @author gaojian
     * @date   2017-10-26
     * @param  $builder
     * @return void
     */
    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder, $defaultValue = 1) {
            $builder->withTrashed();
            return $builder->update(array($builder->getModel()->getStatusColumn() => $defaultValue));
        });
    }

    /**
     * 增加获取包括已软删除数据方法
     *
     * @author gaojian
     * @date   2017-10-26
     * @param  $builder
     * @return void
     */
    protected function addWithTrashed(Builder $builder)
    {
        $builder->macro('withTrashed', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * 增加只获取软删除数据的方法
     *
     * @author gaojian
     * @date   2017-10-26
     * @param  $builder
     * @return void
     */
    protected function addOnlyTrashed(Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();
            $builder->withoutGlobalScope($this)->where($model->getQualifiedStatusColumn(), $this->getInvalidStatus($builder));
            return $builder;
        });
    }

}
