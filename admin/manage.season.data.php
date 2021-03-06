<?php

include "./admin.header.php";

$id = (int)$_GET['id'];
$objectType = $_GET['className'];

$obj = new $objectType($id);

?>

<div class="fluid-row width-90 alignleft no-padding">
    <form action="listn.season.php" id="subForm" method="post">
        <input type='hidden' name='className' value='<?php echo $objectType; ?>' />
        <input type='hidden' id='submitType' name='submitType' value='1' />
        <input type='hidden' id='id' name='id' value='<?php echo $id; ?>' />

        <div class="fluid-row slim">
            <button type="button" class="ui-buttons dark" id="submitButton">Save Changes</button>
            <button type="button" class="ui-buttons dark" id="deleteSeason">Delete</button>
        </div>

        <div class="fluid-row slim">
            <label for="text_id">Name: </label> <input type="text" class="float-right" no-default id="text_id" name="text_id" value="<?php echo $obj->text_id; ?>" />
            <div class="clear-fix"></div>
        </div>

        <div class="fluid-row slim">
            <label for="year">Year: </label> <input type="text" class="float-right" no-default id="year" name="year" value="<?php echo $obj->year; ?>" />
            <div class="clear-fix"></div>
        </div>

        <div class="fluid-row slim">
            <label for="week_count">Week Count: </label> <input type="text" class="float-right" no-default id="week_count" name="week_count" value="<?php echo $obj->week_count; ?>" />
            <div class="clear-fix"></div>
        </div>

        <div class="fluid-row slim">
            <label for="game_count">Game Count: </label> <input type="text" class="float-right" no-default id="game_count" name="game_count" value="<?php echo $obj->game_count; ?>" />
            <div class="clear-fix"></div>
        </div>

        <div class="fluid-row slim">
            <label for="date_start">Date Start: </label> <input type="date" class="float-right" no-default id="date_start" name="date_start" value="<?php echo $obj->date_start; ?>" />
            <div class="clear-fix"></div>
        </div>

        <div class="fluid-row slim">
            <label for="date_end">Date End: </label> <input type="date" class="float-right" no-default id="date_end" name="date_end" value="<?php echo $obj->date_end; ?>" />
            <div class="clear-fix"></div>
        </div>

        <div class="fluid-row slim">
            <label for="type">Season Type: </label>
            <select id="type" name="type" class="float-right">
                <option value="regular" <?php if($obj->type == "regular"){ echo "selected"; } ?>>Regular Season</option>
                <option value="preseason" <?php if($obj->type == "preseason"){ echo "selected"; } ?>>Pre-Season</option>
                <option value="playoff" <?php if($obj->type == "playoff"){ echo "selected"; } ?>>Playoffs</option>
            </select>
            <div class="clear-fix"></div>
        </div>

        <div class="fluid-row"></div>

    </form>
</div>

<script>

    $(document).ready(function() {
        dateTimeFormat();
    });

    $(document).on("mousedown", "#submitButton", function (e) {

        $value = parseInt($("#id").val()) || 0;

        if ($value > 0){

            var $confirm = confirm("While the selected season might be saved,\nnew weeks and games won't be added this way.\nThis may lead to the system being out of sync.\nAre you Sure you want to continue?");

            if ($confirm == true){
                $("#subForm").submit();
            }

        }else{

            $("#submitType").val("0");

            $("#subForm").submit();

        }

    });

    $(document).on("mousedown", "#deleteSeason", function (e) {

        var $confirm = confirm("Are you sure you want to delete <?php echo $obj->text_id; ?>?");

        if ($confirm == true){
           $("#submitType").val("2");

           $("#subForm").submit();
        }

    });
</script>