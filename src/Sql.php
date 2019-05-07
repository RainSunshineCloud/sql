<?php
namespace RainSunshineCloud;

class Sql
{
    protected $select = '';
    protected $table = null;
    protected $prefix = null;
    protected $where = '';
    protected $join = '';
    protected $order = '';
    protected $group = '';
    protected $having = '';
    protected $limit = '';
    protected $alise = '';
    protected $prepare = true;
    protected $prepareData = [];
    protected $incr_data = [];
    protected $switch = 'where';
    private static $number = 1;
    private $varivable_num = 1;
    protected function __construct() {}

    /**
     *join 表
     * @param mix    $join_table 
     * @param string $join 连接方式，默认左连接
     */
    protected function join( $join_table,$on,$alise = '',$join = 'left')
    {
        $join = strtoupper($join);
        if ($join_table instanceof sql && !empty($alise)) {
            $join_table = sprintf('(%s) AS `%s`',$join_table -> get(),$alise);
        } elseif (!empty($alise)) {
            $join_table = sprintf('`%s` AS `%s`',$join_table,$alise);
        } else {
            $join_table = sprintf('`%s`',$join_table);
        }
        $on = explode('=',$on);
        $on[0] = $this->getField($on[0]);
        $on[1] = $this->getField($on[1]);
        $this->join .= sprintf(' %s JOIN %s ON %s = %s',$join,$join_table,$on[0],$on[1]);
    }

    protected function alise (string $alise) 
    {
        $this->alise = sprintf(' AS `%s`', $alise);
    }

    /**
     * 简单构造
     * @param  [type] $field  [description]
     * @param  [type] $op     [description]
     * @param  [type] $value  [description]
     * @return [type]         [description]
     */
    protected function simpleBuilder($field,$op = null,$value = null)
    {
        $switch = $this->switch;
        if (is_array($field) ) { //数组
            foreach ($field as $k => $v) {
                $this->builder($k,'=',$v);
            }
        } else if ($field instanceOf \Closure){//闭包
            $this->builder('','FUNC',$field,'','and',$switch);
        }  else if ( is_string($field) && !is_null($op) && ($value instanceof Sql) ) { //子查询
            $this->builder($field,'child',$value,$op,'and',$switch);
        } else if (is_string($field) && !is_null($op) && !is_null($value) && !is_object($value)) { // 普通操作
            $this->builder($field,$op,$value,'','and',$switch);
        } else if ( is_string($field) && (is_array($op) || is_null($op))) {//原生Sql
            $this->builder($field,'Sql',$op,'','and',$switch);
        } else if (is_string($field) && (is_string($op) || is_numeric($op))) {
             $this->builder($field,'=',$op,'','and',$switch);
        } else { //错误
            throw new SqlException($switch.'参数错误',1003);
        } 
    }

    /**
     * 简单构造
     * @param  [type] $field  [description]
     * @param  [type] $op     [description]
     * @param  [type] $value  [description]
     * @return [type]         [description]
     */
    protected function simpleBuilderOr($field,$op = null,$value = null)
    {

        $switch = $this->switch;
        if (is_array($field) ) { //数组
            foreach ($field as $k => $v) {
                $this->builder($k,'=',$v,'','or');
            }
        } else if ($field instanceof \Closure){//闭包
            $this->builder('','FUNC',$field,'','or',$where);
        }  else if ( is_string($field) && !is_null($op) && ($value instanceof Sql) ) { //子查询
            $this->builder($field,'child',$value,$op,'or',$where);
        } else if (is_string($field) && !is_null($op) && !is_null($value) && !is_object($value)) { // 普通操作
            
            $this->builder($field,$op,$value,'','or',$where);
        } else if ( is_string($field) && (is_array($op) || is_null($op))) {//原生Sql
            $this->builder($field,'Sql',$op,'','or',$where);
        } else if (is_string($field) &&  (is_string($op) || is_numeric($op))) {
             $this->builder($field,'=',$op,'','or',$where);
        } else { //错误
            throw new SqlException($switch.'参数错误',1003);
        } 
    }

