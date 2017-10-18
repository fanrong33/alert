<?php

class EventAction extends Action{

    /**
     * 添加告警事件
     * @param string  $appkey 
     * @param string  $title
     * @param string  $content
     * @param integer $priority
     */
    public function add_event(){
        
        if($this->isPost()){
            $model = D('Event');

            /** 模拟测试数据： 
            $_POST = array(
                'app_key'   => '7f0056c0-454a-47a9-904c-742786e6f131',
                // 'title'   => '主机192.168.0.232 CPU使用率过高',
                // 'content' => '主机cpu使用率90%，请持续观察，30分钟内没有降下来需要重点关注',
                'title'    => '厦门明天天气：雷阵雨，无持续风向 微风，全天气温34℃~26℃',
                'content'  => '舒适度指数：较不舒适。在这样的天气条件下，应会感到比较不清爽和不舒适',
                'priority' => 1,
            );
            */
            
            $_validate = array(
                array('app_key' , 'require'   , '参数app_key为空', 1), // 默认正则regex, email, url, integer, number, double
                array('title'   , 'require'   , '参数title为空', 1),
                array('content' , 'require'   , '参数content为空', 1),
                array('priority', 'require'   , '参数priority为空', 1),
            );

            $model->setProperty('autoCheckFields', false);
            if(false === $data = $model->validate($_validate)->create()){
                $this->ajaxReturn('', $model->getError(), 0);
            }

            $app = D('App')->where(array('app_key'=>$data['app_key']))->find();
            if(!$app || $app['is_deleted']){
                $this->ajaxReturn('', '应用不存在', 0);
            }

            $now = time();
            $data['user_id'] = $app['user_id'];
            $data['app_id']  = $app['id'];

            
            // 2、添加警告⚠️，如果已存在相同的警告，则count+1，10分钟之内不重复提醒，TODO 机器学习最优时间
            $cond = array(
                'app_id'   => $data['app_id'],
                'title'    => $data['title'],
                'content'  => $data['content'],
                'priority' => $data['priority'],
            );
            $event = D('Event')->where($cond)->order('id desc')->find();
            if($event && $event['alert_time']+60*10 > $now){ // 10分钟内，则不重复提醒
                $effect = D('Event')->where(array('id'=>$event['id']))->save(array('count'=>array('exp', 'count+1'), 'update_time'=>$now));
                $this->ajaxReturn(array('event_id'=>$event['id']), 'success', 1);
                exit;
            }else{
                // 已关闭告警，并记录：超时自动关闭, 其实就是创建新的警告

                $data['alert_time']  = $now;
                $data['update_time'] = $now;
                $data['create_time'] = $now;

                $event_id = D('Event')->add($data);
                if(!$event_id){
                    $this->ajaxReturn('', '添加警告失败', 0);
                }
            }



            // 3、获取分派策略，根据策略条件得到触发内容条件和分派用户列表
            $sql =<<<EOF
            select trigger_content,assign from t_distribute 
                where user_id={$app['user_id']} 
                and is_deleted=0 
                and (trigger_app='{$data['app_id']}' or trigger_app='') 
                and trigger_priority regexp '{$data['priority']}'
EOF;
            // select trigger_content,assign from t_distribute where user_id=10001 and is_deleted=0 and (trigger_app='20001' or trigger_app='') and trigger_priority regexp '1'
            $distribute_list = D('Distribute')->query($sql);




            // 3.1 TODO 暂时通过文字匹配的方式，未来应该是人工智能根据大概意思一样，就进行通知，比如妹妹醒了，就通知我
            $assign_list = array();
            foreach ($distribute_list as $key => $distribute) {
                $pattern = '/'.$distribute['trigger_content'].'/';
                if(preg_match($pattern, $data['content']) || preg_match($pattern, $data['title'])){

                    $pieces = explode(',', $distribute['assign']);
                    $assign_list = array_merge($assign_list, $pieces);
                }
            }
            $assign_list = array_unique($assign_list);
            if(!$assign_list){
                $this->ajaxReturn(array('id'=>$insert_id), 'success', 1);
            }


            // 3、通过APP应用渠道分派给所有用户
            // 邮件 短信 微信 APP
            $email_list = array();
            foreach ($assign_list as $val) {
                $member = cache_get('Member', $val);
                // TODO 考虑不存在邮箱的情况
                $email_list[] = $member['email'];
            }
            $email_address = join(',', $email_list);



            import('@.ORG.Email.EmailApiClient', '', '.php');

            $client = new EmailApiClient(C('EMAIL_APP_KEY'), C('EMAIL_APP_SECRET'));


            $method = 'xiaobai.email.send_email';
            $params = array(
                'email_address'  => $email_address,   // 收件人 ?@qq.com,?@qq.com
                'subject'        => $data['title'],   // 邮件主题
                'content'        => $data['content'], // 告警内容
                // 'template_alias' => 'register_user',      // 模板别名
                // 'email_params'   => '{"name":"测试水电费"}',
            );

            $json = $client->post($method, $params);
            if($json['status'] == 1){
            }else{
                // TODO
            }

            $this->ajaxReturn(array('event_id'=>$event_id), 'success', 1);
        }else{
            $this->ajaxReturn('', '请求的HTTP METHOD不支持，请检查是否选择了正确的POST/GET方法', 0);
        }

    }


}