<?php
/**
 * 数据库模型类
 */
 
class Model extends WindEnableValidateModule{
    /**
     * 数据表名
     *
     * @var string
     */
    protected $tableName = null;
    /**
     * 数据表字段信息
     *
     * @var array
     */
    protected $tableFields = array();
    /**
     * 数据表的主键信息
     *
     * @var string
     */
    protected $tablePk = 'id';
    /**
     * model所对应的数据表名的前缀
     *
     * @var string
     */
    protected $tablePrefix = null;
    /**
     * 数据库连接的实例化对象
     *
     * @var $object
     */
    protected $db = null;
    
    /**
     * SQL语句容器，用于存放SQL语句，为SQL语句组装函数提供SQL语句片段的存放空间。
     *
     * @var array
     */
    protected $_sqlParts = array();
    
    /**
     * 构造函数
     *
     * 用于初始化程序运行环境，或对基本变量进行赋值
     * @access public
     * @return boolean
     */
    public function __construct() {
        $this->db =  Wind::getApp()->getWindFactory()->getInstance('db');
    }
    
    /**
     * === 对数据表的查询，更改，写入，删除操作。注：此函数均为数组型数据操作，非面向对象操作
     */
     
     
     /**
     * 对数据表的主键查询
     *
     * 根据主键，获取某个主键的一行信息,主键可以类内设置。默认主键为数据表的物理主键
     * 如果数据表没有主键，可以在model中定义
     * @access public
     * @param int|string|array $pk 所要查询的主键值.注：本参数支持数组，当参数为数组时，可以查询多行数据
     * @param array    $fields     返回数据的有效字段(数据表字段)
     * @return string              所要查询数据信息（单行或多行）
     *
     * @example
     *
     * 实例化model
     * $model = new Demo();
     *
     * $model->find(1024);
     *
     *
     */
    public function find($pk, $fields = null){
        if (!$pk)  return false;
        
        $fields = self::_parseFields($fields);
        
        $sql = 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' WHERE ' . $this->tablePk;
        $sql .= is_array($pk) ? ' IN (\'' . implode('\',\'', $pk) . '\')' : '=\'' . trim($pk) . '\'';

        return $this->db->createStatement($sql)->getOne();
    }
    
    /**
     * 获取数据表的全部数据信息
     *
     * 以主键为中心排序，获取数据表全部数据信息. 注:如果数据表数据量较大时，慎用此函数，以免数据表数据量过大，造成数据库服务器内存溢出,甚至服务器宕机
     * @access public
     * @param array        $fields    返回的数据表字段,默认为全部.即SELECT * FROM table_name
     * @param  boolean    $order_asc  数据排序,若为true时为ASC,为false时为DESC, 默认为DESC
     * @param integer    $offset      limit启起ID
     * @param integer    $count       显示的行数
     * @return array                  数据表数据信息
     */
    public function findAll($fields = null, $order_asc = false, $offset = 0, $count = 200) {

        $fields = self::_parseFields($fields);

        $sql  = 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' ORDER BY ' . $this->tablePk . (($order_asc == true) ? ' ASC' : ' DESC');
        if (!is_null($offset)) {
            $this->_sqlParts['limit'] = '';
            $this->limit($offset, $count);
            $sql .= $this->_sqlParts['limit'];
        }

        return $this->db->createStatement($sql)->queryAll();
    }

    /**
     * 数据表写入操作
     *
     * 向当前model对应的数据表插入数据
     * @access public
     * @param array $data 所要写入的数据内容。注：数据必须为数组
     * @param boolean $return_option 是否返回数据为last insert id
     * @return boolean
     *
     * @example
     *
     * $data = array('name'=>'tommy', 'age'=>23, 'addr'=>'山东'); //注：数组的键值是数据表的字段名
     *
     * $model->insert($data);
     */
    public function insert($data, $return_option = false) {

        //参数分析
        if (!$data || !is_array($data)) {
            return false;
        }

        //TODO:分析验证数据
        /*if(!$this->validate($data)) {
            return false;
        }*/

        //处理数据表字段与数据的对应关系
        $field_array     = array();
        $content_array   = array();

        foreach ($data as $key=>$value) {

            if (in_array($key, $this->tableFields)) {
                $field_array[]   = trim($key);
                $content_array[] = '\'' . $this->db->escape_string(trim($value)) . '\'';
            }
        }

        $field_str      = implode(',', $field_array);
        $content_str    = implode(',', $content_array);

        //清空不必要的内存占用
        unset($field_array);
        unset($content_array);

        $sql_str = 'INSERT INTO ' . $this->tableName . ' (' . $field_str . ') VALUES (' . $content_str . ')';

        $result = $this->db->execute($sql_str);
        //返回last insert id
        if ($result && $return_option === true) {
            return $this->db->lastInsertId();
        }
        return $result;
    }

