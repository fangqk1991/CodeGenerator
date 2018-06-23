<?php

namespace Generator\Lib;

class CompileClass
{
    private $templateFile;
    private $content;
    private $config;

    private $T_P = array();
    private $T_R = array();

    private $referLink = array();

    public function __construct($templateFile, $config, $referLink = array())
    {
        $this->templateFile = $templateFile;
        $this->content = file_get_contents($templateFile);
        $this->referLink = $referLink;

        if($config['php_turn'] === FALSE)
        {
            $this->T_P[] = '#<\?(=|php|)(.+?)\?>#is';
            $this->T_R[] = '&lt;?\1\2 ?&gt;';
        }

        $valid_var_reg = sprintf('[.a-zA-z_%c-%c][.a-zA-z0-9_%c-%c]*', 0x7f, 0xff, 0x7f, 0xff);

        $this->T_P[] = sprintf('#\{\$(%s)\}#', $valid_var_reg);
        $this->T_R[] = '<?php $this->printValueWithKey("\1"); ?>';

        $this->T_P[] = sprintf('#\{\$(%s)!\'([^\']*)\'\}#', $valid_var_reg);
        $this->T_R[] = '<?php $this->printValueWithKey("\1", "\2"); ?>';

        /*
        $this->T_P[] = sprintf('#\{(loop|foreach)\ \$(%s)\}#i', $valid_var_reg);
        $this->T_R[] = '<?php foreach((array)$this-$\2] as $K => $V) { ?>';

        $this->T_P[] = '#\{\/(loop|foreach|if)\}#i';
        $this->T_R[] = '<?php } ?>';

        $this->T_P[] = '#\{([K|V])\}#';
        $this->T_R[] = '<?php echo $\1; ?>';

        $this->T_P[] = '#\{if\ (.*?)\}#i';
        $this->T_R[] = '<?php if(\1) { ?>';

        $this->T_P[] = '#\{(else if|elseif)(.*?)\}#i';
        $this->T_R[] = '<?php } else if(\2) {?>';

        $this->T_P[] = '#\{else\}#i';
        $this->T_R[] = '<?php } else { ?>';

        $this->T_P[] = '#\{(\#|\*)(.*?)(\#|\*)\}#';
        $this->T_R[] = '';
        */
    }

    public function referLinkForChild()
    {
        $referLink = $this->referLink;
        array_push($referLink, $this->templateFile);
        return $referLink;
    }

    /**
     * 预处理，递归处理 include 标签
     */
    public function preCompile()
    {
        // 避免循环引用文件
        foreach($this->referLink as $refer)
        {
            if($refer == $this->templateFile)
            {
                return '';
            }
        }

        $reg = '#\{import\s+\'([^\']+)\'\}#';
        $config = $this->config;
        $referLink = $this->referLinkForChild();
        $this->content = preg_replace_callback($reg, function ($matches) use ($config, $referLink) {
            $templateFile = sprintf('%s/template/%s', __DIR__, $matches[1]);
            $compileTool = new CompileClass($templateFile, $config, $referLink);
            $content = $compileTool->preCompile();
            return $content;
        }, $this->content);

        return $this->content;
    }

    public function compile()
    {
        $content = $this->preCompile();
        $this->content = preg_replace($this->T_P, $this->T_R, $content);
        return $this->content;
    }

    public function saveToDisk($compileFile)
    {
        $dir = dirname($compileFile);
        if(!is_dir($dir))
        {
            mkdir($dir, 0755, TRUE);
        }

        file_put_contents($compileFile, $this->content);
    }
}