<?php

namespace phptars;

class TarsAssistant
{

    const SOCKET_MODE_UDP = 1;
    const SOCKET_MODE_TCP = 2;
    const SOCKET_TCP_MAX_PCK_SIZE = 65536; /* 64*1024 */

    // 错误码定义（需要从扩展开始规划）
    const TARS_SUCCESS = 0; // taf

    const TARS_SOCKET_SET_NONBLOCK_FAILED = -1002; // socket设置非阻塞失败
    const TARS_SOCKET_SEND_FAILED = -1003; // socket发送失败
    const TARS_SOCKET_RECEIVE_FAILED = -1004; // socket接收失败
    const TARS_SOCKET_SELECT_TIMEOUT = -1005; // socket的select超时，也可以认为是svr超时
    const TARS_SOCKET_TIMEOUT = -1006; // socket超时，一般是svr后台没回包，或者seq错误
    const TARS_SOCKET_CONNECT_FAILED = -1007; // socket tcp 连接失败
    const TARS_SOCKET_CLOSED = -1008; // socket tcp 服务端连接关闭
    const TARS_SOCKET_CREATE_FAILED = -1070;

    // taf服务端可能返回的错误码
    const SERVERSUCCESS       = 0; //服务器端处理成功
    const SERVERDECODEERR     = -1; //服务器端解码异常
    const SERVERENCODEERR     = -2; //服务器端编码异常
    const SERVERNOFUNCERR     = -3; //服务器端没有该函数
    const SERVERNOSERVANTERR = -4;//服务器端五该Servant对象
    const SERVERRESETGRID     = -5; //服务器端灰度状态不一致
    const SERVERQUEUETIMEOUT = -6; //服务器队列超过限制
    const ASYNCCALLTIMEOUT    = -7; //异步调用超时
    const PROXYCONNECTERR     = -8; //proxy链接异常
    const SERVERUNKNOWNERR    = -99; //服务器端未知异常


    private $encodeBufs = array();
    private $requestBuf;
    private $responseBuf;
    private $decodeData;

    private $sIp;
    private $iPort;
    private $iVersion;
    private $socketMode;

    private $servantName;
    private $funcName;

    private static $iRequestId = 1;

    public $cPacketType=0;
    public $iMessageType=0;
    public $iTimeout=2;
    public $contexts=array();
    public $statuses=array();

    public function setRequest($servantName,$funcName,$ip="", $port=0,$mode=self::SOCKET_MODE_TCP,
                               $iVersion=3,$cPacketType=0,$iMessageType=0,$iTimeout=2,$contexts=array(),$statuses=array())
    {
        if(empty($ip)) {
            $ret = $this->getRoute($servantName);
            if($ret['code'] != self::TARS_SUCCESS) {
                $this->sIp = "";
            }
            else {
                $this->sIp = $ret['data']['sIp'];
                $this->iPort = $ret['data']['iPort'];
                $this->socketMode = ($ret['data']['bTcp']?self::SOCKET_MODE_TCP:self::SOCKET_MODE_UDP);
            }

        }
        else {
            $this->sIp = $ip;
            $this->iPort = $port;
        }

        $this->servantName = $servantName;
        $this->funcName = $funcName;
        $this->iVersion = $iVersion;
        $this->socketMode = $mode;
        $this->iTimeout = $iTimeout;

        if ($cPacketType) {
            $this->cPacketType = $cPacketType;
        }
        if ($iMessageType) {
            $this->iMessageType = $iMessageType;
        }
        if (!empty($contexts)) {
            $this->contexts = $contexts;
        }
        if (!empty($statuses)) {
            $this->statuses = $statuses;
        }
    }

