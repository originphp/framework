<?php
namespace App\Http\View;

use Origin\Http\View\View;

/**
 * @property \Origin\Http\View\Helper\SessionHelper $Session
 * @property \Origin\Http\View\Helper\CookieHelper $Cookie
 * @property \Origin\Http\View\Helper\FormHelper $Form
 * @property \Origin\Http\View\Helper\DateHelper $Date
 * @property \Origin\Http\View\Helper\NumberHelper $Number
 * @property \Origin\Http\View\Helper\PaginatorHelper $Paginator
 */
class ApplicationView extends View
{
    /**
     * Called when the view is created
     *
     * @return void
     */
    public function initialize() : void
    {
    }
}
