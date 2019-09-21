<?php
namespace App\View;

use Origin\View\View;

/**
 * @property \Origin\View\Helper\SessionHelper $Session
 * @property \Origin\View\Helper\CookieHelper $Cookie
 * @property \Origin\View\Helper\FormHelper $Form
 * @property \Origin\View\Helper\DateHelper $Date
 * @property \Origin\View\Helper\NumberHelper $Number
 * @property \Origin\View\Helper\PaginatorHelper $Paginator
 */
class ApplicationView extends View
{
    /**
     * Called when the view is created
     *
     * @return void
     */
    public function initialize()
    {
    }
}
