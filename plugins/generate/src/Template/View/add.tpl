<div class="page-header">
    <div class="float-right">
        <a href="/%controllerUnderscored%" class="btn btn-secondary" role="button"><?= __('Back') ?></a>
    </div>
    <h2><?= __('Add %singularHuman%') ?></h2>
</div>
<div class="%pluralName% form">
    <?= $this->Form->create($%singularName%) ?>
    <?php
        <RECORDBLOCK>
        echo $this->Form->control('%field%');
        </RECORDBLOCK>
        $this->Form->button(__('Save'), ['class' => 'btn btn-primary']);
        $this->Form->end();
    ?>
</div>