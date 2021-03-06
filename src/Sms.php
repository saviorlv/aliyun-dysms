<?php
namespace saviorlv\aliyun;
ini_set("display_errors", "on");

use saviorlv\aliyun\Core\Config;
use saviorlv\aliyun\Core\Profile\DefaultProfile;
use saviorlv\aliyun\Core\DefaultAcsClient;
use saviorlv\aliyun\Api\Sms\Request\SendSmsRequest;
use saviorlv\aliyun\Api\Sms\Request\QuerySendDetailsRequest;

// 加载区域结点配置
Config::load();

/**
 * 阿里大鱼SDK
 * User: saviorlv
 * Date: 17/10/23
 * Time: 上午11:54
 * @property \saviorlv\aliyun\Core\DefaultAcsClient acsClient
 */
class Sms
{
    // 短信API产品名
    private    $product = "Dysmsapi";
    // 短信API产品域名
    private    $domain = "dysmsapi.aliyuncs.com";
    // 暂时不支持多Region
    private    $region = "cn-hangzhou";
    // 服务结点
    private    $endPointName = "cn-hangzhou";
    // accessKeyId
    public $accessKeyId;
    // accessKeySecret
    public $accessKeySecret;
    // aceClient
    private $acsClient;

    /**
     * Sms constructor.
     * @param $accessKeyId
     * @param $accessKeySecret
     */
    public function __construct($accessKeyId,$accessKeySecret)
    {
        if(!$accessKeyId){
            throw new \Exception('accessKeyId can not be blank.');
        }else{
            $this->accessKeyId = $accessKeyId;
        }
        if(!$accessKeySecret){
            throw new \Exception('accessKeySecret can not be blank.');
        }else{
            $this->accessKeySecret = $accessKeySecret;
        }

        // 初始化用户Profile实例
        $profile = DefaultProfile::getProfile($this->region, $this->accessKeyId, $this->accessKeySecret);

        // 增加服务结点
        DefaultProfile::addEndpoint($this->endPointName, $this->region, $this->product, $this->domain);

        // 初始化AcsClient用于发起请求
        $this->acsClient = new DefaultAcsClient($profile);
    }

    /**
     * 发送短信范例
     *
     * @param string $signName <p>
     * 必填, 短信签名，应严格"签名名称"填写，参考：<a href="https://dysms.console.aliyun.com/dysms.htm#/sign">短信签名页</a>
     * </p>
     * @param string $templateCode <p>
     * 必填, 短信模板Code，应严格按"模板CODE"填写, 参考：<a href="https://dysms.console.aliyun.com/dysms.htm#/template">短信模板页</a>
     * (e.g. SMS_0001)
     * </p>
     * @param string $phoneNumbers 必填, 短信接收号码 (e.g. 12345678901)
     * @param array|null $templateParam <p>
     * 选填, 假如模板中存在变量需要替换则为必填项 (e.g. Array("code"=>"12345", "product"=>"阿里通信"))
     * </p>
     * @param string|null $outId [optional] 选填, 发送短信流水号 (e.g. 1234)
     * @return stdClass
     */
    public function sendSms($signName, $templateCode, $phoneNumbers, $templateParam = null, $outId = null) {

        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();

        // 必填，设置雉短信接收号码
        $request->setPhoneNumbers($phoneNumbers);

        // 必填，设置签名名称
        $request->setSignName($signName);

        // 必填，设置模板CODE
        $request->setTemplateCode($templateCode);

        // 可选，设置模板参数
        if($templateParam) {
            $request->setTemplateParam(json_encode($templateParam));
        }

        // 可选，设置流水号
        if($outId) {
            $request->setOutId($outId);
        }

        // 发起访问请求
        $acsResponse = $this->acsClient->getAcsResponse($request);

        // 打印请求结果
        $acsResponse = self::object_array($acsResponse);
        if(array_key_exists('Message', $acsResponse) && $acsResponse['Code']=='OK'){
            return json_encode([
                'code' => 200,
                'message' => '验证码发送成功'
            ]);
        }
        return Utils::result($acsResponse);

        //return $acsResponse;
    }

    /**
     * 批量发送短信
     * @param $signName
     * @param $templateCode
     * @param $phoneNumbers
     * @param null $templateParam
     * @return false|string
     */
    public function sendBatchSms($signName, $templateCode, $phoneNumbers, $templateParam = null) {

        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendBatchSmsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        // 必填:待发送手机号。支持JSON格式的批量调用，批量上限为100个手机号码,批量调用相对于单条调用及时性稍有延迟,验证码类型的短信推荐使用单条调用的方式
        $request->setPhoneNumberJson(json_encode($phoneNumbers, JSON_UNESCAPED_UNICODE));

        // 必填:短信签名-支持不同的号码发送不同的短信签名
        $request->setSignNameJson(json_encode($signName, JSON_UNESCAPED_UNICODE));

        // 必填:短信模板-可在短信控制台中找到
        $request->setTemplateCode($templateCode);

        // 必填:模板中的变量替换JSON串,如模板内容为"亲爱的${name},您的验证码为${code}"时,此处的值为
        // 友情提示:如果JSON中需要带换行符,请参照标准的JSON协议对换行符的要求,比如短信内容中包含\r\n的情况在JSON中需要表示成\\r\\n,否则会导致JSON在服务端解析失败
        $request->setTemplateParamJson(json_encode($templateParam, JSON_UNESCAPED_UNICODE));

        // 可选-上行短信扩展码(扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段)
        // $request->setSmsUpExtendCodeJson("[\"90997\",\"90998\"]");

        // 发起访问请求
        $acsResponse = $this->acsClient->getAcsResponse($request);
        // 打印请求结果
        $acsResponse = self::object_array($acsResponse);
        if(array_key_exists('Message', $acsResponse) && $acsResponse['Code']=='OK'){
            return json_encode([
                'code' => 200,
                'message' => '验证码发送成功'
            ]);
        }
        return Utils::result($acsResponse);
        //return $acsResponse;
    }

    /**
     * 查询短信发送情况范例
     *
     * @param string $phoneNumbers 必填, 短信接收号码 (e.g. 12345678901)
     * @param string $sendDate 必填，短信发送日期，格式Ymd，支持近30天记录查询 (e.g. 20170710)
     * @param int $pageSize 必填，分页大小
     * @param int $currentPage 必填，当前页码
     * @param string $bizId 选填，短信发送流水号 (e.g. abc123)
     * @return stdClass
     */
    public function queryDetails($phoneNumbers, $sendDate, $pageSize = 10, $currentPage = 1, $bizId=null) {

        // 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
        $request = new QuerySendDetailsRequest();

        // 必填，短信接收号码
        $request->setPhoneNumber($phoneNumbers);

        // 选填，短信发送流水号
        $request->setBizId($bizId);

        // 必填，短信发送日期，支持近30天记录查询，格式Ymd
        $request->setSendDate($sendDate);

        // 必填，分页大小
        $request->setPageSize($pageSize);

        // 必填，当前页码
        $request->setCurrentPage($currentPage);

        // 发起访问请求
        $acsResponse = $this->acsClient->getAcsResponse($request);

        // 打印请求结果
        // var_dump($acsResponse);

        return $acsResponse;
    }

    /**
     * 对象转数组
     * @param $array
     * @return array
     */
    public static function object_array($array) {
        if(is_object($array)) {
            $array = (array)$array;
         } if(is_array($array)) {
            foreach($array as $key=>$value) {
                $array[$key] = self::object_array($value);
            }
        }
        return $array;
    }
}