    /**
     * where 或 having 构造方法
     * @param  string $field   [字段名]
     * @param  string $op      [操作]
     * @param  [type] $values  [值]
     * @param  string $childOp [子查询操作]
     * @return 
     */
    protected function builder(string $field,string $op,$values,string $childOp = '=', string $logic = "and", string $switch = "where")
    { 
        $logic = trim(strtoupper($logic));
        $op = trim(strtoupper($op));
        //where 条件
        switch ($op) {
            case 'CHILD': //子查询
                $sql = $this->builderChild($field,$childOp,$values);
                break;
            case 'FUNC': //闭包
                return $values($this);
            case 'IN': //in
                $sql = $this->builderIn($field,$values);
                break;
            case 'NOTIN':
                $sql = $this->builderIn($field,$values,true);
                break;
            case 'BETWEEN'://between
                $sql = $this->builderBetween($field,$values);
                break;
            case "NOTBETWEEN":
                $sql = $this->builderBetween($field,$values,true);
                break;
            case 'IS'://is
                $field = $this->getField($field);
                $sql = sprintf('%s %s %s',$field,$op,strtoupper($values));
                break;
            case 'SQL'://原生sql
                if (is_null($values)) {
                    $values = [];
                }
                $sql = $this->builderSql($field,$values);
                break;
            default:
                $sql = $this->builderDefault($field,$op,$values);
        }

        switch ($switch) {
            case 'where':
                $logic = empty($this->where) ? 'WHERE' : $logic;
                $this->where .= sprintf(' %s %s ',$logic,$sql);
                break;
            case 'having':
                $logic = empty($this->having) ? 'HAVING' : $logic;
                $this->having .= sprintf(' %s %s ',$logic,$sql);
                break;
        }   
    }

    private function builderSql(string $field,array $values)
    {

        if (array_intersect_key($this->prepareData,$values)) {
            throw new SqlException('where 参数错误,仅支持：方法',1003);
        }

        $this->prepareData += $values;
        return $field;
    }

    private function builderDefault(string $field,$op,$values)
    {
        if (!is_string($values) && !is_numeric($values)) {
            throw new SqlException('不是字符串或数值',1006);
        }

        $values = $this->prepare($values,$field);
        $field = $this->getField($field);
        $sql = sprintf('%s %s %s',$field,$op,$values);
        return $sql;
    }

    /**
     * in构造方法
     * @param  string $field  [字段名]
     * @param  [array | string] $values [值]
     */
    private function builderIn(string $field,$values,bool $not = false)
    {
        if (is_string($values)) {
            $values = explode(',',$values);
        } else if (!is_array($values)) {
            throw new SqlException('In操作必须是字符串或数组',1004);
        }

        $values = $this->prepare($values,$field,'in');
        $field = $this->getField($field);
        $op = $not ? "NOT IN" : "IN";
        return sprintf('%s %s (%s)',$field,$op,$values);
    }

    /**
     * between构造方法
     * @param  string $field  [字段名]
     * @param  array  $values [值]
     * @return [type]         [description]
     */
    private function builderBetween(string $field,array $values,bool $not = false) 
    {
        if (count($values) >= 2) {
            $values[0] = $this->prepare($values[0],$field);
            $values[1] = $this->prepare($values[1],$field);
            $field = $this->getField([$field]);
            $op = $not ? 'NOTBETWEEN' : 'BETWEEN';
            return sprintf('%s BETWEEN %s AND %s',$field,$values[0],$values[1]);
        } else {
            throw new SqlException('Between 参数错误',1005);
        }
    }

    /**
     * where 子查询构造方法
     * @param  string $field [字段名]
     * @param  string $op    [操作]
     * @param  [Sql]  $child [子查询对象]
     */
    private function builderChild(string $field,string $op,Sql $child) 
    {
        $this->prepareData += $child->getPrepareData();//获取子查询的值
        $op = trim(strtoupper($op));
        $field = $this->getField($field);
        if (! $child instanceof Sql) {
             throw new SqlException('子查询值必须是Sql对象',1015);
        }
        if (in_array($op,['EXISTS','NOTEXISTS'])) {
            return sprintf('%s (%s)',$op,$child->get());
        }
        return sprintf('%s %s (%s)',$field,$op,$child->get());
    }

    /**
     * 字段名
     * @param  array|string $select 
     * @return 
     */
    protected  function field($select = [],$is_origin = false)
    {
        if (!is_array($select) && !is_string($select) ) {
            throw new SqlException('select错误','1007');
        }

        $select = str_replace("'", '"', $select);
        if (!empty($this->select)) {
            $this->select .= ',';
        }
        if ($is_origin == true && is_string($select)) {
            $this->select = $select;
        } else if ($select == []) {
            $this->select = '*';
        } else {
            $this->select .= $this->getField($select);
        }
    }


    /**
     *select Sql
     *
     * @return string
     */
    protected function get()
    {
        if (!$this->select) {
            throw new SqlException('field 未定义',1008);
        };

        if (!$this->table) {
            throw new SqlException('table未定义',1008);
        }

        $sql = sprintf('SELECT %s FROM %s%s%s%s%s%s%s%s',
            $this->select,
            $this->table,
            $this->alise,
            $this->join,
            $this->where,
            $this->group,
            $this->having,
            $this->order,
            $this->limit);
        return trim($sql);
    }

