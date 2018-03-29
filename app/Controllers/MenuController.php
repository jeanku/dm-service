<?php
namespace App\Controllers;

use App\Util\Log;
use App\Modules\Menu;

//haha
class MenuController extends BaseController
{

    /**
     * lists function
     * @date 2018-03-29
     * @param array $param require parameters
     * @return array
     */
    public function lists($param = [], $page = 1, $pagesize = 10, $order = 'id', $desc = 'desc', $select = ['*'])
    {
        try {
            return $this->success(Menu::lists($param, $page, $pagesize, $order, $desc, $select));
        } catch (\Exception $e) {
            Log::warning(__CLASS__. '::' . __FUNCTION__, ['code'=>$e->getCode(), 'massage'=>$e->getMessage()]);
            return $this->error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * add function
     * @date 2018-03-29
     * @param array $param require parameters
     * @return array
     */
    public function add($param)
    {
        try {
            $filed = [
                'name' => 'sometime|string',
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
     * @date 2018-03-29
     * @param array $param require parameters
     * @return array
     */
    public function update($param)
    {
        try {
            $filed = [
                'id'   => 'require|int',
                'name' => 'sometime|string',
                'url' => 'sometime|string',
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
     * @date 2018-03-29
     * @param array $param require parameters
     * @return array
     */
    public function show($param, $select = ['*'])
    {
        try {
            $filed = [
                'id' => 'require|int|min:0'
            ];
            $param = self::validate($filed, $param);
            return $this->success(Menu::show($param, $select));
        } catch (\Exception $e) {
            Log::warning(__CLASS__. '::' . __FUNCTION__, ['code'=>$e->getCode(), 'massage'=>$e->getMessage()]);
            return $this->error($e->getCode(), $e->getMessage());
        }
    }


    /**
     * del function
     * @date 2018-03-29
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