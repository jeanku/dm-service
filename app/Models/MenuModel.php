<?php
namespace App\Models;


class MenuModel extends BaseModel
{
    public $table = "manage_menu";

    protected $primaryKey = 'id';

    protected $status_column = 'status';                                        //软删除字段

    const CREATED_AT = 'create_time';

    const UPDATED_AT = 'update_time';

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