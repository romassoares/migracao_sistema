<style>
    .sidebar {
        width: 260px;
        background-color: #212529;
        color: #fff;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        overflow-x: hidden;
        transition: width 0.3s ease;
        z-index: 1040;
    }

    .sidebar-header {
        padding: 1rem;
        border-bottom: 1px solid #444;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sidebar a {
        color: #fff;
        text-decoration: none;
    }

    .sidebar a:hover {
        background-color: #343a40;
    }

    .sidebar .nav-link {
        padding: 0.75rem 1.25rem;
    }
</style>

<aside class="sidebar bg-dark text-white min-vh-100 d-flex flex-column p-0">
    <div class="p-4 border-bottom border-secondary d-flex align-items-center justify-content-between sidebar-header">
        <h4 class="mb-0 d-flex align-items-center">
            <i class="bi bi-list me-2"></i> Menu
        </h4>
    </div>
    <div class="px-4 py-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
            <i class="bi bi-building fs-3 me-2"></i>
            <div>
                <div class="fw-bold small">Cliente</div>
                <div class="text-white-50"><?php echo htmlspecialchars($_SESSION['company']['name'] ?? ''); ?></div>
            </div>
        </div>
    </div>
    <nav class="flex-grow-1 px-4 py-3">
        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a href="/layout/index" class="nav-link text-white d-flex align-items-center">
                    <i class="bi bi-layout-text-sidebar-reverse me-2"></i> Layout
                </a>
            </li>
            <li class="nav-item">
                <a href="/concorrente/index" class="nav-link text-white d-flex align-items-center">
                    <i class="bi bi-people me-2"></i> Concorrentes
                </a>
            </li>
            <li class="nav-item">
                <a href="/layout_colunas/index" class="nav-link text-white d-flex align-items-center">
                    <i class="bi bi-columns-gap me-2"></i> Layout colunas
                </a>
            </li>
        </ul>
    </nav>
    <div class="border-top border-secondary px-4 py-3">
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-2"></i>
                <span><?php echo htmlspecialchars($_SESSION['user']['login_usuario'] ?? ''); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="/auth/trocar_senha">Mudar senha</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/auth/logout">Logout</a></li>
            </ul>
        </div>
    </div>
</aside>
