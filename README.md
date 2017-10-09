# phptars/tars-assistant

phptars是一个用于调用tars服务的php帮助类，其中对phptars扩展中的打包解包和网络收发进行了封装。

## 使用方式

0. 使用phpstorm的同学,请访问https://github.com/yuewenweb/tars-ide-helper,下载并引入到phpstorm的依赖库中,即可获得php扩展中的函数和代码的自动提示

1. 将example.tars文件放入与tars2php同级文件夹

2. 执行php tars2php.php example.tars "App.Server.Servant",其后后两个参数分别为tars文件的文件名和tars服务的servantName


3. 在composer.json中指定require类库:
```
    "phptars/tars-assistant" : "0.1.6"
```

4. 执行composer install命令安装类库,此时会出现vendor目录

5. 开始写业务代码
```
<?php

    require_once "./vendor/autoload.php";

    $ip = "";// taf服务ip
    $port = 0;// taf服务端口
    $servant = new App\Server\Servant\servant($ip,$port);

    $in1 = "test";

    $ss1 = new SimpleStruct();
    $ss1->id = 1;
    $ss1->count = 2;
    $ss1->page = 3;

    try {
        $intVal = $servant->singleParam($in1,$ss1,$out1);
    }
    catch(phptars\TarsException $e) {
        // 错误处理
    }
```
