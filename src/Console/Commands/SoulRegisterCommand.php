<?php


namespace Soul\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Question\Question;

class SoulRegisterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soul:register 
    {--admin-url=http://127.0.0.1:9095 : Soul网关管理地址}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'soul:register  Soul路由网关注册';


    /**
     * SoulRegisterCommand constructor.
     *
     */
    public function __construct()
    {
        Command::__construct();
    }


    public function handle()
    {
        $adminUrl = $this->input->getOption("admin-url");
        while (true) {
            $appName = strtolower($this->output->askQuestion(new Question('请输入应用名称:', 'http')));
            if (!empty($appName)) {
               break;
            }
        }
        while (true) {
            $contextPath = strtolower($this->output->askQuestion(new Question('请输入上下文地址:', '/http')));
            if (!empty($contextPath)) {
                break;
            }
        }
        while (true) {
            $host = strtolower($this->output->askQuestion(new Question('请输入应用地址:', '127.0.0.1')));
            if (!empty($host)) {
                break;
            }
        }
        while (true) {
            $port = strtolower($this->output->askQuestion(new Question('请输入应用端口:', 80)));
            if (!empty($port)) {
                break;
            }
        }
        while (true) {
            $res = strtolower($this->output->askQuestion(new Question('请输入网关地址:', $adminUrl)));
            if (!empty($appName)) {
                $adminUrl=$res;
                break;
            }
        }
        $adminUrl .= "/soul-client/springmvc-register";
        $this->table(['应用名称', '主机地址', '主机端口', '上下文地址', '网关地址'], [[$appName, $host, $port, $contextPath, $adminUrl]]);
        while (true) {
            $res = strtolower($this->output->askQuestion(new Question('您是否要进行网关注册么？(y/n)', 'n')));
            if ($res == 'n') {
                $this->output->error('您取消了任务！');
                return;
            } elseif ($res == 'y') {
                break;
            }
        }
        $urls = [];
        foreach (app('router')->getRoutes() as $route) {
            array_push($urls, $contextPath.'/' . $route->uri());
        }
        if (class_exists('\Dingo\Api\Routing\Router')) {
            $router = app('api.router');
            foreach ($router->getRoutes() as $collection) {
                /**  @var \Dingo\Api\Routing\Route $route */
                foreach ($collection->getRoutes() as $route) {
                    array_push($urls, $contextPath.$route->uri());
                }
            }
        }
        $count = count($urls);
        $default = ["appName" => $appName, "context" => $contextPath, "path" => "", "pathDesc" => "", "rpcType" => "http", "host" => $host, "port" => $port, "ruleName" => "", "enabled" => true];
        foreach ($urls as $k => $url) {
            $info = array_merge($default, ['path' => $url, 'ruleName' => $url]);
            $this->post($adminUrl,$info,($k+1)."/$count");
        }
        $this->output->newLine();
        $this->output->success("注册完成");
    }

    /**
     * 请求
     *
     * @param string $url  请求地址
     * @param array  $data 请求数据
     * @param string $msg  消息
     */
    private function post($url, $data,$msg='')
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($json))
            );
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//是否检测服务器的证书是否由正规浏览器认证过的授权CA颁发的
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//是否检测服务器的域名与证书上的是否一致
            @curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //强制使用IPV4协议解析域名，否则在支持IPV6的环境下请求会异常慢
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $result = curl_exec($ch);
            curl_close($ch);
            if ($result == "success") {
                $this->output->writeln("[$msg]register success : " . $json);
            } else {
                $this->output->warning("[$msg]register error : ($result)" . $json);
            }
        } catch (\Exception $e) {
            $this->output->warning("[$msg]register exception : " . $json);
        }
    }
}