    /**
     * 数据表更改操作
     *
     * 更改当前model所对应的数据表的数据内容
     * @access public
     * @param array     $data 所要更改的数据内容
     * @param mixed        $where 更改数据所要满足的条件
     * @param string    $$params 数值，对满足更改的条件的进一步补充
     * @return boolean
     *
     * @example
     *
     * $update_array = array('title'=>'This is title', 'content'=>'long long ago...');
     *
     * 法一:
     * $model->update($update_array, 'poste_id=12');
     *
     * 法二:
     * $model->update($update_array, 'name=?', 'tommy');
     *
     * 法三:
     * $model->update($update_array, array('name=?', 'content like ?'), array('tommy', 'doitphp%'));
     */
    public function update($data, $where, $params = null) {

        //参数分析
        if (!is_array($data) || !$data || !$where) {
            return false;
        }

        $content_array = array();
        foreach ($data as $key=>$value) {
            if (in_array($key, $this->tableFields)) {
                $content_array[] = $key . ' = \'' . $this->db->escape_string(trim($value)) . '\'';
            }
        }
        $content_str = implode(',', $content_array);
        unset($content_array);

        //组装SQL语句
        $sql_str = 'UPDATE ' . $this->tableName . ' SET ' . $content_str;

        //条件查询SQL语句的处理
        $this->_parts['where'] = '';
        $this->where($where, $params);
        $sql_str .= $this->_parts['where'];
        unset($this->_parts['where']);

        return $this->db->execute($sql_str);
    }

    /**
     * 数据表删除操作
     *
     * 从当前model所对应的数据表中删除满足一定查询条件的数据内容
     * @access public
     * @param  mixed     $where 所要满足的条件
     * @param  sring    $value 数值，对满足条件的进一步补充
     * @return boolean
     *
     * @example
     *
     * $model = new Model();
     *
     * 法一:
     * $model->delete('post_id=23');
     *
     * 法二:
     * $model->delete('post_id=?', 23);
     *
     * 法三:
     * $model->delete(array('name=?', 'content like ?'), array('tommy', 'doitphp%'));
     */
    public function delete($where, $value = null) {

        //参数分析
        if (!$where) {
            return false;
        }

        $sql_str = 'DELETE FROM ' . $this->tableName;

        //处理SQL的条件查询语句
        $this->_parts['where'] = '';
        $this->where($where, $value);
        $sql_str .= $this->_parts['where'];
        unset($this->_parts['where']);

        return $this->db->execute($sql_str);
    }


    /**
     * 部分：SQL语句 组装（from, where, order by, limit, left join , group by, having, orwhere等）
     */


