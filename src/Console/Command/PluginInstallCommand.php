<?php
declare(strict_types = 1);
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Console\Command;

use Origin\Utility\Folder;
use Origin\Inflector\Inflector;

class PluginInstallCommand extends Command
{
    protected $name = 'plugin:install';
    protected $description = 'Installs a plugin using a URL or github username/repo. GIT is required to be installed.';

    public function initialize() : void
    {
        $this->addArgument('url', [
            'help' => 'github repo URL or github username/repo',
            'required' => true,
        ]);
        $this->addArgument('name', [
            'description' => 'name for the plugin. e.g. UserManagement',
        ]);
    }

    /**
     * Gets the full git url
     *
     * @param string $url
     * @return string
     */
    protected function getUrl(string $url) : string
    {
        if (strtolower(substr($url, 0, 4)) !== 'http') {
            $url = "https://github.com/{$url}";
        }
        // Svn friendly urls have .git
        if (substr(strtolower($url), -4) !== '.git') {
            $url .= '.git';
        }

        return $url;
    }

    /**
     * Gets the plugin name
     *
     * @param string $url
     * @param string $plugin
     * @return string
     */
    protected function getPlugin(string $url, string $plugin = null) : string
    {
        if ($plugin) {
            if (! preg_match('/^([A-Z]+[a-z0-9]+)+/', $plugin)) {
                $this->throwError(sprintf('Plugin name `%s` is invalid', $plugin));
            }
        }
        if (! $plugin) {
            $plugin = pathinfo($url, PATHINFO_FILENAME);
            $plugin = preg_replace('/[^a-z0-9]+/i', '_', $plugin);
        }

        return Inflector::underscored($plugin);
    }

    /**
     * Downloads the actual git
     *
     * @param string $url
     * @param string $folder
     * @return bool
     */
    protected function download(string $url, string $folder) : bool
    {
        shell_exec("git clone {$url} {$folder}");
       
        return file_exists($folder) and $this->recursiveDelete($folder . DS . '.git');
    }

    /**
     * Adds LoadPlugin to bootstrap
     *
     * @param string $plugin
     * @return void
     */
    protected function appendApplication(string $plugin) : void
    {
        $file = CONFIG . DS . 'bootstrap.php';
        $contents = file_get_contents($file);
        $contents = str_replace(
            'Plugin::initialize();',
            "Plugin::load('{$plugin}');\nPlugin::initialize();",
            $contents
            );
        file_put_contents($file, $contents);
    }
 
    public function execute() : void
    {
        $url = $this->getUrl($this->arguments('url'));
        $plugin = $this->getPlugin($url, $this->arguments('name'));
    
        $folder = PLUGINS . DS . $plugin;
        if (file_exists($folder)) {
            $this->throwError(sprintf('Plugin `%s` already exists', $plugin));
        }

        // Needs to show this for username/password
        if ($this->download($url, $folder)) {
            $plugin = Inflector::studlyCaps($plugin);
            $this->appendApplication($plugin);
            $this->io->status('ok', sprintf('%s Plugin installed', $plugin));

            return;
        } else {
            $this->io->status('error', sprintf('Plugin not downloaded from `%s`', $url));
        }
    }

    private function recursiveDelete(string $directory)
    {
        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $filename) {
            if (is_dir($directory . DS . $filename)) {
                $this->recursiveDelete($directory . DS . $filename);
                continue;
            }
            unlink($directory . DS . $filename);
        }
        return rmdir($directory);
    }
}
