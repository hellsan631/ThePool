<?php
include_once "./listn.header.php";

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $submitType = intval($_POST['submitType']);

    if($submitType == 0){ // display the pick count;

        $current_week = week::getCurrent();
        $pickCount = pick::getPickCount($current_week->id);
        $gameCount = $current_week->getGameCount();

        echo "{$pickCount}/{$gameCount}";

    }else if($submitType == 1){//timezone offset

        echo week::getNextLock($_POST['offset']);
       
    }

    exit;

}

?>