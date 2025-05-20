<?php $this->layout('layout/default', ['title' => _e('campaigns')]) ?>

<?php $this->start('page_content') ?>

<?php

use alo\Utilities\PaginationHelper;

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
    <div class="d-flex align-items-center flex-wrap">
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
        <div class="btn-toolbar mb-2 mb-md-0 mt-2 mt-md-0">
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

<?php if ($currentPage <= 1) { ?>
    <ul class="nav nav-tabs mb-3" id="campaignTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="sending-queuing-tab" data-bs-toggle="tab" data-bs-target="#sending-queuing" type="button" role="tab" aria-controls="sending-queuing" aria-selected="true">
                <?= _e('sending_campaigns') ?> / <?= _e('queued_campaigns') ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="scheduled-tab" data-bs-toggle="tab" data-bs-target="#scheduled" type="button" role="tab" aria-controls="scheduled" aria-selected="false">
                <?= _e('scheduled_campaigns') ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="draft-tab" data-bs-toggle="tab" data-bs-target="#draft" type="button" role="tab" aria-controls="draft" aria-selected="false">
                <?= _e('draft_campaigns') ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="other-tab" data-bs-toggle="tab" data-bs-target="#other" type="button" role="tab" aria-controls="other" aria-selected="false">
                <?= _e('other_campaigns') ?>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="campaignTabsContent">
        <div class="tab-pane fade show active" id="sending-queuing" role="tabpanel" aria-labelledby="sending-queuing-tab">
            <?php if (isset($sendingCampaigns) && !empty($sendingCampaigns)) { ?>
                <h5 class="mb-3"><?= _e('sending_campaigns') ?></h5>
                <div class="table-responsive bg-white rounded border border-bottom-0">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 220px;"><?= _e('campaign') ?></th>
                                <th style="min-width: 320px;"><?= _e('details') ?></th>
                                <th style="min-width: 180px;"><?= _e('rate') ?></th>
                                <th style="min-width: 190px;"><?= _e('history') ?></th>
                                <th style="min-width: 124px; width: 124px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sendingCampaigns as $campaign) { ?>
                                <tr>
                                    <td class="small"><?= htmlspecialchars($campaign['name']) ?></td>
                                    <td class="small">
                                        <div><a href="<?= htmlspecialchars($campaign['push_url'] ?? '#') ?>" class="link-secondary" target="_blank"><?= htmlspecialchars($campaign['push_title']) ?></a></div>
                                        <div title="<?= htmlspecialchars($campaign['push_body']) ?>"><?= strlen($campaign['push_body']) > 60 ? substr(htmlspecialchars($campaign['push_body']), 0, 60) . '...' : htmlspecialchars($campaign['push_body']) ?></div>
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
                                        <a href="/campaign/analytics/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-primary" title="<?= _e('analytics') ?>">
                                            <i class="bi bi-graph-up"></i>
                                        </a>
                                        <a href="/campaign/duplicate/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-info" title="<?= _e('duplicate') ?>">
                                            <i class="bi bi-files"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>

            <?php if (isset($queuingCampaigns) && !empty($queuingCampaigns)) { ?>
                <h5 class="mt-4 mb-3"><?= _e('queued_campaigns') ?></h5>
                <div class="table-responsive bg-white rounded border border-bottom-0">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 220px;"><?= _e('campaign') ?></th>
                                <th style="min-width: 320px;"><?= _e('details') ?></th>
                                <th style="min-width: 180px;"><?= _e('scheduled_for') ?></th>
                                <th style="min-width: 190px;"><?= _e('history') ?></th>
                                <th style="min-width: 124px; width: 124px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queuingCampaigns as $campaign) { ?>
                                <tr>
                                    <td class="small"><?= htmlspecialchars($campaign['name']) ?></td>
                                    <td class="small">
                                        <div><a href="<?= htmlspecialchars($campaign['push_url'] ?? '#') ?>" class="link-secondary" target="_blank"><?= htmlspecialchars($campaign['push_title']) ?></a></div>
                                        <div title="<?= htmlspecialchars($campaign['push_body']) ?>"><?= strlen($campaign['push_body']) > 60 ? substr(htmlspecialchars($campaign['push_body']), 0, 60) . '...' : htmlspecialchars($campaign['push_body']) ?></div>
                                    </td>
                                    <td class="small">
                                        <?php if (isset($campaign['send_at']) && !empty($campaign['send_at'])) { ?>
                                            <?= date('Y-m-d H:i', strtotime($campaign['send_at'])) ?>
                                        <?php } else { ?>
                                            <?php if ($campaign['created_at'] !== $campaign['updated_at']) { ?>
                                                <?= date('Y-m-d H:i', strtotime($campaign['updated_at'])) ?>
                                            <?php } else { ?>
                                                <?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?>
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                    <td class="small">
                                        <div><?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?></div>
                                        <?php if ($campaign['created_at'] !== $campaign['updated_at']) { ?>
                                            <div><?= _e('updated_at') ?>: <?= date('Y-m-d H:i', strtotime($campaign['updated_at'])) ?></div>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="/campaign/cancel/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('<?= _e('confirm_cancel_campaign') ?>')" title="Cancel">
                                            <i class="bi bi-x-circle"></i>
                                        </a>
                                        <a href="/campaign/duplicate/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-info" title="<?= _e('duplicate') ?>">
                                            <i class="bi bi-files"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </div>

        <div class="tab-pane fade" id="scheduled" role="tabpanel" aria-labelledby="scheduled-tab">
            <?php if (isset($scheduledCampaigns) && !empty($scheduledCampaigns)) { ?>
                <div class="table-responsive bg-white rounded border border-bottom-0">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 220px;"><?= _e('campaign') ?></th>
                                <th style="min-width: 320px;"><?= _e('details') ?></th>
                                <th style="min-width: 180px;"><?= _e('scheduled_for') ?></th>
                                <th style="min-width: 190px;"><?= _e('history') ?></th>
                                <th style="min-width: 124px; width: 124px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ((isset($statusFilter) ? $scheduledCampaigns : $scheduledCampaigns) as $campaign) { ?>
                                <tr>
                                    <td class="small"><?= htmlspecialchars($campaign['name']) ?></td>
                                    <td class="small">
                                        <div><a href="<?= htmlspecialchars($campaign['push_url'] ?? '#') ?>" class="link-secondary" target="_blank"><?= htmlspecialchars($campaign['push_title']) ?></a></div>
                                        <div title="<?= htmlspecialchars($campaign['push_body']) ?>"><?= strlen($campaign['push_body']) > 60 ? substr(htmlspecialchars($campaign['push_body']), 0, 60) . '...' : htmlspecialchars($campaign['push_body']) ?></div>
                                    </td>
                                    <td class="small">
                                        <?php if (isset($campaign['send_at']) && !empty($campaign['send_at'])) { ?>
                                            <?= date('Y-m-d H:i', strtotime($campaign['send_at'])) ?>
                                        <?php } else { ?>
                                            <?php if ($campaign['created_at'] !== $campaign['updated_at']) { ?>
                                                <?= date('Y-m-d H:i', strtotime($campaign['updated_at'])) ?>
                                            <?php } else { ?>
                                                <?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?>
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                    <td class="small">
                                        <div><?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?></div>
                                        <?php if ($campaign['created_at'] !== $campaign['updated_at']) { ?>
                                            <div><?= _e('updated_at') ?>: <?= date('Y-m-d H:i', strtotime($campaign['updated_at'])) ?></div>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="/campaign/cancel/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('<?= _e('confirm_cancel_campaign') ?>')" title="Cancel">
                                            <i class="bi bi-x-circle"></i>
                                        </a>
                                        <a href="/campaign/duplicate/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-info" title="<?= _e('duplicate') ?>">
                                            <i class="bi bi-files"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </div>

        <div class="tab-pane fade" id="draft" role="tabpanel" aria-labelledby="draft-tab">
            <?php if (isset($draftCampaigns) && !empty($draftCampaigns)) { ?>
                <form id="batchScheduleForm" action="/campaigns/batch-schedule" method="post">
                    <div class="table-responsive bg-white rounded border border-bottom-0">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAllDraft">
                                        </div>
                                    </th>
                                    <th style="min-width: 220px;"><?= _e('campaign') ?></th>
                                    <th style="min-width: 320px;"><?= _e('details') ?></th>
                                    <th style="min-width: 90px;"><?= _e('status') ?></th>
                                    <th style="min-width: 190px;"><?= _e('history') ?></th>
                                    <th style="min-width: 124px; width: 124px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($draftCampaigns as $campaign) { ?>
                                    <tr>
                                        <td>
                                            <div class="form-check position-relative ps-0">
                                                <input class="form-check-input campaign-checkbox float-none ms-0" type="checkbox" name="campaign_ids[]" value="<?= $campaign['id'] ?>" data-campaign-id="<?= $campaign['id'] ?>">
                                                <small class="d-block text-muted selection-order"></small>
                                                <input type="hidden" name="campaign_order[<?= $campaign['id'] ?>]" class="campaign-order-input" value="">
                                            </div>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($campaign['name']) ?></td>
                                    <td class="small">
                                        <div><a href="<?= htmlspecialchars($campaign['push_url'] ?? '#') ?>" class="link-secondary" target="_blank"><?= htmlspecialchars($campaign['push_title']) ?></a></div>
                                        <div title="<?= htmlspecialchars($campaign['push_body']) ?>"><?= strlen($campaign['push_body']) > 60 ? substr(htmlspecialchars($campaign['push_body']), 0, 60) . '...' : htmlspecialchars($campaign['push_body']) ?></div>
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
                                            <?= _e('status_' . $campaign['status']) ?>
                                        </span>
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
                                        <?php if (in_array($campaign['status'], ['draft', 'cancelled'])) { ?>
                                            <a href="/campaign/delete/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= _e('confirm_delete_campaign') ?>')" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if (in_array($campaign['status'], ['sent'])) { ?>
                                            <a href="/campaign/analytics/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-primary" title="<?= _e('analytics') ?>">
                                                <i class="bi bi-graph-up"></i>
                                            </a>
                                        <?php } ?>
                                        <a href="/campaign/duplicate/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-info" title="<?= _e('duplicate') ?>">
                                            <i class="bi bi-files"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3 batch-schedule-options" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <?= _e('batch_schedule_campaigns') ?>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 col-md-5">
                                    <label for="startDateTime" class="form-label"><?= _e('start_datetime') ?></label>
                                    <input type="datetime-local" class="form-control" id="startDateTime" name="start_datetime" required>
                                    <div class="form-text"><?= _e('start_datetime_description') ?></div>
                                </div>
                                <div class="col-12 col-md-5 mb-3 mb-md-0">
                                    <label for="timeInterval" class="form-label"><?= _e('time_interval') ?></label>
                                    <select class="form-select" id="timeInterval" name="time_interval" required>
                                        <option value="15"><?= _e('15_minutes') ?></option>
                                        <option value="30"><?= _e('30_minutes') ?></option>
                                        <option value="60"><?= _e('60_minutes') ?></option>
                                    </select>
                                    <div class="form-text"><?= _e('time_interval_description') ?></div>
                                </div>
                                <div class="col-12 col-md-2 mb-0 mb-md-0 mt-md-2 pt-md-4">
                                    <button type="submit" class="btn btn-primary w-100"><?= _e('schedule_selected_campaigns') ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </form>
                
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const selectAllCheckbox = document.getElementById('selectAllDraft');
                        const campaignCheckboxes = document.querySelectorAll('.campaign-checkbox');
                        const batchScheduleOptions = document.querySelector('.batch-schedule-options');
                        let selectionOrder = [];
                        
                        function toggleBatchScheduleOptions() {
                            const anyChecked = Array.from(campaignCheckboxes).some(checkbox => checkbox.checked);
                            batchScheduleOptions.style.display = anyChecked ? 'block' : 'none';
                        }
                        
                        function updateSelectionOrderDisplay() {
                            document.querySelectorAll('.selection-order').forEach(el => {
                                el.style.display = 'none';
                                el.textContent = '';
                            });
                            
                            document.querySelectorAll('.campaign-order-input').forEach(input => {
                                input.value = '';
                            });
                            
                            selectionOrder.forEach((id, index) => {
                                const checkbox = document.querySelector(`.campaign-checkbox[data-campaign-id="${id}"]`);
                                if (checkbox && checkbox.checked) {
                                    const orderDisplay = checkbox.closest('.form-check').querySelector('.selection-order');
                                    orderDisplay.textContent = '#' + (index + 1);
                                    orderDisplay.style.display = 'block';
                                    
                                    const orderInput = checkbox.closest('.form-check').querySelector('.campaign-order-input');
                                    orderInput.value = index + 1;
                                }
                            });
                        }
                        
                        selectAllCheckbox.addEventListener('change', function() {
                            if (selectAllCheckbox.checked) {
                                campaignCheckboxes.forEach(checkbox => {
                                    const campaignId = checkbox.getAttribute('data-campaign-id');
                                    if (!checkbox.checked && !selectionOrder.includes(campaignId)) {
                                        selectionOrder.push(campaignId);
                                    }
                                    checkbox.checked = true;
                                });
                            } else {
                                selectionOrder = [];
                                campaignCheckboxes.forEach(checkbox => {
                                    checkbox.checked = false;
                                });
                            }
                            updateSelectionOrderDisplay();
                            toggleBatchScheduleOptions();
                        });
                        
                        campaignCheckboxes.forEach(checkbox => {
                            checkbox.addEventListener('change', function() {
                                const campaignId = this.getAttribute('data-campaign-id');
                                
                                if (this.checked && !selectionOrder.includes(campaignId)) {
                                    selectionOrder.push(campaignId);
                                } else if (!this.checked) {
                                    selectionOrder = selectionOrder.filter(id => id !== campaignId);
                                }
                                
                                updateSelectionOrderDisplay();
                                toggleBatchScheduleOptions();
                            });
                        });
                        
                        const startDateTimeInput = document.getElementById('startDateTime');
                        if (startDateTimeInput) {
                            const now = new Date();
                            const year = now.getFullYear();
                            const month = String(now.getMonth() + 1).padStart(2, '0');
                            const day = String(now.getDate()).padStart(2, '0');
                            const hours = String(now.getHours()).padStart(2, '0');
                            const minutes = String(now.getMinutes()).padStart(2, '0');
                            
                            const currentDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                            startDateTimeInput.min = currentDateTime;
                            startDateTimeInput.value = currentDateTime;
                        }
                    });
                </script>
            <?php } ?>
        </div>

        <div class="tab-pane fade" id="other" role="tabpanel" aria-labelledby="other-tab">
            <?php if (isset($otherCampaigns) && !empty($otherCampaigns)) { ?>
                <div class="table-responsive bg-white rounded border border-bottom-0">
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
                            <?php foreach ($otherCampaigns as $campaign) { ?>
                                <tr>
                                    <td class="small"><?= htmlspecialchars($campaign['name']) ?></td>
                                    <td class="small">
                                        <div><a href="<?= htmlspecialchars($campaign['push_url'] ?? '#') ?>" class="link-secondary" target="_blank"><?= htmlspecialchars($campaign['push_title']) ?></a></div>
                                        <div title="<?= htmlspecialchars($campaign['push_body']) ?>"><?= strlen($campaign['push_body']) > 60 ? substr(htmlspecialchars($campaign['push_body']), 0, 60) . '...' : htmlspecialchars($campaign['push_body']) ?></div>
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
                                            <?= _e('status_' . $campaign['status']) ?>
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
                                        <?php if (in_array($campaign['status'], ['draft', 'cancelled'])) { ?>
                                            <a href="/campaign/delete/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= _e('confirm_delete_campaign') ?>')" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if (in_array($campaign['status'], ['sent'])) { ?>
                                            <a href="/campaign/analytics/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-primary" title="<?= _e('analytics') ?>">
                                                <i class="bi bi-graph-up"></i>
                                            </a>
                                        <?php } ?>
                                        <a href="/campaign/duplicate/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-info" title="<?= _e('duplicate') ?>">
                                            <i class="bi bi-files"></i>
                                        </a>
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

            <?php } ?>
        </div>
    </div>
<?php } else { ?>
    <?php if (isset($otherCampaigns) && !empty($otherCampaigns)) { ?>

        <nav class="mt-4 mb-4">
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

        <div class="table-responsive bg-white rounded border border-bottom-0">
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
                    <?php foreach ($otherCampaigns as $campaign) { ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($campaign['name']) ?></td>
                            <td class="small">
                                <div><a href="<?= htmlspecialchars($campaign['push_url'] ?? '#') ?>" class="link-secondary" target="_blank"><?= htmlspecialchars($campaign['push_title']) ?></a></div>
                                <div title="<?= htmlspecialchars($campaign['push_body']) ?>"><?= strlen($campaign['push_body']) > 60 ? substr(htmlspecialchars($campaign['push_body']), 0, 60) . '...' : htmlspecialchars($campaign['push_body']) ?></div>
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
                                    <?= _e('status_' . $campaign['status']) ?>
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
                                <?php if (in_array($campaign['status'], ['draft', 'cancelled'])) { ?>
                                    <a href="/campaign/delete/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= _e('confirm_delete_campaign') ?>')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php } ?>
                                <?php if (in_array($campaign['status'], ['sent'])) { ?>
                                    <a href="/campaign/analytics/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-primary" title="<?= _e('analytics') ?>">
                                        <i class="bi bi-graph-up"></i>
                                    </a>
                                <?php } ?>
                                <a href="/campaign/duplicate/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline-info" title="<?= _e('duplicate') ?>">
                                    <i class="bi bi-files"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

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
<?php } ?>

<?php $this->end() ?>