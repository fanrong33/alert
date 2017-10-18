<?php
/**
 * 报告 控制器类
 * @author 蔡繁荣
 * @version 1.0.2 build 20171010
 */
class ReportAction extends CommonAction{

    public function _initialize(){
        parent::_initialize();

        $this->addPath('报告');
    }


    public function index(){


        $this->assign_date();
        $this->display();
    }


    public function app(){


        $this->assign_date();
        $this->display();
    }


    /**
     * 按天获取日志统计信息列表接口
     * @param  integer  $offer_id            * 所属offerID
     * @param  string   $begin_date          添加开始日期
     * @param  string   $end_date            添加结束日期，当天23:59:59
     */
    public function get_summary_report_list(){
        $model = M('Event');

        if($this->isGet()){

            $begin_date = $this->_get('begin_date');
            $end_date   = $this->_get('end_date');
            
            // 1、安全验证，组装sql方式, 需要特别防止sql注入
            if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $begin_date)){
                $this->ajaxReturn('', '起始日期格式错误', 0);
            }
            if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)){
                $this->ajaxReturn('', '结束日期格式错误', 0);
            }
            

            // 2、按天统计日期区间的告警数据 (事件次数,告警次数)
            $user_id_str = ' AND user_id='.$this->_user['id'];
            if($begin_date && $end_date){
                import('@.ORG.Util.ChartsUtil');
                $create_time_str = ChartsUtil::generate_time_str('create_time', $begin_date, $end_date);
            }

            $sql =<<<EOF
                select DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d') days, 
                count(id) as events,
                sum(count) as counts
                from t_event
                where 1=1 
                $user_id_str
                $create_time_str
                group by days
EOF;
            $data_list = $model->query($sql);
            

            // 3、统计汇总, 并对数据进行安全验证，没有数据，则用0进行填补
            $y_data_map = array();

            $total_events = 0;
            $total_counts = 0;
            if($data_list){ // 有可能存在，也可能不存在数据
                foreach($data_list as $rs){
                    $y_data_map[$rs['days']] = $rs;

                    $total_events += $rs['events'];
                    $total_counts += $rs['counts'];
                }
            }

            $day_list = ChartsUtil::generate_day_list($begin_date, $end_date);
            foreach ($day_list as $x_rs) {
                if(!isset($y_data_map[$x_rs])){
                    $y_data_map[$x_rs] = array(
                        'days'   => $x_rs,
                        'events' => 0,
                        'counts' => 0,
                    );
                }
            }
            ksort($y_data_map, SORT_NATURAL);



            $result = array(
                'stats' => array(
                    'total_events' => intval($total_events),
                    'total_counts' => intval($total_counts),
                ),
                'list' => array_values($y_data_map)
            );
            $this->ajaxReturn($result, 'success', 1);
        }else{
            $this->ajaxReturn('', '请求的HTTP METHOD不支持，请检查是否选择了正确的POST/GET方法', 0);
        }
    }


    /**
     * 按天获取日志统计信息列表接口
     * @param  integer  $offer_id            * 所属offerID
     * @param  string   $begin_date          添加开始日期
     * @param  string   $end_date            添加结束日期，当天23:59:59
     */
    public function get_app_report_list(){
        $model = M('Report');

        if($this->isGet()){

            $begin_date = $this->_get('begin_date');
            $end_date   = $this->_get('end_date');
            

            // 1、安全验证，组装sql方式, 需要特别防止sql注入
            if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $begin_date)){
                $this->ajaxReturn('', '起始日期格式错误', 0);
            }
            if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)){
                $this->ajaxReturn('', '结束日期格式错误', 0);
            }
            

            import('@.ORG.Util.ChartsUtil');

            $user_id_str = ' AND user_id='.$this->_user['id'];
            if($begin_date && $end_date){
                $create_time_str = ChartsUtil::generate_time_str('create_time', $begin_date, $end_date);
            }


            // 按天统计日期区间的报告数据, 用于charts展示
            $sql =<<<EOF
                select DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d') days, 
                count(id) as events,
                sum(count) as counts
                from t_event
                where 1=1 
                $user_id_str
                $create_time_str
                group by days
EOF;
            $data_list = $model->query($sql);
            

            // 统计汇总
            $y_data_map = array();

            $total_events = 0;
            $total_counts = 0;


            if($data_list){ // 有可能存在，也可能不存在数据
                foreach($data_list as $rs){
                    $y_data_map[$rs['days']] = $rs;

                    $total_events += $rs['events'];
                    $total_counts += $rs['counts'];
                }
            }


            // 对数据进行安全验证，没有数据，则用0进行填补
            $day_list = ChartsUtil::generate_day_list($begin_date, $end_date);
            foreach ($day_list as $x_rs) {
                if(!isset($y_data_map[$x_rs])){
                    $y_data_map[$x_rs] = array(
                        'days'   => $x_rs,
                        'events' => 0, // 事件数
                        'counts' => 0, // 告警数
                    );
                }
            }
            ksort($y_data_map, SORT_NATURAL);


            // 按app_id统计日期区间的报告数据
            $user_id_str = ' AND a.user_id='.$this->_user['id'];
            if($begin_date && $end_date){
                $create_time_str = ChartsUtil::generate_time_str('a.create_time', $begin_date, $end_date);
            }
            $sql =<<<EOF
                select  
                a.app_id,
                b.name as app_name,
                count(a.id) as events,
                sum(a.count) as counts
                from t_event as a
                left join t_app as b on a.app_id=b.id
                where 1=1
                $user_id_str
                $create_time_str
                group by a.app_id
