<?php
/**
 * 分组 模型类
 * @author 蔡繁荣 <fanrong33@qq.com>
 * @version 1.0.0 build 20170407
 */
class GroupModel extends CommonModel{

    /**
     * 记录缓存key 和 更新文件位置
     */
    protected $cache_options = array(

        'list_belong_id'       => 'user_id', // 只支持单维度的list, 比如group_list 应该是user_id所属的
        'list_belong_id_extra' => array('is_deleted' => 0), // 与list_belong_id组合的缓存查询条件

        // generate_cache_key('ad_setting_list', $placementId); // 在Admin端和Home端更新时删除缓存
        //  - Admin/Lib/Action/PlacementAction/source
        //  - Home/Lib/Action/PlacementAction/source
    );

}

?>