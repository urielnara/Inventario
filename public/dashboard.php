<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.html');
    exit;
}

$nombre = htmlspecialchars(
    (string) ($_SESSION['usuario_nombre'] ?? 'Usuario'),
    ENT_QUOTES,
    'UTF-8'
);

$rol = (string) ($_SESSION['usuario_rol'] ?? 'vendedor');
$esAdmin = $rol === 'admin';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario | Panel principal</title>

    
<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet"
>

<link rel="stylesheet" href="assets/css/styles.css">

</head>

<body>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-box-seam"></i> Inventario
        </div>

        <div class="nav-title">MENÚ PRINCIPAL</div>

        <a href="dashboard.php" class="side-link active">
            <i class="bi bi-grid-1x2"></i>
            Panel principal
        </a>

        <a href="productos.php" class="side-link">
            <i class="bi bi-boxes"></i>
            Productos
        </a>

        <a href="ventas.html" class="side-link">
            <i class="bi bi-receipt"></i>
            Ventas
        </a>

        <?php if ($esAdmin): ?>
            <a href="usuarios.html" class="side-link">
                <i class="bi bi-people"></i>
                Usuarios
            </a>
        <?php endif; ?>

        <div class="sidebar-bottom">
            <a href="../api/logout.php" class="side-link"
                onclick="return confirm('¿Deseas cerrar sesión?')">
                <i class="bi bi-box-arrow-left"></i>
                Cerrar sesión
            </a>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="mobile-menu" id="menuButton"
                    aria-label="Abrir menú">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title">Panel principal</h1>
            </div>

            <div class="user-chip">
                <div class="avatar">
                    <i class="bi bi-person"></i>
                </div>
                <div>

                    <span class="user-name"><?= $nombre ?></span>
                    <span class="user-role">
                        <?= $esAdmin ? 'Administrador' : 'Vendedor' ?>
                    </span>

                </div>
            </div>
        </header>

        <section class="content">
            <div class="welcome">
                <h2>¡Bienvenido, <?= $nombre ?>!</h2>
                <p class="muted mb-0">
                    Administra tus productos, ventas y operaciones desde aquí.
                </p>
            </div>

            <div class="row g-3">
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="stat-card-dashboard">
                        <div class="stat-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stat-label">Gestión de productos</div>
                        <div class="stat-value">Productos</div>
                        <p class="muted mb-0 mt-2">
                            Controla existencias y precios.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="stat-card-dashboard">
                        <div class="stat-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="stat-label">Operaciones comerciales</div>
                        <div class="stat-value">Ventas</div>
                        <p class="muted mb-0 mt-2">
                            Registra y consulta operaciones.
                        </p>
                    </div>
                </div>

                <?php if ($esAdmin): ?>
                    <div class="col-12 col-sm-6 col-xl-4">
                        <div class="stat-card-dashboard">
                            <div class="stat-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-label">Control de acceso</div>
                            <div class="stat-value">Usuarios</div>
                            <p class="muted mb-0 mt-2">
                                Administra las cuentas del sistema.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <h2 class="section-heading">Módulos del sistema</h2>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-4">
                    <a href="productos.html" class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <h3>Gestión de productos</h3>
                        <p>
                            Consulta el catálogo, registra productos,
                            actualiza precios y controla el stock.
                        </p>
                        <div class="module-footer d-flex justify-content-between">
                            Abrir módulo
                            <i class="bi bi-arrow-up-right"></i>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <a href="ventas.html" class="module-card">
                        <div class="module-icon">
                            <i class="bi bi-cart3"></i>
                        </div>
                        <h3>Gestión de ventas</h3>
                        <p>
                            Registra ventas, calcula importes y revisa
                            el historial de transacciones.
                        </p>
                        <div class="module-footer d-flex justify-content-between">
                            Abrir módulo
                            <i class="bi bi-arrow-up-right"></i>
                        </div>
                    </a>
                </div>

                <?php if ($esAdmin): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <a href="usuarios.html" class="module-card">
                            <div class="module-icon">
                                <i class="bi bi-person-gear"></i>
                            </div>
                            <h3>Gestión de usuarios</h3>
                            <p>
                                Consulta cuentas y administra los roles
                                de acceso al sistema.
                            </p>
                            <div class="module-footer d-flex justify-content-between">
                                Abrir módulo
                                <i class="bi bi-arrow-up-right"></i>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="info-card-dashboard mt-4">
                <h3 class="fs-6 fw-bold">
                    <i class="bi bi-shield-check me-2"></i>
                    Sesión activa
                </h3>
                <p class="muted mb-0">
                    Has iniciado sesión como <?= $nombre ?>.
                    Utiliza el menú lateral para navegar por el sistema.
                </p>
            </div>
        </section>
    </main>

    <script>
        const sidebar = document.getElementById('sidebar');

        document.getElementById('menuButton').addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', (event) => {
            if (
                window.innerWidth <= 900 &&
                !sidebar.contains(event.target) &&
                !event.target.closest('#menuButton')
            ) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>

</html>