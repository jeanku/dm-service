<?php namespace App\Util;

/**
 * Curl类
 * @desc curl Class support post&get
 * @date 2016-05-18
 */
class Curl
{
    const CURL_REQUEST_GET = 'GET';                                         //get方式的静态常量
    const CURL_REQUEST_POST = 'POST';                                       //post方式的静态常量
    const REQUEST_TIMEOUT = 30;                                             //超时时间

    protected $_ch = null;                                                  //curl的句柄

    protected function __construct()
    {
    }

    /**
     * Curl请求的方法
     * @param string $url required 请求的url
     * @param array $params required 请求的参数
     * @param string $method required 请求的方式 POST|GET
     * @param int $timeout 请求的超时时间
     * @demo Curl::request('http://asd.com.cn',['name'=>123,'age'=>88],Curl::CURL_REQUEST_POST)
     * @return array
     */
    public static function request($url, $params = array(), $method = self::CURL_REQUEST_POST, $timeout = self::REQUEST_TIMEOUT, $headers = [])
    {
        $model = new self();
        $model->_ch = curl_init();
        $model->_setParams($url, $params, $method);
        curl_setopt($model->_ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($model->_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($model->_ch, CURLOPT_HEADER, 0);
        !empty ($headers) && curl_setopt($model->_ch, CURLOPT_HTTPHEADER, $headers);
        $aRes = curl_exec($model->_ch);
        if ($error = curl_errno($model->_ch)) {
            return $model->_response([], $error, curl_error($model->_ch));
        }
        curl_close($model->_ch);
        return $aRes;
    }


    /**
     * ssl处理 如果是https，则设置相关的配置信息
     *
     * @author GaoJian
     * @date 2016-05-18
     * @param string $url required 请求的url
     * @return void
     */
    protected function _setSsl($url)
    {
        if (true === strstr($url, 'https://', true)) {
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($this->_ch, CURLOPT_DNS_USE_GLOBAL_CACHE, 0);
            curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, 1);
        }
    }


    /**
     * curl请求方式和POST|GET请求数据处理
     *
     * @author GaoJian
     * @date 2016-05-18
     * @param string $url required 请求的url
     * @param array $data required 请求的参数
     * @param string $method required 请求的方式 POST|GET
     * @return void
     */
    protected function _setParams($url, $data, $method)
    {
        $this->_setSsl($url);
        switch ($method) {
            case self::CURL_REQUEST_POST:
                $_postData = is_array($data) ? http_build_query($data) : $data;
                curl_setopt($this->_ch, CURLOPT_POST, true);
                curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $_postData);
                curl_setopt($this->_ch, CURLOPT_URL, $url);
                break;
            case self::CURL_REQUEST_GET:
                $_getData = is_array($data) ? http_build_query($data) : $data;
                $uri = preg_match('/\?/', $url) ? '&' . $_getData : '?' . $_getData;
                curl_setopt($this->_ch, CURLOPT_URL, $url . $uri);
                break;
            default:
                return false;
        }
    }

    /**
     * curl结果返回数据处理
     *
     * @author GaoJian
     * @date 2016-05-18
     * @param array $data option 业务数据
     * @param int $code option 异常code 0:表示正常
     * @param string $msg option 异常信息 异常信息，正确时为ok
     * @return array
     */
    protected function _response($data, $code = 0, $msg = 'ok')
    {
        return ['code' => $code, 'msg' => $msg, 'data' => $data];
    }

}
