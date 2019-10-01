<?php
namespace Origin\Console\Command\Extra;

use Origin\Utility\Security;
use Origin\Console\Command\Command;

class InstallCommand extends Command
{
    protected $name = 'install';
    protected $description = 'Post install command';
 
    public function execute() : void
    {
        $source = ROOT . '/config/.env.php.default';
        $destination = ROOT.  '/config/.env.php';

        if (file_exists($destination)) {
            $this->io->status('error', 'config/.env.php already exists');
            $this->abort();
        }

        $template = str_replace('{key}', Security::generateKey(), file_get_contents($source));
        file_put_contents($destination, $template);
        $this->io->status('ok', 'config/.env.php created');
    }
}