    /**
     * 从agent获取主控
     */
    private function getRoute($sObj)
    {
        // 主控根据不同环境进行切换,也可以从环境配置中读取
        $ip = isset($_SERVER['LOCATOR_IP'])?$_SERVER['LOCATOR_IP']:'127.0.0.1';
        $port = isset($_SERVER['LOCATOR_PORT'])?$_SERVER['LOCATOR_PORT']:17890;

        $this->sIp = $ip;
        $this->iPort = $port;

        // 进行寻址的请求,暂不支持灰度参数
        $iVersion = 3;
        $iRequestId = self::$iRequestId;
        $servantName = "tars.tarsregistry.QueryObj";
        $funcName = "findObjectById";

        $stringBuffer = \TUPAPI::putString("id",$sObj);
        $inbuf_arr = [
            'id' => $stringBuffer
        ];

        $this->requestBuf = \TUPAPI::encode($iVersion, $iRequestId, $servantName, $funcName,
            $this->cPacketType,$this->iMessageType,$this->iTimeout,$this->contexts,$this->statuses,
            $inbuf_arr);


        // 超时时间为2秒
        $this->tcpSocket();

        $decodeRet = \TUPAPI::decode($this->responseBuf);
        if($decodeRet['code'] !== self::TARS_SUCCESS) {
            throw new TarsException($decodeRet['msg'], $decodeRet['code']);
        }
        $decodeData = $decodeRet['sBuffer'];

        $addrs = $result = \TUPAPI::getVector("",new \TARS_VECTOR(new EndpointF()),$decodeData);

        // 从获取的地址中随机出一个来
        $count = count($addrs);
        $seed = rand(0, $count - 1);

        if(isset($addrs[0])){
            $addrTemp = $addrs[$seed];
            $addr['sIp'] = $addrTemp['host'];
            $addr['iPort'] = $addrTemp['port'];
            $addr['bTcp'] = $addrTemp['istcp'];
        }
        else {
            $addr = [];
        }

        return [
            'code' => self::TARS_SUCCESS,
            'data' => $addr
        ];
    }

