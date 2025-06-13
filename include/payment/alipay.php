<?php
if(!defined('DEDEINC')) exit('DedeCMS Error: Request Error!');
/**
 * 支付宝接口类
 */
class Alipay
{
    var $dsql;
    var $mid;
    var $return_url = "/plus/carbuyaction.php?dopost=return";
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function Alipay()
    {
        global $dsql;
        $this->dsql = $dsql;
    }

    function __construct()
    {
        $this->Alipay();
    }
    
    /**
     *  设定接口会送地址
     *
     *  例如: $this->SetReturnUrl($cfg_basehost."/tuangou/control/index.php?ac=pay&orderid=".$p2_Order)
     *
     * @param     string  $returnurl  会送地址
     * @return    void
     */
    function SetReturnUrl($returnurl='')
    {
        if (!empty($returnurl))
        {
            $this->return_url = $returnurl;
        }
    }

    /**
    * 生成支付代码
    * @param   array   $order      订单信息
    * @param   array   $payment    支付方式信息
    */
    function GetCode($order, $payment)
    {
        global $cfg_basehost,$cfg_cmspath,$cfg_soft_lang;
        $charset = $cfg_soft_lang;
        //对于二级目录的处理
        if(!empty($cfg_cmspath)) $cfg_basehost = $cfg_basehost.'/'.$cfg_cmspath;

        $alipayTradePagePay = new AlipayTradePagePay();
        $alipayTradePagePay->setOutTradeNo($order['out_trade_no']);
        $alipayTradePagePay->setTotalAmount($order['price']);
        $alipayTradePagePay->setSubject("支付订单号:".$order['out_trade_no']);
        $alipayTradePagePay->setAppId($payment['app_id']);
        $alipayTradePagePay->setMethod('alipay.trade.page.pay');
        $alipayTradePagePay->setReturnUrl($cfg_basehost.$this->return_url."&code=".$payment['code']);
        $alipayTradePagePay->setNotifyUrl($cfg_basehost.$this->return_url."&code=".$payment['code']);
        $alipayTradePagePay->setMerchantPrivateKey($payment['merchant_private_key']);
        $alipayTradePagePay->setAlipayPublicKey($payment['alipay_public_key']);
        $alipayTradePagePay->setPostCharset($charset == 'utf-8' ? 'utf-8' : 'gbk');
        $alipayTradePagePay->setFileCharset($charset == 'utf-8' ? 'utf-8' : 'gbk');
        
        $button = $alipayTradePagePay->pagePay();

        /* 清空购物车 */
        require_once DEDEINC.'/shopcar.class.php';
        $cart     = new MemberShops();
        $cart->clearItem();
        $cart->MakeOrders();
        return $button;
    }

    
    /**
    * 响应操作
    */
    function respond()
    {
        if (!empty($_POST))
        {
            foreach($_POST as $key => $data)
            {
                $_GET[$key] = $data;
            }
        }
        /* 引入配置文件 */
		$code = preg_replace( "#[^0-9a-z-]#i", "", $_GET['code'] );
		require_once DEDEDATA.'/payment/'.$code.'.php';
		
        /* 取得订单号 */
        $order_sn = trim(addslashes($_GET['out_trade_no']));
        /*判断订单类型*/
        if(preg_match ("/S-P[0-9]+RN[0-9]/",$order_sn)) {
            //检查支付金额是否相符
            $row = $this->dsql->GetOne("SELECT * FROM #@__shops_orders WHERE oid = '{$order_sn}'");
            if ($row['priceCount'] != $_GET['total_amount'])
            {
                return $msg = "支付失败，支付金额与商品总价不相符!";
            }
            $this->mid = $row['userid'];
            $ordertype="goods";
        }else if (preg_match ("/M[0-9]+T[0-9]+RN[0-9]/", $order_sn)){
            $row = $this->dsql->GetOne("SELECT * FROM #@__member_operation WHERE buyid = '{$order_sn}'");
            //获取订单信息，检查订单的有效性
            if(!is_array($row)||$row['sta']==2) return $msg = "您的订单已经处理，请不要重复提交!";
            elseif($row['money'] != $_GET['total_amount']) return $msg = "支付失败，支付金额与商品总价不相符!";
            $ordertype = "member";
            $product =    $row['product'];
            $pname= $row['pname'];
            $pid=$row['pid'];
            $this->mid = $row['mid'];
        } else {    
            return $msg = "支付失败，您的订单号有问题！";
        }

        /* 检查数字签名是否正确 */
        global $cfg_soft_lang;
        $charset = $cfg_soft_lang;

        $alipayTradePagePay = new AlipayTradePagePay();
        $alipayTradePagePay->setAlipayPublicKey($payment['alipay_public_key']);
        $alipayTradePagePay->setPostCharset($charset == 'utf-8' ? 'utf-8' : 'gbk');
        $alipayTradePagePay->setFileCharset($charset == 'utf-8' ? 'utf-8' : 'gbk');
        
        if (!$alipayTradePagePay->rsaCheck($_GET))
        {
            return $msg = "支付失败!";
        }

        if ($_GET['trade_status'] == '') {
            $alipayTradePagePay->setOutTradeNo($_GET['out_trade_no']);
            $alipayTradePagePay->setTradeNo($_GET['trade_no']);
            $alipayTradePagePay->setAppId($payment['app_id']);
            $alipayTradePagePay->setMethod('alipay.trade.query');
            $alipayTradePagePay->setMerchantPrivateKey($payment['merchant_private_key']);
    
            $resp = $alipayTradePagePay->query();
    
            $_GET['trade_status'] = $resp->trade_status;
        }

        if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS' || $_GET['trade_status'] == 'TRADE_SUCCESS')
        {
            if($ordertype=="goods"){ 
                if($this->success_db($order_sn))  return $msg = "支付成功!<br> <a href='/'>返回主页</a> <a href='/member'>会员中心</a>";
                else  return $msg = "支付失败！<br> <a href='/'>返回主页</a> <a href='/member'>会员中心</a>";
            } else if ( $ordertype=="member" ) {
                $oldinf = $this->success_mem($order_sn,$pname,$product,$pid);
                return $msg = "<font color='red'>".$oldinf."</font><br> <a href='/'>返回主页</a> <a href='/member'>会员中心</a>";
            }
        } else {
            $this->log_result ("verify_failed");
            return $msg = "支付失败！<br> <a href='/'>返回主页</a> <a href='/member'>会员中心</a>";
        }
    }