    /**
     *表名
     */
    protected function table($table,$alise = null)
    {
       
        if ($table instanceof Sql) { //子查询
            $this->table = sprintf('(%s)',$table->get());
        } else {
            if (!empty($this->prefix)) {
                $table = $this->prefix.$table;
            }
            $this->table = sprintf('`%s`',$table);
        }
        
        if (isset($alise)) {
            $this->alise($alise);
        }
    }

    /**
     *表前缀
     * User: RyanWu
     * Date: 2018/6/1
     * Time: 20:18
     *
     * @param $prefix
     */
    protected function prefix($prefix)
    {
        if (!empty($this->table)) {
            throw new SQlException('前缀必须设置在表之前');
        }
        $this->prefix = $prefix;
    }

    /**
     *Sql update
     * User: qing
     * Date: 2018/6/2
     * Time: 下午8:21
     *
     * @param array  $data 要更新的数据
     * @param array  $func 处理字段的匿名函数
     */
    protected function update($data,$func = '')
    {
        $sql = '';

        if (array_intersect_key($data,$this->incr_data)) {
            throw new SqlException("已设置为incr_data",1016);
        }

        if (!$this->table) {
            throw new SqlException('table未定义',1008);
        }

        if (!$this->where) {
            throw new SqlException('where未定义',1017);
        }

        foreach ($data as $k=>$v) {
            if ($func instanceof \Closure) {
                $v = $func($k,$v);
            }
           $sql .= sprintf('%s = %s,',$this->getField($k),$this->prepare($v,$k));
        }
        foreach ($this->incr_data as $k=>$v) {
            $key = $this->getField($k);
            $sql .= sprintf('%s = %s + %s',$key,$key,$this->prepare($v,$k));
        }
        $sql = rtrim($sql,',');
        return sprintf('UPDATE %s SET %s%s',$this->table,$sql ,$this->where);
    }

    /**
     * 设置自增
     * @param  [string] $field [字段]
     * @param  [float] $value [值]
     */
    protected function incr(string $field,float $value)
    {
        $this->incr_data[$field] = $value;
    }

     /**
     *Sql delete
     * User: qing
     * Date: 2018/6/2
     * Time: 下午8:21
     */
    protected function delete()
    {

        if (!$this->where) {
            throw new SqlException('where未定义',1017);
        }
        
       return sprintf('DELETE FROM %s%s',$this->table,$this->where);
    }

   /**
    * insert Sql
    * @param  array  $data   二维数组
    * @param  string $func   回调函数处理每个字段值
    * @return [type]        
    */
    protected  function insertAll(array $data,$func = '')
    {
        if ( !is_array($data)|| !($one = reset($data)) || !is_array($one)) {
            throw new SqlException('insert 必须是二维数组',1009);
        }

        if (!$this->table) {
            throw new SqlException('table未定义',1008);
        }

        $sql = '';
        $field = [];
        foreach($data as $v) {
            $tmp = '';
            ksort($v);
            foreach ($v as $kk => $vv) {
                if ($func instanceof \Closure) {
                    $vv = $func($kk,$vv);
                }
                $tmp .= $this->prepare($vv,$kk).',';
            }
            $sql .= sprintf('(%s),',trim($tmp,','));
        }


        $field = array_keys($v);
        $field = $this->getField($field);
        $sql = rtrim($sql,',');
        return sprintf('INSERT INTO %s(%s) VALUES%s',$this->table,$field,$sql);
    }

    /**
     * insert Sql
     * @param  array|object  $data  一维数组
     * @param  string  | array      $func  回调函数 | $data 为 object时insert的字段
     * @return
     */
    protected  function insert($data,$func = '')
    {
        if (!$this->table) {
            throw new SqlException('table未定义',1008);
        }

        if ($data instanceof Sql) {
            $sql = $data->get();
            return sprintf("INSERT INTO %s (%s)",$this->table,$sql);
        } else if (!is_array($data)) {
            throw new SqlException('insert 必须是一维数组',1009);
        } else {
            $field = $this->getField(array_keys($data));
            foreach ($data as $k => $v) {

                if ($func instanceof \Closure) {
                    $v = $func($k,$v);
                }
                $data[$k] = $this->prepare($v,$k);
            }

            return sprintf("INSERT INTO %s(%s) VALUES(%s)",$this->table,$field,join(',',$data));
        }  
    }

