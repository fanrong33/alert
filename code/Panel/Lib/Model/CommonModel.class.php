<?php
/**
 * 通用 模型类
 * @author 蔡繁荣
 * @version 1.0.0 build 20170407
 */
class CommonModel extends Model{

    /**
     * 记录缓存key 和 更新文件位置
     */
    // protected $cache_options = array(

    //     'list_belong_id'       => 'user_id', // 只支持单维度的list, 比如group_list 应该是user_id所属的
    //     'list_belong_id_extra' => array('is_deleted' => 0),

        // generate_cache_key('ad_setting_list', $placementId); // 在Admin端和Home端更新时删除缓存
        //  - Admin/Lib/Action/PlacementAction/source
        //  - Home/Lib/Action/PlacementAction/source
    // );

    /**
     * 更新成功后的回调方法
     */
    protected function _after_update($data, $options) {
        $model_name = $this->getModelName();
        
        if(!isset($this->cache_options)){
            throw new Exception($model_name."Model must have cache_options attribute", 1);
        }


        $id = $options['where']['id'];
        $obj = $this->find($id);


        $cache_key = generate_cache_key($model_name, $obj['id']);
        S($cache_key, null); // 删除API端的内存缓存信息


        $belong_id = $this->cache_options['list_belong_id'];
        $params = array($belong_id=>$obj[$belong_id]);

        if($this->cache_options['list_belong_id_extra']){
            $params = array_merge($params, $this->cache_options['list_belong_id_extra']);
        }
        $cache_key = generate_cache_key($model_name, 'list', $params);
        S($cache_key, null);
    }

}

?>