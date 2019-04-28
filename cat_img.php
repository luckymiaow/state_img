<?php
/**
 * @Author: luckymiaow.com
 * @Date:   2019-4-26
 * @Email:  162493361@qq.com
 * @Filename: img.php
 * @Last modified by:   luckymiaow.com
 * @Last modified time: 2019-4-26T10:29:31+08:00
 */

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers:Authorization');
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Cache-Control,Authorization");
date_default_timezone_set('Asia/Shanghai');
$img = new CatImg();
$img->find();
class CatImg
{
	    public function find()
		{
            $page = 1;
            if (isset($_GET['page']) )$page = $_GET['page'];
			$name_arr = $this->my_scandir();
            $name_arr = $this->page_array(20,$page,$name_arr);
			echo json_encode($name_arr);
			die();
		}
	
	private function my_scandir()
    {
        //返回所有文件名
        $handler = opendir('img');
        while( ($filename = readdir($handler)) !== false ) {
            //3、目录下都会有两个文件，名字为’.'和‘..’，不要对他们进行操作
            if($filename != "." && $filename != ".."){
                if (preg_match("/(gif|jpg|png)$/",$filename)) {
                    $img_name[] = "../img/".$filename;
                }
                //4、进行处理
                //这里简单的用echo来输出文件名
            }
        }
        //5、关闭目录
        closedir($handler);
        return $img_name;
    }
    /**
     * 数组分页
     * @param   $count      每页多少条
     * @param   $page       当前页
     * @param   $array      数组
     * @param   $order      顺序
     */
    private  function page_array($count,$page,$array,$order=1){//数组分页
        if (empty($count))$count=15;
        $page=(empty($page))?'1':$page; #判断当前页面是否为空 如果为空就表示为第一页面
        $start=($page-1)*$count; #计算每次分页的开始位置
        if($order==1){
            $array=array_reverse($array);
        }
        $pagedata=array();
        $pagedata=array_slice($array,$start,$count);    //分隔数组
        $res= [
            'listArr'=>$pagedata,
            'total'=>count($array),
            'curr'=> $page,
            'row'   => $count,
            // 最后一页
            'last'  => ceil(count($array)/$count),
        ];

        return $res;  #返回查询数据
    }
	
}