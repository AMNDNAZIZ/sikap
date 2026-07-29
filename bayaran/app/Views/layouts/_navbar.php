<nav class="navbar navbar-light bg-light shadow-sm fixed-top d-md-none">
  <div class="container-fluid">
    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
      <i class="bi bi-list"></i>
    </button>
    <span class="navbar-brand mb-0 h6 ms-2"><?= esc($title ?? 'Admin') ?></span>
    <button class="btn btn-sm btn-outline-dark ms-auto" id="toggleDark">
      <i class="bi bi-moon"></i>
    </button>
  </div>
</nav>
