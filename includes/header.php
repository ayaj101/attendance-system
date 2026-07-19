<?php
/**
 * Shared page head + opening layout markup.
 *
 * Each protected page sets $pageTitle (and optionally $breadcrumbs) before
 * including this file. Requires the user to be authenticated.
 */

require_once __DIR__ . '/../config/auth.php';
require_login();

$user     = current_user();
$set      = settings();
$theme    = $set['theme'] ?? 'light';
$title    = $pageTitle ?? 'Dashboard';
$active   = $activePage ?? '';
$crumbs   = $breadcrumbs ?? [];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> &middot; <?= e($set['company_name'] ?? APP_NAME) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">
</head>
<body>
<div class="app-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="app-content">
        <?php include __DIR__ . '/navbar.php'; ?>

        <main class="page-body p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="page-title mb-1"><?= e($title) ?></h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 small">
                            <li class="breadcrumb-item"><a href="<?= url('dashboard.php') ?>">Home</a></li>
                            <?php foreach ($crumbs as $label => $link): ?>
                                <?php if ($link): ?>
                                    <li class="breadcrumb-item"><a href="<?= e($link) ?>"><?= e($label) ?></a></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active" aria-current="page"><?= e($label) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                </div>
            </div>
