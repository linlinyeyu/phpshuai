<?php
namespace app\common\model;
use think\Model;
use app\library\ActionTransferHelper;
class Tag extends Model{

    public function getCustomerTags($customer_id){
        if(empty($customer_id) || $customer_id < 0){
            return;
        }
        $result = M("tag")->where(["customer_id" => $customer_id,"is_deleted" => 0])->select();
    }

    public function searchTagId($tag_name = "",$tag_type = "normal"){
        $tag_type = trim($tag_type);
        $result = M("tag") -> where(["tag_name" => $tag_name,"tag_type" => $tag_type]) -> getField("tag_id");
        if(empty($result)){
            $result = M("tag") -> add(["tag_name" => $tag_name,"tag_type" => $tag_type]);
        }
        return $result;
    }
    public function searchTagIdNoAdd($tag_name = "",$tag_type = "normal"){
        $tag_type = trim($tag_type);
        $result = M("tag") -> where(["tag_name" => $tag_name,"tag_type" => $tag_type]) -> getField("tag_id");
        return $result;
    }

    public function deleteTag($tag_name,$tag_type){

    }

    public function updateJPushTags($customer_id,$tags_id){
        if (empty($customer_id) || $customer_id<0){
            return;
        }
        $results = M("customer_tag") -> where(["customer_id" =>$customer_id]) -> field("tag_id") -> select();
        var_dump($results);
    }
    public function addCustomerTagByText($customer_id,$tag_name,$tag_type,$delete_before = false){
        if(empty($customer_id) || $customer_id < 0){
            return;
        }
        if ($delete_before == true && $tag_type != "normal"){
            $results =M("customer_tag") -> alias("ct")
                -> join("tag t","t.tag_id = ct.tag_id")
                -> where(["customer_id" => $customer_id , "tag_type" => $tag_type]) ->getField("tag_id",true);

            M("customer_tag") -> where(["tag_id" => ["in",$results],"customer_id" => $customer_id]) -> delete();
        }
        $tag_id = $this->searchTagId($tag_name,$tag_type);

        M("customer_tag") -> add(["customer_id" => $customer_id,"tag_id" => $tag_id,"date_subscribe" => time()]);
    }
    public function addCustomerTagById($customer_id,$tag_id){
        if(empty($customer_id) || $customer_id < 0){
            return;
        }
        M("customer_tag") -> add(["customer_id" => $customer_id,"tag_id" => $tag_id,"date_subscribe" => time()]);
    }
    public function deleteCustomerTagById($customer_id,$tag_id){


    }



}