    /*处理物品交易*/
    function success_db($order_sn)
    {
        //获取订单信息，检查订单的有效性
        $row = $this->dsql->GetOne("SELECT state FROM #@__shops_orders WHERE oid='$order_sn' ");
        if($row['state'] > 0)
        {
            return TRUE;
        }    
        /* 改变订单状态_支付成功 */
        $sql = "UPDATE `#@__shops_orders` SET `state`='1' WHERE `oid`='$order_sn' AND `userid`='".$this->mid."'";
        if($this->dsql->ExecuteNoneQuery($sql))
        {
            $this->log_result("verify_success,订单号:".$order_sn); //将验证结果存入文件
            return TRUE;
        } else {
            $this->log_result ("verify_failed,订单号:".$order_sn);//将验证结果存入文件
            return FALSE;
        }
    }

    /*处理点卡，会员升级*/
    function success_mem($order_sn,$pname,$product,$pid)
    {
        //更新交易状态为已付款
        $sql = "UPDATE `#@__member_operation` SET `sta`='1' WHERE `buyid`='$order_sn' AND `mid`='".$this->mid."'";
        $this->dsql->ExecuteNoneQuery($sql);

        /* 改变点卡订单状态_支付成功 */
        if($product=="card")
        {
            $row = $this->dsql->GetOne("SELECT cardid FROM #@__moneycard_record WHERE ctid='$pid' AND isexp='0' ");;
            //如果找不到某种类型的卡，直接为用户增加金币
            if(!is_array($row))
            {
                $nrow = $this->dsql->GetOne("SELECT num FROM #@__moneycard_type WHERE pname = '{$pname}'");
                $dnum = $nrow['num'];
                $sql1 = "UPDATE `#@__member` SET `money`=money+'{$nrow['num']}' WHERE `mid`='".$this->mid."'";
                $oldinf ="已经充值了".$nrow['num']."金币到您的帐号！";
            } else {
                $cardid = $row['cardid'];
                $sql1=" UPDATE #@__moneycard_record SET uid='".$this->mid."',isexp='1',utime='".time()."' WHERE cardid='$cardid' ";
                $oldinf='您的充值密码是：<font color="green">'.$cardid.'</font>';
            }
            //更新交易状态为已关闭
            $sql2=" UPDATE #@__member_operation SET sta=2,oldinfo='$oldinf' WHERE buyid='$order_sn'";
            if($this->dsql->ExecuteNoneQuery($sql1) && $this->dsql->ExecuteNoneQuery($sql2))
            {
                $this->log_result("verify_success,订单号:".$order_sn); //将验证结果存入文件
                return $oldinf;
            } else {
                $this->log_result ("verify_failed,订单号:".$order_sn);//将验证结果存入文件
                return "支付失败！";
            }
        /* 改变会员订单状态_支付成功 */
        } else if ( $product=="member" ){
            $row = $this->dsql->GetOne("SELECT rank,exptime FROM #@__member_type WHERE aid='$pid' ");
            $rank = $row['rank'];
            $exptime = $row['exptime'];
            /*计算原来升级剩余的天数*/
            $rs = $this->dsql->GetOne("SELECT uptime,exptime FROM #@__member WHERE mid='".$this->mid."'");
            if($rs['uptime']!=0 && $rs['exptime']!=0 ) 
            {
                $nowtime = time();
                $mhasDay = $rs['exptime'] - ceil(($nowtime - $rs['uptime'])/3600/24) + 1;
                $mhasDay=($mhasDay>0)? $mhasDay : 0;
            }
            //获取会员默认级别的金币和积分数
            $memrank = $this->dsql->GetOne("SELECT money,scores FROM #@__arcrank WHERE rank='$rank'");
            //更新会员信息
            $sql1 =  " UPDATE #@__member SET rank='$rank',money=money+'{$memrank['money']}',
                       scores=scores+'{$memrank['scores']}',exptime='$exptime'+'$mhasDay',uptime='".time()."' 
                       WHERE mid='".$this->mid."'";
            //更新交易状态为已关闭
            $sql2=" UPDATE #@__member_operation SET sta='2',oldinfo='会员升级成功!' WHERE buyid='$order_sn' ";
            if($this->dsql->ExecuteNoneQuery($sql1) && $this->dsql->ExecuteNoneQuery($sql2))
            {
                $this->log_result("verify_success,订单号:".$order_sn); //将验证结果存入文件
                return "会员升级成功！";
            } else {
                $this->log_result ("verify_failed,订单号:".$order_sn);//将验证结果存入文件
                return "会员升级失败！";
            }
        }    
    }

