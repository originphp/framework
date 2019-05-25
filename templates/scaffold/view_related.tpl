<div class="row %pluralName% related">
    <h4><?= __('%pluralHuman%') ?></h4>
    <?php if (!empty($%currentModel%->%pluralName%)): ?>
    <table class="table">
        <tr>
             <th><?= __('%primaryKey%') ?></th>
            <RECORDBLOCK>
            <th><?= __('%field%') ?></th>
            </RECORDBLOCK>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($%currentModel%->%pluralName% as $%singularName%): ?>
        <tr>
            <td><?= $this->Html->link($%singularName%->%primaryKey%, ['controller' => '%controller%', 'action' => 'view', $%singularName%->id]) ?></td>
             <RECORDBLOCK>
            <td><?= h($%singularName%->%field%) ?></td>
            </RECORDBLOCK>
            <td class="actions">
                <?= $this->Html->link(__('Edit'), ['controller' => '%controller%', 'action' => 'edit', $%singularName%->id]) ?>
                |
                <?= $this->Form->postLink(__('Del'), ['controller' => '%controller%', 'action' => 'delete', $%singularName%->id], ['confirm' => __('Are you sure you want to delete # %d?', $%singularName%->%primaryKey%)]) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>