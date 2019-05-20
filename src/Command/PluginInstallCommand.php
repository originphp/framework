<?php
namespace Origin\Command;
use Origin\Command\Command;
use Origin\Core\Inflector;

class PluginInstallCommand extends Command
{
    protected $name = 'plugin:install';

    protected $description = 'Installs a plugin using a URL or github username/repo. GIT is required to be installed.';

    public function initialize(){
        $this->addArgument('url', [
            'help' => 'github repo URL or github username/repo',
            'required' => true
        ]);
        $this->addArgument('name',[
            'description' => 'name for the plugin. e.g. UserManagement'
        ]);
    }
 
    public function execute(){
       
        $url = $this->arguments('url');
        if (strtolower(substr($url, 0, 4)) !==  'http') {
           $url = "https://github.com/{$url}.git";
        }
        // Svn friendly urls have .git
        if(substr(strtolower($url),-4) !== '.git'){
            $url .= '.git';
        }

        $plugin = $this->arguments('name');
        if($plugin){
            if(!preg_match('/^([A-Z]+[a-z0-9]+)+/', $plugin)){
                $this->throwError(sprintf('Plugin name `%s` is invalid',$plugin));
            }
        }
        if(!$plugin){
            $plugin = pathinfo($url,PATHINFO_FILENAME);
            $plugin = preg_replace('/[^a-z0-9]+/i', '_', $plugin);
        }
        $plugin = Inflector::underscore($plugin);
    
        $folder = PLUGINS . DS . $plugin;
        if(file_exists($folder)){
            $this->throwError(sprintf('Plugin `%s` already exists',$plugin));
        }
        /**
         * Needs to show to user, incase requires username or password
         */
         exec("git clone {$url} {$folder}");
 
         // Needs to show this for username/password
        if (file_exists($folder)) {
            $plugin = Inflector::camelize($plugin);
            file_put_contents(CONFIG . '/bootstrap.php', "Plugin::load('{$plugin}');\n", FILE_APPEND);
            $this->io->status('ok',sprintf('%s Plugin installed',$plugin));
            return;
        }
        else{
            $this->io->status('error',sprintf('Plugin not downloaded from `%s`',$url));
        }
    }
}