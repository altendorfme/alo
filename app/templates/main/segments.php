<?php
$this->layout('layout/default', ['title' => _e('segments')]) ?>

<?php $this->start('page_content') ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="title d-flex">
        <i class="bi bi-diagram-3 me-2 fs-5"></i>
        <h1 class="h4"><?= _e('segments') ?></h1>
    </div>
</div>

<div class="table-responsive bg-white rounded border border-bottom-0 overflow-hidden">
    <table class="table table-striped table-hover align-middle mb-0">
        <thead>
            <tr>
                <th style="min-width: 48px; width: 48px;"></th>
                <th style="min-width: 190px;"><?= _e('segment_key') ?></th>
                <th style="min-width: 190px;"><?= _e('description') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($segments as $segment) { ?>
                <tr>
                    <td>
                        <a href="/segment/edit/<?= $segment['id'] ?>" class="btn btn-sm btn-outline-secondary" title="<?= _e('edit') ?>">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                    </td>
                    <td class="small"><?= htmlspecialchars($segment['name']) ?></td>
                    <td class="small"><?= !empty($segment['description']) ? htmlspecialchars($segment['description']) : _e('no_description') ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1) { ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center mb-0">
            <?php
            $paginationLinks = \Pushbase\Utilities\PaginationHelper::generatePaginationLinks(
                $currentPage,
                $totalPages,
                '/segments',
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