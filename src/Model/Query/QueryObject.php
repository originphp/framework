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
declare(strict_types = 1);
namespace Origin\Model\Query;

use Origin\Core\HookTrait;

/**
 * Query Objects - These execute a query (sql) and return a result set. If you have a complex
 * (joins,unions,calculations, reporting) query in your Repository bloating the code, then
 * consider using a QueryObject. Do not use QueryObjects for every query.
 * Inject the model or models when constructing and use Query in the suffix of the class name
 *
 * class BooleanSearchQuery extends QueryObject
 * {
 *      protected initialize(Model $Article) : void
 *      {
 *          $this->Article = $Article;
 *      }
 *
 *      public function execute() : Collection
 *      {
 *          ....
 *      }
 * }
 *
 * Example
 *
 * $result = (new BooleanSearchQuery($this->Article))->execute('how to');
 *
 * @see https://www.martinfowler.com/eaaCatalog/queryObject.html
 */
class QueryObject
{
    use HookTrait;

    public function __construct()
    {
        $this->executeHook('initialize', func_get_args());
    }
}
