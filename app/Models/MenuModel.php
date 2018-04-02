<?php
namespace App\Models;

class MenuModel extends BaseModel
{
    public $table = "t_manage_menu";

    protected $primaryKey = 'id';

    const STATUS = 'status';                                                    //软删除字段

    const CREATED_AT = 'create_time';                                           //创建时间

    const UPDATED_AT = 'update_time';                                           //更新时间

    protected $fillable = [
        'id',
        'name',
        'parent_id',
        'url',
        'key',
        'type',
        'icon',
        'status',
        'create_time',
        'update_time',
    ];
}