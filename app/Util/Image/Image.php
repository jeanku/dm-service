<?php

namespace App\Util\Image;


/**
 * 图片处理类
 * @desc image class
 * @package \App\Libraries\Image
 * @author gaojian291
 * @date 2017-04-14
 */
class Image
{


    private $image = null;

    private function __construct()
    {
    }


    /**
     * 获取当前图片操作类对象
     * @author gaojian291
     * @date 2017-04-17
     * @param object $imageObject option ImageObject::getInstance()方法返回的对象
     * @return array
     */
    public static function getInstance($imageObject = null)
    {
        $model = new self;
        $model->image = $imageObject;
        return $model;
    }

    /**
     * 获取当前image
     * @author gaojian291
     * @date 2017-04-14
     * @return Obhect
     */
    public function getImage()
    {
        return $this->image;
    }


    /**
     * 获取颜色对象
     * @author gaojian291
     * @date 2017-04-14
     * @param int $r required 红色0-255
     * @param int $g required 绿色0-255
     * @param int $b required 蓝色0-255
     * @return model
     */
    public function getColor($r, $g, $b)
    {
        return imagecolorallocate($this->image, $r, $g, $b);
    }


    /**
     * 获取颜色对象
     * @author gaojian291
     * @date 2017-04-14
     * @param int $r required 红色0-255
     * @param int $g required 绿色0-255
     * @param int $b required 蓝色0-255
     * @param int $alpha required 透明度
     * @return model
     */
    public function getColorAlpha($r, $g, $b, $alpha)
    {
        return imagecolorallocatealpha($this->image, $r, $g, $b, $alpha);
    }


    /**
     * 图片添加文字水印
     * @author gaojian291
     * @date 2017-04-14
     * @param int $size required 文字大小
     * @param int $angle required 文字旋转的角度
     * @param Object $fontColor required 文字颜色对象 getColor方法返回的结果
     * @param string $fontFamily required ttf字体的路径
     * @param string $text required 文字内容
     * @param int $x required 文字x位置
     * @param int $y required 文字y位置
     * @return model
     */
    public function markText($size, $angle, $fontColor, $fontFamily, $text, $x, $y)
    {
        imagettftext($this->image, $size, $angle, $x, $y, $fontColor, $fontFamily, $text);
        return $this;
    }

    /**
     * 图片添加图片水印（把水印图片copy到目标图片指定位置上去）
     * @author gaojian291
     * @date 2017-04-14
     * @param Object $waterImage required 添加的图片的ImageObject对象
     * @param int $x required 文字x位置
     * @param int $y required 文字y位置
     * @return model
     */
    public function markPic($waterImage, $x, $y)
    {
        $w_w = imagesx($waterImage);
        $w_h = imagesy($waterImage);
        imagecopy($this->image, $waterImage, $x, $y, 0, 0, $w_w, $w_h);
        imagedestroy($waterImage);
        return $this;
    }


    /**
     * 图片旋转
     * @author gaojian291
     * @date 2017-04-10
     * @param int $angle required 旋转的角度
     * @param int $bgColor option 旋转空白区域的背景色
     * @return Object
     */
    public function rotate($angle, $bgColor = 0)
    {
        $this->image = imagerotate($this->image, $angle, $bgColor);
        return $this;
    }


    /**
     * 图片左右翻转
     * @author gaojian291
     * @date 2017-04-10
     * @param null
     * @return Object
     */
    function turnX()
    {
        $width = imagesx($this->image);
        $height = imagesy($this->image);
        $new = imagecreatetruecolor($width, $height);
        for ($x = 0; $x < $width; $x++) {
            imagecopy($new, $this->image, $width - $x - 1, 0, $x, 0, 1, $height);   //这个方法可以实现图片左右对称
        }
        $this->image = $new;
        return $this;
    }


