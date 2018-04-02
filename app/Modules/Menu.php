<?php
namespace App\Modules;

use App\Models\MenuModel;

/**
 * simple description
 * @desc more description
 * @author jeanku
 * @date 2018-04-02
 */
class Menu extends BaseModule
{

    /**
     * get new model
     * @date 2018-04-02
     * @return object
     */
    public static function getModel()
    {
        return new MenuModel();
    }


}