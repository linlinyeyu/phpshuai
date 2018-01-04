<?php
namespace app\common\model;
use think\Model;
class ExpressModel extends Model{

    /**
     * 现实下单选择快递列表
     */
    public function getAddOrderShow()
    {

        return M("express")
            ->alias('e')
            ->field("e.*")
            ->where(['add_order_show' => 1])
            ->order("e.sort, e.express_id asc")
            ->select();
    }

}