<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/2/4
 * Time: 下午3:38
 */

$_SERVER['LOCATOR_IP'] = "172.16.0.161";
$_SERVER['LOCATOR_PORT'] = 17890;

require_once("./vendor/autoload.php");

$hello = new \TestApp\HelloServer\HelloObj\Hello();
$hello->testHello("test",$rsp);
var_dump($rsp);
