<?php

namespace Pagination;

/*
 * Paginator.php
 *
 * (c) 2014 overtrue <anzhengchao@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author overtrue <anzhengchao@gmail.com>
 * @github https://github.com/overtrue
 * @url    http://overtrue.me
 * @date   2014-10-23T20:05:33
 */
use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Serializable;

class Paginator implements
    ArrayAccess,
    Countable,
    IteratorAggregate,
    Serializable,
    JsonSerializable
{
    public $count;
    public $totalPages;
    public $totalRows;
    protected $pager;
    protected $pageSize;
    public $nowPage;
    public $html;
    public $data;

    /**
     * Constructor.
     *
     * @param \Slim\Http\Request $request
     * @param string             $pager
     */
    public function __construct($pager = 'page')
    {
        $this->pager = $pager;
    }

    /**
     * @param $data
     * @param $total
     * @param int $pageSize
     *
     * @return array
     */
    public function make($data, $total, $pageSize = 10)
    {
        $this->count = $this->totalRows = abs($total);
        $this->pageSize = $pageSize;
        $this->data = $data;
        $this->html = $this->links();

        return array(
            'count'      => $this->count,
            'totalPages' => $this->totalPages,
            'totalRows'  => $this->totalRows,
            'nowPage'    => $this->nowPage,
            'html'       => $this->html,
            'data'       => $this->data,
        );
    }

    /**
     * Return current page.
     *
     * @return int
     */
    public function getCurrentPage($total = null)
    {
        //$page = abs(app()->request->get('page', 1));
        $page = $this->nowPage = $_REQUEST['p'] ? floatval($_REQUEST['p']) : floatval($_REQUEST['load_count']);
        if ($total) {
            $this->totalRows = $total;
        }
        $page >= 1 || $page = 1;
        if ($this->data) {
            $page <= $this->totalPages || $page = $this->totalPages;
        }

        return $page;
    }

    /**
     * Return total pages.
     *
     * @return int
     */
    public function getTotalPage()
    {
        $this->pageSize > 0 || $this->pageSize = 10;
        $totalPage = ceil($this->totalRows / $this->pageSize);
        $totalPage >= 1 || $totalPage = 1;

        return $totalPage;
    }

    public function links()
    {
        $html = '';
        $this->totalPages = $this->getTotalPage();
        $currentPage = $this->getCurrentPage();
        if ($this->totalPages < 10) {
            for ($i = 1; $i <= $this->totalPages; $i++) {
                $active = $i == $currentPage ? 'class="current" ' : '';
                $html .= "<a {$active}href='".$this->getLink($i)."'>$i</a>";
            }
        } else {
            if ($currentPage > 3) {
                $html .= '<a href='.$this->getLink(1).'>&laquo;</a>';
                $start = $currentPage - 2;
            } else {
                $start = 1;
            }
            for ($i = $start; $i <= $currentPage; $i++) {
                $active = $i == $currentPage ? 'class="current" ' : '';
                $html .= "<a {$active}href=".$this->getLink($i).">$i</a>";
            }
            for ($i = $currentPage + 1; $i <= $currentPage + 3; $i++) {
                $active = $i == $currentPage ? 'class="current" ' : '';
                $html .= "<a {$active}href=".$this->getLink($i).">$i</a>";
            }
            if ($this->totalPages - $currentPage >= 5) {
                $html .= "<a href='javascript:void(0)'>...</a>";
                $html .= '<a href='.$this->getLink($this->totalPages).">$this->totalPages</a>";
            }
        }
        /*$html = '<ul class="pagination">';
        $this->totalPages   = $this->getTotalPage();
        $currentPage = $this->getCurrentPage();
        if ($this->totalPages < 10) {
            for ($i = 1; $i <= $this->totalPages; $i++) {
                $active = $i == $currentPage ? 'class="active"':'';
                $html .= "<li $active><a href=".$this->getLink($i).">$i</a></li>";
            }
        } else {
            if ($currentPage > 3) {
                $html .= "<li><a href=".$this->getLink(1).">&laquo;</a></li>";
                $start = $currentPage - 2;
            } else {
                $start = 1;
            }
            for ($i = $start; $i <= $currentPage; $i++) {
                $active = $i == $currentPage ? 'class="active"':'';
                $html .= "<li $active><a href=".$this->getLink($i).">$i</a></li>";
            }
            for ($i = $currentPage + 1; $i <= $currentPage + 3; $i++) {
                $active = $i == $currentPage ? 'class="active"':'';
                $html .= "<li $active><a href=".$this->getLink($i).">$i</a></li>";
            }
            if ($this->totalPages - $currentPage >= 5) {
                $html .= "<li><a href='javascript:void(0)'>...</a></li>";
                $html .= "<li><a href=".$this->getLink($this->totalPages).">$this->totalPages</a></li>";
            }
        }*/
        return $html;
    }

    /**
     * getLink.
     *
     * @param int $page
     *
     * @return string
     */
    public function getLink($page)
    {
        $url = $_SERVER['QUERY_STRING'];
        $url = preg_replace("/<script(.*?)<\/script>/is", '', $url);
        $url = preg_replace('/<frame(.*?)>/is', '', $url);
        $url = preg_replace("/<\/fram(.*?)>/is", '', $url);
        $url = str_replace('&amp;', '&', $url);
        $url = str_replace('&nbsp;', ' ', $url);
        $url = str_replace("'", '&#39;', $url);
        $url = str_replace('"', '&quot;', $url);
        $url = str_replace('<', '&lt;', $url);
        $url = str_replace('>', '&gt;', $url);
        $url = str_replace("\t", '&nbsp; &nbsp; ', $url);
        $url = str_replace("\r", '', $url);
        $url = str_replace('   ', '&nbsp; &nbsp;', $url);
        $url = preg_replace(sprintf('/(#.+$|%s=[0-9]+)/is', C('VAR_PAGE')), '', t($_SERVER['SCRIPT_NAME']).'?'.$url);
        // $url = eregi_replace("(#.+$|".C('VAR_PAGE')."=[0-9]+)", '', t($_SERVER['PHP_SELF']).'?'.$url);
        $url = $url.(strpos($url, '?') ? '' : '?');
        // $url = eregi_replace("(&+)", '&', $url);
        $url = preg_replace('/(\&+)/is', '&', $url);
        $url = trim($url, '&');

        return $url.'&'.C('VAR_PAGE').'='.$page;
    }

    /** {@inhertDoc} */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /** {@inhertDoc} */
    public function serialize()
    {
        return serialize($this->data);
    }

    /** {@inhertDoc} */
    public function unserialize($data)
    {
        return $this->data = unserialize($data);
    }

    /** {@inhertDoc} **/
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /** {@inhertDoc} */
    public function count($mode = COUNT_NORMAL)
    {
        return count($this->data, $mode);
    }

    /**
     * Get a data by key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Assigns a value to the specified data.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Whether or not an data exists by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Unsets an data by key.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Assigns a value to the specified offset.
     *
     * @param string $offset
     * @param mixed  $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * Whether or not an offset exists.
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Unsets an offset.
     *
     * @param string $offset
     *
     * @return array
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->data[$offset]);
        }
    }

    /**
     * Returns the value at specified offset.
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? array_get($this->data, $offset) : null;
    }
}
