<?php

class credit extends Logos_MySQL_Object{

    public $date;
    public $user_id;
    public $week_id = -1;
    public $nid;
    public $amount;

    public static function useCredit($user_id = null, $week_id = null){

        if($user_id === null)
            $user_id = users::returnCurrentUser()->id;

        if($week_id === null)
            $week_id = week::getCurrent()->id;

        $credits = self::validCredit($user_id, $week_id);

        if($credits !== false){
            return true;
        }else{

            $credits = credit::query(["orderBy" => "id ASC"])->getList(["user_id" => $user_id, "week_id" => -1]);

            if(count($credits) > 0){
                return credit::saveSingle(["week_id" => $week_id], ["id" => $credits[0]->id]);
            }

        }

        return false;

    }

    public static function validCredit($user_id = null, $week_id = null){

        $useCredit = options::loadSingle(["name" => "use_credit"]);

        if((int) $useCredit->value <= 0)
            return true;

        if($user_id === null)
            $user_id = users::returnCurrentUser()->id;

        if($week_id === null)
            $week_id = week::getCurrent()->id;

        return self::returnInstance([$user_id, $week_id], array("type" => ["user_id", "week_id"]));

    }

    public static function generateCredit($data){

        $newInstance = new self($data);

        return $newInstance->save();

    }

    public static function getCreditCount($user_id, $week_id = null){

        if($user_id === null)
            $user_id = users::returnCurrentUser()->id;

        $credits = new credit();

        if($week_id === null)
            return count($credits->getList(null, ["user_id" => $user_id]));
        else
            return count($credits->getList(null, ["user_id" => $user_id, "week_id" => $week_id]));

    }


}
