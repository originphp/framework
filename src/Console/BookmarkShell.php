<?php

namespace App\Console;

use App\Console\AppShell;
use Origin\Exception\Exception;

class BookmarkShell extends AppShell
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
        SRC . DS .  'View' . DS  . 'Users'. DS . 'login.ctp'
    ];

    public function main()
    {
        $this->out('Usage:');
        $this->out('bin/console bookmarks <command>');
        $this->out('');
        $this->help();
    }

    public function help()
    {
        $this->out('Avaliable commands:');
        $this->out('help - shows this');
        $this->out('list - lists all the bookmarks');
        $this->out('uninstall - Deletes all the demo files and bookmarks');
        $this->out('exception - throws an exception');
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
        }
    }
}
