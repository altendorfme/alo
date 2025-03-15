<!DOCTYPE html>
<html lang="<?= _e('lang') ?>" data-bs-theme="light">

<head>
    <?= $this->insert('parts/head', ['title' => $title ?? 'PushBase', 'css' => $css ?? null]) ?>

    <?= $this->section('page_styles'); ?>
</head>

<body>
    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom shadow" aria-label="Main navigation">
        <div class="container">
            <!-- Branding Section -->
            <div class="brand">
                <a title="Dashboard" href="/dashboard"><i class="text-white me-2 bi bi-bell-fill fs-4"></i></a>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNav"
                aria-controls="mainNav"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Items Container -->
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <!-- Dashboard Link -->
                    <li class="nav-item">
                        <a class="nav-link <?= $route == 'dashboard' ? 'active' : '' ?>"
                            title="Dashboard"
                            href="/dashboard">
                            <i class="bi bi-speedometer2 me-1"></i>
                            <?= _e('dashboard') ?>
                            <?php if ($route == 'dashboard') { ?>
                                <span class="visually-hidden">(<?= _e('current') ?>)</span>
                            <?php } ?>
                        </a>
                    </li>

                    <!-- Campaigns Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array($route, ['campaign', 'campaign/edit', 'campaign/analytics', 'campaigns', 'campaigns/page']) ? 'active' : '' ?>"
                            href="#"
                            id="campaignsDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Campaigns">
                            <i class="bi bi-megaphone me-1"></i>
                            <?= _e('campaigns') ?>
                            <?php if (in_array($route, ['campaign', 'campaign/edit', 'campaign/analytics', 'campaigns', 'campaigns/page'])) { ?>
                                <span class="visually-hidden">(<?= _e('current') ?>)</span>
                            <?php } ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="campaignsDropdown">
                            <li><a class="dropdown-item" href="/campaigns"> <?= _e('campaigns_list') ?></a></li>
                            <li><a class="dropdown-item" href="/campaign"> <?= _e('campaign_create') ?></a></li>
                        </ul>
                    </li>

                    <?php if ($user['role'] != 'editor') { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= in_array($route, ['user', 'user/edit', 'users', 'users/page']) ? 'active' : '' ?>"
                                href="#"
                                id="usersDropdown"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                title="Users">
                                <i class="bi bi-megaphone me-1"></i>
                                <?= _e('users') ?>
                                <?php if (in_array($route, ['user', 'user/edit', 'users', 'users/page'])) { ?>
                                    <span class="visually-hidden">(<?= _e('current') ?>)</span>
								<?php } ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="usersDropdown">
                                <li><a class="dropdown-item" href="/users"><?= _e('users_list') ?></a></li>
                                <li><a class="dropdown-item" href="/user"><?= _e('user_create') ?></a></li>
                            </ul>
                        </li>
                    <?php } ?>

                    <li class="nav-item">
                        <a class="nav-link <?= in_array($route, ['segment', 'segment/edit', 'segments', 'segments/page']) ? 'active' : '' ?>" title="Segments"
                            href="/segments">
                            <i class="bi bi-tag me-1"></i>
                            <?= _e('segments') ?>
                            <?php if (in_array($route, ['segment', 'segment/edit', 'segments', 'segments/page'])) { ?>
                                <span class="visually-hidden">(<?= _e('current') ?>)</span>
                            <?php } ?>
                        </a>
                    </li>
                </ul>

                <!-- User Section -->
                <div class="navbar-nav">
                    <div class="nav-item text-nowrap d-md-flex align-items-center">
                        <span class="text-light me-3 d-block d-md-inline-block mb-1 mb-md-0">
                            <?= $this->e($user['email']) ?>
                        </span>
                        <?php if ($user['role'] != 'editor') { ?>
                            <a href="/client" title="<?= _e('client_configuration') ?>"
                                class="nav-link border rounded border-secondary py-1 px-2 d-inline-block my-1 my-md-0 me-md-2 <?= in_array($route, ['client']) ? 'active' : '' ?>">
                                <i class="bi bi-magic"></i>
                            </a>
                        <?php } ?>
                        <a href="/logout" title="<?= _e('sign_out') ?>"
                            class="nav-link border rounded border-secondary py-1 px-2 d-inline-block my-1 my-md-0">
                            <?= _e('sign_out') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <main>
            <?php
            if (isset($error)) {
                ?>
                <div class="alert alert-danger">
                    <span class="bi bi-exclamation-triangle-fill me-2"></span>
                    <?= $error; ?>
                </div>
                <?php
            } elseif (isset($_GET['error'])) {
                ?>
                <div class="alert alert-danger">
                    <?= _e($_GET['error']); ?>
                </div>
                <?php
            }

            if (isset($warning)) {
                ?>
                <div class="alert alert-warning">
                    <span class="bi bi-exclamation-triangle-fill me-2"></span>
                    <?= $warning; ?>
                </div>
                <?php
            } elseif (isset($_GET['warning'])) {
                ?>
                <div class="alert alert-warning">
                    <span class="bi bi-exclamation-triangle-fill me-2"></span>
                    <?= _e($_GET['warning']); ?>
                </div>
                <?php
            }

            if (isset($success)) {
                ?>
                <div class="alert alert-success">
                    <span class="bi bi-check-circle-fill me-2"></span>
                    <?= $success; ?>
                </div>
                <?php
            } elseif (isset($_GET['success'])) {
                ?>
                <div class="alert alert-success">
                    <span class="bi bi-check-circle-fill me-2"></span>
                    <?= _e($_GET['success']); ?>
                </div>
                <?php
            }
            ?>

            <?= $this->section('page_content'); ?>
        </main>
    </div>

    <?= $this->insert('parts/footer', ['js' => $js ?? null]) ?>

    <?= $this->section('page_scripts'); ?>
</body>

</html>