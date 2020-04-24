<?php

namespace Faitheir\DomParser;


class Node
{
    # node name
    public $nodeName;
    # node id
    public $nodeId;
    # node level
    public $level = 0;
    # is Single
    public $isSingle = false;

    public $domId;
    # dom attrs id name class style ...
    public $domAttrs = [];
    # dom
    public $domExtra = [];

    # child nodes
    public $childs = [];

    # node content
    public $content = '';


    /**
     * Node constructor.
     * @param string $nodeName
     * @param null $nodeId
     * @param null $level
     * @param null $isSingle
     */
    public function __construct($nodeName = '', $nodeId = null, $level = null, $isSingle = null)
    {
        $this->nodeName = $nodeName;

        if ($nodeId)
            $this->nodeId = $nodeId;

        if (!is_null($level))
            $this->level = (int) $level;
        if (!is_null($isSingle))
            $this->isSingle = (bool) $isSingle;
    }

    /**
     * set Node id
     * @param string $nodeId
     * @return $this
     */
    public function setNodeId($nodeId = '')
    {
        if ($nodeId)
            $this->nodeId = $nodeId;

        if (empty($this->nodeId))
            $this->nodeId = Tools::getInstance()->nodeId();

        return $this;
    }

    /**
     * @param string $domId
     * @return $this
     */
    public function setDomId($domId = '')
    {
        $this->domId = $domId;
        return $this;
    }

    /**
     * @param bool $isSingle
     * @return $this
     */
    public function setIsSingle($isSingle = true)
    {
        $this->isSingle = $isSingle;
        return $this;
    }

    public function setContent($content = '')
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param null $match
     * @return $this
     */
    public function setAttrMatch($match = null)
    {
        $key    = !empty($match[1]) ? $match[1] : ($match[3] ?? '');
//        $value  = !empty($match[2]) ? $match[2] : null;
        $value  = (isset($match[2]) && strpos($match[0], '=') !== false) ? $match[2] : null;

        if (!$key)
            return $this;

        if ($key == 'class' && $value) {
            $values = array_filter(explode(' ', $value), function ($item) {
                return strlen($item) > 0;
            });
            $this->domAttrs[$key] = $values;
            return $this;
        } elseif ($key == 'data-genid' && $value) {
            $this->setNodeId($value);
        } elseif ($key == 'id' && $value) {
            $this->setDomId($value);
        } else {
            $this->domAttrs[$key] = $value;
        }

        return $this;
    }


    /**
     * @return $this
     */
    public static function generRootNode()
    {
        return (new self('root'))->setNodeId();
    }

    /**
     * @param string $content
     * @return $this
     */
    public static function generContentNode($content = '')
    {
        return (new self('content'))->setNodeId()->setContent($content);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        # config
        $config = Config::getInstance();
        $tagIndent = $config->get('tag_indent');
        $hideGenid = $config->get('hide_genid');
        $tagIndentString    = str_repeat($tagIndent, $this->level);

        # content
        if (in_array($this->nodeName, ['content'])) {
            return $tagIndentString . $this->content . PHP_EOL;
        }
        # root
        if (in_array($this->nodeName, ['root'])) {
            return $this->_invParseChilds($this->childs);
        }
        if (empty($this->nodeName))
            return '';

        # normal
        $string = $tagIndentString . '<' . $this->nodeName . ' ';
        # id
        if (!empty($this->domId))
            $string .= ' id="' . $this->domId . '" ';
        # gener id
        if ($hideGenid == false)
            $string .= ' data-genid="' . $this->nodeId . '" ';
        # class
        if (!empty($this->domAttrs['class'])) {
            $string .= ' class="' . implode(' ', $this->domAttrs['class']) . '" ';
            unset($this->domAttrs['class']);
        }
        # style
        if (!empty($this->domAttrs['style'])) {
//            $string .= implode(' ', $this->domAttrs['class']);
            unset($this->domAttrs['style']);
        }
        # attrs
        if (!empty($this->domAttrs)) {
            foreach ($this->domAttrs as $k => $v) {
                $string .= ' ' . $k . '="' . $v . '" ';
            }
        }
        # is single
        if ($this->isSingle == true)
            return $string . ' />' . PHP_EOL;

        # start tag end
        $string .= ' >' . PHP_EOL ;

        if (!empty($this->childs))
            $string .= $this->_invParseChilds($this->childs);

        # end
        $string .= $tagIndentString . '</' . $this->nodeName . '>' . PHP_EOL;

        return $string;
    }

    private function _invParseChilds($childs)
    {
        if (empty($childs))
            return '';

        $string = '';
        foreach ($childs as $child) {
            $string .= (string) $child;
        }
        return $string;
    }

}