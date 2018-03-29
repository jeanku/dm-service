<?php
namespace App\Controllers;

use App\Util\Log;
use App\Modules\Menu;


class HomeController extends BaseController
{


    public function home($param)
    {
        try {
            $filed = [
                'id' => 'require|int|min:1',
            ];
            $param = self::validate($filed, $param);
            return $this->success(Menu::show($param));
        } catch (\Exception $e) {
            Log::emergency(__CLASS__. '::' . __FUNCTION__, ['code'=>$e->getCode(), 'massage'=>$e->getMessage()]);
            return $this->error($e->getCode(), $e->getMessage());
        }
    }
}