<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="%pluralName% index">
    <div class="page-header">
        <h2><?= __('%pluralHuman%') ?></h2>
    </div>
    <table class="table">
    <thead>
        <tr>
            <RECORDBLOCK>
            <th><?= $this->Paginator->sort('%field%') ?></th>
            </RECORDBLOCK>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
    </thead>
        <tbody>
        <?php foreach ($%pluralName% as $%singularName%): ?>
            <tr>
               
                <RECORDBLOCK>
                 <td><?= h($%singularName%->%field%) ?></td>
                </RECORDBLOCK>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $%singularName%->id]) ?>
                    |
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $%singularName%->id]) ?>
                    |
                    <?= $this->Form->postLink(__('Del'), ['action' => 'delete', $%singularName%->id], ['confirm' => __('Are you sure you want to delete # {id}?', ['id'=>$%singularName%->id])]) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="paginator">
        <?= $this->Paginator->control() ?>
    </div>

    <div class="actions">
    <?= $this->Html->link(
            __('New %singularHuman%'),
            ['controller' => '%controller%', 'action' => 'add'],
            ['class' => 'btn btn-primary']
            )
        ?>
    </div>
</div>