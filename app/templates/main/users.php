<?php $this->layout('layout/default', ['title' => _e('users')]) ?>

<?php $this->start('page_content') ?>

<?php

use Pushbase\Utilities\PaginationHelper;

$queryParams = [];
if ($statusFilter) {
    $queryParams['status'] = $statusFilter;
}
if ($roleFilter) {
    $queryParams['role'] = $roleFilter;
}
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="title d-flex">
        <i class="bi bi-people me-2 fs-5"></i>
        <h1 class="h4"><?= _e('users') ?></h1>
    </div>
    <div class="d-flex align-items-center">
        <form class="me-2 d-flex align-items-center" method="get" action="/users">
            <select name="status" class="form-select form-select-sm me-2 " onchange="this.form.submit()">
                <option value=""><?= _e('all_statuses') ?></option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>><?= _e('active') ?></option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>><?= _e('inactive') ?></option>
            </select>
            <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value=""><?= _e('all_roles') ?></option>
                <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>><?= _e('admin') ?></option>
                <option value="editor" <?= $roleFilter === 'editor' ? 'selected' : '' ?>><?= _e('editor') ?></option>
            </select>
        </form>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="/user" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-circle me-2"></i><?= _e('user_create') ?>
            </a>
        </div>
    </div>
</div>

<div class="table-responsive bg-white rounded border border-bottom-0 overflow-hidden">
    <table class="table table-striped table-hover align-middle mb-0">
        <thead>
            <tr>
                <th style="min-width: 48px; width: 48px;"></th>
                <th style="min-width: 190px;"><?= _e('email') ?></th>
                <th style="min-width: 90px;"><?= _e('status') ?></th>
                <th style="min-width: 110px;"><?= _e('role') ?></th>
                <th style="min-width: 190px;"><?= _e('last_login') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user) { ?>
                <tr>
                    <td>
                        <a href="/user/edit/<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary" title="<?= _e('edit') ?>">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                    </td>
                    <td class="small"><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <?php
                        $statusClass = match ($user['status']) {
                            'active' => 'bg-success',
                            'inactive' => 'bg-secondary',
                            'suspended' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        ?>
                        <span class="badge <?= $statusClass ?>">
                            <?= _e('status_' . $user['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $roleClass = match ($user['role']) {
                            'admin' => 'bg-danger',
                            'editor' => 'bg-primary',
                            default => 'bg-primary'
                        };
                        ?>
                        <span class="badge <?= $roleClass ?>">
                            <?= _e('role_' . $user['role']) ?>
                        </span>
                    </td>
                    <td class="small">
                        <?php if (!empty($user['last_login'])) { ?>
                            <?= date('Y-m-d H:i', strtotime($user['last_login'])) ?>
                        <?php } else { ?>
                            <?= _e('never_logged') ?>
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
                '/users',
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