<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Http\View\Helper;

use Origin\Inflector\Inflector;
use Origin\Http\View\TemplateTrait;

class PaginatorHelper extends Helper
{
    use TemplateTrait;

    protected $defaultConfig = [
        'templates' => [
            'sort' => '<a href="{url}">{text}</a>',
            'sortAsc' => '<a href="{url}" class="asc">{text}</a>',
            'sortDesc' => '<a href="{url}" class="desc">{text}</a>',
            'control' => '<ul class="pagination">{content}</ul>',
            'number' => '<li class="page-item"><a class="page-link" href="{url}">{text}</a></li>',
            'numberActive' => '<li class="page-item active"><a class="page-link" href="{url}">{text}</a></li>',
            'prev' => '<li class="page-item"><a class="page-link" href="{url}">{text}</a></li>',
            'prevDisabled' => '<li class="page-item disabled"><a class="page-link" href="#" onclick="return false;">{text}</a></li>',
            'next' => '<li class="page-item"><a class="page-link" href="{url}">{text}</a></li>',
            'nextDisabled' => '<li class="page-item disabled"><a class="page-link" href="#" onclick="return false;">{text}</a></li>', ],
    ];

    /**
     * Creates a sort link for a particular field.
     *
     * @param string $column field name
     * @param string $text   link text
     * @return string link
     */
    public function sort(string $column, string $text = null): string
    {
        if ($text === null) {
            $text = Inflector::human($column);
        }
        $query = $this->request()->query();
        $paging = $this->params();

        $query['sort'] = $column;
        if ($paging && $column === $paging['sort']) {
            $query['direction'] = ($paging['direction'] === 'asc' ? 'desc' : 'asc');
            $template = 'sort' . ucfirst($paging['direction']);
        } else {
            $query['direction'] = 'asc';
            $template = 'sort';
        }

        $options = [
            'text' => $text,
            'url' => $this->request()->path() . '?' . http_build_query($query)
        ];

        return $this->templater()->format($template, $options);
    }

    /**
     * Generates the previous link
     *
     * @param string $text
     * @param array $options
     * @return string
     */
    public function prev(string $text = 'Previous', array $options = []): string
    {
        return $this->generateLink($text, 'prevPage', $options);
    }

    /**
     * Generates the numbers string
     *
     * @param array $options
     * @return string
     */
    public function numbers(array $options = []): string
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

            $options['url'] = $this->request()->path() . '?' . http_build_query($query);
            $options['text'] = $i;
            $output .= $this->templater()->format($template, $options);
        }

        return $output;
    }

    /**
     * Generates the next link
     *
     * @param string $text
     * @param array $options
     * @return string
     */
    public function next(string $text = 'Next', array $options = []): string
    {
        return $this->generateLink($text, 'nextPage', $options);
    }

    /**
     * Generates the paginator control, which includes previous,numbers and next links
     *
     * @param string $previous
     * @param string $next
     * @return string
     */
    public function control($previous = 'Previous', $next = 'Next'): string
    {
        $output = $this->prev($previous) . $this->numbers() . $this->next($next);

        return $this->templater()->format('control', ['content' => $output]);
    }

    /**
     * @param string $text
     * @param string $type
     * @param array $options
     * @return string
     */
    protected function generateLink(string $text, string $type, array $options): string
    {
        $options += ['active' => '', 'text' => $text, 'url' => '#'];
       
        $query = $this->request()->query();
        $paging = $this->params();

        if (! isset($query['page'])) {
            $query['page'] = $paging['current'];
        }
        if ($type === 'nextPage') {
            if ($paging['nextPage']) {
                $query['page'] = $query['page'] + 1;
                $template = 'next';
            } else {
                $template = 'nextDisabled';
            }
        }

        if ($type === 'prevPage') {
            if ($paging['prevPage']) {
                $query['page'] = $query['page'] - 1;
                $template = 'prev';
            } else {
                $template = 'prevDisabled';
            }
        }
        $options['url'] = $this->request()->path() . '?' . http_build_query($query);
        
        return $this->templater()->format($template, $options);
    }

    /**
     * Gets the paging paramaters
     *
     * @return array
     */
    public function params(): array
    {
        return $this->view()->get('paging');
    }
}
