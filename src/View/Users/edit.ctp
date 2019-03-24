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
                <?= $this->Form->postLink(
                        __('Delete'),
                        ['action' => 'delete', $user->id],
                        ['confirm' => __('Are you sure you want to delete user # %d ?', $user->id),
                        'class' => 'nav-link', ]
                    );
                ?>
            </li>
            <li class="nav-item">
                <?= $this->Html->link(
                    __('List Users'),
                    ['action' => 'index'],
                    ['class' => 'nav-link']
                );
                    ?>
            </li>
        </ul>
    </div>
    <div class="col-9 users form">
        <?= $this->Form->create($user); ?>
        <h2><?= __('Edit User'); ?></h2>
            <?php
                echo $this->Form->control('name');
                echo $this->Form->control('email');
                echo $this->Form->control('dob');
                echo $this->Form->button(__('Save'), ['class' => 'btn btn-primary']);
                echo $this->Form->end();
            ?>
    </div>
</div>