    function  log_result($word) 
    {
        global $cfg_cmspath;
        $fp = fopen(dirname(__FILE__)."/../../data/payment/log.txt","a");
        flock($fp, LOCK_EX) ;
        fwrite($fp,$word.",执行日期:".strftime("%Y-%m-%d %H:%I:%S",time())."\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}//End API

// 电脑网站支付
class AlipayTradePagePay
{
    // 商户订单号
    private $outTradeNo;

    // 支付宝交易号
    private $tradeNo;

    // 订单总金额，整形，此处单位为元，精确到小数点后2位，不能超过1亿元
    private $totalAmount;

    // 订单标题，粗略描述用户的支付目的
    private $subject;

    // 应用ID
    private $appId;

    // 接口名称
    private $method;

    // 同步跳转
    private $returnUrl;

    // 异步通知地址
    private $notifyUrl;

    // 商户私钥
    private $merchantPrivateKey;

    // 支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
    private $alipayPublicKey;

    // 表单提交字符集编码
    private $postCharset;

    // 文件编码
    private $fileCharset;

    // 签名类型
    private $signType;

    // 网关
    private $gatewayUrl;


    private $RESPONSE_SUFFIX = "_response";

    private $ERROR_RESPONSE = "error_response";

    private $SIGN_NODE_NAME = "sign";


    public function __construct()
    {
        $this->signType = "RSA2";
        $this->gatewayUrl = "https://openapi.alipay.com/gateway.do";
    }

    public function setOutTradeNo($outTradeNo)
    {
        $this->outTradeNo = $outTradeNo;
    }

    public function setTradeNo($tradeNo)
    {
        $this->tradeNo = $tradeNo;
    }

    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }

    public function setMerchantPrivateKey($merchantPrivateKey)
    {
        $this->merchantPrivateKey = $merchantPrivateKey;
    }

    public function setAlipayPublicKey($alipayPublicKey)
    {
        $this->alipayPublicKey = $alipayPublicKey;
    }

    public function setPostCharset($postCharset)
    {
        $this->postCharset = $postCharset;
    }

    public function setFileCharset($fileCharset)
    {
        $this->fileCharset = $fileCharset;
    }

    public function pagePay()
    {
        // 获取业务参数
        $apiParams["out_trade_no"] = $this->outTradeNo;
        $apiParams["total_amount"] = $this->totalAmount;
        $apiParams["subject"] = $this->subject;
        $apiParams["product_code"] = "FAST_INSTANT_TRADE_PAY";

        // 组装系统参数
        $sysParams["app_id"] = $this->appId;
        $sysParams["method"] = $this->method;
        $sysParams["charset"] = $this->postCharset;
        $sysParams["sign_type"] = $this->signType;
        $sysParams["timestamp"] = date("Y-m-d H:i:s");
        $sysParams["version"] = "1.0";
        $sysParams["biz_content"] = json_encode($apiParams);
        $sysParams["return_url"] = $this->returnUrl;
        $sysParams["notify_url"] = $this->notifyUrl;

        $totalParams = array_merge($apiParams, $sysParams);

        // 签名
        $totalParams["sign"] = $this->generateSign($totalParams, $this->signType);

        // 拼接表单字符串
        return $this->buildRequestForm($totalParams);
    }

    public function generateSign($params, $signType = "RSA")
    {
        return $this->sign($this->getSignContent($params), $signType);
    }

    protected function sign($data, $signType = "RSA")
    {
        $priKey = $this->merchantPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }

        $sign = base64_encode($sign);
        return $sign;
    }

    public function getSignContent($params)
    {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value)
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset)
    {

        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //				$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }


        return $data;
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     */
    protected function buildRequestForm($para_temp)
    {

        $sHtml = "<form target='_blank' id='alipaysubmit' name='alipaysubmit' action='" . $this->gatewayUrl . "?charset=" . trim($this->postCharset) . "' method='POST'>";
        foreach ($para_temp as $key => $val) {
            if (false === $this->checkEmpty($val)) {
                //$val = $this->characet($val, $this->postCharset);
                $val = str_replace("'", "&apos;", $val);
                //$val = str_replace("\"","&quot;",$val);
                $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
            }
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input type='submit' value='立即使用alipay支付宝支付'></form>";

        return $sHtml;
    }

    public function rsaCheck($params)
    {
        $sign = $params['sign'];
        $params['sign_type'] = null;
        $params['sign'] = null;
        $params['dopost'] = null;
        $params['code'] = null;
        return $this->verify($this->getSignContent($params), $sign, $this->signType);
    }

    function verify($data, $sign, $signType = 'RSA')
    {
        $pubKey = $this->alipayPublicKey;
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        ($res) or die('支付宝RSA公钥错误。请检查公钥文件格式是否正确');

        //调用openssl内置方法验签，返回bool值

        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }

        return $result;
    }

    public function query()
    {
        // 获取业务参数
        $apiParams["out_trade_no"] = "K2QJLQJGD107TM9APR811IELBO";
        $apiParams["trade_no"] = $this->tradeNo;

        // 组装系统参数
        $sysParams["app_id"] = $this->appId;
        $sysParams["method"] = $this->method;
        $sysParams["charset"] = $this->postCharset;
        $sysParams["sign_type"] = $this->signType;
        $sysParams["timestamp"] = date("Y-m-d H:i:s");
        $sysParams["version"] = "1.0";
        $sysParams["biz_content"] = json_encode($apiParams);

        // 签名
        $sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams), $this->signType);

