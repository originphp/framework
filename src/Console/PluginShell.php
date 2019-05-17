<?php
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

namespace Origin\Console;

use Origin\Core\Inflector;

/**
 * @todo think about importing generating plugin stuff. and option parsing
 */
class PluginShell extends Shell
{
    public $description = '<green>Plugin Console Application</green>';
    public function initialize()
    {
        $this->addCommand('install', [
            'help' => 'Installs a plugin using a URL or github username/repo. GIT is required to be installed.',
            'arguments' => [
                'location' => [
                    'help' => 'URL or github username/repo',
                    'required' => true
                ]
            ]
        ]);
    }

    /**
     * Installs a plugin using a URL or github username/repo
     *
     * @return void
     */
    public function install()
    {
        $location = $this->args(0);
        if (strtolower(substr($location, 0, 4)) !==  'http') {
            $location = "https://github.com/{$location}.git";
        }

        $id = uniqid();
        $tmpPath = TMP . "/{$id}";
        $packageFile = "{$tmpPath}/package.json";
  
        exec("git clone {$location} {$tmpPath}"); // Needs to show this for username/password
        if (!file_exists($tmpPath)) {
            $this->status('error', "Plugin could not be downloaded from {$location}");
            return;
        }
       
        if (file_exists($packageFile)) {
            $package = json_decode(file_get_contents($packageFile), true);
            if (!empty($package['name'])) {
                $plugin = Inflector::underscore($package['name']);
                $pluginFolder = PLUGINS . "/{$plugin}";
                if (file_exists($pluginFolder)) {
                    $this->status('error', "Plugin folder {$plugin} already exists");
                    return;
                }
                exec("mv {$tmpPath} {$pluginFolder}");
                if (!file_exists($pluginFolder)) {
                    $this->status('error', 'Error installing plugin');
                    return;
                } else {
                    file_put_contents(CONFIG . '/bootstrap.php', "Plugin::load('{$package['name']}');\n", FILE_APPEND);
                    exec("rm -rf {$pluginFolder}/.git");
                    $this->status('ok', "{$package['name']} Plugin installed");
                }
            }
        } else {
            $this->status('error', "Invalid plugin - no package file found"); // could not be downloaded or missing package.json
        }
    }
}
