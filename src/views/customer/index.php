<?php

/**
 * @var \yii\web\View $this
 * @var \splynx\client_config\models\ClientConfig $model
 * @var int $customerId
 * @var \splynx\client_config\models\ClientConfigHistory[] $history
 */

use splynx\client_config\assets\ClientConfigAsset;

ClientConfigAsset::register($this);

$saveUrl = \yii\helpers\Url::to(['/client-config/customer/save', 'customerId' => $customerId]);
$historyUrl = \yii\helpers\Url::to(['/client-config/customer/history', 'customerId' => $customerId]);

$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="client-config-container">
    <!-- Header bar -->
    <div class="config-header">
        <div class="config-header-left">
            <h4><i class="fa fa-file-text-o"></i> Client Configuration Notes</h4>
        </div>
        <div class="config-header-right">
            <span class="config-status" id="config-status">
                <?php if ($model->updated_at): ?>
                    Last saved by <strong><?= htmlspecialchars($model->updated_by_name) ?></strong>
                    on <?= $model->updated_at ?>
                <?php else: ?>
                    No changes yet
                <?php endif; ?>
            </span>
            <button type="button" class="btn btn-primary btn-sm" id="btn-save" disabled>
                <i class="fa fa-save"></i> Save
            </button>
        </div>
    </div>

    <!-- Rich text editor -->
    <div class="config-editor-wrap">
        <div id="editor-toolbar">
            <span class="ql-formats">
                <select class="ql-header">
                    <option value="1">Heading 1</option>
                    <option value="2">Heading 2</option>
                    <option value="3">Heading 3</option>
                    <option selected>Normal</option>
                </select>
            </span>
            <span class="ql-formats">
                <button class="ql-bold"></button>
                <button class="ql-italic"></button>
                <button class="ql-underline"></button>
                <button class="ql-strike"></button>
            </span>
            <span class="ql-formats">
                <select class="ql-color"></select>
                <select class="ql-background"></select>
            </span>
            <span class="ql-formats">
                <button class="ql-list" value="ordered"></button>
                <button class="ql-list" value="bullet"></button>
            </span>
            <span class="ql-formats">
                <button class="ql-blockquote"></button>
                <button class="ql-code-block"></button>
            </span>
            <span class="ql-formats">
                <button class="ql-link"></button>
                <button class="ql-image"></button>
            </span>
            <span class="ql-formats">
                <button class="ql-clean"></button>
            </span>
        </div>
        <div id="config-editor"><?= $model->content ?></div>
    </div>

    <!-- Audit history panel -->
    <div class="config-history-panel">
        <div class="config-history-header" id="toggle-history">
            <h5>
                <i class="fa fa-history"></i> Change History
                <span class="badge"><?= count($history) ?></span>
                <i class="fa fa-chevron-down pull-right toggle-icon"></i>
            </h5>
        </div>
        <div class="config-history-body" id="history-body" style="display: none;">
            <table class="table table-striped table-condensed">
                <thead>
                    <tr>
                        <th style="width: 180px;">Date</th>
                        <th style="width: 150px;">Changed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="history-table-body">
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td><?= htmlspecialchars($entry->created_at) ?></td>
                            <td><?= htmlspecialchars($entry->changed_by_name) ?></td>
                            <td>
                                <button class="btn btn-xs btn-default btn-view-diff"
                                        data-before="<?= htmlspecialchars($entry->content_before) ?>"
                                        data-after="<?= htmlspecialchars($entry->content_after) ?>">
                                    <i class="fa fa-eye"></i> View Changes
                                </button>
                                <button class="btn btn-xs btn-warning btn-restore"
                                        data-content="<?= htmlspecialchars($entry->content_before) ?>">
                                    <i class="fa fa-undo"></i> Restore Before
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="history-load-more" class="text-center" style="display: none;">
                <button class="btn btn-sm btn-default" id="btn-load-more">
                    <i class="fa fa-refresh"></i> Load More
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Diff viewer modal -->
<div class="modal fade" id="diffModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-exchange"></i> Change Diff</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Before</h5>
                        <div class="diff-pane" id="diff-before"></div>
                    </div>
                    <div class="col-md-6">
                        <h5>After</h5>
                        <div class="diff-pane" id="diff-after"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
window.ClientConfigInit = {
    saveUrl: <?= json_encode($saveUrl) ?>,
    historyUrl: <?= json_encode($historyUrl) ?>,
    csrfParam: <?= json_encode($csrfParam) ?>,
    csrfToken: <?= json_encode($csrfToken) ?>,
};
</script>
