<?php
namespace App\Controllers;

use App\Exceptions\ServiceException;
use App\Models\Article;
use App\Models\CoachModel;
use App\Models\WxCoachExtendModel;
use App\Util\Request;
use App\Util\Log;
use Jeanku\Database\DatabaseManager as DB;


/**
* \HomeController
*/
class HomeController extends BaseController
{


    public function home()
    {
        try {
            $data = DB::table('coach')->where('c_id', 10189)->get();

//            $data = DB::select('select * from coach where c_id = 10189', []);


//            $data = CoachModel::where('c_id', 10189)->select('c_id')->get()->toArray();

            return $this->success($data);
        } catch (\Exception $e) {
            Log::emergency(__CLASS__. '::' . __FUNCTION__, ['code'=>$e->getCode(), 'massage'=>$e->getMessage()]);
            return $this->error($e->getCode(), $e->getMessage());
        }
    }
}