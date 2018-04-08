<?php

namespace app\afun\controller;

use controller\BasicAdmin;
use think\App;
use think\Db;
use service\IPService;
use service\DataService;
use service\LogService;
use think\Request;

class Hosts extends BasicAdmin
{

    /**
     * 指定当前数据表
     * @var string
     */
    public $table = 'afun_computer';
    public $status = array(0=>'', 1=>'空闲', 2=>'出租', 3=>'返厂');//, 4=>'锁定');
    
    
    public function index(){
        // 主机列表
        list($this->title, $get) = ['主机展示列表', $this->request->get()];
        $db = Db::table('afun_computer')->field(true)->limit(15);
        
        //!empty($get['hostmac'])&&$get['hostmac'] != 0 ? $db->whereLike('hostmac', "{$get['hostmac']}%") : '' ;
        unset($this->status[0]);
        //$db->limit('15');
        
        $this->assign('status',$this->status);
        $this->assign('title', $this->title);
        return parent::_list($db,false);
        //$res = parent::_list($db,false,false);
        
        /* foreach ($res['list'] as &$v){
            $v['status_text'] = $this->status[$v['status']];
        }
        return $this->fetch('', $res);*/
    } 
    /**
     * 列表数据处理
     * @param array $data
     * @throws \Exception
     */
    protected function _index_data_filter(&$data){
        foreach ($data as &$vo) {
            $vo['status_text'] = $this->status[$vo['status']];
        }
    }
    /**
     * 可出租
     * @return string*/
    public function free(){
        // 主机列表
        list($this->title, $get) = ['可出租主机列表', $this->request->get()];
        //$db = Db::table('afun_computer')->field(true)->where('status', 1)->limit(15);
        $db = Db::table('afun_pc')->field(true)->where('status', 1)->limit(15);
        !empty($get['hostmac']) ? $db->whereLike('hostmac', "{$get['hostmac']}%") : '' ;
        unset($this->status[0]);
        
        $this->assign('status',$this->status);
        $this->assign('title', $this->title);
        //return parent::_list($db);
        return parent::_list($db,false);
    }
    
    public function getRent(){
        $get = $this->request->get();
        
        !empty($get['id']) ? '' : $get = ['id'=>''];
        $post = $this->request->post();
        if(!empty($post['company'])&&!empty($post['num'])){
            $h = model('hostsModel');
            $res = $h->rent($post);
            
            //$this->success1("状态修改成功!",'');
            echo '<script>alert("设置成功!");window.parent.location.reload();</script>';
        }
        $this->assign('get', $get);
        return $this->fetch();
    }
    
    /**
     * 出租中
     * */
    public function rent(){
        // 主机列表
        list($this->title, $get) = ['出租中主机列表', $this->request->get()];
        $db = Db::table('afun_pc')->field(true)->where('status', 'between', '2,3');//->limit(15);
        
        !empty($get['hostmac']) ? $db->whereLike('hostmac', "{$get['hostmac']}%") : '' ;
        !empty($get['company']) ? $db->whereLike('rent_company', "%{$get['company']}%") : '' ;
        
        unset($this->status[0]);
    
        $this->assign('status',$this->status);
        $this->assign('title', $this->title);
        return parent::_list($db);
    } 
    protected function _rent_data_filter(&$data){
        foreach ($data as &$vo) {
            $vo['status_text'] = $this->status[$vo['status']];
        }
    }
    public function getFree(){
        $post = $this->request->post();
        $id = explode(',', $post['id']);
        
        $res = Db::table('afun_pc')->field('hostmac,UNIX_TIMESTAMP(rent_start) stime')
        ->where('hostmac','in',$post['id'])->select();
        
        $r = array_column($res , 'stime', 'hostmac');
        $t = time();
        
        foreach ($id as $v){
            $num = 0;
            if($r[$v] != 0){
                $num = ($t - $r[$v])/3600/24;
            }
            /*$sql = Db::table('afun_computer')->where('hostmac', $v)
            ->update(['status'=>1, 'rent_days'=> "`rent_days` + {$num}", 'rent_company'=>'', 'rent_start'=>'', 'rent_end'=>''])
            ->buildsql();*/
            $sql = "update afun_pc set `status`=1,`rent_days`=rent_days+{$num}, `rent_company`='', 
            `rent_start`='1000-01-01', `rent_end`='1000-01-01' where `hostmac` = '{$v}'";
            $return = Db::execute($sql);
            if(!$return){
                $this->error("主机{$v}回收失败！");
            }
        }
        $this->success('回收成功！','');
        //var_dump($sql); exit();
        //$res = $h->rent($post);
    }
    
    
    