    public function putBool($paramName,$bool) {
        try {
            $boolBuffer = \TUPAPI::putBool($paramName,$bool);

            $this->encodeBufs[$paramName] = $boolBuffer;

            return  self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }
    public function getBool($name) {
        try {
            $result = \TUPAPI::getBool($name,$this->decodeData);

            return $result;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putChar($paramName,$char) {
        try {
            $charBuffer = \TUPAPI::putChar($paramName,$char);
            $this->encodeBufs[$paramName] = $charBuffer;

            return  self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getChar($name) {
        try {
            $result = \TUPAPI::getChar($name,$this->decodeData);

            return  $result;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putUInt8($paramName,$uint8) {
        try {
            $uint8Buffer = \TUPAPI::putUInt8($paramName,$uint8);
            $this->encodeBufs[$paramName] = $uint8Buffer;

            return self::TARS_SUCCESS;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getUInt8($name) {
        try {
            $result = \TUPAPI::getUInt8($name,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putShort($paramName,$short) {
        try {
            $shortBuffer = \TUPAPI::putShort($paramName,$short);

            $this->encodeBufs[$paramName] = $shortBuffer;

            return  self::TARS_SUCCESS;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getShort($name) {
        try {
            $result = \TUPAPI::getShort($name,$this->decodeData);

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putUInt16($paramName,$uint16) {
        try {
            $uint16Buffer = \TUPAPI::putUInt16($paramName,$uint16);

            $this->encodeBufs[$paramName] = $uint16Buffer;

            return self::TARS_SUCCESS;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getUInt16($name) {
        try {
            $result = \TUPAPI::getUInt16($name,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function putInt32($paramName,$int) {
        try {
            $int32Buffer = \TUPAPI::putInt32($paramName,$int);
            $this->encodeBufs[$paramName] = $int32Buffer;

            return  self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getInt32($name) {
        try {
            $result = \TUPAPI::getInt32($name,$this->decodeData);

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putUInt32($paramName,$uint) {
        try {
            $uint32Buffer = \TUPAPI::putUInt32($paramName,$uint);
            $this->encodeBufs[$paramName] = $uint32Buffer;

            return self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    public function getUInt32($name) {
        try {
            $result = \TUPAPI::getUInt32($name,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putInt64($paramName,$bigint) {
        try {
            $int64Buffer = \TUPAPI::putInt64($paramName,$bigint);
            $this->encodeBufs[$paramName] = $int64Buffer;
            return self::TARS_SUCCESS;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getInt64($name) {
        try {
            $result = \TUPAPI::getInt64($name,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function putDouble($paramName,$double) {
        try {
            $doubleBuffer = \TUPAPI::putDouble($paramName,$double);
            $this->encodeBufs[$paramName] = $doubleBuffer;
            return  self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getDouble($name) {
        try {
            $result = \TUPAPI::getDouble($name,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }


    }

    public function putFloat($paramName,$float) {
        try {
            $floatBuffer = \TUPAPI::putFloat($paramName,$float);
            $this->encodeBufs[$paramName] = $floatBuffer;
            return  self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getFloat($name) {
        try {
            $result = \TUPAPI::getFloat($name,$this->decodeData);

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function putString($paramName,$string) {
        try {
            $stringBuffer = \TUPAPI::putString($paramName,$string);
            $this->encodeBufs[$paramName] = $stringBuffer;
            return self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getString($name) {
        try {
            $result = \TUPAPI::getString($name,$this->decodeData);
            return  $result;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putVector($paramName,$vec) {
        try {
            $vecBuffer = \TUPAPI::putVector($paramName,$vec);
            $this->encodeBufs[$paramName] = $vecBuffer;
            return self::TARS_SUCCESS;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getVector($name,$vec) {
        try {
            $result = \TUPAPI::getVector($name,$vec,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }


    public function putMap($paramName,$map) {
        try {
            $mapBuffer = \TUPAPI::putMap($paramName,$map);

            $this->encodeBufs[$paramName] = $mapBuffer;

            return self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getMap($name,$obj) {
        try {
            $result = \TUPAPI::getMap($name,$obj,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function putStruct($paramName,$obj) {
        try {
            $structBuffer = \TUPAPI::putStruct($paramName,$obj);
            $this->encodeBufs[$paramName] = $structBuffer;
            return self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    public function getStruct($name,&$obj) {
        try {
            $result = \TUPAPI::getStruct($name,$obj,$this->decodeData);

            $this->fromArray($result,$obj);
            return 0;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function sendAndReceive() {
        // 首先尝试encode
        try {
            $this->requestBuf = \TUPAPI::encode($this->iVersion,self::$iRequestId,$this->servantName,
                $this->funcName,$this->cPacketType,$this->iMessageType,$this->iTimeout,$this->contexts,$this->statuses,$this->encodeBufs);
            $this->encodeBufs = array();

            $ret = self::TARS_SUCCESS;
            if ($this->socketMode === self::SOCKET_MODE_UDP) {
                $ret = $this->udpSocket();
            } else if($this->socketMode === self::SOCKET_MODE_TCP) {
                $ret = $this->tcpSocket();
            }

            // 收发包失败了
            if ($ret !== self::TARS_SUCCESS) {
                throw new TarsException('Socket异常', $ret);
            }

        } catch (\Exception $e) {
            throw $e;
        }
        self::$iRequestId++;

        // 其次尝试decode
        try {
            $decodeRet = \TUPAPI::decode($this->responseBuf);
            if($decodeRet['code'] !== self::TARS_SUCCESS) {
                throw new TarsException($decodeRet['msg'], $decodeRet['code']);
            }
            $this->decodeData = $decodeRet['sBuffer'];

            return array('code' => self::TARS_SUCCESS);
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * udp收发包
     * @param $ip
     * @param $port
     * @param $timeout
     * @return int 0-成功，非0-失败（具体参考类头部错误码常量定义）
     */
    private function udpSocket()
    {

        $time = microtime(true);
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (false === $sock) {
            return self::TARS_SOCKET_CREATE_FAILED; // socket创建失败
        }

        if (!socket_set_nonblock($sock)) {
            socket_close($sock);
            return self::TARS_SOCKET_SET_NONBLOCK_FAILED; // 设置socket非阻塞失败
        }

        $len = strlen($this->requestBuf);
        if (socket_sendto($sock, $this->requestBuf, $len, 0x100, $this->sIp, $this->iPort) != $len) {
            socket_close($sock);
            return self::TARS_SOCKET_SEND_FAILED; // socket发送失败
        }

        if (0 == $this->iTimeout) {
            socket_close($sock);
            return self::TARS_SUCCESS; // 无回包的情况，返回成功
        }

        $read = array($sock);
        $second = floor($this->iTimeout);
        $usecond = ($this->iTimeout - $second) * 1000000;
        $ret = socket_select($read, $write, $except, $second, $usecond);

        if (FALSE === $ret) {
            socket_close($sock);
            return self::TARS_SOCKET_RECEIVE_FAILED; // 收包失败
        } elseif ($ret != 1) {
            socket_close($sock);
            return self::TARS_SOCKET_SELECT_TIMEOUT; // 收包超时
        }

        $out = null;
        $this->responseBuf = null;
        while (true) {
            if (microtime(true) - $time > $this->iTimeout) {
                socket_close($sock);
                return self::TARS_SOCKET_TIMEOUT; // 收包超时
            }

            // 32k：32768 = 1024 * 32
            $outLen = @socket_recvfrom($sock, $out, 32768, 0, $ip, $port);
            if (!($outLen > 0 && $out != '')) {
                continue;
            }

            $this->responseBuf = $out;
            socket_close($sock);


            return self::TARS_SUCCESS;
        }
    }

    /**
     * udp收发包
     * @param $ip
     * @param $port
     * @param $timeout
     * @return int 0-成功，非0-失败（具体参考类头部错误码常量定义）
     */
    private function tcpSocket()
    {
        $time = microtime(true);
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (false === $sock) {
            return -1; // socket创建失败
        }

        if (!socket_connect($sock, $this->sIp, $this->iPort)) {
            socket_close($sock);
            return self::TARS_SOCKET_CONNECT_FAILED;
        }

        $len = strlen($this->requestBuf);
        if (socket_write($sock, $this->requestBuf, $len) != $len) {
            socket_close($sock);
            return self::TARS_SOCKET_SEND_FAILED;
        }
        // 如果timeout为0，不等回包
        if (0 == $this->iTimeout) {
            socket_close($sock);
            return self::TARS_SUCCESS; // 无回包的情况，返回成功
        }

        $read = array($sock);
        $ret = socket_select($read, $write, $except, $this->iTimeout);

        if (false === $ret) {
            socket_close($sock);
            return self::TARS_SOCKET_RECEIVE_FAILED;
        } elseif ($ret != 1) {
            socket_close($sock);
            return self::TARS_SOCKET_SELECT_TIMEOUT;
        }

        $totalLen = 0;
        $this->responseBuf = null;
        while (true) {
            if (microtime(true) - $time > $this->iTimeout) {
                socket_close($sock);
                return self::TARS_SOCKET_TIMEOUT; // 收包超时
            }

            //读取最多32M的数据
            $data = socket_read($sock, self::SOCKET_TCP_MAX_PCK_SIZE, PHP_BINARY_READ);

            if (empty($data)) {
                // 已经断开连接
                return self::TARS_SOCKET_CLOSED;
            } else {
                //第一个包
                if ($this->responseBuf === null) {
                    $this->responseBuf = $data;

                    //在这里从第一个包中获取总包长
                    $list = unpack('Nlen', substr($data, 0, 4));
                    $totalLen = $list['len'];
                } else {
                    $this->responseBuf .= $data;
                }

                //check if all package is receved
                if (strlen($this->responseBuf) >= $totalLen) {
                    socket_close($sock);
                    return self::TARS_SUCCESS;
                }
            }
        }
    }

    public function fromArray($data,&$structObj)
    {
        if(!empty($data)) {
            foreach ($data as $key => $value) {
                if (method_exists($structObj, 'set' . ucfirst($key))){
                    call_user_func_array([$this, 'set' . ucfirst($key)], [$value]);
                } else if ($structObj->$key instanceOf \TARS_Struct) {
                    $this->fromArray($value,$structObj->$key);
                } else {
                    $structObj->$key = $value;
                }
            }
        }
    }
}
