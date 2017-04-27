<?php
/**
 * Top Secret
 * Created by PhpStorm.
 * User: xing.zou
 * Date: 2017/4/27
 * Time: 10:50
 */
namespace uploadImg;

use Aws\S3\S3Client;
use Guzzle\Http\Mimetypes;
use Aws\S3\Exception\S3Exception;
use Guzzle\Http\EntityBody;
use uploadImg\common;

class uploadImgToS3
{

    protected static $obj;
    protected $file_host_url;
    protected $bucket;
    protected $key;
    protected $secret;
    protected $s3dir;

    public static function getInstance($s3url, $bucket, $key, $secret, $s3dir = '')
    {
        if (!self::$obj) {
            self::$obj = new uploadImg($s3url, $bucket, $key, $secret, $s3dir);
        }
        return self::$obj;
    }

    public function __construct($s3url, $bucket, $key, $secret, $s3dir = '')
    {
        $this->file_host_url = $s3url;
        $this->bucket = $bucket;
        $this->key = $key;
        $this->secret = $secret;
        $this->s3dir = $s3dir;
    }

    /*
     * 图片类型，是图片URL还是上传的文件
     * $type  1:表示是图片URL 2：表示上传图片文件
     */
    public function uploadFile($type, $imgUrl = '', $fileInputName = '', $isRename = true)
    {
        $filePath = $type == 1 ? $imgUrl : (($fileInputName && !empty($_FILES[$fileInputName]['tmp_name'])) ? $_FILES[$fileInputName]['tmp_name'] : '');
        $filename = $type == 1 ? basename($imgUrl) : (($fileInputName && !empty($_FILES[$fileInputName]['name'])) ? $_FILES[$fileInputName]['name'] : '');

        if (!$filePath) {
            return array('code' => 1, 'msg' => '请选择要上传的文件!');
        }
        $imgInfo = common::getImgInfo($filePath);
        if (!$imgInfo) {
            return array('code' => 1, 'msg' => '你选择的图片格式不符合要求!');
        }

        $extensionArr = [1 => 'gif', 2 => 'jpg', 3 => 'png', 4 => 'swf', 5 => 'psd', 6 => 'bmp', 7 => 'tiff', 8 => 'tiff', 9 => 'jpc', 10 => 'jp2', 11 => 'jpx', 12 => 'jb2', 13 => 'swc', 14 => 'iff', 15 => 'wemp', 16 => 'xbm', 'webp' => 'webp'];
        $extension = $extensionArr[$imgInfo[2]];
        $result = $this->putFileToAws($filename, $filePath, $extension, $isRename);
        if (!$result || $result['code'] != 0) {
            return array('code' => 1, 'msg' => '上传图片到s3失败，' . $result['msg']);
        }
        return ['code' => 0, 'imgurl' => $result['imgUrl'], 'width' => $imgInfo[0], 'height' => $imgInfo[1]];
    }

    public function putFileToAws($filename, $filePath, $extension, $isRename = true)
    {
        $bucket = $this->bucket;
        $config = array(
            'key' => $this->key,
            'secret' => $this->secret,
        );
        $newName = $isRename ? sha1(time() . $filename) . '.' . $extension : $filename;
        //$key = 'adn/image/' . $newName;
        $key = $this->s3dir . $newName;
        try {
            $mimetypesModel = Mimetypes::getInstance();
            $s3Client = S3Client::factory($config);
            $result = $s3Client->putObject(array(
                'Bucket' => $bucket,
                'Key' => 'public/' . $key,
                'Body' => EntityBody::factory(fopen($filePath, 'r')),
                'ContentType' => $mimetypesModel->fromFilename($filename),
                'StorageClass' => 'STANDARD',
                'ACL' => 'public-read'
            ));
            //We can poll ther objext until it is accessible
            $s3Client->waitUntilObjectExists(array(
                'Bucket' => $bucket,
                'Key' => 'public/' . $key
            ));
            $imgUrl = $this->file_host_url . '/' . $key;

            return array('code' => 0, 'imgUrl' => $imgUrl,);
        } catch (S3Exception $e) {
            return array('code' => 1, 'msg' => $e->getMessage());
        }
    }
}