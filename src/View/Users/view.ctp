<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
    <div class="col-3 actions">
        <h3><?= __('Actions'); ?></h3>
        <ul class="nav flex-column">
            <li class="nav-item">
                <?= $this->Html->link(__('List Users'), ['action' => 'index'], ['class' => 'nav-link']); ?>
                <?= $this->Html->link(__('Add User'), ['action' => 'add'], ['class' => 'nav-link']); ?>
                <?= $this->Html->link(__('Edit User'), ['action' => 'edit', $user->id], ['class' => 'nav-link']); ?>
                <?= $this->Form->postLink(
                        __('Delete'),
                        ['action' => 'delete', $user->id],
                        ['confirm' => __('Are you sure you want to delete user # %d ?', $user->id),
                        'class' => 'nav-link', ]
                    );
                ?>
            </li>
        </ul>
    </div>
    <div class="col-9 users form">
        <h2><?= h($user->name); ?></h2>
        <dl class="row"> 
            <dt class="col-sm-3"><?= __('Email'); ?></dt>
            <dd class="col-sm-9"><?= $this->Html->link($user->email, "mailto:{$user->email}"); ?></dd>

            <dt class="col-sm-3"><?= __('DOB'); ?></dt>
            <dd class="col-sm-9"><?= $this->Date->format($user->dob); ?></dd>

            <dt class="col-sm-3"><?= __('Created'); ?></dt>
            <dd class="col-sm-9"><?= $this->Date->format($user->created); ?></dd>

            <dt class="col-sm-3"><?= __('Modified'); ?></dt>
            <dd class="col-sm-9"><?= $this->Date->format($user->modified); ?></dd>
        </dl>  
    </div>
</div>

<div class="row related">
    <h4><?= __('Related Bookmarks'); ?></h4>
    <?php if (!empty($user->bookmarks)): ?>
    <table class="table">
        <tr>
            <th><?= __('Title'); ?></th>
            <th><?= __('Category'); ?></th>
            <th><?= __('Modified'); ?></th>
            <th class="actions"><?= __('Actions'); ?></th>
        </tr>
        <?php foreach ($user->bookmarks as $bookmark): ?>
        <tr>
            <td><?= $this->Html->link($bookmark->title, ['controller' => 'Bookmarks', 'action' => 'view', $bookmark->id]); ?></td>
            <td><?= h($bookmark->category); ?></td>
            <td><?= $this->Date->format($bookmark->modified); ?></td>

            <td class="actions">
                <?= $this->Html->link(__('Edit'), ['controller' => 'Bookmarks', 'action' => 'edit', $bookmark->id]); ?>
                |
                <?= $this->Form->postLink(__('Del'), ['controller' => 'Bookmarks', 'action' => 'delete', $bookmark->id], ['confirm' => __('Are you sure you want to delete # %d?', $bookmark->id)]); ?>

            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
   
</div>