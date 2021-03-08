<?php 
/*
Plugin Name: WP JWT
Description: Esse plugin faz o login via JWT e permite utilizar requisicoes autenticadas sem cookie.
*/
include 'JWT.php';

function wm_api_init(){
    $namespace = 'wpjwt/v1';

    register_rest_route($namespace,'/login', array(
        'methods'=>'POST',
        'callback'=>'wm_api_endpoint_login'
    ));

    register_rest_route($namespace,'/validate', array(
        'methods'=>'GET',
        'callback'=>'wm_api_endpoint_validate'
    ));

    add_filter('rest_pre_dispatch', 'wm_rest_pre_dispatch',10, 3);
}

function wm_rest_pre_dispatch($url, $server, $request){
    $params = $request->get_params();

    if(!empty($params['jwt'])){
        $jwt = new JWT();

        $info = $jwt->validate($params['jwt']);
        if($info && !empty($info->id)){
            wp_set_current_user($info->id);
        }
    }
}

function wm_api_endpoint_validate($request){
    $array = array('valid' => false);
    $params = $request->get_params();

    if(!empty($params['jwt'])){
        $jwt = new JWT();

        $info = $jwt->validate($params['jwt']);

        if($info && !empty($info->id)){
            $array['valid'] = true;
        }
    }

    return $array;
}

function wm_api_endpoint_login($request){
    $array = array('logged'=>false);
    $params = $request->get_params();

    $result = wp_signon(array(
        'user_login' => $params['username'],
        'user_password'=>$params['password']
    ));
    if(isset($result->data)){
        $jwt = new JWT();
        
        $token = $jwt->create(array('id'=>$result->data->ID));

        $array['logged'] = true;
        $array['token'] = $token;
    }

    return $array;
}

add_action('rest_api_init', 'wm_api_init');