<?php

/**
 * Class Jarvis
 *
 * Main class for interfacing with the various HLK-RM04 chips around the house.
 * Contains a list of Name<->IP pairs, grouped as either "Device" or "Lighting", and can generate a JSON list
 * of those entries.
 */
class Jarvis
{
    private static $keepAliveUrl = 'http://%s/overview.asp';
    private static $pinCommandURL = 'http://%s/goform/ser2netconfigAT';
    private static $sysCommandURL = 'http://%s/goform/hlk34fge3360llf94wwq24';
    private static $ipTranslationsForDevices = array(
        'Kitchen Extractor' => '192.168.0.130'
    );
    private static $ipTranslationsForLighting = array(
        'Bed' => '192.168.0.131'
    );
    private static $authenticationCredentials = 'admin:admin';

    /**
     * Prints a list of all targets ("Device" and "Lighting" nodes) to standard output.
     *
     * @return int
     */
    public static function GetTargetList()
    {
        $list = array('devices' => array(), 'lighting' => array());

        foreach(Jarvis::$ipTranslationsForDevices as $name => $ip)
        {
            $list['devices'][] = array('name' => $name, 'state' => false);
        }

        foreach(Jarvis::$ipTranslationsForLighting as $name => $ip)
        {
            $list['lighting'][] = array('name' => $name, 'state' => false);
        }

        $list['deviceCount'] = count(Jarvis::$ipTranslationsForDevices);
        $list['lightCount'] = count(Jarvis::$ipTranslationsForLighting);

        $json_list = json_encode($list);
        print $json_list;

        return 0;
    }

    /**
     * Sets pin state to either 1 (high) or 0 (low) for the given target node.
     *
     * @param $target Name of the target node
     * @param int $state New pin state (1 for high, 0 for low)
     * @return bool succession
     */
    public static function SetPinState($target, $state = 0)
    {
        $url = Jarvis::BuildPinCommandUrlFromTarget($target);
        $data = array('gpio2' => $state);
        $response = Jarvis::GetPostResponse($url, $data);

        return $response != false;
    }

    /**
     * Prints the pin state (1 for high, 0 for low) of the target node.
     *
     * @param $target Name of the target node
     */
    public static function GetPinState($target)
    {
        $url = Jarvis::BuildPinCommandUrlFromTarget($target);
        $data = array('gpio2' => '?');
        $response = Jarvis::GetPostResponse($url, $data);

        if($response !== FALSE && !empty($response))
        {
            $tokens = explode(" ", $response);
            if(count($tokens) < 6)
            {
                die("0");
            }
            $value = $tokens[6];
            if($value == 1)
            {
                die("1");
            }
        }

        die("0");
    }

    /**
     * Sends a dummy HTTP POST request to all target nodes to keep them from going into low-power mode.
     */
    public static function KeepAlive()
    {
        $allIPs = array_merge(Jarvis::$ipTranslationsForDevices, Jarvis::$ipTranslationsForLighting);
        foreach($allIPs as $IP)
        {
            $url = sprintf(Jarvis::$keepAliveUrl, $IP);
            Jarvis::GetPostResponse($url, array('test'));
        }
    }

    /**
     * Builds a URL that can set the pin state for the given target node.
     *
     * @param $target Name of the target node
     * @return string commandUrl
     */
    private static function BuildPinCommandUrlFromTarget($target)
    {
        if(array_key_exists($target, Jarvis::$ipTranslationsForDevices))
        {
            $ip = Jarvis::$ipTranslationsForDevices[$target];
        }
        elseif(array_key_exists($target, Jarvis::$ipTranslationsForLighting))
        {
            $ip = Jarvis::$ipTranslationsForLighting[$target];
        }
        else
        {
            Jarvis::kernelPanic("Panic! '{$target}' target is not in IP list!");
        }
        $url = sprintf(Jarvis::$pinCommandURL, $ip);

        return $url;
    }

    /**
     * Builds a URL that can execute any given command as the system user on the target node.
     *
     * @param $target Name of the target node
     * @return string commandUrl
     */
    private static function BuildSysCommandUrlFromTarget($target)
    {
        if(array_key_exists($target, Jarvis::$ipTranslationsForDevices))
        {
            $ip = Jarvis::$ipTranslationsForDevices[$target];
        }
        elseif(array_key_exists($target, Jarvis::$ipTranslationsForLighting))
        {
            $ip = Jarvis::$ipTranslationsForLighting[$target];
        }
        else
        {
            Jarvis::kernelPanic("Panic! '{$target}' target is not in IP list!");
        }
        $url = sprintf(Jarvis::$pinCommandURL, $ip);

        return $url;
    }

    /*
     * 
     */
    private static function GetPostResponse($Url, $data)
    {
        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n" . "Authorization: Basic " . base64_encode(Jarvis::$authenticationCredentials) . "\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($Url, false, $context);

        return($result);
    }

    private static function kernelPanic($message)
    {
        error_log($message);
        exit -1;
    }
}
