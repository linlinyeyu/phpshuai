<?php
namespace app\library;
abstract class ExpressHelper{
    public static function getInstance($type = "yunda"){
        switch ($type){
            case "yunda":
                $type = "YunDa";
                break;
            case "yuantong":
                $type = "YuanTong";
                break;
            case "sms":
                $type = "Sms";
                break;

        }
        $class_name = "app\\library\\express\\Express" . $type;
        if(class_exists($class_name)){
            return new $class_name();
        }else{
            return new express\ExpressYuanTong();
        }
    }
    public abstract function get_express($express_sn);


}