        // 系统参数放入GET请求串
        $requestUrl = $this->gatewayUrl . "?";
        foreach ($sysParams as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($this->characet($sysParamValue, $this->postCharset)) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1);

        // 发起HTTP请求
        try {
            $resp = $this->curl($requestUrl, $apiParams);
        } catch (Exception $e) {
            die('HTTP_ERROR');
        }

        // 将返回结果转换本地文件编码
        $r = iconv($this->postCharset, $this->fileCharset . "//IGNORE", $resp);

        $respWellFormed = false;
        $signData = null;
        $respObject = json_decode($r);
        if (null !== $respObject) {
            $respWellFormed = true;
            $signData = $this->parserJSONSignData($this->method, $resp, $respObject);
        }

        if (false === $respWellFormed) {
            die('返回的HTTP文本不是标准JSON');
        }

        // 验签
        $this->checkResponseSign($this->method, $signData, $resp, $respObject);

        return $respObject->alipay_trade_query_response;
    }

    protected function curl($url, $postFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postBodyString = "";
        $encodeArray = array();
        $postMultipart = false;


        if (is_array($postFields) && 0 < count($postFields)) {

            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                {

                    $postBodyString .= "$k=" . urlencode($this->characet($v, $this->postCharset)) . "&";
                    $encodeArray[$k] = $this->characet($v, $this->postCharset);
                } else //文件上传用multipart/form-data，否则用www-form-urlencoded
                {
                    $postMultipart = true;
                    $encodeArray[$k] = new \CURLFile(substr($v, 1));
                }
            }
            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }

        if ($postMultipart) {

            $headers = array('content-type: multipart/form-data;charset=' . $this->postCharset . ';boundary=' . $this->getMillisecond());
        } else {

            $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->postCharset);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);




        $reponse = curl_exec($ch);

        if (curl_errno($ch)) {

            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }

        curl_close($ch);
        return $reponse;
    }

    function parserJSONSignData($apiName, $responseContent, $responseJSON)
    {

        $signData = new SignData();

        $signData->sign = $this->parserJSONSign($responseJSON);
        $signData->signSourceData = $this->parserJSONSignSource($apiName, $responseContent);


        return $signData;
    }

    function parserJSONSign($responseJSon)
    {

        return $responseJSon->sign;
    }

    function parserJSONSignSource($apiName, $responseContent)
    {

        $rootNodeName = str_replace(".", "_", $apiName) . $this->RESPONSE_SUFFIX;

        $rootIndex = strpos($responseContent, $rootNodeName);
        $errorIndex = strpos($responseContent, $this->ERROR_RESPONSE);


        if ($rootIndex > 0) {

            return $this->parserJSONSource($responseContent, $rootNodeName, $rootIndex);
        } else if ($errorIndex > 0) {

            return $this->parserJSONSource($responseContent, $this->ERROR_RESPONSE, $errorIndex);
        } else {

            return null;
        }
    }

    function parserJSONSource($responseContent, $nodeName, $nodeIndex)
    {
        $signDataStartIndex = $nodeIndex + strlen($nodeName) + 2;
        $signIndex = strpos($responseContent, "\"" . $this->SIGN_NODE_NAME . "\"");
        // 签名前-逗号
        $signDataEndIndex = $signIndex - 1;
        $indexLen = $signDataEndIndex - $signDataStartIndex;
        if ($indexLen < 0) {

            return null;
        }

        return substr($responseContent, $signDataStartIndex, $indexLen);
    }

    /**
     * 验签
     * @param $request
     * @param $signData
     * @param $resp
     * @param $respObject
     * @throws Exception
     */
    public function checkResponseSign($apiName, $signData, $resp, $respObject)
    {

        if (!$this->checkEmpty($this->alipayPublicKey) || !$this->checkEmpty($this->alipayrsaPublicKey)) {


            if ($signData == null || $this->checkEmpty($signData->sign) || $this->checkEmpty($signData->signSourceData)) {

                throw new Exception(" check sign Fail! The reason : signData is Empty");
            }


            // 获取结果sub_code
            $responseSubCode = $this->parserResponseSubCode($apiName, $resp, $respObject, "json");


            if (!$this->checkEmpty($responseSubCode) || ($this->checkEmpty($responseSubCode) && !$this->checkEmpty($signData->sign))) {

                $checkResult = $this->verify($signData->signSourceData, $signData->sign, $this->signType);


                if (!$checkResult) {

                    if (strpos($signData->signSourceData, "\\/") > 0) {

                        $signData->signSourceData = str_replace("\\/", "/", $signData->signSourceData);

                        $checkResult = $this->verify($signData->signSourceData, $signData->sign, $this->signType);

                        if (!$checkResult) {
                            throw new Exception("check sign Fail! [sign=" . $signData->sign . ", signSourceData=" . $signData->signSourceData . "]");
                        }
                    } else {

                        throw new Exception("check sign Fail! [sign=" . $signData->sign . ", signSourceData=" . $signData->signSourceData . "]");
                    }
                }
            }
        }
    }

    function parserResponseSubCode($apiName, $responseContent, $respObject, $format)
    {

        if ("json" == $format) {

            $rootNodeName = str_replace(".", "_", $apiName) . $this->RESPONSE_SUFFIX;
            $errorNodeName = $this->ERROR_RESPONSE;

            $rootIndex = strpos($responseContent, $rootNodeName);
            $errorIndex = strpos($responseContent, $errorNodeName);

            if ($rootIndex > 0) {
                // 内部节点对象
                $rInnerObject = $respObject->$rootNodeName;
            } elseif ($errorIndex > 0) {

                $rInnerObject = $respObject->$errorNodeName;
            } else {
                return null;
            }

            // 存在属性则返回对应值
            if (isset($rInnerObject->sub_code)) {

                return $rInnerObject->sub_code;
            } else {

                return null;
            }
        } elseif ("xml" == $format) {

            // xml格式sub_code在同一层级
            return $respObject->sub_code;
        }
    }
}

class SignData
{
    public $signSourceData = null;
    public $sign = null;
}