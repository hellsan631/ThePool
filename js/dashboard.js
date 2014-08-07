
var $chartData = {};

$(window).resize(function () {
    resizeCharts();
});

var myApp = angular.module('myDash', ['angular-velocity']);

function RowController($scope, $http) {

    $scope.force = false;

    getLiveStats($scope, $http);

    //$scope.$watch('games', function() {}, true);

}

function getLiveStats($scope, $http){

    $scope.week_id = week_id;

    return $http.post( "./_listeners/listn.dashboard.php?method=GET", { "week_id" : $scope.week_id}).
        success(function(data, status) {

            $scope.status = status;

            //storeLocalStats($scope, data);

            var $formatedPicks = [];
            var $formatedLabels = [];
            var $legend = $("#performanceLegend");
            var $seasonRank = $("#rankInfo");

            //.sort(function(a,b) { return parseFloat(a.price) - parseFloat(b.price) } );

            $scope.myUserID = parseInt(data.myUserID);

            $scope.rankList = [];

            data.userRank[0].list.forEach(function(entity){

                entity.percent = (entity.percent*100).toFixed(2);
                entity.total = parseInt(entity.total);
                entity.username = getUsername(data.users,entity.userID);
                entity.userID = parseInt(entity.userID);

                if(entity.username !== false)
                    $scope.rankList.push(entity);

            });

            data.userPicks.forEach(function(entity){

                $tempArray = {};
                $tempArray.title = entity.title;
                $tempArray.data = [];
                $tempArray.strokeColor = entity.strokeColor;
                $tempArray.pointColor = entity.pointColor;
                $tempArray.pointStrokeColor = entity.pointStrokeColor;

                entity.data.forEach(function(tempEnt){

                    $tempArray.data.push(tempEnt.value);

                });

                $formatedPicks.push($tempArray);

            });

            var tempRank = "<div class='ui-spacer-item large'>"+ordinal_suffix_of(data.userRank[0].rankData.rank)+" <small class='ui-super'>rank</small></div>";

            tempRank += "<div class='ui-spacer-item large'>"+data.userRank[0].rankData.total+" <small class='ui-super'>total</small></div>";

            var $average = data.userRank[0].rankData.total/data.weeks.length;

            tempRank += "<div class='ui-spacer-item large'>"+$average.toFixed(2)+" <small class='ui-super'>avg weekly</small></div>";

            var $percent =  data.userRank[0].rankData.percent*100;

            tempRank += "<div class='ui-spacer-item large'>"+$percent.toFixed(2)+"% <small class='ui-super'>pick</small></div>";

            $seasonRank.append(tempRank);

            data.weeks.forEach(function(entity){

                $formatedLabels.push("Week "+entity.week_number);

            });

            var options_two = {
                scaleShowLabels : false,
                scaleOverlay : false,
                scaleShowGridLines : false,
                animation : true,
                scaleFontColor : "#333",
                scaleLineColor : "rgba(255,255,255,0.0)",
                scaleGridLineWidth : 1,
                pointDotRadius : 5,
                pointDotStrokeWidth : 4,
                datasetStrokeWidth : 5
            }

            var chartData = {
                labels : $formatedLabels,
                datasets: []
            };

            $formatedPicks.forEach(function(entity){

                var $temp = {
                    label: entity.title,
                    fillColor : "rgba(220,220,220,0.0)",
                    strokeColor : entity.strokeColor,
                    pointColor : entity.pointColor,
                    pointStrokeColor : entity.pointStrokeColor,
                    data : entity.data
                };

                chartData.datasets.push($temp);

                var tempLegend = "<div style='border-color: "+entity.strokeColor+"' class='ui-legend-item'>"+entity.title+" <i class='fa fa-user' style='color: "+entity.pointColor+"'></i></div>";

                $legend.append(tempLegend)

            });



            var $charts = resizeCharts({ctxPicks: {picksData: chartData, optionData: options_two}});

            return true;

        })
        .error(function(data, status) {
            $scope.data = data || "Request failed";
            $scope.status = status;

            return false;
        });



}

function getUsername($data, $user_id){

    var $username = false;

    if(!checkSet($data))
        return false;
    else if($data === false)
        return false;

    $data.forEach(function(entity){

        if(parseInt($user_id) == parseInt(entity.id))
            $username = entity.username;

    });

    return $username;
}

function resizeCharts($chartData){

    if(checkSet($chartData.ctxPicks))
        $chartData['ctxPicks'] = $chartData.ctxPicks;

    var ctxPicksChart = document.getElementById("performanceChart").getContext("2d");

    var container =  $("#perChart");

    ctxPicksChart.canvas.width  = container.innerWidth()*0.9;
    ctxPicksChart.canvas.height = container.innerHeight()*0.9;

    var performanceChart = new Chart(ctxPicksChart).Line($chartData.ctxPicks.picksData, $chartData.ctxPicks.optionData);

    return {ctxPicks: ctxPicksChart};

}

function buildPicks($scope, $callback){

    var $picks = [];

    $scope.games.forEach(function(entity){
        if(checkSet(entity.pick) !== false && parseInt(entity.pick.team_id) !== -1)
            $picks.push(entity.pick);
    });

    $scope.picks = JSON.parse(JSON.stringify($picks));

    if(checkSet($callback))
        $callback();

}

function refreshStoreLocal($scope){

    localStorage["week_id"] = $scope.week_id;
    localStorage["week_data"] = JSON.stringify($scope.week);
    localStorage["game_data"] = JSON.stringify($scope.games);

}

function storeLocalStats($scope, data){

    if(checkSet(localStorage["week_data"]) === false || $scope.force === true){

        $scope.week = data;
        $scope.games = data.games;

        refreshStoreLocal($scope);

    }else{

        $scope.week = JSON.parse(localStorage["week_data"]);
        $scope.games = JSON.parse(localStorage["game_data"]);

    }

}
