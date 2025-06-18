<?php
$this->layout('layout/default', ['title' => _e('segment_data') . ' - ' . htmlspecialchars($segment['name'])]) ?>

<?php $this->start('page_content') ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="title d-flex">
        <i class="bi bi-bar-chart me-2 fs-5"></i>
        <h1 class="h4"><?= _e('segment_data') ?> - <?= htmlspecialchars($segment['name']) ?></h1>
    </div>
    <div class="btn-toolbar">
        <a href="/segments" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> <?= _e('back_to_segments') ?>
        </a>
        <a href="/segment/edit/<?= $segment['id'] ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil-square"></i> <?= _e('edit') ?>
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= _e('segment_information') ?></h5>
                <div class="row">
                    <div class="col-md-6">
                        <p class="m-0"><strong><?= _e('segment_key') ?>:</strong> <?= htmlspecialchars($segment['name']) ?></p>
                        <p class="m-0"><strong><?= _e('description') ?>:</strong> <?= !empty($segment['description']) ? htmlspecialchars($segment['description']) : _e('no_description') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="m-0"><strong><?= _e('created_at') ?>:</strong> <?= date('d/m/Y H:i', strtotime($segment['created_at'])) ?></p>
                        <p class="m-0"><strong><?= _e('updated_at') ?>:</strong> <?= date('d/m/Y H:i', strtotime($segment['updated_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-primary"><?= number_format($stats['total_records']) ?></h5>
                <p class="card-text"><?= _e('total_records') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-info"><?= number_format($stats['unique_values']) ?></h5>
                <p class="card-text"><?= _e('unique_values') ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($topValues)) { ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?= _e('top_values') ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><?= _e('value') ?></th>
                                    <th><?= _e('count') ?></th>
                                    <th><?= _e('percentage') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topValues as $topValue) {
                                    $percentage = ($topValue['count'] / $stats['total_records']) * 100;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($topValue['value']) ?></td>
                                        <td><?= number_format($topValue['count']) ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar"
                                                    style="width: <?= $percentage ?>%;"
                                                    aria-valuenow="<?= $percentage ?>"
                                                    aria-valuemin="0" aria-valuemax="100">
                                                    <?= number_format($percentage, 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<!-- Detailed Data -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?= _e('segment_records') ?></h5>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($segmentData)) { ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= _e('value') ?></th>
                            <th><?= _e('subscriber') ?></th>
                            <th><?= _e('subscriber_status') ?></th>
                            <th><?= _e('subscribed_at') ?></th>
                            <th><?= _e('last_active') ?></th>
                            <th><?= _e('recorded_at') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($segmentData as $data) { ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary"><?= htmlspecialchars($data['value']) ?></span>
                                </td>
                                <td class="small">
                                    <code><?= htmlspecialchars(substr($data['subscriber_uuid'], 0, 8)) ?>...</code>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match ($data['subscriber_status']) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'unsubscribed' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>"><?= _e($data['subscriber_status']) ?></span>
                                </td>
                                <td class="small"><?= date('d/m/Y H:i', strtotime($data['subscribed_at'])) ?></td>
                                <td class="small"><?= $data['last_active'] ? date('d/m/Y H:i', strtotime($data['last_active'])) : '-' ?></td>
                                <td class="small"><?= date('d/m/Y H:i', strtotime($data['created_at'])) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h5 class="mt-3"><?= _e('no_data_found') ?></h5>
                <p class="text-muted"><?= _e('no_data_recorded_for_segment') ?></p>
            </div>
        <?php } ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1) { ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center mb-0">
            <?php
            $paginationLinks = \alo\Utilities\PaginationHelper::generatePaginationLinks(
                $currentPage,
                $totalPages,
                '/segment/data/' . $segment['id'],
                []
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