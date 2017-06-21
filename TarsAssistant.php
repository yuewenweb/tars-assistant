<?php

namespace phptars;

class TarsAssistant
{

    const SOCKET_MODE_UDP = 1;
    const SOCKET_MODE_TCP = 2;
    const SOCKET_TCP_MAX_PCK_SIZE = 65536; /* 64*1024 */

    // 错误码定义（需要从扩展开始规划）
    const TARS_SUCCESS = 0; // taf

    const TARS_SOCKET_SET_NONBLOCK_FAILED = -2; // socket设置非阻塞失败
    const TARS_SOCKET_SEND_FAILED = -3; // socket发送失败
    const TARS_SOCKET_RECEIVE_FAILED = -4; // socket接收失败
    const TARS_SOCKET_SELECT_TIMEOUT = -5; // socket的select超时，也可以认为是svr超时
    const TARS_SOCKET_TIMEOUT = -6; // socket超时，一般是svr后台没回包，或者seq错误
    const TARS_SOCKET_CONNECT_FAILED = -7; // socket tcp 连接失败
    const TARS_SOCKET_CLOSED = -8; // socket tcp 服务端连接关闭
    const TARS_SOCKET_CREATE_FAILED = -70;

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
        $this->sIp = $ip;
        $this->iPort = $port;

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

    public function putBool($paramName,$bool) {
        try {
            $boolBuffer = \TARS::putBool($paramName,$bool);

            $this->encodeBufs[$paramName] = $boolBuffer;

            return  self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }
    public function getBool($name) {
        try {
            $result = \TARS::getBool($name,$this->decodeData);

            return $result;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putChar($paramName,$char) {
        try {
            $charBuffer = \TARS::putChar($paramName,$char);
            $this->encodeBufs[$paramName] = $charBuffer;

            return  self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getChar($name) {
        try {
            $result = \TARS::getChar($name,$this->decodeData);

            return  $result;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putUInt8($paramName,$uint8) {
        try {
            $uint8Buffer = \TARS::putUInt8($paramName,$uint8);
            $this->encodeBufs[$paramName] = $uint8Buffer;

            return self::TARS_SUCCESS;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getUInt8($name) {
        try {
            $result = \TARS::getUInt8($name,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putShort($paramName,$short) {
        try {
            $shortBuffer = \TARS::putShort($paramName,$short);

            $this->encodeBufs[$paramName] = $shortBuffer;

            return  self::TARS_SUCCESS;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getShort($name) {
        try {
            $result = \TARS::getShort($name,$this->decodeData);

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putUInt16($paramName,$uint16) {
        try {
            $uint16Buffer = \TARS::putUInt16($paramName,$uint16);

            $this->encodeBufs[$paramName] = $uint16Buffer;

            return self::TARS_SUCCESS;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getUInt16($name) {
        try {
            $result = \TARS::getUInt16($name,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function putInt32($paramName,$int) {
        try {
            $int32Buffer = \TARS::putInt32($paramName,$int);
            $this->encodeBufs[$paramName] = $int32Buffer;

            return  self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getInt32($name) {
        try {
            $result = \TARS::getInt32($name,$this->decodeData);

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putUInt32($paramName,$uint) {
        try {
            $uint32Buffer = \TARS::putUInt32($paramName,$uint);
            $this->encodeBufs[$paramName] = $uint32Buffer;

            return self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    public function getUInt32($name) {
        try {
            $result = \TARS::getUInt32($name,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putInt64($paramName,$bigint) {
        try {
            $int64Buffer = \TARS::putInt64($paramName,$bigint);
            $this->encodeBufs[$paramName] = $int64Buffer;
            return self::TARS_SUCCESS;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getInt64($name) {
        try {
            $result = \TARS::getInt64($name,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function putDouble($paramName,$double) {
        try {
            $doubleBuffer = \TARS::putDouble($paramName,$double);
            $this->encodeBufs[$paramName] = $doubleBuffer;
            return  self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getDouble($name) {
        try {
            $result = \TARS::getDouble($name,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }


    }

    public function putFloat($paramName,$float) {
        try {
            $floatBuffer = \TARS::putFloat($paramName,$float);
            $this->encodeBufs[$paramName] = $floatBuffer;
            return  self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getFloat($name) {
        try {
            $result = \TARS::getFloat($name,$this->decodeData);

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function putString($paramName,$string) {
        try {
            $stringBuffer = \TARS::putString($paramName,$string);
            $this->encodeBufs[$paramName] = $stringBuffer;
            return self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getString($name) {
        try {
            $result = \TARS::getString($name,$this->decodeData);
            return  $result;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function putVector($paramName,$vec) {
        try {
            $vecBuffer = \TARS::putVector($paramName,$vec);
            $this->encodeBufs[$paramName] = $vecBuffer;
            return self::TARS_SUCCESS;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getVector($name,$vec) {
        try {
            $result = \TARS::getVector($name,$vec,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }


    public function putMap($paramName,$map) {
        try {
            $mapBuffer = \TARS::putMap($paramName,$map);

            $this->encodeBufs[$paramName] = $mapBuffer;

            return self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    public function getMap($name,$obj) {
        try {
            $result = \TARS::getMap($name,$obj,$this->decodeData);

            return  $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function putStruct($paramName,$obj) {
        try {
            $structBuffer = \TARS::putStruct($paramName,$obj);
            $this->encodeBufs[$paramName] = $structBuffer;
            return self::TARS_SUCCESS;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    public function getStruct($name,&$obj) {
        try {
            \TARS::getStruct($name,$obj,$this->decodeData);

            return 0;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function sendAndReceive($timeout=2) {
        // 首先尝试encode
        try {
            $this->requestBuf = \TARS::encode($this->iVersion,self::$iRequestId,$this->servantName,
                $this->funcName,$this->cPacketType,$this->iMessageType,$timeout,$this->contexts,$this->statuses,$this->encodeBufs);
            $this->encodeBufs = array();

            $ret = self::TARS_SUCCESS;
            if ($this->socketMode === self::SOCKET_MODE_UDP) {
                $ret = $this->udpSocket($timeout);
            } else if($this->socketMode === self::SOCKET_MODE_TCP) {
                $ret = $this->tcpSocket($timeout);
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
            $decodeRet = \TARS::decode($this->responseBuf);
            if($decodeRet['code'] !== self::TARS_SUCCESS) {
                throw new TarsException($decodeRet['msg'], $decodeRet['code']);
            }
            $this->decodeData = $decodeRet['buf'];

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
    private function udpSocket($timeout)
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

        if (0 == $timeout) {
            socket_close($sock);
            return self::TARS_SUCCESS; // 无回包的情况，返回成功
        }

        $read = array($sock);
        $second = floor($timeout);
        $usecond = ($timeout - $second) * 1000000;
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
            if (microtime(true) - $time > $timeout) {
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
    private function tcpSocket($timeout)
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

        $read = array($sock);
        $ret = socket_select($read, $write, $except, $timeout);

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
            if (microtime(true) - $time > $timeout) {
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
}
