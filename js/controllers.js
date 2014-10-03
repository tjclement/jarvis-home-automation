var jarvisApp = angular.module('jarvisApp', []);

jarvisApp.controller('Jarvis', function Jarvis($scope, $http,  $timeout)
{
    $http.defaults.headers.post['Content-type'] = 'application/x-www-form-urlencoded;charset=utf-8';

    // Regularly update device states
     $timeout(function update(){
        $scope.updateTargets();
        $timeout(update, 5000);
    },5000);

    $http.get('api.php?action=GetTargetList').success(function(data){
            $scope.devices = data.devices;
            $scope.lighting = data.lighting;
            $scope.deviceCount = data.deviceCount;
            $scope.lightCount = data.lightCount;
            $scope.updateTargets();
        });

    $scope.updateTarget = function(target, targetType)
    {
        if(targetType === "device")
        {
            state = $scope.devices[target]["state"];
            targetName = $scope.devices[target].name;
        }
        else if(targetType === "lighting")
        {
            state = $scope.lighting[target]["state"];
            targetName = $scope.lighting[target].name;
        }

        $http.get(
                "api.php?action=GetPinState&target=" + targetName
        ).success(function(data){
                state = Boolean(parseInt(data))? "on" : "off";

                if(targetType === "device")
                {
                    $scope.devices[target]["state"] = Boolean(parseInt(data));
                }
                else if(targetType === "lighting")
                {
                    $scope.lighting[target]["state"] = Boolean(parseInt(data));
                }
            });
    };

    $scope.updateTargets = function(){

        for(var target in $scope.lighting)
        {
            $scope.updateTarget(target, "lighting");
        }
        for(var target in $scope.devices)
        {
            $scope.updateTarget(target, "device");
        }
    };

    $scope.toggleTarget = function(targetName, state)
    {
        newState = state ? 1 : 0;

        $http.get(
            "api.php?action=SetPinState&target=" + targetName + "&state=" + newState
        ).success(function(){
               $scope.updateTargets();
            });
    };
});