    /**
     * 组装SQL语句中的From语句
     *
     * 用于处理 SELECT fields FROM table之类的SQL语句部分
     * @access public
     * @param mixed $table_name  所要查询的数据表名，参数支持数组
     * @param mixed $columns       所要查询的数据表字段，参数支持数组，默认为null, 即数据表全部字段
     * @return $this
     *
     * @example
     * $model = new Demo();
     *
     * 法一：
     * $model->from('数据表名', array('id', 'name', 'age'));
     *
     * 法二：
     * $model->from('数据表名'); //当第二参数为空时，默认为全部字段
     *
     * 法三:
     * $model->from(array('p'=>'product', 'i'=>'info'), array('p.id', 'p.name', 'i.value'));
     *
     * 法四：
     * $model->from('list', array('total_num'=>'count(id)')); //第二参数支持使用SQL函数，目前支持，count(),sum(),avg(),max(),min(), distinct()等
     *
     * 法五：
     * $model->from();
     *
     */
    public function from($tableName = null, $fields = null) {

        //对数据表名称的分析
        if (!$tableName) {//未指定表
            $tableName = $this->$tableName;
            
        } elseif ($tableName && is_array($tableName)) {//指定多表

            $option_array = array();
            foreach ($tableName as $key=>$value) {
                //当有数据表前缀时
                if (!empty($this->tablePrefix)) {
                    $option_array[] = is_int($key) ? ' ' . $this->tablePrefix . trim($value) : ' ' . $this->tablePrefix . trim($value) . ' AS ' . $key;
                } else {
                    $option_array[] = is_int($key) ? ' ' . trim($value) : ' ' . trim($value) . ' AS ' . $key;
                }
            }
            $table_str = implode(',', $option_array);
            //清空不必要的内存占用
            unset($option_array);

        } else {//指定单表
            $table_str = (!empty($this->tablePrefix)) ? $this->tablePrefix . trim($tableName) : trim($tableName);
        }

        //组装SQL中的FROM片段
        $this->_sqlParts['from'] = 'SELECT ' . self::_parseFields($fields) . ' FROM ' . $table_str;

        return $this;
    }

    /**
     * 组装SQL语句的WHERE语句
     *
     * 用于处理 WHERE id=3721 诸如此类的SQL语句部分
     * @access public
     * @param string $where WHERE的条件
     * @param string $value 数据参数，一般为字符或字符串
     * @return $this
     *
     * @example
     * $model = new DemoModel();
     *
     * 法一：
     * $model->where('id=23');
     *
     * 法二:
     * $model->where('name=?', 'doitphp');
     *
     * 法三:
     * $model->where(array('class=3', 'age>21', 'no=10057'));
     *
     * 法四:
     * $model->where('class=3')->where('age>21')->where('no=10057');
     *
     * 法五:
     * $model->where(array('id>5', 'name=?'), 'tommy');
     *
     * 法六:
     * $model->where(array('name=?', 'group=?'), 'tommy', 5);
     *
     * 法七:
     * $model->where(array('name=?', 'group=?', 'title like ?'), array('tommy', 5, 'doitphp%'));
     *
     */
    public function where($where, $value = null) {

        return $this->_where($where, $value, true);
    }

    /**
     * 组装SQL语句的ORWHERE语句
     *
     * 用于处理 ORWHERE id=2011 诸如此类的SQL语句部分
     * @access public
     * @param string $where WHERE的条件
     * @param string $value 数据参数，一般为字符或字符串
     * @return $this
     *
     * @example
     * 使用方法与$this->where()类似
     */
    public function orwhere($where, $value = null) {

        return $this->_where($where, $value, false);
    }

    /**
     * 组装SQL语句中WHERE及ORWHERE语句
     *
     * 本方法用来为方法where()及orwhere()提供"配件"
     * @access protected
     * @param string $where SQL中的WHERE语句中的条件.
     * @param string $value 数值（数据表某字段所对应的数据，通常都为字符串或字符）
     * @param boolean $is_where 注:为true时是为where()， 反之 为orwhere()
     * @return $this
     */
    protected function _where($where, $value = null, $is_where = true) {

        //参数分析
        if (!$where) {
            return false;
        }

        //分析参数条件，当参数为数组时
        if (is_array($where)) {
            $where_array = array();
            foreach ($where as $string) {
                $where_array[] = trim($string);
            }

            $where = implode(' AND ', $where_array);
            unset($where_array);
        }

        //当$model->where('name=?', 'tommy');操作时
        if (!is_null($value)) {
            if (!is_array($value)) {
                $value = $this->quote_into($value);
                $where = str_replace(array('?', '%s', "'%s'", '"%s"'), $value, $where);
            } else {
                $where = $this->prepare($where, $value);
            }
        }

        //处理where或orwhere.
        if ($is_where == true) {
            $this->_sqlParts['where']        = ($this->_sqlParts['where']) ? $this->_sqlParts['where'] . ' AND ' . $where : ' WHERE ' . $where;
        } else {
            $this->_sqlParts['or_where']     = ($this->_sqlParts['or_where']) ? $this->_sqlParts['or_where'] . ' AND ' . $where : ' OR ' . $where;
        }

        return $this;
    }