    /**
     * 图片上下翻转
     * @author gaojian291
     * @date 2017-04-10
     * @param null
     * @return Object
     */
    function turnY()
    {
        $width = imagesx($this->image);
        $height = imagesy($this->image);
        $new = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y++) {
            imagecopy($new, $this->image, 0, $height - $y - 1, 0, $y, $width, 1);
        }
        $this->image = $new;
        return $this;
    }


    /**
     * 等比例缩放图片
     * @author gaojian291
     * @date 2017-04-10
     * @param int $width required 缩放的宽度
     * @param int $height required 缩放的高度
     * @return Object
     */
    function scale($width, $height)
    {
        $s_w = imagesx($this->image);
        $s_h = imagesy($this->image);
        if ($width && ($s_w < $s_h)) {
            $width = ($height / $s_h) * $s_w;
        } else {
            $height = ($width / $s_w) * $s_h;
        }
        $new = imagecreatetruecolor($width, $height);
        imagecopyresampled($new, $this->image, 0, 0, 0, 0, $width, $height, $s_w, $s_h);
        $this->image = $new;
        return $this;
    }


    /**
     * 缩放图片
     * @author gaojian291
     * @date 2017-04-10
     * @param int $width required 缩放的宽度
     * @param int $height required 缩放的高度
     * @return Object
     */
    function resize($width, $height)
    {
        $s_w = imagesx($this->image);
        $s_h = imagesy($this->image);
        $new = imagecreatetruecolor($width, $height);
        imagecopyresampled($new, $this->image, 0, 0, 0, 0, $width, $height, $s_w, $s_h);  //imagecopyresized 生成的图片质量差一点
        $this->image = $new;
        return $this;
    }


    /**
     * 图片透明处理
     * @author gaojian291
     * @date 2017-04-11
     * @param int $trans required 透明度 0 到 127。0 表示完全不透明，127 表示完全透明
     * @tips jpg格式不支持透明度,所以有透明度处理的图片请安png格式输出
     * @return Object
     */
    function transparent($trans = 0)
    {
        $width = imagesx($this->image);
        $height = imagesy($this->image);
        $new = imagecreatetruecolor($width, $height);
        imagealphablending($new, false);
        imagesavealpha($new, true);
        for ($w = 0; $w < $width; $w++) {
            for ($h = 0; $h < $height; $h++) {
                $rgb = imagecolorat($this->image, $w, $h);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $color = imagecolorallocatealpha($new, $r, $g, $b, $trans);
                imagefilledrectangle($new, $w, $h, $w, $h, $color);
            }
        }
        $this->image = $new;
        return $this;
    }


    /**
     * 剪切图片(原图片复制)
     * @author gaojian291
     * @date 2017-04-10
     * @param int $cut_x required 剪切位置X坐标
     * @param int $cut_y required 剪切位置Y坐标
     * @param int $cut_width required 剪切的宽度
     * @param int $cut_height required 剪切的高度
     * @return Object
     */
    function cut($cut_x, $cut_y, $cut_width, $cut_height)
    {
        $width = imagesx($this->image);
        $height = imagesy($this->image);
        $width = min($width, $cut_width);
        $height = min($height, $cut_height);
        $new = imagecreatetruecolor($width, $height);
        imagecopyresampled($new, $this->image, 0, 0, $cut_x, $cut_y, $width, $height, $width, $height);
        $this->image = $new;
        return $this;
    }


    /**
     * 剪切图片 建议用这个方法 (原图片截取)
     * @author gaojian291
     * @date 2017-04-10
     * @param int $cut_x required 剪切位置X坐标
     * @param int $cut_y required 剪切位置Y坐标
     * @param int $cut_width required 剪切的宽度
     * @param int $cut_height required 剪切的高度
     * @return Object
     */
    function crop($cut_x, $cut_y, $cut_width, $cut_height)
    {
        $this->image = imagecrop($this->image, ['x' => $cut_x, 'y' => $cut_y, 'width' => $cut_width, 'height' => $cut_height]);
        return $this;
    }


    /**
     * 画一条直线
     * @author gaojian291
     * @date 2017-04-10
     * @param int $star_x required 直线起始X坐标
     * @param int $star_y required 直线起始Y坐标
     * @param int $end_x required 直线终点X坐标
     * @param int $end_x required 直线终点Y坐标
     * @param object $color required 线条颜色
     * @param int $thick required 线条宽度
     * @return Object
     */
    function line($star_x, $star_y, $end_x, $end_y, $color, $thick = 1)
    {
        if ($thick == 1) {
            imageline($this->image, $star_x, $star_y, $end_x, $end_y, $color);
        }
        $t = $thick / 2 - 0.5;
        if ($star_x == $end_x || $star_y == $end_y) {
            imagefilledrectangle($this->image, round(min($star_x, $end_x) - $t), round(min($star_y, $end_y) - $t), round(max($star_x, $end_x) + $t), round(max($star_y, $end_y) + $t), $color);
        }
        $k = ($end_y - $star_y) / ($end_x - $star_x); //y = kx + q
        $a = $t / sqrt(1 + pow($k, 2));
        $points = array(
            round($star_x - (1 + $k) * $a), round($star_y + (1 - $k) * $a),
            round($star_x - (1 - $k) * $a), round($star_y - (1 + $k) * $a),
            round($end_x + (1 + $k) * $a), round($end_y - (1 - $k) * $a),
            round($end_x + (1 - $k) * $a), round($end_y + (1 + $k) * $a),
        );
        imagefilledpolygon($this->image, $points, 4, $color);
        imagepolygon($this->image, $points, 4, $color);
        return $this;
    }


    /**
     * 图片滤镜处理
     * @author gaojian291
     * @date 2017-04-14
     * @param int $filterType required 滤镜类型
     *      IMG_FILTER_NEGATE：将图像中所有颜色反转。
     *      IMG_FILTER_GRAYSCALE：将图像转换为灰度的。
     *      IMG_FILTER_BRIGHTNESS：改变图像的亮度。用 arg1 设定亮度级别。
     *      IMG_FILTER_CONTRAST：改变图像的对比度。用 arg1 设定对比度级别。
     *      IMG_FILTER_COLORIZE：与 IMG_FILTER_GRAYSCALE 类似，不过可以指定颜色。用 arg1，arg2 和 arg3 分别指定 red，blue 和 green。每种颜色范围是 0 到 255。
     *      IMG_FILTER_EDGEDETECT：用边缘检测来突出图像的边缘。
     *      IMG_FILTER_EMBOSS：使图像浮雕化。
     *      IMG_FILTER_GAUSSIAN_BLUR：用高斯算法模糊图像。
     *      IMG_FILTER_SELECTIVE_BLUR：模糊图像。
     *      IMG_FILTER_MEAN_REMOVAL：用平均移除法来达到轮廓效果。
     *      IMG_FILTER_SMOOTH：使图像更柔滑。用 arg1 设定柔滑级别。
     * @param int|array $arg1 option 参数说明
     *      IMG_FILTER_BRIGHTNESS: Brightness level.
     *      IMG_FILTER_CONTRAST: Contrast level.
     *      IMG_FILTER_COLORIZE: 红色成分的值。
     *      IMG_FILTER_SMOOTH: Smoothness level.
     *      IMG_FILTER_PIXELATE: Block size in pixels.
     * @param int|array $arg2 option  参数说明
     *      IMG_FILTER_COLORIZE: 绿色成分的值。
     *      IMG_FILTER_PIXELATE: Whether to use advanced pixelation effect or not (defaults to FALSE).
     * @param int|array $arg3 option 参数说明
     *      IMG_FILTER_COLORIZE: 蓝色成分的值。
     * @return array
     */
    public function filter($filterType, $arg1 = 0, $arg2 = 0, $arg3 = 0)
    {
        imagefilter($this->image, $filterType, $arg1, $arg2, $arg3);
        return $this;
    }


    /**
     * 翻转图像
     * @author gaojian291
     * @date 2017-04-14
     * @param int $filterType required 图像翻转类型
     *      IMG_FLIP_HORIZONTAL   水平翻转  等同于方法turnX
     *      IMG_FLIP_VERTICAL     垂直翻转  等同于方法turnY
     *      IMG_FLIP_BOTH         水平垂直翻转 等同于rotate旋转180度
     * @return array
     */
    public function flip($filterType)
    {
        imageflip($this->image, $filterType);
        return $this;
    }


    /**
     * 验证码图片生成
     * @author gaojian291
     * @date 2017-04-17
     * @param string $code require 验证码
     * @param int $size require 验证码字体大小
     * @param int $width require 验证码图片宽度
     * @param int $height require 验证码图片高度
     * @param int $x option 验证码起始x图片位置 默认0
     * @param int $y option 验证码起始y图片位置 默认0
     * @param string $fontpath option 验证码字体路径
     * @demo Image::getInstance()->vCode('fasf', 20, 100, 40, 12, 5)->imagePrint();
     * @return array
     */
    function verifyCode($code, $size, $width, $height, $x = 0, $y = 0, $fontpath = '')
    {
        if (!is_string($code)) {
            $code = (string)$code;
        }
        if (empty($font)) {
            $fontpath = __DIR__ . '/Fonts/Belly.ttf';
        }
        // 画图像
        $im = imagecreatetruecolor($width, $height);
        // 定义要用到的颜色
        $back_color = imagecolorallocate($im, 235, 236, 237);
        $boer_color = imagecolorallocate($im, 255, 255, 255);
        $text_color = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
        // 画背景
        imagefilledrectangle($im, 0, 0, $width, $height, $back_color);
        // 画边框
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $boer_color);
        // 画干扰线
        for ($i = 0; $i < 5; $i++) {
            $font_color = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagearc($im, mt_rand(-$width, $width), mt_rand(-$height, $height), mt_rand(30, $width * 2), mt_rand(20, $height * 2), mt_rand(0, 360), mt_rand(0, 360), $font_color);
        }
        // 画干扰点
        for ($i = 0; $i < 50; $i++) {
            $font_color = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $font_color);
        }
        // 画验证码
        $length = strlen($code);
        $base_y = $height / 2 + $size / 2;
        for ($i = 0; $i < $length; $i++) {
            imagefttext($im, $size, mt_rand(-20, 20), $x + $i * $size, $base_y + $y, $text_color, $fontpath, $code[$i]);
        }
        $this->image = $im;
        return $this;
    }


    /**
     *
     * @author gaojian291
     * @date 2017-04-14
     * @param string $type option 输出的图片类型
     * @return array
     */
    public function imagePrint($type = 'png')
    {
        switch ($type) {
            case 'gif':             //GIF
                header('Content-Type:image/gif');
                imagegif($this->image);
                break;
            case 'jpeg':             //JPG
                header('Content-Type:image/jpeg');
                imagepng($this->image);
                break;
            case 'png':             //PNG
                header('Content-Type:image/png');
                imagepng($this->image);
                break;
            default:
                header('Content-Type:image/png');
                imagepng($this->image);
                break;
        }
        imagedestroy($this->image);
        exit;
    }


    /**
     * 图片转base64输出
     * @author gaojian291
     * @date 2017-04-14
     * @param null
     * @return string
     */
    public function imageToBase64()
    {
        ob_start();
        imagejpeg($this->image);
        $image_data = ob_get_contents();
        imagedestroy($this->image);
        ob_end_clean();
        return base64_encode($image_data);
    }
}