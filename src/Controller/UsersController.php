<?php
namespace App\Controller;

/**
 * @property \App\Model\User $User
 */
class UsersController extends AppController
{
    public $paginate = [
      'limit' => 20,
    ];

    public function initialize()
    {
        $this->loadComponent('Auth',[
            'loginRedirect' => '/bookmarks'
            ]); // Load Authentication - placed here so we can uninstall
        parent::initialize();
    }

    public function index()
    {
        $this->set('users', $this->paginate('User'));
    }

    public function view($id = null)
    {
        $user = $this->User->get($id, [
            'associated'=>['Bookmark']
            ]);
        $this->set('user', $user);
    }

    public function add()
    {
        $user = $this->User->new();

        if ($this->request->is(['post'])) {
            $user = $this->User->new($this->request->data());
            if ($this->User->save($user)) {
                $this->Flash->success('Your user has been created.');

                return $this->redirect(['action' => 'view', $this->User->id]);
            }
            $this->Flash->error('Your user could not be saved');
        }

        $this->set('user', $user);
    }

    public function edit($id = null)
    {
        $user = $this->User->get($id);

        if ($this->request->is(['post', 'put'])) {
            $user = $this->User->new($this->request->data());

            $user->id = $id;

            if ($this->User->save($user)) {
                $this->Flash->success('Your user has been updated.');

                return $this->redirect(['action' => 'view', $this->User->id]);
            }

            $this->Flash->error('Your user could not be saved');
        }

        $this->set('user', $user);
    }

    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $user = $this->User->get($id);

        if ($this->User->delete($user)) {
            $this->Flash->success(__('The user %d has been deleted.', $id));
        } else {
            $this->Flash->error(__('The user could not be deleted.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function login()
    {
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->login($user);

                return $this->redirect($this->Auth->redirectUrl());
            }
            $this->Flash->error(__('Incorrect username or password.'));
        }
    }

    public function logout()
    {
        $this->Flash->success(__('You have logged out.'));

        return $this->redirect($this->Auth->logout());
    }
}
