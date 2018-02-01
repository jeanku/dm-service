<?php
namespace App\Models;


class CoachModel extends \Jeanku\Database\Eloquent\Model
{
    public $table = "coach";

    protected $primaryKey = 'id';

    protected $status_column = 'status';                                        //软删除字段

    const CREATED_AT = 'create_time';

    const UPDATED_AT = 'update_time';

    protected $fillable = [
        'c_id',
        'c_s_id',
        'c_u_id',
        'c_nickname',
        'c_phone',
        'c_color',
        'c_card',
        'c_introduce',
        'c_create_time',
        'c_s_name',
        'c_face',
        'sourceSelf',
        'sourceEdt',
        'total',
        'isCard',
        'isRed',
        'markSource',
        'markBack',
        'markOpition',
        'markNo',
        'markOther',
        'markTime',
        'u_city_id',
        'uptimestamp',
        'vip',
        'auth',
        'totalFee',
        'sex',
        'comment_star',
        'comment_count',
        'svip',
        'frozen',
    ];
}