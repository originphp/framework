<div class="%pluralName% form">
    <div class="page-header">
        <h2><?php echo __('%singularHuman%'); ?></h2>
    </div>
    <dl class="row">
    <RECORDBLOCK>
        <dt class="col-sm-3"><?= __('%fieldName%'); ?></dt>
        <dd class="col-sm-9"><?= h($%singularName%->%field%); ?></dd>
    </RECORDBLOCK>
    </dl>  
    <div class="text-center">
        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $%singularName%->id],['class'=>'btn btn-primary']); ?>
        <?= $this->Html->link(__('Back'), ['action' => 'index'],['class'=>'btn btn-primary']); ?>
    </div>
</div>
