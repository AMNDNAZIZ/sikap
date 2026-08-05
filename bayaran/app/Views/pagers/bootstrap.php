<?php
$pager->setSurroundCount(2);
if ($pager->getPageCount() > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination pagination-sm justify-content-center mb-0">
            <?php if ($pager->hasPrevious()): ?>
                <li class="page-item">
                    <a href="<?= $pager->getFirst() ?>" class="page-link" aria-label="First">
                        <span aria-hidden="true">&laquo;&laquo;</span>
                    </a>
                </li>
                <li class="page-item">
                    <a href="<?= $pager->getPreviousPage() ?>" class="page-link" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif ?>

            <?php foreach ($pager->links() as $link): ?>
                <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                    <a href="<?= $link['uri'] ?>" class="page-link">
                        <?= $link['title'] ?>
                    </a>
                </li>
            <?php endforeach ?>

            <?php if ($pager->hasNext()): ?>
                <li class="page-item">
                    <a href="<?= $pager->getNextPage() ?>" class="page-link" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <li class="page-item">
                    <a href="<?= $pager->getLast() ?>" class="page-link" aria-label="Last">
                        <span aria-hidden="true">&raquo;&raquo;</span>
                    </a>
                </li>
            <?php endif ?>
        </ul>
    </nav>
<?php endif ?>