EOF;
            $app_report_list = $model->query($sql);

            $result = array(
                'stats' => array(
                    'total_events' => intval($total_events),
                    'total_counts' => intval($total_counts),
                ),
                'list' => array_values($y_data_map),
                'app_list' => $app_report_list,
            );
            $this->ajaxReturn($result, 'success', 1);
        }else{
            $this->ajaxReturn('', '请求的HTTP METHOD不支持，请检查是否选择了正确的POST/GET方法', 0);
        }
    }


    /**
     * 导出整体报告到cvs
     */
    public function export_summary_cvs(){
        set_time_limit(0);

        $model = M('Report');

        $begin_date = $this->_get('begin_date');
        $end_date   = $this->_get('end_date');
        
        // 1、安全验证，组装sql方式, 需要特别防止sql注入
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $begin_date)){
            $this->ajaxReturn('', '起始日期格式错误', 0);
        }
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)){
            $this->ajaxReturn('', '结束日期格式错误', 0);
        }
        

        // 2、按天统计日期区间的告警数据 (事件次数,告警次数)
        $user_id_str = ' AND user_id='.$this->_user['id'];
        if($begin_date && $end_date){
            import('@.ORG.Util.ChartsUtil');
            $create_time_str = ChartsUtil::generate_time_str('create_time', $begin_date, $end_date);
        }

        $sql =<<<EOF
            select DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m-%d') days, 
            count(id) as events,
            sum(count) as counts
            from t_event
            where 1=1 
            $user_id_str
            $create_time_str
            group by days
EOF;
        $data_list = $model->query($sql);
        

        // 3、统计汇总, 并对数据进行安全验证，没有数据，则用0进行填补
        $y_data_map = array();

        $total_events = 0;
        $total_counts = 0;
        if($data_list){ // 有可能存在，也可能不存在数据
            foreach($data_list as $rs){
                $y_data_map[$rs['days']] = $rs;

                $total_events += $rs['events'];
                $total_counts += $rs['counts'];
            }
        }

        $day_list = ChartsUtil::generate_day_list($begin_date, $end_date);
        foreach ($day_list as $x_rs) {
            if(!isset($y_data_map[$x_rs])){
                $y_data_map[$x_rs] = array(
                    'days'   => $x_rs,
                    'events' => 0,
                    'counts' => 0,
                );
            }
        }
        ksort($y_data_map, SORT_NATURAL);
        $list = array_values($y_data_map);


        // 4、写入csv文件，并导出
        $dir = 'export/report/'.$this->_user['id'].'/'.date('Ymd').'/';
        $filename = 'report_summary_'.$begin_date.'_'.$end_date.'_'.time().'.csv';

        $options = array(
            'list'      => $list,
            'head'      => array('时间', '事件数', '告警数'),
            'save_path' => $dir,
            'save_name' => $filename,
            'download'  => true
        );
        export_csv($options);
    }
    

    /**
     * 导出应用报告到cvs
     */
    public function export_app_cvs(){
        set_time_limit(0);

        $model = M('Report');

        $begin_date = $this->_get('begin_date');
        $end_date   = $this->_get('end_date');
        
        // 1、安全验证，组装sql方式, 需要特别防止sql注入
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $begin_date)){
            $this->ajaxReturn('', '起始日期格式错误', 0);
        }
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)){
            $this->ajaxReturn('', '结束日期格式错误', 0);
        }
        

        import('@.ORG.Util.ChartsUtil');

        // 按app_id统计日期区间的报告数据
        $user_id_str = ' AND a.user_id='.$this->_user['id'];
        if($begin_date && $end_date){
            $create_time_str = ChartsUtil::generate_time_str('a.create_time', $begin_date, $end_date);
        }
        $sql =<<<EOF
            select  
            a.app_id,
            b.name as app_name,
            count(a.id) as events,
            sum(a.count) as counts
            from t_event as a
            left join t_app as b on a.app_id=b.id
            where 1=1
            $user_id_str
            $create_time_str
            group by a.app_id
EOF;
        $app_report_list = $model->query($sql);
        

        // 4、写入csv文件，并导出
        $dir = 'export/report/'.$this->_user['id'].'/'.date('Ymd').'/';
        $filename = 'report_app_'.$begin_date.'_'.$end_date.'_'.time().'.csv';
        
        $options = array(
            'list'      => $app_report_list,
            'head'      => array('APP', 'APPID', '事件数', '告警数'),
            'save_path' => $dir,
            'save_name' => $filename,
            'download'  => true
        );
        export_csv($options);
    }


    private function assign_date(){
        // 分别获取今天、昨天、最近7天、本月的时间
        $today_begin     = date('Y-m-d');
        $today_end       = date('Y-m-d');
        
        $yesterday_begin = date("Y-m-d", strtotime("-1 day"));
        $yesterday_end   = date("Y-m-d", strtotime("-1 day"));
        
        $week_begin      = date('Y-m-d', strtotime('-1 week'));
        $week_end        = date('Y-m-d');
        
        $month_begin     = date('Y-m-d', strtotime('-30 day'));
        $month_end       = date('Y-m-d');

        // 其他时间为默认本月到现在的时间区间
        $other_begin     = date('Y-m-01');
        $other_end       = date("Y-m-d");




        $this->assign('today_begin', $today_begin);
        $this->assign('today_end', $today_end);

        $this->assign('yesterday_begin', $yesterday_begin);
        $this->assign('yesterday_end', $yesterday_end);

        $this->assign('week_begin', $week_begin);
        $this->assign('week_end', $week_end);

        $this->assign('month_begin', $month_begin);
        $this->assign('month_end', $month_end);

        $this->assign('other_begin', $other_begin);
        $this->assign('other_end', $other_end);
    }


}

?>