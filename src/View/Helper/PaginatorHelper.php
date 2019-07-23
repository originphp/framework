<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\View\Helper;

use Origin\Core\Inflector;
use Origin\View\TemplateTrait;

class PaginatorHelper extends Helper
{
    use TemplateTrait;

    public $defaultConfig = [
        'templates' => [
            'sort' => '<a href="{url}">{text}</a>',
            'sortAsc' => '<a href="{url}" class="asc">{text}</a>',
            'sortDesc' => '<a href="{url}" class="desc">{text}</a>',
            'control' => '<ul class="pagination">{content}</ul>',
            'number' => '<li class="page-item"><a class="page-link" href="{url}">{text}</a></li>',
            'numberActive' => '<li class="page-item active"><a class="page-link" href="{url}">{text}</a></li>',
            'prev' => '<li class="page-item"><a class="page-link" href="{url}">{text}</a></li>',
            'prevDisabled' => '<li class="page-item"><a class="page-link" href="#" onclick="return false;">{text}</a></li>',
            'next' => '<li class="page-item"><a class="page-link" href="{url}">{text}</a></li>',
            'nextDisabled' => '<li class="page-item"><a class="page-link" href="#" onclick="return false;">{text}</a></li>', ],
    ];

    /**
     * Creates a sort link for a particular field.
     *
     * @param string $column field name
     * @param string $text   link text
     *
     * @return string link
     */
    public function sort(string $column, string $text = null)
    {
        if ($text === null) {
            $text = Inflector::humanize($column);
        }
        $query = $this->request()->query();
        $paging = $this->params();

        $query['sort'] = $column;
        if ($paging and $column === $paging['sort']) {
            $query['direction'] = ($paging['direction'] == 'asc' ? 'desc' : 'asc');
            $template = 'sort'.ucfirst($paging['direction']);
        } else {
            $query['direction'] = 'asc';
            $template = 'sort';
        }

        $options = [
            'text' => $text,
            'url' => $this->request()->path().'?'.http_build_query($query),
        ];

        return $this->templater()->format($template, $options);
    }

    public function prev(string $text = 'Previous', array $options = [])
    {
        return $this->generateLink($text, 'prevPage', $options);
    }

    public function numbers(array $options = [])
    {
        $paging = $this->params();

        $first = $last = $current = 1;

        if ($paging) {
            $first = $paging['current'] > 5 ? ($paging['current'] - 4) : 1;
            $last = $first + 7;
            if ($last > $paging['pages']) {
                $last = $paging['pages'];
            }
            $current = $paging['current'];
        }

        $output = '';
        $query = $this->request()->query();
        for ($i = $first; $i < $last + 1; ++$i) {
            $template = 'number';
            if ($current == $i) {
                $template = 'numberActive';
            }
            $query['page'] = $i;

            $options['url'] = $this->request()->path().'?'.http_build_query($query);
            $options['text'] = $i;
            $output .= $this->templater()->format($template, $options);
        }

        return $output;
    }

    public function next(string $text = 'Next', array $options = [])
    {
        return $this->generateLink($text, 'nextPage', $options);
    }

    /**
     * Generates the html for the Pagnation control (includes previous, numbers and next).
     */
    public function control($previous = 'Previous', $next = 'Next')
    {
        $output = $this->prev($previous).$this->numbers().$this->next($next);

        return $this->templater()->format('control', ['content' => $output]);
    }

    protected function generateLink($text, $type, $options)
    {
        $defaults = ['active' => '', 'text' => $text, 'url' => '#', 'onclick' => 'return false;'];
        $options += $defaults;

        $query = $this->request()->query();
        $paging = $this->params();

        if (! isset($query['page'])) {
            $query['page'] = $paging['current'];
        }
        if ($type === 'nextPage') {
            $template = 'nextDisabled';
            if ($paging['nextPage']) {
                $query['page'] = $query['page'] + 1;
                $template = 'next';
            }
        }

        if ($type === 'prevPage') {
            $template = 'prevDisabled';
            if ($paging['prevPage']) {
                $query['page'] = $query['page'] - 1;
                $template = 'prev';
            }
        }
        $options['url'] = $this->request()->path().'?'.http_build_query($query);

        return $this->templater()->format($template, $options);
    }

    /**
     * Gets the paging paramaters
     *
     * @return array
     */
    public function params()
    {
        return $this->view()->get('paging');
    }
}
