<?php
namespace App\Modules;

/**
 * module base类
 * @desc more description
 * @package \Supercoach\Module
 * @date 2018-03-23
 */
class BaseModule
{

    /**
     * 数据列表
     * @date 2017-03-21
     * @param array $param option 条件参数
     * @param int $page option 当前页
     * @param int $size option 页大小
     * @param string $orderColumn option 排序字段
     * @param string $order option 排序key asc:正序 desc：倒序
     * @param array $select option 展示的字段
     * @return array
     */
    public static function lists($param = [], $page = 1, $size = 10, $orderColumn = 'id', $order = 'desc', $select = ['*'])
    {
        return static::getModel()->getLists($param, $page, $size, $orderColumn, $order, $select);
    }

    /**
     * 数据添加
     * @date 2017-03-21
     * @param array $param require 条件参数
     * @return array
     */
    public static function add($param)
    {
        static::getModel()->create($param);
        return true;
    }

    /**
     * 数据更新
     * @date 2017-03-21
     * @param array $param require 条件参数
     * @return array
     */
    public static function update($param)
    {
        return static::getModel()->where('id', $param['id'])->update($param) ? true : false;
    }

    /**
     * 数据详情
     * @date 2017-03-21
     * @param array $param require 条件参数
     * @param array $select option 展示的字段
     * @return array
     */
    public static function show($param, $select)
    {
        return static::getModel()->show($param, $select);
    }

    /**
     * 数据删除
     * @date 2017-03-21
     * @param array $param require 条件参数
     * @return array
     */
    public static function del($param)
    {
        return static::getModel()->where($param)->delete();
    }

}