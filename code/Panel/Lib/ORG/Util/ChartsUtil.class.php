<?php
/**
 * 生成报表数据工具类
 * @author 蔡繁荣
 * @version 1.0.6 build 20160723
 */
class ChartsUtil{

    /**
     * 根据起始和结束日期，组装时间sql查询条件
     * @param  string $time_field   时间戳字段
     * @param  string $begin_date   起始日期
     * @param  string $end_date     结束日期，可能为空
     * @return array                
     */
    static public function generate_time_str($time_field, $begin_date, $end_date){
        if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $time_field)){
            throw new Exception("time_field parameter is null", 1);
        }
        
        if(!$end_date){
            // 兼容按月统计情况，传入年的情况，返回年的时间区间
            if(preg_match('/^\d{4}$/', $begin_date)){
                $year = $begin_date;
                $time_begin = strtotime($year.'-01-01');
                $time_end   = strtotime($year.'-12-31 23:59:59');
            }else{
                // 当为今天、昨天时，只有一个begin_date参数，end_date为空
                $time_begin = strtotime($begin_date);
                $time_end   = strtotime($begin_date.' 23:59:59');
            }
        }else{
            // 日期区间TODO 完善日期区间
            $time_begin = strtotime($begin_date);
            $time_end   = strtotime($end_date.' 23:59:59');
        }
        $time_str = " AND ".$time_field." between {$time_begin} and {$time_end} ";
        return $time_str;
    }


    /**
     * 根据起始和结束日期，获取日期列表
     * @param  string $begin_date   起始日期
     * @param  string $end_date     结束日期，可能为空
     * @return array                
     */
    static public function generate_day_list($begin_date, $end_date){
        $day_list = array();
        if(!$end_date){ 
            // 当为今天、昨天时，只有一个begin_date参数，end_date为空
            $day_list[] = $begin_date;
        }else{
            // 日期区间TODO 完善日期区间
            $day = $begin_date;
            $day_list[] = $day;
            while ($day != $end_date) {
                $day = date('Y-m-d', strtotime("$day +1 day"));
                $day_list[] = $day;
            }
        }
        return $day_list;
    }

    /**
     * 返回相应年份的月份列表，支持格式自定义
     * 注: 格式需与data数据量的key一致
     * eg:
     * select DATE_FORMAT(FROM_UNIXTIME(create_time), '%c月') months, count(id) count 
     *       from t_member 
     *       where 1=1 AND {$time_str}
     *       group by months;
     *       
     * @param  integer  $year          年份
     * @param  string   $month_format  月份格式，默认'n月'，你也可以'Y-m'
     * @param  array
     */
    static public function generate_month_list($year, $month_format='n月'){
        $month_list = array();
        for($i=1; $i<=12; $i++){
            $month_list[] = date($month_format, strtotime($year.'-'.$i));
        }
        return $month_list;
    }


    /**
     * 返回列表的$field数组作为x轴数据
     * @param   array   $raw_list   原始数据列表
     * @param   string  $field      数据中的字段, 例如：title（商品名）分组作为x轴分类
     * @return  array
     */
    static public function generate_x_list($raw_list, $field){
        $x_list = array();
        foreach ($raw_list as $rs) {
            $x_list[] = $rs[$field];
        }
        return $x_list;
    }

    /**
     * 对没有完整的数据进行补齐 [{ 'days':'2016-08-01', 'user_count':12 }, { 'days':'2016-08-02', 'user_count':0 }...]
     * @param  array  $raw_list    原始数据列表
     * @param  array  $x_list      xAxis横轴数据列表 ['2016-08-01'，'2016-08-02']
     * @param  string $key_field   键 字段, days
     * @param  string $value_field 值 字段, user_count
     */
    static public function list_to_complete_list($raw_list, $x_list, $key_field, $value_field){
        if(!$x_list){
            throw new Exception("x_list parameter is null", 1);
        }
        if(!$key_field){
            throw new Exception("key_field parameter is null", 1);
        }
        if(!$value_field){
            throw new Exception("value_field parameter is null", 1);
        }

        $y_data_map = array();
        if($raw_list){ // 有可能存在，也可能不存在数据
            foreach($raw_list as $rs){
                $y_data_map[$rs[$key_field]] = array(
                    $key_field   => $rs[$key_field],
                    $value_field => $rs[$value_field],
                );
                
            }
        }

        // 对数据进行安全验证，没有数据，则用0进行填补
        foreach ($x_list as $x_rs) {
            if(!isset($y_data_map[$x_rs])){
                $y_data_map[$x_rs] = array(
                    $key_field   => $x_rs,
                    $value_field => 0,
                );
            }
        }
        ksort($y_data_map, SORT_NATURAL);

        $y_data_list = array_values($y_data_map);
        return $y_data_list;
    }

    /**
     * 将[{title:'可乐',quantity:8}...]转化为['可乐':8,...]
     * @param   array  $raw_list    要数据可视化的数据列表，[{title:'', quantity:''}, ...]
     * @param   array  $x_list      xAxis横轴数据列表 ['可乐'，'雪碧']
     * @param   string $key_field   键 字段, title
     * @param   string $value_field 值 字段, quantity
     * @return  array              
     */
    static public function list_to_data_map($raw_list, $x_list, $key_field, $value_field, $sort_flag=SORT_NATURAL){
        if(!$x_list){
            throw new Exception("x_list parameter is null", 1);
        }
        if(!$key_field){
            throw new Exception("key_field parameter is null", 1);
        }
        if(!$value_field){
            throw new Exception("value_field parameter is null", 1);
        }

        $y_data_map = array();
        if($raw_list){ // 有可能存在，也可能不存在数据
            foreach($raw_list as $rs){
                $y_data_map[$rs[$key_field]] = $rs[$value_field];
            }
        }

        // 对数据进行安全验证，没有数据，则用0进行填补
        foreach ($x_list as $x_rs) {
            if(!isset($y_data_map[$x_rs])){
                $y_data_map[$x_rs] = 0;
            }
        }

        if($sort_flag != ''){
            // 对数据进行按days(key)升序处理
            ksort($y_data_map, $sort_flag);
        }

        return $y_data_map;
    }

    /**
     * 将[{days:'2016-05-15',count:8}...]转化为['2016-05-16':8,...]
     * @param  array $data_list   要数据可视化的数据列表，[{days:'', count:''}, ...]
     * @param  array $day_list    xAxis横轴日期数据列表 ['2016-05-01'，'2016-05-02']
     * @return array        
     * @不推荐  
     */
    static public function list_to_day_map($data_list, $day_list){
        if(!$day_list){
            throw new Exception("day_list parameter is null", 1);
        }

        // 转换成days:count形式
        $data_map = array();
        if($data_list){ // 有可能存在，也可能不存在数据
            foreach ($data_list as $rs) {
                $data_map[$rs['days']] = $rs['count'];
            }
        }
        // 对数据进行安全验证，没有数据，则用0进行填补
        foreach ($day_list as $rs) {
            if(!isset($data_map[$rs])){
                $data_map[$rs] = 0;
            }
        }
        // 对数据进行按days(key)升序处理
        ksort($data_map, SORT_NATURAL);
        return $data_map;
    }

    /**
     * 将[{months:'2016-01',count:8}...]转化为['2016-01':8,...]
     * 注：月份格式不一定为2016-01，可自定义月份格式
     * @param  array $data_list   要数据可视化的数据列表，[{months:'', count:''}, ...]
     * @param  array $day_list    xAxis横轴日期数据列表 ['2016-01'，'2016-02']
     * @return array   
     * @不推荐           
     */
    static public function list_to_month_map($data_list, $month_list){
        // 转换成months:count形式
        $data_map = array();
        if($data_list){ // 有可能存在，也可能不存在数据
            foreach ($data_list as $rs) {
                $data_map[$rs['months']] = $rs['count'];
            }
        }
        // 对数据进行安全验证，没有数据，则用0进行填补
        foreach ($month_list as $rs) {
            if(!isset($data_map[$rs])){
                $data_map[$rs] = 0;
            }
        }
        // 对数据进行按months(key)升序处理, 自然排序，否则可能出现10月,11月,12月,1月...的情况
        ksort($data_map, SORT_NATURAL);
        return $data_map;
    }

    /**
     * 对数据量map进行累加处理
     * @param  array $data_map   数据量map
     * @return array          
     */
    static public function accumulate_map($data_map){
        $last_value = 0;
        foreach($data_map as $key=>$val){
            $data_map[$key] = $val + $last_value;
            $last_value = $data_map[$key];
        }
        return $data_map;
    }

    /**
     * 对数据量list进行累加处理 [{ 'days':'2016-08-01', 'user_count':12 }, { 'days':'2016-08-02', 'user_count':38 }...]
     * @param  array $data_list     数据量list
     * @param  array $value_field   要进行叠加的字段, 例如：user_count
     * @return array [{ 'days':'2016-08-01', 'user_count':12 }, { 'days':'2016-08-02', 'user_count':50 }...]
     */
    static public function accumulate_list($data_list, $value_field){
        $last_value = 0;
        foreach($data_list as $key=>$rs){
            $data_list[$key][$value_field] = $rs[$value_field] + $last_value;
            $last_value = $data_list[$key][$value_field];
        }
        return $data_list;
    }

}
?>