    /**
     * 组装SQL语句排序(ORDER BY)语句
     *
     * 用于处理 ORDER BY post_id ASC 诸如之类的SQL语句部分
     * @access public
     * @param mixed $string 排序条件。注：本参数支持数组
     * @return $this
     */
    public function order($string) {

        //参数分析
        if (!$string) {
            return false;
        }

        //当参数为数组时
        if (is_array($string)) {
            $order_array = array();
            foreach ($string as $lines) {
                $order_array[] = trim($lines);
            }
            $string = implode(',', $order_array);
            unset($order_array);
        }

        $string = trim($string);
        $this->_sqlParts['order'] = ($this->_sqlParts['order']) ? $this->_sqlParts['order'] . ', ' . $string : ' ORDER BY ' . $string;

        return $this;
    }

    /**
     * 组装SQL语句LIMIT语句
     *
     * limit(10,20)用于处理LIMIT 10, 20之类的SQL语句部分
     * @access public
     * @param int $offset 启始id, 注:参数为整形
     * @param int $count  显示的行数
     * @return $this
     */
    public function limit($offset, $count = null) {

        //参数分析
        $offset     = (int)$offset;
        $count      = (int)$count;

        $limit_str = !empty($count) ? $offset . ', ' . $count : $offset;
        $this->_sqlParts['limit'] = ' LIMIT ' . $limit_str;

        return $this;
    }

    /**
     * 组装SQL语句的LIMIT语句
     *
     * 注:本方法与$this->limit()功能相类，区别在于:本方法便于分页,参数不同
     * @access public
     * @param int $page     当前的页数
     * @param int $count     每页显示的数据行数
     * @return $this
     */
    public function page_limit($page, $count) {

        $start_id = (int)$count * ((int)$page - 1);

        return $this->limit($start_id, $count);
    }

    /**
     * 组装SQL语句中LEFT JOIN语句
     *
     * jion('表名2', '关系语句')相当于SQL语句中LEFT JOIN 表2 ON 关系SQL语句部分
     * @access public
     * @param string $tableName    数据表名，注：本参数支持数组，主要用于数据表的alias别名
     * @param string $where            join条件，注：不支持数组
     * @return $this
     */
    public function join($tableName, $where) {

        //参数分析
        if (!$tableName || !$where) {
            return false;
        }

        //处理数据表名
        if (is_array($tableName)) {
            foreach ($tableName as $key=>$string) {
                if (!empty($this->prefix)) {
                    $table_name_str = is_int($key) ? $this->tablePrefix . trim($string) : $this->tablePrefix . trim($string) . ' AS ' . $key;
                } else {
                    $table_name_str = is_int($key) ? trim($string) :  trim($string) . ' AS ' . $key;
                }
                //数据处理，只处理一个数组元素
                break;
            }
        } else {
            $table_name_str = (!empty($this->tablePrefix)) ? $this->prefix . trim($tableName) : trim($tableName);
        }

        //处理条件语句
        $where = trim($where);
        $this->_sqlParts['join'] = ' LEFT JOIN ' . $table_name_str . ' ON ' . $where;

        return $this;
    }

    /**
     * 组装SQL的GROUP BY语句
     *
     * 用于处理SQL语句中GROUP BY语句部分
     * @access public
     * @param mixed $group_name    所要排序的字段对象
     * @return $this
     */
    public function group($group_name) {

        //参数分析
        if (!$group_name) {
            return false;
        }

        if (is_array($group_name)) {
            $group_array = array();
            foreach ($group_name as $lines) {
                $group_array[] = trim($lines);
            }
            $group_name = implode(',', $group_array);
            unset($group_array);
        }

        $this->_sqlParts['group'] = ($this->_sqlParts['group']) ? $this->_sqlParts['group'] . ', ' . $group_name : ' GROUP BY ' . $group_name;

        return $this;
    }

    /**
     * 组装SQL的HAVING语句
     *
     * 用于处理 having id=2011 诸如此类的SQL语句部分
     * @access pulbic
     * @param string|array $where 条件语句
     * @param string $value    数据表某字段的数据值
     * @return $this
     *
     * @example
     * 用法与where()相似
     */
    public function having($where, $value = null) {

        return $this->_having($where, $value, true);
    }

