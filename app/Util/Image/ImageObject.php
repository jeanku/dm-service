<?php

namespace App\Util\Image;

/**
 * 根据图片信息生成对应的图片对象
 * @desc get image Object class
 * @package \App\Util\Image
 * @author gaojian291
 * @date 2017-04-17
 */
class ImageObject {

    public static $imageOb = [];

    private function __construct(){}

    public static function getInstance($path)
    {
        $image = imagecreatefromstring(file_get_contents($path));
        if (!$image) {
            throw new \Exception ('获取图片信息失败');
        }
        return $image;
    }
}