    protected function duplicate(array $insert_data,array $update_data = [])
    {
        if (array_intersect_key($update_data,$this->incr_data)) {
            throw new SqlException("已设置为incr_data",1016);
        }
        $field = $this->getField(array_keys($insert_data));
        foreach ($insert_data as $k => $v) {
            $data[$k] = $this->prepare($v,$k);
        }
        $sql = sprintf("INSERT INTO %s(%s) VALUES(%s) ON DUPLICATE KEY UPDATE ",$this->table,$field,join(',',$data));

        foreach ($update_data as $k=>$v) {
            $sql .= sprintf('%s = %s,',$this->getField($k),$this->prepare($v,$k));
        }

        foreach ($this->incr_data as $k=>$v) {
            $key = $this->getField($k);
            $sql .= sprintf('%s = %s + %s',$key,$key,$this->prepare($v,$k));
        }
        $sql = rtrim($sql,',');
        $sql .= $this->where;
        return $sql;
       
    }

     /**
     * replace Sql
     * @param  array|object  $data  一维数组
     * @param  string  | array      $func  回调函数 | $data 为 object时insert的字段
     * @return
     */
    protected function replace($data,$func = "")
    {
         if ($data instanceof Sql) {
            $sql = $data->get();
            return sprintf("REPLACE INTO %s (%s)",$this->table,$sql);
        } else if (!is_array($data)) {
            throw new SqlException('REPLACE 必须是一维数组',1009);
        } else {
            $field = $this->getField(array_keys($data));
            foreach ($data as $k => $v) {

                if ($func instanceof \Closure) {
                    $v = $func($v);
                }
                $data[$k] = $this->prepare($v,$k);
            }
            return sprintf("REPLACE INTO %s(%s) VALUES(%s)",$this->table,$field,join(',',$data));
        }
    }

    /**
     *外部统一调用方式
     * User: qing
     * Date: 2018/6/2
     * Time: 下午8:30
     *
     * @param $func
     * @param $arg
     * @return bool|Sql
     */
    public static function __callStatic($func,$arg)
    {

        $class = get_called_class();
        $obj = new $class();
        $obj->preparesignal = sprintf(':%s_',self::$number++);
        if (in_array($func,['where','having'])) {
            $obj->switch = $func;
            $func = 'simpleBuilder';
        } else if ($func == 'whereOr') {
            $obj->switch = 'where';
            $func = 'simpleBuilderOr';
        } else if ($func == 'havingOr') {
            $obj->switch = 'having';
            $func = 'simpleBuilderOr';
        }
        if (method_exists($obj,$func)) {
            call_user_func_array([$obj,$func],$arg);
            return $obj;
        }

        throw new SqlException('Sql 未有该方法',1002);  
    }

    /**
     *外部统一调用方式
     * User: qing
     * Date: 2018/6/2
     * Time: 下午8:31
     *
     * @param $func
     * @param $arg
     * @return $this|mixed
     */
    public function __call($func,$arg)
    {
        if (in_array($func,['where','having'])) {
            $this->switch = $func;
            $func = 'simpleBuilder';
        } else if ($func == 'whereOr') {
            $this->switch = 'where';
            $func = 'simpleBuilderOr';
        } else if ($func == 'havingOr') {
            $this->switch = 'having';
            $func = 'simpleBuilderOr';
        }

        if (method_exists(get_called_class(),$func)) {
            $sql = call_user_func_array([$this,$func],$arg);
            if ($sql) {
                $data = $this->getPrepareData();
                $this->clear();
                return ['sql'=>$sql,'data'=>$data];
            }
            return $this;
        } else {
            array_unshift($arg,$func);
            if (count($arg) < 2) {
                throw new SqlException("使用数据库函数至少必须有三个参数",1017);
            }
            call_user_func_array([$this,'func'],$arg);
            return $this;
        }
    }

    /**
     *群组，直接传
     * User: qing
     * Date: 2018/6/2
     * Time: 下午8:31
     *
     * @param $group string 传入字段
     */
    protected  function group($group)
    {
        if (!$this->group) $this->group = ' GROUP BY ';

        $this->group .= $this->getField($group);
    }