    public function alist(){
        $afun = array();
        $afun['cpu'] = config("afun.cpu");
        $afun['ram1_manu'] = config("afun.ram1_manufacturer");
        $afun['ram2_manu'] = config("afun.ram2_manufacturer");
        $afun['ram3_manu'] = config("afun.ram3_manufacturer");
        $afun['ram4_manu'] = config("afun.ram4_manufacturer");
        $afun['ram1_sn'] = config("afun.ram1_sn");
        $afun['ram2_sn'] = config("afun.ram2_sn");
        $afun['ram3_sn'] = config("afun.ram3_sn");
        $afun['ram4_sn'] = config("afun.ram4_sn");
        $afun['gpu1_id'] = config("afun.gpu1_id");
        $afun['gpu2_id'] = config("afun.gpu2_id");
        $afun['hd1_model'] = config("afun.hd1_model");
        $afun['hd2_model'] = config("afun.hd2_model");
        $afun['hd1_sn'] = config("afun.hd1_sn");
        $afun['hd2_sn'] = config("afun.hd2_sn");
        
        
        $data = Db::table('afun_pc')->field(true)->whereor('cpu','<>',$afun['cpu'])
        ->whereor('ram1_manufacturer','<>',$afun['ram1_manu'])->whereor('ram2_manufacturer','<>',$afun['ram2_manu'])
        ->whereor('ram3_manufacturer','<>',$afun['ram3_manu'])->whereor('ram4_manufacturer','<>',$afun['ram4_manu'])
        ->whereor('ram1_sn','<>',$afun['ram1_sn'])->whereor('ram2_sn','<>',$afun['ram2_sn'])
        ->whereor('ram3_sn','<>',$afun['ram3_sn'])->whereor('ram4_sn','<>',$afun['ram4_sn'])
        ->whereor('gpu1_id','<>',$afun['gpu1_id'])
        ->whereor('gpu2_id','<>',$afun['gpu2_id'])
        ->whereor('hd1_model','<>',$afun['hd1_model'])
        ->whereor('hd2_model','<>',$afun['hd2_model'])
        ->whereor('hd1_sn','<>',$afun['hd1_sn'])
        ->whereor('hd2_sn','<>',$afun['hd2_sn']);
        //->cursor()();
        //echo $data; exit();
        /*foreach ($data as $user){
            var_dump($user);
        }*/
        
        $this->assign('afun',$afun);
        return parent::_list($data);
        //var_dump($res);
        //exit();
        
    }
    
    
    
    /**
     * 状态修改操作
     */
    public function getStatus()
    {
        if (DataService::update($this->table)) {
            $post = $this->request->post();
            $action = '修改主机状态';
            $content = '主机'.$post['id'].'的状态修改为'.$this->status[$post['value']];
            LogService::afunWrite($action, $content);
            $this->success("状态修改成功!", '');
        }
        $this->error("状态修改失败, 请稍候再试!");
    }
    
    
    
    public function findMark($mark=34){
        $lenthg = 15;
        //bindec();//二进制转十进制
        $i = $mark;
        $er1 = decbin($i);
        
        $l = strlen($er1);
        if($l < $lenthg){
            $er1 = str_pad($er1, $lenthg,0,STR_PAD_LEFT);//左侧补零
        }
        
        $cpu = str_split($er1);
        
        for($i=0;$i<15;$i++){
            $base[] = '0';
        }
        $res = array_diff($cpu,$base);
        $res = array_keys($res);
        
        return $res;
    }
    
    
    public function map(){
        $data = array();
        $data = Db::table('afun_pc')->field("y, x")
        ->where("`x` <> 0 AND `y` <> 0 ")
        ->select();
        
        $str = '';
        foreach ($data as $v){
            $v['x'] = (float)$v['x'];
            $v['y'] = (float)$v['y'];
            $str .= implode(',',$v).';';
        }     
        $str = '"'.$str.'"';
        unset($data);
        //$str = '116.806640,42.098222;86.264648,29.190532;116.806640,29.190532;86.264648,40.211721;116.806640,40.211721';
        //$str = '"'.$str.'"';
        $this->assign('str',$str);
        
        return $this->fetch();
    }
    
    
    
    
    
