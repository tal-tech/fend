<?php

namespace Fend;

use Fend\Exception\ExitException;
use Fend\Log\EagleEye;

class Response
{
    private $_type = "fpm";

    /**
     * Response constructor.
     * @param string $type 可选项fpm,swoole_http
     * @throws \Exception
     */
    public function __construct($type = "fpm")
    {
        $this->_type = $type;
    }

    /**
     * 设置返回的header
     * @param $key
     * @param $value
     * @param bool $process 是否启用swoole header头处理
     * @throws \Exception
     */
    public function header($key, $value, $process = false)
    {
        if ($this->_type === "fpm") {
            header($key . ": " . $value);
        } elseif ($this->_type == "swoole_http") {

            $response = Di::factory()->get("http_response");

            if (!$response) {
                throw new \Exception("swoole request 获取失败", 11);
            }

            $response->header($key, $value, $process);

        } else {
            throw new \Exception("未知request类型", 12);
        }
    }

    /**
     * 设置返回的cookie
     * @param string $key
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return bool
     * @throws \Exception
     */
    public function cookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        if ($this->_type === "fpm") {
            return setcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
        } elseif ($this->_type == "swoole_http") {

            $response = Di::factory()->get("http_response");

            if (!$response) {
                throw new \Exception("swoole request 获取失败", 11);
            }

            return $response->cookie($key, $value, $expire, $path, $domain, $secure, $httponly);

        } else {
            throw new \Exception("未知request类型", 12);
        }
    }

    /**
     * 设置返回http code，并附加header
     * @param int $httpCode
     * @return bool
     * @throws \Exception
     */
    public function status($httpCode)
    {
        if ($this->_type === "fpm") {
            EagleEye::setRequestLogInfo("code", $httpCode);
            return http_response_code($httpCode);
        } elseif ($this->_type == "swoole_http") {
            EagleEye::setRequestLogInfo("code", $httpCode);
            $response = Di::factory()->get("http_response");

            if (!$response) {
                throw new \Exception("swoole request 获取失败", 11);
            }
            return $response->status($httpCode);

        } else {
            throw new \Exception("未知request类型", 12);
        }

    }

    /**
     * 跳转到指定网址
     * @param string $url
     * @param int $code
     * @throws \Exception
     */
    public function redirect($url, $code = 302)
    {
        if ($this->_type === "fpm") {

            switch ($code) {
                case 301:
                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: " . $url);
                    break;
                case 302:
                    header("Location: " . $url);
                    break;
                default:
                    throw new \Exception("未知redirect code类型", 23);
            }

        } elseif ($this->_type == "swoole_http") {

            $response = Di::factory()->get("http_response");

            if (!$response) {
                throw new \Exception("swoole request 获取失败", 11);
            }

            switch ($code) {
                case 301:
                    $response->redirect($url, 301);
                    break;
                case 302:
                    $response->redirect($url, 302);
                    break;
                default:
                    throw new \Exception("未知redirect code类型", 23);
            }
        } else {
            throw new \Exception("未知request类型", 12);
        }

        //end rest code
        $this->break("");

    }

    /**
     * 返回json结果，并设置json header
     * @param $data
     * @param mixed ...$option
     * @return mixed
     * @throws \Exception
     */
    public function json($data, ...$option)
    {
        $this->header("Content-Type", "application/json; charset=utf-8");
        array_unshift($option, $data);
        return call_user_func_array("json_encode", $option);
    }

    /**
     * 结束后续代码，返回指定结果
     * @param string $data 返回的结果，仅接受字符串
     * @throws \Exception
     */
    public function break($data)
    {
        throw new ExitException($data);
    }

    /**
     * 设置输出内容保存为文件
     * @param string $filename 指定保存文件名
     * @throws \Exception
     */
    public function attachment($filename)
    {
        $this->header("Content-Disposition", "attachment;filename=" . $filename);
    }

    /**
     * 设置csv格式header，并且输出标题
     * @param array $head
     * @throws \Exception
     */
    public function csvHead($head)
    {
        $this->header("Content-Type", "text/csv");

        if ($this->_type === "fpm") {
            ob_end_flush();
            foreach ($head as $key => $headItem) {
                $head[$key] = iconv("UTF-8", "GBK//IGNORE", $headItem);
            }
            $fp = fopen('php://output', 'a');
            fputcsv($fp, $head);
            fclose($fp);
            ob_start();

        } elseif ($this->_type == "swoole_http") {
            $response = Di::factory()->get("http_response");
            $response->write($this->csvFilterLineArray($head) . "\n");
        } else {
            throw new \Exception("未知request类型", 12);
        }
        ob_start();

    }

    /**
     * 多次输出csv数据到客户端，搭配csv函数使用
     * 输出完毕后调用\Fend\Response->break;
     * @param array $data
     * @throws \Exception
     */
    public function csvAppendWrite($data)
    {

        if ($this->_type === "fpm") {
            ob_end_flush();

            $fp = fopen('php://output', 'a');

            foreach ($data as $line) {
                foreach ($line as $key => $citem) {
                    $line[$key] = iconv("UTF-8", "GBK//IGNORE", $citem);
                }
                fputcsv($fp, $line);
            }

            fclose($fp);
            ob_start();

        } elseif ($this->_type == "swoole_http") {
            $response = Di::factory()->get("http_response");
            foreach ($data as $line) {
                $response->write($this->csvFilterLineArray($line) . "\n");
            }

        } else {
            throw new \Exception("未知request类型", 12);
        }

    }

    /**
     * 过滤一行数据到csv中,并生成csv一行
     * @param array $dataArray
     * @return string
     */
    public function csvFilterLineArray($dataArray)
    {
        foreach ($dataArray as $key => $data) {
            $dataArray[$key] = iconv("UTF-8", "GBK//IGNORE", $data);
            $dataArray[$key] = str_replace(["\\", "\"", "\r", "\n"], ["", "\"\"", "", ""], $dataArray[$key]);
        }
        return "\"" . implode("\",\"", $dataArray) . "\"";
    }

}