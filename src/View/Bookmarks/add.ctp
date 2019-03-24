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
            </li>
        </ul>
    </div>
    <div class="col-9 bookmarks form">
        <?= $this->Form->create($bookmark); ?>
        <h2><?= __('Add Bookmark'); ?></h2>
            <?php
                echo $this->Form->control('title');
                echo $this->Form->control('url');
                echo $this->Form->control('tag_string', ['label' => __('Tags')]);
            ?>
                <small id="tagsHelpBlock" class="form-text text-muted">
                    Enter a list of comma separated tags.
                </small>
            <?php 
                echo $this->Form->control('category', ['options' => $categories, 'empty' => true]);
                echo $this->Form->control('description', ['rows' => 5]);
                echo $this->Form->button(__('Save'), ['class' => 'btn btn-primary']);
                echo $this->Form->end();
            ?>
    </div>
</div>
<?php
