<div class="%pluralName% form">
    <h2><?= h($%singularName%->title); ?></h2>
    <dl class="row">
    <RECORDBLOCK>
        <dt class="col-sm-3"><?= __('%fieldName%'); ?></dt>
        <dd class="col-sm-9"><?= h($%singularName%->%field%); ?></dd>
    </RECORDBLOCK>
    </dl>  
</div>