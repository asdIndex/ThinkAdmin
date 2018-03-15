<?php

namespace app\afun\controller;

use controller\BasicAdmin;
use think\App;
use think\Db;


class Hosts extends BasicAdmin
{

    /**
     * 后台框架布局
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $db = Db::table('afun_computer')->field(true);
        
        return parent::_list($db);
        
        //$this->assign('list',$data);
        //var_dump($data);
        
        //return $this->fetch();
    }

    
    public function insql(){
        $data = array();  $row = array();
        
        for ($i=8000;$i<10000;$i++){
            $row['hostmac'] = 'abcdabcd'.$i;
            $row['machine_id'] = '123abcd'.$i;
            $row['vm2mac'] = '1234abcd'.$i;
            $row['cpu'] = 'AMD FX(tm)-8300 Eight-Core Processor'.$i;
            $row['ram1_manufacturer'] = 'Kingston'.$i;
            $row['ram2_manufacturer'] = 'Kingston'.$i;
            $row['ram3_manufacturer'] = 'Kingston'.$i;
            $row['ram4_manufacturer'] = 'Kingston'.$i;
            $row['ram1_sn'] = '0398562a'.$i;
            $row['ram2_sn'] = '0398562b'.$i;
            $row['ram3_sn'] = '0398562c'.$i;
            $row['ram4_sn'] = '0398562d'.$i;
            $row['gpu1_id'] = '002,68fe'.$i;
            $row['gpu2_id'] = '002,68fe'.$i;
            $row['hd1_model'] = 'ST1000DM003-1ER'.$i;
            $row['hd2_model'] = 'ST1000DM003-1ER'.$i;
            $row['hd1_sn'] = 'S4Y11PER'.$i;
            $row['hd2_sn'] = 'S4Y11PER'.$i;
            
            //$data[] = $row;
            Db::table('afun_computer')->insert($row);
        }
        
        //Db::table('afun_computer')->insertAll($data);
        var_dump($row); exit();
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

    /**
     * 修改密码
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function pass()
    {
        if (intval($this->request->request('id')) !== intval(session('user.id'))) {
            $this->error('只能修改当前用户的密码！');
        }
        if ($this->request->isGet()) {
            $this->assign('verify', true);
            return $this->_form('SystemUser', 'user/pass');
        }
        $data = $this->request->post();
        if ($data['password'] !== $data['repassword']) {
            $this->error('两次输入的密码不一致，请重新输入！');
        }
        $user = Db::name('SystemUser')->where('id', session('user.id'))->find();
        if (md5($data['oldpassword']) !== $user['password']) {
            $this->error('旧密码验证失败，请重新输入！');
        }
    
        $this->error('密码修改失败，请稍候再试！');
    }


}
