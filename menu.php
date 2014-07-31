
<?php

    include_once "./_header.php";

    FormValidation::generate();

?>

<nav id="main-nav">

    <div id="logo"><h3>TP</h3></div>
    <ul>
        <li id="expand-menu"><h2><i class="fa fa-bars"style="padding-left:1px;"></i></h2></li>
        <li data-link="./home.php"><h2><i class="fa fa-home" style="padding-left:2px;"></i> Home</h2></li>
        <li><h2><i class="fa fa-tachometer" style="padding-left:0px;"></i> Dashboard</h2></li>
        <li data-link="./picks.php"><h2><i class="fa fa-check-square-o" style="padding-left:2px;"></i> Picks</h2></li>
        <li data-link="./results.php"><h2><i class="fa fa-bar-chart-o" style="margin-left:-1px;"></i> Results</h2></li>
        <li class="spacer" style="height:60px; width:60px;"></li>
        <li><h2><i class="fa fa-gavel" style="padding-left:0px;"></i> Rules</h2></li>
        <li data-link="./settings.php"><h2><i class="fa fa-cogs" style="padding-left:0px;"></i> Settings</h2></li>
        <li data-link="./logout.php"><h2><i class="fa fa-sign-out" style="padding-left:2px;"></i> Logout</h2></li>
    </ul>

</nav>

<script>
    setTimeout(function(){
        var $mi = $("[data-menu-id='1']");

        if($mi.hasClass("hidden"))
            toggleMenuItemOverlay($mi);

    },3000);
</script>