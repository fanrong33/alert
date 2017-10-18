<?php

class IndexAction extends Action {

    public function index(){
        
        
        redirect('event/index');
    }

    public function sample(){
        header("Content-type: text/html; charset=utf-8");

        import('@.ORG.Email.SimpleHttpClient', '', '.php');
        $params = array(
            'app_key'   => '7f0056c0-454a-****-****-742786e6f131',
            // 'title'   => '主机192.168.0.232 CPU使用率过高',
            // 'content' => '主机cpu使用率90%，请持续观察，30分钟内没有降下来需要重点关注',
            'title'    => '厦门明天天气：雷阵雨，无持续风向 微风，全天气温34℃~26℃',
            'content'  => '舒适度指数：较不舒适。在这样的天气条件下，应会感到比较不清爽和不舒适',
            'priority' => 2,
        );
        $api_url = 'http://alert.fanrong.com/api.php/event/add_event';
        $response = SimpleHttpClient::post($api_url, $params);
        $json = json_decode($response, true);
        dump($json);
        exit;
    }


}