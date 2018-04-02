<?php
namespace App\Controllers;

use App\Util\Log;
use App\Modules\Menu;

/**
 * simple description
 * @desc more description
 * @author jeanku
 * @date 2018-04-02
 */
class MenuController extends BaseController
{

    /**
     * lists function
     * @date 2018-04-02
     * @param array $param require parameters
     * @return array
     */
    public function lists($param, $page = 1, $pagesize = 10, $order = 'id', $desc = 'desc')
    {
        try {
            return $this->success(Menu::lists($param, $page, $pagesize, $order, $desc));
        } catch (\Exception $e) {
            Log::warning(__CLASS__. '::' . __FUNCTION__, ['code'=>$e->getCode(), 'massage'=>$e->getMessage()]);
            return $this->error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * add function
     * @date 2018-04-02
     * @param array $param require parameters
     * @return array
     */
    public function add($param)
    {
        try {
            $filed = [
                'id'=>'sometime|int|min:0',                                                         //自增ID
                'name'=>'sometime|string|length:[0,30]',                                            //菜单名称
                'parent_id'=>'sometime|int|min:0',                                                  //父菜单ID
                'url'=>'sometime|string|length:[0,100]',                                            //菜单地址
                'key'=>'sometime|string|length:[0,50]',                                             //菜单key
                'type'=>'sometime|int|min:0',                                                       //菜单类型 0:菜单 1:权限 2:资源
                'icon'=>'sometime|string|length:[0,25]',                                            //菜单图标
                'status'=>'sometime|int|min:0',                                                     //数据状态:1:正常 0:失效
                'create_time'=>'sometime|int|min:0',                                                //创建时间
                'update_time'=>'sometime|int|min:0',                                                //更新时间
            ];
            $param = self::validate($filed, $param);
            return $this->success(Menu::add($param));
        } catch (\Exception $e) {
            Log::warning(__CLASS__. '::' . __FUNCTION__, ['code'=>$e->getCode(), 'massage'=>$e->getMessage()]);
            return $this->error($e->getCode(), $e->getMessage());
        }
    }


    /**
     * update function
     * @date 2018-04-02
     * @param array $param require parameters
     * @return array
     */
    public function update($param)
    {
        try {
            $filed = [
                'id'=>'sometime|int|min:0',                                                         //自增ID
                'name'=>'sometime|string|length:[0,30]',                                            //菜单名称
                'parent_id'=>'sometime|int|min:0',                                                  //父菜单ID
                'url'=>'sometime|string|length:[0,100]',                                            //菜单地址
                'key'=>'sometime|string|length:[0,50]',                                             //菜单key
                'type'=>'sometime|int|min:0',                                                       //菜单类型 0:菜单 1:权限 2:资源
                'icon'=>'sometime|string|length:[0,25]',                                            //菜单图标
                'status'=>'sometime|int|min:0',                                                     //数据状态:1:正常 0:失效
                'create_time'=>'sometime|int|min:0',                                                //创建时间
                'update_time'=>'sometime|int|min:0',                                                //更新时间
            ];
            $param = self::validate($filed, $param);
            return $this->success(Menu::update($param));
        } catch (\Exception $e) {
            Log::warning(__CLASS__. '::' . __FUNCTION__, ['code'=>$e->getCode(), 'massage'=>$e->getMessage()]);
            return $this->error($e->getCode(), $e->getMessage());
        }
    }


    /**
     * show function
     * @date 2018-04-02
     * @param array $param require parameters
     * @return array
     */
    public function show($param)
    {
        try {
            $filed = [
                'id' => 'require|int|min:0'
            ];
            $param = self::validate($filed, $param);
            return $this->success(Menu::show($param));
        } catch (\Exception $e) {
            Log::warning(__CLASS__. '::' . __FUNCTION__, ['code'=>$e->getCode(), 'massage'=>$e->getMessage()]);
            return $this->error($e->getCode(), $e->getMessage());
        }
    }


    /**
     * del function
     * @date 2018-04-02
     * @param array $param require parameters
     * @return array
     */
    public function del($param)
    {
        try {
            $filed = [
                'id' => 'require|int|min:0'
            ];
            $param = self::validate($filed, $param);
            return $this->success(Menu::del($param));
        } catch (\Exception $e) {
            Log::warning(__CLASS__. '::' . __FUNCTION__, ['code'=>$e->getCode(), 'massage'=>$e->getMessage()]);
            return $this->error($e->getCode(), $e->getMessage());
        }
    }

}