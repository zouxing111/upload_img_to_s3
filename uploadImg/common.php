<?php
/**
 * Top Secret
 * Created by PhpStorm.
 * User: xing.zou
 * Date: 2017/4/27
 * Time: 13:37
 */
namespace uploadImg;
class common
{

    protected static $obj;

    public static function getInstance()
    {
        if (!self::$obj) {
            self::$obj = new common();
        }
        return self::$obj;
    }

    public static function getImgInfo($filePath)
    {
        $imgInfo = @getimagesize($filePath);
        if (!$imgInfo) {
            //判断是否是webp的图片
            $handle = fopen($filePath, 'rb');
            $sizeArr = fread($handle, 30);
            fclose($handle);
            $arr = unpack('C12/S9n', $sizeArr);
            $filetype = chr($arr[9]) . chr($arr[10]) . chr($arr[11]) . chr($arr[12]);
            if ($filetype == 'WEBP') {
                $imgInfo[0] = $arr['n8'];
                $imgInfo[1] = $arr['n9'];
                $imgInfo[2] = 'webp';
            }
        }
        return $filePath;
    }
}