<?php
    
    use insta_rate\insta_rate;
    
    require "src/insta_rate.php";
    
    $user = new insta_rate('remixadam');
    
    $json2 = $user->get_user();
    
    print_r($json2);