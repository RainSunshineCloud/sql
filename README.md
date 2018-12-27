# sql

### 这是一个Sql的封装类

- 通过setPrepare 判定是否使用预处理
```
<?php 
include "../../../autoload.php";
use RainSunshineCloud\Sql;
use RainSunshineCloud\SqlException;
try {

    //where 查询
    echo Sql::setPrepare(false)
            ->where(["id"=>1])
            ->count('a.id','total')
            ->where('a.id','is','not null')
            ->where('id',1)
            ->where('create_time','>',1)
            ->where('`id` in (1)')
            ->where('id','in',[1,2])
            ->prefix('syf_')
            ->table("user",'a')
            ->group('id',1)
            ->get()["sql"];

    echo Sql::setPrepare(false)
            ->count('a.id','total')
            ->where('create_time','>',1)
            ->whereOr(["id"=>'sdfjk'])
            ->where('id','>',100)
            ->prefix('syf_')
            ->table("user",'a')
            ->group('id,nickname')
            ->get()["sql"];

    echo Sql::setPrepare(false)
            ->field('*')
            ->where('id','in',[1,2])
            ->prefix('syf_')
            ->table("user",'a')
            ->get()["sql"];

    //子查询
    $model = Sql::setPrepare(false)
            ->table("syf_user",'a')
            ->field('id')
            ->where('create_time','>',1);

    echo Sql::setPrepare(false)
            ->table("syf_user",'a')
            ->count('a.id','total')
            ->where('id','in',$model)
            ->get()['sql'];
    
    //闭包
    echo Sql::setPrepare(false)
            ->table("syf_user",'a')
            ->count('a.id','total')
            ->where(function ($model) {
                $model->where('id',1);
            })
            ->get()['sql'];

    //join
    echo Sql::setPrepare(false)->field('a.nickname,b.nickname as refer_user')->table("syf_user",'a')->join('syf_user','b.id=a.refer_id','b')->get()['sql'];

    //limit
    echo Sql::setPrepare(false)->field('a.nickname,b.nickname as refer_user')
                                ->table("syf_user",'a')
                                ->join('syf_user','b.id=a.refer_id','b')
                                ->order('a.id desc')
                                ->limit(10)
                                ->get()['sql'];
    
    // update
    echo Sql::setPrepare(false)->table('syf_user')
                                ->where('id',1)
                                ->update(['nickname'=>'1','create_time'=>2],function($key,$value){
                                    return $_SERVER['REQUEST_TIME'];
                                })['sql'];
    
    // insert
    echo Sql::setPrepare(false)->table('syf_sms')->insert(['moble'=>12,'code'=> 1,'type'=>1,'content'=>'sdkf','create_time'=>1],function ($key,$value) {
                                                        if ($key == 'create_time') {
                                                            return $_SERVER['REQUEST_TIME'];
                                                        }
                                                        return $value;
                                                    })['sql'];
    
    // insertAll
    echo Sql::setPrepare(false)->table('syf_sms')->insertAll([
            ['moble'=>12,'code'=> 1,'type'=>1,'content'=>'sdkf','create_time'=>1],
            ['moble'=>12,'code'=> 1,'type'=>1,'content'=>'sdkf','create_time'=>1]
        ],function ($key,$value) {
            if ($key == 'code') {
                return 12321;
            }
            return $value;
        })['sql'];
     
    // duplicate
    echo Sql::setPrepare(false)->table('syf_sms')->duplicate(['id'=>1,'moble'=>12,'code'=> 1,'type'=>1,'content'=>'sdkf','create_time'=>1],['moble'=>212])['sql'];
    
    //replace
    echo Sql::setPrepare(false)->table('syf_sms')->replace(['id'=>1,'moble'=>12,'code'=> 1,'type'=>1,'content'=>'sdkf','create_time'=>1])['sql'];


------------------使用预查询----------------------------

    $dbms='mysql';     //数据库类型
    $host=''; //数据库主机名
    $dbName='';    //使用的数据库
    $user='';      //数据库连接用户名
    $pass='';          //对应的密码
    $dsn="$dbms:host=$host;dbname=$dbName";
    $dbh = new \PDO($dsn, $user, $pass); //初始化一个PDO对象

    // //where 查询
    $res = Sql::where(["id"=>1])->count('a.id','total')
                                ->where('a.id','is','not null')
                                ->where('id',1)->where('create_time','>',1)
                                ->where('`id` in (1)')
                                ->where('id','in',[1,2])
                                ->prefix('syf_')
                                ->table("user",'a')
                                ->group('id',1)
                                ->get();


    $res = Sql::where('create_time','>',1)->count('a.id','total')
                                            ->whereOr(["id"=>'sdfjk'])
                                            ->where('id','>',100)
                                            ->prefix('syf_')->table("user",'a')
                                            ->group('id,nickname')
                                            ->get();


    $res = Sql::field('id')->where('a.id','is','not null')
                            ->where('id','in',[1,2])->prefix('syf_')
                            ->table("user",'a')
                            ->get();

    $model = Sql::table("syf_user")->field('id')->where('id',1);
    $res = Sql::table("syf_user")->field('id')->where('id','in',$model)->where('id',1)->get();
    

    // //子查询
    $model = Sql::table("syf_user",'a')->field('id')->where('create_time','>',1);
    $res = Sql::table("syf_user",'a')->count('a.id','total')->where('id','in',$model)->get();
    // //闭包
    $res = Sql::table("syf_user",'a')->count('a.id','total')->where(function ($model) {
        $model->where('id',1);
    })->get();

    //join
    $res = Sql::field('a.nickname,b.nickname as refer_user')->table("syf_user",'a')->join('syf_user','b.id=a.refer_id','b')->get();

    //limit
    $res = Sql::field('a.nickname,b.nickname as refer_user')->table("syf_user",'a')->join('syf_user','b.id=a.refer_id','b')->order('a.id desc')->limit(10)->get();

    // update
    $res = Sql::table('syf_user')->where('id',1)->update(['nickname'=>'1','create_time'=>2],function($key,$value){
        return $_SERVER['REQUEST_TIME'];
    });

    // insert
    $res = Sql::table('syf_sms')->insert(['moble'=>12,'code'=> 1,'type'=>1,'content'=>'sdkf','create_time'=>1],function ($key,$value) {
        if ($key == 'create_time') {
            return $_SERVER['REQUEST_TIME'];
        }
        return $value;
    });

    // insertAll
    $res = Sql::table('syf_sms')->insertAll([
                ['moble'=>12,'code'=> 1,'type'=>1,'content'=>'sdkf','create_time'=>1],
                ['moble'=>12,'code'=> 1,'type'=>1,'content'=>'sdkf','create_time'=>1]
            ],function ($key,$value) {
                if ($key == 'code') {
                    return 12321;
                }
                return $value;
            });

    // duplicate
    $res = Sql::table('syf_sms')->duplicate(['id'=>1,'moble'=>12,'code'=> 1,'type'=>1,'content'=>'sdkf','create_time'=>1],['moble'=>212]);

    // replace
    $res = Sql::table('syf_sms')->replace(['id'=>1,'moble'=>12,'code'=> 1,'type'=>1,'content'=>'sdkf','create_time'=>1]);

    var_dump($res);
    var_dump($model->get());
    $stmt = $dbh->query($res['sql']);
    $stmt = $dbh->prepare($res['sql'],$res['data']);
    $data = $stmt->execute($res['data']);

}catch (SqlException $e) {
    echo $e->getMessage();
}

```