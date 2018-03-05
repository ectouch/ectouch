<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：WechatModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 微信通模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
class WechatModel extends BaseModel {

    protected $table = 'wechat';

    // 记录集列表
    public function get_wechat_list() {
        return $this->select('', '', 'sort asc, id asc');
    }

    // 获取单记录
    public function get_wechat_info($condition = array()) {
        return $this->find($condition);
    }

    //新增记录
    public function append_wechat($data = array()) {
        return $this->insert($data);
    }

    // 更新记录
    public function update_wechat($condition = array(), $data = array()) {
        return $this->update($condition, $data);
    }

    // 删除记录
    public function delete_wechat($condition = array()) {
        return $this->delete($condition);
    }

    // 获取微信通配置
    public function get_setting() {
        $this->table = 'wechat_setting';
        return $this->select('', '', 'sort asc, id asc');
    }

    // 更新微信通设置
    public function update_setting($condition = array(), $data = array()) {
        $this->table = 'wechat_setting';
        return $this->update($condition, $data);
    }

}
