<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
    <div class="col users index">
        <h3><?= __('Users'); ?></h3>
        <table class="table">
        <thead>
            <tr>
                <th><?= $this->Paginator->sort('name'); ?></th>
                <th><?= $this->Paginator->sort('email'); ?></th>
                <th><?= $this->Paginator->sort('created'); ?></th>
                <th><?= $this->Paginator->sort('modified'); ?></th>
                <th class="actions"><?= __('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
            <td><?= $this->Html->link($user->name, ['action' => 'view', $user->id]); ?></td>
                <td><?= h($user->email); ?></td>
                <td><?= h($this->Date->format($user->created)); ?></td>
                <td><?= h($this->Date->format($user->modified)); ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $user->id]); ?>
                    |
                    <?= $this->Form->postLink(__('Del'), ['action' => 'delete', $user->id], ['confirm' => __('Are you sure you want to delete # %s?', $user->id)]); ?>
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
                __('New User'),
                    ['controller' => 'Users', 'action' => 'add'],
                    ['class' => 'btn btn-primary']
                );
                ?>
            <?= $this->Html->link(
                __('New Bookmark'),
                ['controller' => 'Bookmarks', 'action' => 'add'],
                ['class' => 'btn btn-primary']
                );
            ?>
        </div>
    </div>
</div>