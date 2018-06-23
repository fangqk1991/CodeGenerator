<?php

namespace Generator\Lib;

class Template
{
    private $arrayConfig = array();
    public $debug = array();

    private $valueMap = array();

    private function createDirs()
    {
        if(!is_dir($this->arrayConfig['templateDir']))
            mkdir($this->arrayConfig['templateDir'], 0777, TRUE);

        if(!is_dir($this->arrayConfig['compileDir']))
            mkdir($this->arrayConfig['compileDir'], 0777, TRUE);

        if(!is_dir($this->arrayConfig['cacheDir']))
            mkdir($this->arrayConfig['cacheDir'], 0777, TRUE);
    }

    public function __construct($arrayConfig = array())
    {
        $this->debug['begin'] = microtime(TRUE);

        $this->arrayConfig = array(
            'templateDir' => __DIR__ . '/../template',
            'compileDir' => __DIR__ . '/../bin',
            'cacheDir' => __DIR__ . '/../cache',
            'cache_html' => FALSE,
            'cache_time' => 7200,
            'php_turn' => TRUE,
            'debug' => FALSE
        );

        $this->arrayConfig = $arrayConfig + $this->arrayConfig;

        $this->createDirs();

        if(!is_dir($this->arrayConfig['templateDir'])
            || !is_dir($this->arrayConfig['compileDir'])
            || !is_dir($this->arrayConfig['cacheDir']))
        {
            exit('templateDir or compileDir or cacheDir is not found');
        }
    }

    public function setConfig($key, $value = NULL)
    {
        if(is_array($key))
        {
            $this->arrayConfig = $key + $this->arrayConfig;
        }
        else
        {
            $this->arrayConfig[$key] = $value;
        }
    }

    public function getConfig($key = NULL)
    {
        if($key)
        {
            return $this->arrayConfig[$key];
        }
        else
        {
            return $this->arrayConfig;
        }
    }

    public function assign($key, $value)
    {
        $this->valueMap[$key] = $value;
    }

    public function assignArray($array)
    {
        if(is_array($array))
        {
            foreach($array as $k => $v)
            {
                $this->valueMap[$k] = $v;
            }
        }
    }

    public function isCaching()
    {
        return $this->arrayConfig['cache_html'];
    }

    public function canReadFromCache($key)
    {
        $cacheFile = $this->cacheFile($key);
        if($this->isCaching())
        {
            $timeFlag = (time() - @filemtime($cacheFile)) < $this->arrayConfig['cache_time'];

            if(is_file($cacheFile) && filesize($cacheFile) > 1 && $timeFlag)
            {
                return TRUE;
            }
        }

        return FALSE;
    }

    public function templateFile($key)
    {
        return sprintf('%s/%s.php', $this->arrayConfig['templateDir'], $key);
    }

    public function compiledFile($key, $suffix, $ext = 'php')
    {
        return sprintf('%s/%s.%s.%s', $this->arrayConfig['compileDir'], $key, $suffix, $ext);
    }

    public function cacheFile($key)
    {
        return sprintf('%s/%s.html', $this->arrayConfig['cacheDir'], $key);
    }

    public $output = NULL;

    public function show($key)
    {
        $tmpl_path = $this->templateFile($key);

        if(!is_file($tmpl_path))
        {
            exit('找不到对应的模板');
        }

        $cacheFile = $this->cacheFile($key);

        if($this->canReadFromCache($key))
        {
            $this->debug['cached'] = 'true';

            ob_start();

            readfile($cacheFile);

            $this->output = ob_get_contents();
        }
        else
        {
            $this->debug['cached'] = 'false';

            ob_start();

//            extract($this->valueMap, EXTR_OVERWRITE);
//            if(!is_file($compileFile) || filemtime($compileFile) < filemtime($tmpl_path))
            {
                $compileTool = new CompileClass($tmpl_path, $this->arrayConfig);
                $content = $compileTool->compile();
                $compileFile = $this->compiledFile($key, substr(md5($content), 0, 8), 'php');
                if(!is_file($compileFile))
                {
                    $compileTool->saveToDisk($compileFile);
                }

                $func = function($file) {
                    include $file;
                };
                $func($compileFile);
            }

            $compileFile = $this->compiledFile($key, substr(md5($content), 0, 8), 'html');
            $this->output = ob_get_contents();
            file_put_contents($compileFile, $this->output);

            if($this->isCaching())
            {
                file_put_contents($cacheFile, $this->output);
            }
        }

        $this->debug['spend'] = microtime(TRUE) - $this->debug['begin'];
        $this->debug['count'] = count($this->valueMap);
        $this->debugInfo();
    }

    public function debugInfo()
    {
        if($this->arrayConfig['debug'])
        {
            echo PHP_EOL, '---------- debug info ----------', PHP_EOL;
            echo '程序运行日期: ', date('Y-m-d h:i:s'), PHP_EOL;
            echo '模板解析耗时: ', $this->debug['spend'], '秒', PHP_EOL;
            echo '模板包含标签数目: ', $this->debug['count'], PHP_EOL;
            echo '是否使用静态缓存: ', $this->debug['cached'], PHP_EOL;
            echo '模板引擎示例参数: ';
            var_dump($this->getConfig());
        }
    }

    public function clean($key = NULL)
    {
        if($key === NULL)
        {
            $path = $this->cacheFile('*');
        }
        else
        {
            $path = $this->cacheFile($key);
        }

        foreach((array)$path as $v)
        {
            unlink($v);
        }
    }

    /*
     * key 对应的 value 是字符串，并且长度大于 0
     */
    public function isRich($key)
    {
        if(array_key_exists($key, $this->valueMap)
            && is_string($this->valueMap[$key])
            && strlen($this->valueMap[$key]) > 0)
        {
            return TRUE;
        }

        return FALSE;
    }

    public function getValue($key, $default_value)
    {
        if(array_key_exists($key, $this->valueMap))
        {
            return $this->valueMap[$key];
        }
        else
        {
            return $default_value;
        }
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function __get($key)
    {
        if(array_key_exists($key, $this->valueMap))
        {
            return $this->valueMap[$key];
        }
        else
        {
            return NULL;
        }
    }

    public function printValueWithKey($key, $default_value = '')
    {
        $value = $this->getValue($key, $default_value);
        echo $value;
    }
}