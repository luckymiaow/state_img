<?php
/**
 * @Author: luckymiaow.com
 * @Date:   2019-4-26
 * @Email:  162493361@qq.com
 * @Filename: img.php
 * @Last modified by:   luckymiaow.com
 * @Last modified time: 2019-4-26T10:29:31+08:00
 */
date_default_timezone_set('Asia/Shanghai');
$img = new img();
$img->imgs();
class img
{

    public function imgs()
    {
        $link='cache';
        if(isset($_GET['link']) && $_GET['link']!=="")$link = $_GET['link'];
        $key =$_SERVER['REMOTE_ADDR'];
        $data = json_decode($this->redis($key,'get',$link),true);
        if (empty($data)){
            $data = $this->my_scandir();
            shuffle($data);
        }
        $image_file = 'img/'.$data[0];
        unset($data[0]);
        $res =  $this->redis($key,'set',$link,json_encode(array_values($data)));
        $this->openput($image_file);
        // $this->curl();
        die;

    }

    private function  openput($url)
    {
        //输出图片
        $info = getimagesize($url);
        $imgExt = image_type_to_extension($info[2], false);  //获取文件后缀
        $fun = "imagecreatefrom{$imgExt}";
        $imgInfo = $fun($url);                     //1.由文件或 URL 创建一个新图象。如:imagecreatefrompng ( string $filename )
        //$mime = $info['mime'];
        $mime = image_type_to_mime_type(exif_imagetype($url)); //获取图片的 MIME 类型
        header('Content-Type:'.$mime);
        $quality = 100;
        if($imgExt == 'png') $quality = 9;        //输出质量,JPEG格式(0-100),PNG格式(0-9)
        if (filesize($url)>30000000){
            $quality = 60;
            if($imgExt == 'png') $quality = 5;        //输出质量,JPEG格式(0-100),PNG格式(0-9)
        }
        $getImgInfo = "image{$imgExt}";
        $getImgInfo($imgInfo, null, $quality);    //2.将图像输出到浏览器或文件。如: imagepng ( resource $image )
        imagedestroy($imgInfo);

    }

    private function redis($key='',$type='get',$link='redis',$content=[])
    {
        //使用文件缓存
        if ($link=='cache')return $this->cache($key,$type,$content);
        //链接redis
        $redis = new Redis();
        $conn =  $redis->connect('127.0.0.1', 6379);
        switch ($type)
        {
            case 'set';
                $res = $redis->set($key, $content);
                return $res;
                break;
            case 'get';
                $res = $redis->get($key);
                return $res;
                break;
            default;
                return false;
                break;
        }
    }

    private function cache($key='',$type='get',$content=[])
    {
        if($type=='set') {
            $data = $content;
            $numbytes = file_put_contents('json/'.$key.'.json', $data); //如果文件不存在创建文件，并写入内容
            if(!$numbytes){
                echo '写入失败或者没有权限，注意检查';
                die();
            }
            return 1;
        }else{
            if (file_exists('json/'.$key.'.json')) return file_get_contents('json/'.$key.'.json');
                return null;
        }

    }
    private function my_scandir()
    {
        //返回所有文件名
        $handler = opendir('img');
        while( ($filename = readdir($handler)) !== false ) {
            //3、目录下都会有两个文件，名字为’.'和‘..’，不要对他们进行操作
            if($filename != "." && $filename != ".."){
                if (preg_match("/(gif|jpg|png)$/",$filename)) {
                    $img_name[] = $filename;
                }
                //4、进行处理
                //这里简单的用echo来输出文件名
            }
        }
        //5、关闭目录
        closedir($handler);
        return $img_name;
    }

    private  function curl()
    {
        // 1. 初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, 'www.dmoe.cc/random.php?return=json');
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        $data_json = json_decode($data,true);
        //return $data_json['imgurl'];
        $this->file_exists_S3($data_json['imgurl']);
    }
    private function file_exists_S3($url)
    {
        $state = @file_get_contents($url,0,null,0,1);//获取网络资源的字符内容
        //https://ws3.sinaimg.cn/large/7f0c40d4gy1fqbfh91nf9j21hc0u0q62.jpg
        $rule = "#large\/(.+)#";
        preg_match($rule,$url,$img_name);
        if($state){

            $filename =$img_name[1];//文件名称生成

            ob_start();//打开输出

            readfile($url);//输出图片文件

            $img = ob_get_contents();//得到浏览器输出

            ob_end_clean();//清除输出并关闭

            $size = strlen($img);//得到图片大小

            $fp2 = @fopen('img/'.$filename, "a");

            fwrite($fp2, $img);//向当前目录写入图片文件，并重新命名

            fclose($fp2);

            return 1;

        } else{

            return 0;

        }

    }

}