    /**
     * 字段转换
     * @param  [type] $field [description]
     * @return [type]        [description]
     */
    private function getField($field)
    {
        if (is_string($field)) {
            if (strpos($field,',')) {
                $fieldArray = explode(',',$field);
            } else {
                $fieldArray = [$field];
            }
        }else if (is_array($field)) {
            $fieldArray = $field;
        } else {
            throw new SqlException('field 错误',1010);
        }


        $list = [];
        foreach ($fieldArray as $v) {
            $v = trim($v);
            
            //获取别名
            if (strpos($v,' ')) {
                $tmp = explode(' ',$v);
                $v = trim(reset($tmp));
                $alise = end($tmp);
            }
 
            //获取表名
            if (strpos($v,'.')) {
                $tmp = explode('.',$v);
                if ($tmp[1] == '*') {
                    $res = sprintf('`%s`.%s',trim($tmp[0]),trim($tmp[1]));
                } else {
                    $res = sprintf('`%s`.`%s`',trim($tmp[0]),trim($tmp[1]));
                }
            } else {
                if ($v== '*') {
                    $res = sprintf('%s',trim($v));
                } else {
                    $res = sprintf('`%s`',trim($v));
                }
            }

            //添加别名
            if (isset($alise)) {
                $list[] = sprintf('%s AS `%s`',$res , $alise);
                unset($alise);
            } else {
                $list[] = $res;
            }

        }


        return join(',',$list);
    }

    /**
     * 排序
     * @param  [type] $order [description]
     * @return [type]        [description]
     */
    protected  function order($order)
    {
        if (!$this->order) $this->order = ' ORDER BY ';
        $tmp = [];
        $res = explode(' ',$order);
        $tmp[0] = array_shift($res);
        $tmp[1] = array_pop($res);
        $tmp[0] = $this->getField($tmp[0]);
        if (!empty($tmp[1])) {
            $tmp[1] = strtoupper($tmp[1]);
        }

        $order = join(' ',$tmp);
        $this->order .= $order;
    }

    /**
     * [limit description]
     * @param  [type] $pagenum [description]
     * @param  string $offset  [description]
     * @return [type]          [description]
     */
    protected  function limit($pagenum,$offset = '')
    {

        if (is_array($pagenum)) $limit = join($pagenum,',');
        else if ($offset) $limit = $offset .','.$pagenum;
        else $limit = $pagenum;
        $this->limit .= ' LIMIT '.$limit;
    }



    /**
     *其他传入的函数，会放置在select 里面
     * User: qing
     * Date: 2018/6/2
     * Time: 下午8:35
     *
     * @param $func
     * @param $arg
     */
    protected function func(string $func,string $arg ,string $other ,$alise= '')
    {
        $func = $this->getFunc($func,$arg,$other);
        if ($this->select) {
            $this->select .= ','.$func;
        } else {
            $this->select .= $func;
        }  
    }

    /**
     * 构造函数
     * @return [type] [description]
     */
    private function getFunc($func,$arg,$other)
    {
        $arg = $this->getField($arg);
        switch ($func) {
            case 'from_unixtime':
                return $func = sprintf('%s(%s,"%s") AS  `%s`',$func,$arg,$other,$alise);
            case 'count':
            case 'max':
            case 'min':
            case 'average':
               return $func = sprintf('%s(%s) AS `%s`',$func,$arg,$other);
            default:
                 throw new SqlException("未有{$func}函数",1020);
            
        }
    }

    /**
     *返回字符串后清空相关数据，用于重用对象
     * User: qing
     * Date: 2018/6/2
     * Time: 下午8:59
     */
    protected function clear()
    {
         $this->select = '';
         $this->where = '';
         $this->join = '';
         $this->order = '';
         $this->group = '';
         $this->having = '';
         $this->limit = '';
         $this->incr_data = [];
         $this->prepareData = [];
    }

    /**
     * 转换预查询数据
     * @param  string $data [description]
     * @return [type]       [description]
     */
    protected function prepare($data,$key,$special = '')
    {
        if ( $special != 'in' && !is_numeric($data) && !is_string($data)) {
            throw new SqlException('转换预查询数据格式错误',1019);
        }

        if (!$this->prepare) {

            if (is_string($data)) {
                $data = '"'.$data.'"';
            }

            if ($special == 'in') {
                $data = join(',',$data);
            }

            return $data;
        }

        $key = str_replace('.', '_', $key);
        if ($special == 'in') {
            $all_key = [];
            foreach ($data as $k => $v) {
                $now_key = $this->preparesignal.$k.$key.$this->varivable_num++;
                $this->prepareData[$now_key] = $v;
                $all_key[] = $now_key;
            }

            return join(',',$all_key);
        }

        $now_key = $this->preparesignal.$key.$this->varivable_num++;

        $this->prepareData[$now_key] = $data;
        return $now_key;

    }

    /**
     * 获取预查询数据
     * @return [type] [description]
     */
    protected function getPrepareData()
    {
        return $this->prepareData;
    }

    /**
     * 设置预查询
     * @param boolean $p [description]
     */
    protected function setPrepare(bool $p = false)
    {
        $this->prepare = $p;
    }
}

Class SqlException extends \Exception {}