    public function insql(){
        $data = array();  $row = array();
        exit();
        for ($i=286101;$i<=151100;$i++){
            $row['hostmac'] = 'abcdabcd'.$i;
            $row['machine_id'] = '12345678a'.$i;
            $row['vm2mac'] = 'abcd1234a'.$i;
            $row['cpu'] = 'AMD FX(tm)-8300 Eight-Core Processor';
            $row['ram1_manufacturer'] = 'Kingston1';
            $row['ram2_manufacturer'] = 'Kingston2';
            $row['ram3_manufacturer'] = 'Kingston3';
            $row['ram4_manufacturer'] = 'Kingston4';
            $row['ram1_sn'] = '03985621';
            $row['ram2_sn'] = '03985622';
            $row['ram3_sn'] = '03985623';
            $row['ram4_sn'] = '03985624';
            $row['gpu1_id'] = '002,68f9';
            $row['gpu2_id'] = '003,68f8';
            $row['hd1_model'] = 'ST1000DM003-1ER1';
            $row['hd2_model'] = 'ST1000DM003-1ER2';
            $row['hd1_sn'] = 'S4Y11PER1';
            $row['hd2_sn'] = 'S4Y11PER2';
            
            //$data[] = $row;
            Db::table('afun_computer')->insert($row);
        }
        
        //echo getcwd();
        
        
        //var_dump(IPService::find($ip)); exit();
        
        for($i=20000;$i<1000;$i++){
            $ip = $this->rand_ip();
            //var_dump(IPService::find($ip));
            
            $add = IPService::find($ip);
            //$json[] = IPService::find($ip);
            
            Db::table('afun_computer')->where("ip != 0 and province = '' ")->limit('1')
            ->data(['province'=>$add[1], 'city'=>$add[2]])->update();
        }
        //echo $i;
        for($i=1000;$i<761;$i++){//42.0982224112,116.8066406250
            /* $wei = rand(29190532, 40211721);
            $wei = $wei/1000000;
            $jing = rand(86264648, 116806640);
            $jing = $jing/1000000; */
            /* $wei = rand(23483400, 29690587);
            $wei = $wei/1000000;
            $jing = rand(107138671, 118806640);
            $jing = $jing/1000000; */
            $wei = rand(42233333, 50457504);
            $wei = $wei/1000000;
            $jing = rand(94115268, 116265625);
            $jing = $jing/1000000;
            Db::table('afun_computer')->where("`province` <> '' AND ip != 0 and `x` =0 and `y`=0 ")
            ->limit('1')->data(['x'=>$wei, 'y'=>$jing])->update();
        }
        echo $i;
        //var_dump($json);
        //var_dump(json_encode($json));
        //file_put_contents('address10000.json', json_encode($json));
        /*Db::table('afun_computer')->field('hostmac')->chunk(10, function($users) {
            foreach ($users as $user) {
                var_dump($user);
            }
            return false;
        });*/
        //var_dump($row['hostmac']); exit();
    }
    
    
    /**
     * 主机信息显示
     * @return string
     */
    public function main()
    {
        $_version = Db::query('select version() as ver');
        return $this->fetch('', [
            'title'     => '后台首页',
            'think_ver' => App::VERSION,
            'mysql_ver' => array_pop($_version)['ver'],
        ]);
    }


    public function rand_ip(){
        $ip_long = array(
            array('607649792', '608174079'), //36.56.0.0-36.63.255.255
            array('975044608', '977272831'), //58.30.0.0-58.63.255.255
            array('999751680', '999784447'), //59.151.0.0-59.151.127.255
            array('1019346944', '1019478015'), //60.194.0.0-60.195.255.255
            array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
            array('1947009024', '1947074559'), //116.13.0.0-116.13.255.255
            array('1987051520', '1988034559'), //118.112.0.0-118.126.255.255
            array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 14);
        $huoduan_ip= long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
        //$huoduan_ip= (mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
        return $huoduan_ip;
    }
    
    
    public function switchtable(){
        
    }


}
