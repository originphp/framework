<?php
namespace App\Controller;

/**
 * @property \App\Model\Bookmark $Bookmark
 * @property \Origin\Controller\Component\SessionComponent $Session
 * @property \Origin\Controller\Component\CookieComponent $Cookie
 */
class BookmarksController extends AppController
{
    public function initialize()
    {
        $this->loadComponent('Auth',[
            'loginRedirect' => '/bookmarks'
            ]); // Load Authentication - placed here so we can uninstall
        parent::initialize();
    }

    public $paginate = [
        'limit' => 20,
      ];
    
    public function index()
    {
        $this->set('bookmarks', $this->paginate('Bookmark', [
           'associated' => ['User']
        ]));
    }

    public function view($id = null)
    {
        $bookmark = $this->Bookmark->get($id, [
           'associated'=>['User','Tag']
            ]);

        $this->set('bookmark', $bookmark);
    }

    public function add()
    {
        $bookmark = $this->Bookmark->new();
        
        if ($this->request->is(['post'])) {
            $bookmark = $this->Bookmark->new($this->request->data());
            
            $bookmark->user_id = $this->Auth->user('id');
            
            if ($this->Bookmark->save($bookmark)) {
                $this->Flash->success(__('Your bookmark has been created.'));

                return $this->redirect(['action' => 'view', $bookmark->id]);
            }
            $this->Flash->error(__('Your bookmark could not be saved'));
        }

        $this->set('bookmark', $bookmark);
        $this->set('categories', $this->Bookmark->categories);
    }


    public function edit($id)
    {
        $bookmark = $this->Bookmark->get($id, [
           'associated' => ['Tag']
        ]);

        if ($this->request->is(['post', 'put'])) {
            $bookmark = $this->Bookmark->patch($bookmark, $this->request->data());

            if ($this->Bookmark->save($bookmark)) {
                $this->Flash->success(__('Your bookmark has been updated.'));

                return $this->redirect(['action' => 'view', $id]);
            }
     
            $this->Flash->error(__('Your bookmark could not be saved'));
        }

        $this->set('bookmark', $bookmark);
        $this->set('categories', $this->Bookmark->categories);
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $bookmark = $this->Bookmark->get($id);

        if ($this->Bookmark->delete($bookmark)) {
            $this->Flash->success(__('The bookmark %d has been deleted.', $bookmark->id));
        } else {
            $this->Flash->error(__('The bookmark could not be deleted.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
