<?php
$this->layout('layout/default', ['title' => _e('user_edit')]) ?>

<?php $this->start('page_content') ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="title d-flex">
        <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle'; ?> me-2 fs-5"></i>
        <h1 class="h4"><?= $isEdit ? _e('user_edit') : _e('user_create'); ?></h1>
    </div>
    <div class="btn-toolbar">
        <?php if($userData['id'] != 1) { ?>
            <button type="submit" form="userForm" name="action" value="save" class="btn btn-primary">
                <?= $isEdit ? _e('user_edit') : _e('user_create'); ?>
            </button>
        <?php } ?>
    </div>
</div>

<form method="POST" class="needs-validation" id="userForm">
    <div class="row g-3">
        <?php if (!$isEdit) { ?>
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="email" class="form-label fw-bold">
                        <i class="bi bi-envelope me-2"></i>
                        <?= _e('email') ?>
                    </label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?= $isEdit ? htmlspecialchars($userData['email']) : '' ?>"
                        required>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="role" class="form-label fw-bold">
                        <i class="bi bi-dice-3 me-2"></i>
                        <?= _e('role') ?>
                    </label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="admin"><?= _e('role_admin') ?></option>
                        <option value="editor" selected><?= _e('role_editor') ?></option>
                    </select>
                </div>
            </div>
        <?php }

        if ($isEdit) { ?>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="email" class="form-label fw-bold">
                        <i class="bi bi-envelope me-2"></i>
                        <?= _e('email') ?>
                    </label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?= $isEdit ? htmlspecialchars($userData['email']) : '' ?>"
                        required
                        <?= $userData['id'] == 1 ? 'disabled' : '' ?>>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="role" class="form-label fw-bold">
                        <i class="bi bi-dice-3 me-2"></i>
                        <?= _e('role') ?>
                    </label>
                    <select class="form-control" id="role" name="role" required <?= $userData['id'] == 1 ? 'disabled' : '' ?>>
                        <option value="admin" <?= $userData['role'] === 'admin' ? 'selected' : '' ?>><?= _e('role_admin') ?></option>
                        <option value="editor" <?= $userData['role'] === 'editor' ? 'selected' : '' ?>><?= _e('role_editor') ?></option>
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-group">
                    <label for="status" class="form-label fw-bold">
                        <i class="bi bi-person-check me-2"></i>
                        <?= _e('status') ?>
                    </label>
                    <select class="form-control" id="status" name="status" required <?= $userData['id'] == 1 ? 'disabled' : '' ?>>
                        <option value="active" <?= $userData['status'] === 'active' ? 'selected' : '' ?>><?= _e('active') ?></option>
                        <option value="inactive" <?= $userData['status'] === 'inactive' ? 'selected' : '' ?>><?= _e('inactive') ?></option>
                    </select>
                </div>
            </div>
        <?php } ?>
    </div>
</form>

<?php if (isset($userData['role']) && $userData['role'] != 'editor' && $isEdit) { ?>
    <div class="d-flex gap-3 pt-4 mt-4 border-top">
        <form id="formGenerateApiKey" method="POST" action="/user/token/<?= $userData['id'] ?>">
            <input type="hidden" name="generateApiKey" value="true">
            <button class="btn btn-sm btn-outline-primary" name="action" form="formGenerateApiKey">
                <i class="bi bi-key me-1"></i> <?= _e('generate_key') ?>
            </button>
        </form>

        <?php if (!empty($userData['api_key'])) { ?>
            <button class="btn btn-sm btn-outline-success copy-bearer" data-bearer="<?= htmlspecialchars($userData['api_key']) ?>">
                <i class="bi bi-clipboard me-1"></i> <?= _e('copy_key') ?>
            </button>
        <?php } ?>
    </div>
<?php } ?>

<?php $this->end() ?>

<?php $this->start('page_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function attachCopyListeners() {
            document.querySelectorAll('.copy-bearer').forEach(button => {
                button.addEventListener('click', function() {
                    const bearerToken = this.getAttribute('data-bearer');

                    navigator.clipboard.writeText(bearerToken).then(() => {
                        this.innerHTML = '<i class="bi bi-clipboard-check me-1"></i><?= _e('copied') ?>';

                        setTimeout(() => {
                            this.innerHTML = '<i class="bi bi-clipboard me-1"></i><?= _e('copy_key') ?>';
                        }, 2000);
                    }).catch(err => {
                        // Error
                    });
                });
            });
        }

        attachCopyListeners();
    });
</script>
<?php $this->end() ?>