    /**
     * 组装SQL的ORHAVING语句
     *
     * 用于处理or having id=2011 诸如此类的SQL语句部分
     * @access pulbic
     * @param string|array $where 条件语句
     * @param string $value    数据表某字段的数据值
     * @return $this
     *
     * @example
     * 用法与where()相似
     */
    public function orhaving($where, $value = null) {

        return $this->_having($where, $value, false);
    }

    /**
     * 组装SQL的HAVING,ORHAVING语句
     *
     * 为having()及orhaving()方法的执行提供'配件'
     * @access protected
     * @param mixed $where 条件语句
     * @param string $value    数据表某字段的数据值
     * @param boolean $is_having 当参数为true时，处理having()，当为false时，则为orhaving()
     * @return $this
     */
    protected function _having($where, $value = null, $is_having = true) {

        //参数分析
        if (!$where) {
            return false;
        }

        //分析参数条件，当参数为数组时
        if (is_array($where)) {
            $where_array = array();
            foreach ($where as $string) {
                $where_array[] = trim($string);
            }

            $where = implode(' AND ', $where_array);
            unset($where_array);
        }

        //当程序$model->where('name=?', 'tommy');操作时
        if (!is_null($value)) {
            if (!is_array($value)) {
                $value = $this->quote_into($value);
                $where = str_replace('?', $value, $where);
            } else {
                $where = $this->prepare($where, $value);
            }
        }

        //分析having() 或 orhaving()
        if ($is_having == true) {
            $this->_sqlParts['having']     = ($this->_sqlParts['having']) ? $this->_sqlParts['having'] . ' AND ' . $where : ' HAVING ' . $where;
        } else {
            $this->_sqlParts['or_having']     = ($this->_sqlParts['or_having']) ? $this->_sqlParts['or_having'] . ' AND ' . $where : ' OR ' . $where;
        }

        return $this;
    }

    /**
     * 执行SQL语句中的SELECT查询语句
     *
     * 组装SQL语句并完成查询，并返回查询结果，返回结果可以是多行，也可以是单行
     * @access public
     * @param boolean $all_data 是否输出为多行数据，默认为true,即多行数据；当false时输出的为单行数据
     * @return array
     */
    public function select($all_data = true) {

        //分析查询数据表的语句
        if (!$this->_sqlParts['from']) {
            return false;
        }

        //组装完整的SQL查询语句
        $parts_name_array = array('from', 'join', 'where', 'or_where', 'group', 'having', 'or_having', 'order', 'limit');
        $sql_str = '';
        foreach ($parts_name_array as $part_name) {
            if ($this->_sqlParts[$part_name]) {
                $sql_str .= $this->_sqlParts[$part_name];
                unset($this->_sqlParts[$part_name]);
            }
        }

        if(true == $all_data){
            return $this->db->createStatement($sql_str)->queryAll();
        }
        else{
            return $this->db->createStatement($sql_str)->getOne();
        }
    }

    
     /**
     * 分析数据表字段信息
     *
     * @access     protected
     * @param    array    $fields    数据表字段信息.本参数为数组
     * @return     string
     */
    protected static function _parseFields($fields = null) {

        if (is_null($fields)) {
            return '*';
        }

        if(is_array($fields)) {
            $fields_array = array();
            foreach($fields as $key=>$value) {
                $fields_array[] = is_int($key) ? $value : $value . ' AS ' . $key;
            }
            $fields_str = implode(',', $fields_array);
            //清空不必要的内存占用
            unset($fields_array);
        } else {
            $fields_str = $fields;
        }

        return $fields_str;
    }
    
     /**
     * 对表名进行前缀的处理
     * 未向方法指定表名，则用$tableName；若￥tableName有值，且已被{{}}包裹,有prefix
     * 若
     *
     * @access     protected
     * @param    array    $fields    数据表字段信息.本参数为数组
     * @return     string
     */
    protected static function _wrapTableName($tableName = null) {
        if(is_null($tableName)){//未指定表名
            
        } elseif ($tableName){
            
        }
    }
    
}
