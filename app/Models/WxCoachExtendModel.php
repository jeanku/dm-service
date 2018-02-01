<?php
namespace App\Models;


class WxCoachExtendModel extends \Jeanku\Database\Eloquent\Model
{

    protected $connection = 'wcard';

    public $table = "wx_coach_extend";

    protected $primaryKey = 'id';

    protected $status_column = 'status';                                        //软删除字段

    const CREATED_AT = 'create_time';

    const UPDATED_AT = 'update_time';

    protected $fillable = [
        'id',
        'coach_id',
        'openid',
        'cid',
        'province',
        'city',
        'area',
        'addr',
        'lng',
        'lat',
        'mobile',
        'orderNo',
        'orderFee',
        'orderDate',
        'crdate',
        'remark',
        'stuCount',
        'stuDealCount',
        'addrTitle',
        'status',
        'create_time',
        'update_time',
    ];
}