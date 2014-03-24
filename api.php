<?php

include('jarvis.php');

if(isset($_REQUEST['action']))
{
    $action = $_REQUEST['action'];
    unset($_REQUEST['action']);

    if(method_exists('Jarvis', $action))
    {
        call_user_func_array(array('Jarvis', $action), $_REQUEST);
        return 0;
    }
}

die("Can't find the code you're looking for.. Sorry!");