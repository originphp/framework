<?php 
namespace Debug;

use Origin\Core\Log;

/**
 * Simple Profiler uses ticks, output is sent to logs\profile.log.  As i test the framework
 * and apps built with this, needed to check if there was a memory leak so this quick and simple
 * solution. Xdebug can handle this as well.
 *
 * Sample output :
 *
 * 2019-01-27 10:58:30 - Origin\Controller\Controller isAccessible  526.40 kb
 * 2019-01-27 10:58:30 - Origin\Core\Dispatcher dispatch  524.66 kb
 * 2019-01-27 10:58:30 - Origin\Core\Dispatcher invoke  525.41 kb
 * 2019-01-27 10:58:30 - Origin\Controller\Controller set  527.73 kb
 * 2019-01-27 10:58:30 - Origin\Controller\Controller set  528.10 kb
 *
 * Include declare(ticks=1); at the start of each file for this work.
 *
 * To test whole app: in public/index.php add before the dispatcher code: (Or you can add to your App controller)
 *
 *  require dirname(__DIR__).'/plugins/debug/src/Profiler.php';
 *  $profiler = new Debug\Profiler();
 *  $profiler->register();
 *
 *  Then add
 *
 *  declare(ticks=1);
 *
 *  to the top of each file you want to profile such as dispatcher, controller etc. If you
 *  are just testing one script, then add then just add this
 *
 *  require dirname(__DIR__).'/plugins/debug/src/Profiler.php';
 *  $profiler = new Debug\Profiler();
 *  $profiler->register();
 *  declare(ticks=1);
 *
 */
class Profiler
{
    protected $timestamp = null;

    public function __construct()
    {
        $this->timestamp = time();
    }
    /**
     * This is run on every statement
     *
     * @return void
     */
    public function tick()
    {
        $backtrace = debug_backtrace();
        if (isset($backtrace[1])) {
            $what = $backtrace[1]['function'];
            if (isset($backtrace[1]['class'])) {
                $what = $backtrace[1]['class'] . ' ' . $backtrace[1]['function'];
            }
            $message = $what .'  ' . $this->memoryUsage();
            $this->log($message);
        }
        return;
    }

    /**
     * Registers the tick function
     *
     * @return void
     */
    public function register()
    {
        register_tick_function([&$this, 'tick']);
    }
    /**
     * Returns the memory usage in human readable format
     *
     * @return void
     */
    protected function memoryUsage()
    {
        $bytes = memory_get_usage(false);
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' mb';
        }

        return number_format($bytes / 1024, 2).' kb';
    }

    /**
     * Logs message
     *
     * @param string $message
     * @return void
     */
    public function log(string $message)
    {
        return file_put_contents(LOGS . DS . 'profile-' . $this->timestamp . '.log', $message ."\n", FILE_APPEND | LOCK_EX);
    }
}
