<?php
/**
 * Admin layout fallback
 * This view ensures that any admin page that attempts to extend "layouts/admin"
 * will render correctly even when the original layout file is missing.
 * It simply extends the primary application layout and defines a content section.
 */
?>
<?php echo $this->extend('layouts/app'); ?>
<?php echo $this->section('content'); ?>
<div class="container-fluid py-4">
    <?= $this->renderSection('admin') ?>
</div>
<?php echo $this->endSection(); ?>
