<?php
    
    use insta_rate\insta_rate;
    
    require "src/insta_rate.php";
    
    $user = new insta_rate('tugceden');
    
    $json2 = $user->get_user_rate();
    
    print_r($json2);