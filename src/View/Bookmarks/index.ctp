<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
    <div class="col bookmarks index">
        <h2><?= __('Bookmarks'); ?></h2>
        <table class="table">
        <thead>
            <tr>
                <th><?= $this->Paginator->sort('title'); ?></th>
                <th><?= $this->Paginator->sort('user_id', 'User'); ?></th>
                <th><?= $this->Paginator->sort('created'); ?></th>
                <th><?= $this->Paginator->sort('modified'); ?></th>
                <th class="actions"><?= __('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bookmarks as $bookmark): ?>
            <tr>
                <td><?= $this->Html->link($bookmark->title, ['action' => 'view', $bookmark->id]); ?></td>
                <td><?= h($bookmark->user->name); ?></td>
                <td><?= h($this->Date->format($bookmark->created)); ?></td>
                <td><?= h($this->Date->format($bookmark->modified)); ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $bookmark->id]); ?>
                    |
                    <?= $this->Form->postLink(__('Del'), ['action' => 'delete', $bookmark->id], ['confirm' => __('Are you sure you want to delete # %s?', $bookmark->id)]); ?>
                </td>
            </tr>

        <?php endforeach; ?>
        </tbody>
        </table>

        <div class="paginator">
            <?= $this->Paginator->control(); ?>
        </div>

        <div class="actions">
        <?= $this->Html->link(
                __('New Bookmark'),
                ['controller' => 'Bookmarks', 'action' => 'add'],
                ['class' => 'btn btn-primary']
                );
            ?>
        <?= $this->Html->link(
                __('New User'),
                    ['controller' => 'Users', 'action' => 'add'],
                    ['class' => 'btn btn-primary']
                );
                ?>
    
        </div>
    </div>
</div>