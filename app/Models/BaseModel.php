<?php
namespace App\Models;

class BaseModel extends \Jeanku\Database\Eloquent\Model
{

    /**
     * 数据列表
     * @author gaojian291
     * @date 2017-03-21
     * @param array $param option 条件参数
     * @param int $page option 当前页
     * @param int $size option 页大小
     * @param string $orderColumn option 排序字段
     * @param string $order option 排序key asc:正序 desc：倒序
     * @return array
     */
    public static function getLists($param = [], $page = 1, $size = 10, $orderColumn = 'id', $order = 'desc', $select = ['*'])
    {
        $newModel = new static();
        $returnData = [
            'page'       => $page,
            'page_size'  => $size,
            'total_count'=> 0,
            'list'       => []
        ];
        $returnData['page'] = $page = $page > 0 ? (int)$page : 1;
        $returnData['page_size'] = $size = $size > 0 && $size < 50 ? (int)$size : 10;
        $operate = ['<','<=','>','>=','!=','in','notin','or','between','like'];
        foreach ($param as $key => $val) {
            if (in_array($key, $operate)) {
                switch ($key) {
                    case 'in':
                        foreach ($val as $k => $v) {
                            $newModel = $newModel->whereIn($k, $v);
                        }
                        break;
                    case 'notin':
                        foreach ($val as $k => $v) {
                            $newModel = $newModel->whereNotIn($k, $v, 'and', true);
                        }
                        break;
                    case 'or':
                        foreach ($val as $k => $v) {
                            $newModel = $newModel->orWhere($k, $v);
                        }
                        break;
                    default:
                        foreach ($val as $k => $v) {
                            $newModel = $newModel->where($k, $key, $v);
                        }
                        break;
                }
            } else {
                $newModel = $newModel->where($key, $val);
            }
        }
        $returnData['list'] = $newModel->select($select)->orderBy($orderColumn, $order)
            ->skip(($page - 1) * $size)->take($size)->get()->toArray();
        return $returnData;
    }

    /**
     * 获取数据详情
     * @author gaojian291
     * @date 2018-01-12
     * @param array $param required 请求条件
     * @return array
     */
    public static function show($param,  $column = ['*'])
    {
        $model = static::where($param)->first($column);
        return $model ? $model->toArray() : [];
    }
}