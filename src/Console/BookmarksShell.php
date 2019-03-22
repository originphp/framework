<?php
namespace App\Console;

use App\Console\AppShell;
use Origin\Exception\Exception;

/**
 * @property \App\Model\Bookmark $Bookmark
 */
class BookmarksShell extends AppShell
{
    private $files = [
        SRC . DS .  'Console' . DS  . 'BookmarksShell.php',
        SRC . DS .  'Controller' . DS  . 'BookmarksController.php',
        SRC . DS .  'Controller' . DS  . 'UsersController.php',
        SRC . DS .  'Model' . DS  . 'Bookmark.php',
        SRC . DS .  'Model' . DS  . 'Tag.php',
        SRC . DS .  'Model' . DS  . 'User.php',
        SRC . DS .  'View' . DS  . 'Bookmarks'. DS . 'add.ctp',
        SRC . DS .  'View' . DS  . 'Bookmarks'. DS . 'edit.ctp',
        SRC . DS .  'View' . DS  . 'Bookmarks'. DS . 'index.ctp',
        SRC . DS .  'View' . DS  . 'Bookmarks'. DS . 'view.ctp',
        SRC . DS .  'View' . DS  . 'Users'. DS . 'add.ctp',
        SRC . DS .  'View' . DS  . 'Users'. DS . 'edit.ctp',
        SRC . DS .  'View' . DS  . 'Users'. DS . 'index.ctp',
        SRC . DS .  'View' . DS  . 'Users'. DS . 'view.ctp',
        SRC . DS .  'View' . DS  . 'Users'. DS . 'login.ctp',
        ROOT . DS . 'tests' . DS . 'Fixture' . DS  . 'BookmarkFixture.php',
        ROOT . DS . 'tests' . DS . 'Fixture' . DS  . 'BookmarksTagFixture.php',
        ROOT . DS . 'tests' . DS . 'Fixture' . DS  . 'UserFixture.php',
        ROOT . DS . 'tests' . DS . 'TestCase' . DS  . 'Controller' .DS . 'BookmarksControllerTest.php',
        ROOT . DS . 'tests' . DS . 'TestCase' . DS  . 'Model' .DS . 'BookmarkTest.php',
    ];


    public function initialize()
    {
        $this->addCommand('list', ['help'=>'Fetch a list of bookmarks from the db']);
        $this->addCommand('exception', ['help'=>'Throws an exception so you can see the debug magic']);
        $this->addCommand('uninstall', ['help'=>'Uninstalls the bookmark demo files']);
    }

    public function list()
    {
        $this->loadModel('Bookmark');
        $list = $this->Bookmark->find('list', ['fields'=>['title','url']]);

        foreach ($list as $title => $url) {
            $this->out("[{$title}] - {$url}");
        }
    }

    public function exception()
    {
        throw new Exception('You asked for this');
    }
    /**
     * Uninstall the Bookmarks demo app
     *
     * @return void
     */
    public function uninstall()
    {
        $this->out("The following files will deleted:");
        $this->out('');

        foreach ($this->files as $file) {
            $this->out('<yellow> '. $file .'</yellow>');
        }
        $this->out('');
        $result = $this->in('Are you sure?', ['yes','no'], 'no');
        if ($result === 'yes') {
            $this->out('');
            $this->out('Deleting files');
            $this->out('');
            foreach ($this->files as $file) {
                if (unlink($file)) {
                    $this->out('<white>[</white> <green>OK</green> <white>] ' . $file . '</white>');
                } else {
                    $this->out('<white>[</white> <red>ERROR</red> <white>] ' . $file . '</white>');
                }
            }
            $this->out('');
            $this->out('Deleting Folders');
            $this->out('');
            foreach ([ SRC . DS .  'View' . DS . 'Bookmarks', SRC . DS .  'View' . DS . 'Users'] as $folder) {
                if (rmdir($folder)) {
                    $this->out('<white>[</white> <green>OK</green> <white>] ' . $folder . '</white>');
                } else {
                    $this->out('<white>[</white> <red>ERROR</red> <white>] ' . $folder . '</white>');
                }
            }
            $appController = SRC . DS .'Controller'.DS .'AppController.php';
        
            $contents =  file_get_Contents($appController);
            $contents = str_replace('$this->loadComponent(\'Auth\');', '', $contents);
            if (file_put_contents($appController, $contents)) {
                $this->out('<white>[</white> <green>OK</green> <white>] modify AppController</white>');
            } else {
                $this->out('<white>[</white> <red>ERROR</red> <white>] modify AppController</white>');
            }
        }
    }
}
