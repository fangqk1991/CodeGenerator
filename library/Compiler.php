<?php

namespace FC\Generator\Lib;

class Compiler
{
    private $_tplFile;
    private $_valueMap;

    public function __construct($tplFile, array $valueMap)
    {
        $this->_tplFile = $tplFile;
        $this->_valueMap = $valueMap;
    }

    public function compile()
    {
        $dic = $this->_valueMap;

        $content = file_get_contents($this->_tplFile);

        $blank = "[\ \t]";
        $enter = "[\n|\r]";

        $content = preg_replace_callback(
            sprintf('#%s*\{foreach\s+\$(\w+)\s+as\s+\$(\w+)\s*=>\s*\$(\w+)\s*\}([\s\S]*?)\{/foreach\}%s*%s#m',
                $blank, $blank, $enter),
            function ($matches) use ($dic) {

                $mapMatched = $matches[1];
                $keyMatched = $matches[2];
                $valueMatched = $matches[3];
                $contentMatched = $matches[4];

                $content = $this->_formatContent($contentMatched);

                $arr = array();
                $data = $dic[$mapMatched];
                foreach ($data as $key => $v)
                {
                    array_push($arr, preg_replace_callback(
                        sprintf('#(\{\$)(%s|%s)([-.\w\'"\[\]]*\})#', $keyMatched, $valueMatched),
                        function ($matches) use ($key, $mapMatched, $valueMatched) {
                            if($matches[2] === $valueMatched)
                                return sprintf('%s%s["%s"]%s', $matches[1], $mapMatched, $key, $matches[3]);
                            return $key;
                        },
                        $content
                    ));
                }

                return implode("\n", $arr);
            },
            $content
        );

        $content = preg_replace_callback(
            '#\{\$(\w+)([-.\w\'"\[\]]*)\}#',
            function ($matches) use ($dic) {
                $key = $matches[1];
                $ext = $matches[2];

                if(isset($dic[$key]))
                {
                    $str = '';
                    $cmd = sprintf('$str = $dic["%s"]%s;', $key, $ext);
                    eval($cmd);
                    return $str;
                }

                return '';
            },
            $content
        );

        return $content;
    }

    private function _formatContent($content)
    {
        // 去除空白行
        $items = explode(PHP_EOL, $content);
        $arr = array_filter($items, function ($obj) {
            return strlen(trim($obj)) > 0;
        });
        return implode(PHP_EOL, $arr);
    }
}