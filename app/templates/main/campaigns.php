<?php $this->layout('layout/default', ['title' => _e('campaigns')]) ?>

<?php $this->start('page_content') ?>

<?php

use Pushbase\Utilities\PaginationHelper;

$queryParams = [];
if ($statusFilter) {
    $queryParams['status'] = $statusFilter;
}
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="title d-flex">
        <i class="bi bi-megaphone me-2 fs-5"></i>
        <h1 class="h4"><?= _e('campaigns') ?></h1>
    </div>
    <div class="d-flex align-items-center">
        <form class="me-2" method="get" action="/campaigns">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value=""><?= _e('all_statuses') ?></option>
                <?php foreach ($statuses as $status) { ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                        <?= ucfirst(htmlspecialchars($status)) ?>
                    </option>
                <?php } ?>
            </select>
        </form>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="/campaigns/export/xlsx" class="btn btn-sm btn-outline-secondary" title="<?= _e('export_excel') ?>">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </a>
                <a href="/campaigns/export/csv" class="btn btn-sm btn-outline-secondary" title="<?= _e('export_csv') ?>">
                    <i class="bi bi-file-earmark-text me-1"></i>CSV
                </a>
            </div>
            <a href="/campaign" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-circle me-2"></i><?= _e('campaign_create') ?>
            </a>
        </div>
    </div>
</div>

<div class="table-responsive bg-white rounded border border-bottom-0 overflow-hidden">
    <table class="table table-striped table-hover align-middle mb-0">
        <thead>
            <tr>
                <th style="min-width: 220px;"><?= _e('campaign') ?></th>
                <th style="min-width: 320px;"><?= _e('details') ?></th>
                <th style="min-width: 90px;"><?= _e('status') ?></th>
                <th style="min-width: 180px;"><?= _e('rate') ?></th>
                <th style="min-width: 190px;"><?= _e('history') ?></th>
                <th style="min-width: 124px; width: 124px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($campaigns as $campaign) { ?>
                <tr>
                    <td class="small"><?= htmlspecialchars($campaign['name']) ?></td>
                    <td class="small">
                        <div><a href="<?= htmlspecialchars($campaign['push_url'] ?? '#') ?>" class="link-secondary" target="_blank"><?= htmlspecialchars($campaign['push_title']) ?></a></div>
                        <div title="<?= htmlspecialchars($campaign['push_body']) ?>"><?= strlen($campaign['push_body']) > 60 ? htmlspecialchars(substr($campaign['push_body'], 0, 60)) . '...' : htmlspecialchars($campaign['push_body']) ?></div>
                    </td>
                    <td>
                        <?php
                        $statusClass = match ($campaign['status']) {
                            'draft' => 'bg-secondary',
                            'scheduled' => 'bg-primary',
                            'sent' => 'bg-success',
                            'sending' => 'bg-info',
                            'cancelled' => 'bg-danger',
                            'queuing' => 'bg-warning',
                            default => 'bg-secondary'
                        };
                        ?>
                        <span class="badge <?= $statusClass ?>">
                            <?= ucfirst(htmlspecialchars($campaign['status'])) ?>
                        </span>
                    </td>
                    <td class="small">
                        <?php if (in_array($campaign['status'], ['sent', 'sending'])) {
                            $totalRecipients = $campaign['total_recipients'] ?? 1;

                            $successCount = $campaign['successfully_count'] ?? 0;
                            $successPercentage = $totalRecipients > 0 ? round(($successCount / $totalRecipients) * 100, 2) : 0;

                            $failedCount = $campaign['error_count'] ?? 0;
                            $failedPercentage = $successCount > 0 ? round(($failedCount / $totalRecipients) * 100, 2) : 0;

                            $clickCount = $campaign['clicked_count'] ?? 0;
                            $clickPercentage = $successCount > 0 ? round(($clickCount / $totalRecipients) * 100, 2) : 0;
                        ?>
                            <div><?= _e('success') ?>: <?= $successCount ?> (<?= $successPercentage ?>%)</div>
                            <div><?= _e('failed') ?>: <?= $failedCount ?> (<?= $failedPercentage ?>%)</div>
                            <div><?= _e('clicks') ?>: <?= $clickCount ?> (<?= $clickPercentage ?>%)</div>
                        <?php } ?>
                    </td>
                    <td class="small">
                        <div><?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?></div>
                        <?php if ($campaign['created_at'] !== $campaign['updated_at']) { ?>
                            <div><?= _e('updated_at') ?>: <?= date('Y-m-d H:i', strtotime($campaign['updated_at'])) ?></div>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if (in_array($campaign['status'], ['draft', 'cancelled'])) { ?>
                            <a href="/campaign/edit/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-secondary" title="<?= _e('edit') ?>">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        <?php } ?>
                        <?php if (in_array($campaign['status'], ['scheduled'])) { ?>
                            <a href="/campaign/cancel/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('<?= _e('confirm_cancel_campaign') ?>')" title="Cancel">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        <?php } ?>
                        <?php if (in_array($campaign['status'], ['draft', 'cancelled'])) { ?>
                            <a href="/campaign/delete/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= _e('confirm_delete_campaign') ?>')" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        <?php } ?>
                        <?php if (in_array($campaign['status'], ['sent', 'sending'])) { ?>
                            <a href="/campaign/analytics/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-primary" title="<?= _e('analytics') ?>">
                                <i class="bi bi-graph-up"></i>
                            </a>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1) { ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center mb-0">
            <?php
            $paginationLinks = PaginationHelper::generatePaginationLinks(
                $currentPage,
                $totalPages,
                '/campaigns',
                $queryParams
            );

            foreach ($paginationLinks as $link) {
            ?>
                <li class="page-item 
                    <?= isset($link['disabled']) && $link['disabled'] ? 'disabled' : '' ?> 
                    <?= isset($link['active']) && $link['active'] ? 'active' : '' ?>
                ">
                    <a class="page-link"
                        href="<?= $link['url'] ?>"
                        <?= isset($link['disabled']) && $link['disabled'] ? 'tabindex="-1"' : '' ?>>
                        <?= htmlspecialchars($link['label']) ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </nav>
<?php } ?>

<?php $this->end() ?>