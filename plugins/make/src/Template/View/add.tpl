<div class="page-header">
    <div class="float-right">
        <a href="/%controllerUnderscored%" class="btn btn-secondary" role="button"><?php echo __('Back');?></a>
    </div>
    <h2><?php echo __('Add %singularHuman%'); ?></h2>
</div>
<div class="bookmarks form">
    <?= $this->Form->create($%singularName%); ?>
        <?php
            echo $this->Form->create($%singularName%);
            <RECORDBLOCK>
            echo $this->Form->control('%field%');
            </RECORDBLOCK>
        ?>
        <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary']); ?>
        <?= $this->Form->end(); ?>
</div>