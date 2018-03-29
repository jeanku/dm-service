<?php
namespace App\Modules;

use App\Models\MenuModel;

class Menu extends BaseModule
{

    /**
     * get new model
     * @date 2018-03-29
     * @return object
     */
    public static function getModel()
    {
        return new MenuModel();
    }


}