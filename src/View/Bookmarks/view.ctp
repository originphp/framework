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
                <?= $this->Html->link(__('List Bookmarks'), ['action' => 'index'], ['class' => 'nav-link']); ?>
                <?= $this->Html->link(__('Add Bookmark'), ['action' => 'add'], ['class' => 'nav-link']); ?>
                <?= $this->Html->link(__('Edit Bookmark'), ['action' => 'edit', $bookmark->id], ['class' => 'nav-link']); ?>
                <?= $this->Form->postLink(
                        __('Delete'),
                        ['action' => 'delete', $bookmark->id],
                        ['confirm' => __('Are you sure you want to delete bookmark # %d ?', $bookmark->id),
                        'class' => 'nav-link', ]
                    );
                ?>
            </li>
        </ul>
    </div>
    <div class="col-9 bookmarks form">
        <h2><?= h($bookmark->title); ?></h2>
        <dl class="row">
        
        <dt class="col-sm-3"><?= __('User'); ?></dt>
        <dd class="col-sm-9"><?= $this->Html->link($bookmark->user->name, ['controller' => 'users', 'action' => 'view', $bookmark->user->id]); ?></dd>

        <dt class="col-sm-3"><?= __('Title'); ?></dt>
        <dd class="col-sm-9"><?= h($bookmark->title); ?></dd>

        <dt class="col-sm-3"><?= __('Tags'); ?></dt>
        <dd class="col-sm-9"><?php 
            foreach ($bookmark->tags as $tag) {
                ?>
                    <span class="badge badge-primary"><?= h($tag->title); ?></span>
                <?php
            }
        ?></dd>

        <dt class="col-sm-3"><?= __('Category'); ?></dt>
        <dd class="col-sm-9"><?= h($bookmark->category); ?></dd>

        <dt class="col-sm-3"><?= __('Description'); ?></dt>
        <dd class="col-sm-9"><?= h($bookmark->description); ?></dd>

        <dt class="col-sm-3"><?= __('URL'); ?></dt>
        <dd class="col-sm-9"><?= $this->Html->link($bookmark->url, $bookmark->url, ['target' => '_blank']); ?></dd>

        <dt class="col-sm-3"><?= __('Created'); ?></dt>
        <dd class="col-sm-9"><?= $this->Date->format($bookmark->created); ?></dd>

        <dt class="col-sm-3"><?= __('Modified'); ?></dt>
        <dd class="col-sm-9"><?= $this->Date->format($bookmark->modified); ?></dd>
    </dl>  
    </div>
</div>