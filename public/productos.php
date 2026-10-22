<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.html');
    exit;
}

$nombre = htmlspecialchars(
    (string)($_SESSION['usuario_nombre'] ?? 'Usuario'),
    ENT_QUOTES,
    'UTF-8'
);

$rol = (string)($_SESSION['usuario_rol'] ?? 'vendedor');
$esAdmin = $rol === 'admin';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos | Sistema de Inventario</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">



</head>

<body>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-box-seam"></i> Inventario
        </div>

        <div class="nav-title">MENÚ PRINCIPAL</div>

        <a href="dashboard.php" class="side-link">
            <i class="bi bi-grid-1x2"></i>
            Panel principal
        </a>

        <a href="productos.php" class="side-link active">
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
                <h1 class="page-title">Gestión de Productos</h1>
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

        <section class="stats">
            <article class="stat-card">
                <div class="stat-icon purple"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="stat-label">Productos registrados</div>
                    <div class="stat-number" id="totalProducts">6</div>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-icon orange"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="stat-label">Stock bajo</div>
                    <div class="stat-number" id="lowStock">2</div>
                </div>
            </article>

            <article class="stat-card">
                <div class="stat-icon red"><i class="bi bi-eye-slash"></i></div>
                <div>
                    <div class="stat-label">Productos inactivos</div>
                    <div class="stat-number" id="inactiveProducts">1</div>
                </div>
            </article>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <div class="panel-title">Todos los productos</div>
                    <div class="panel-subtitle">Consulta y administra tu catálogo.</div>
                </div>
                <button class="btn btn-primary btn-sm px-3 py-2" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo producto
                </button>
            </div>

            <div class="filters">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="search" id="searchInput" class="form-control"
                        placeholder="Buscar por nombre o código...">
                </div>

                <select id="categoryFilter" class="form-select" style="max-width:190px">
                    <option value="">Todas las categorías</option>
                    <option value="Electrónica">Electrónica</option>
                    <option value="Accesorios">Accesorios</option>
                    <option value="Papelería">Papelería</option>
                    <option value="Oficina">Oficina</option>
                </select>

                <select id="statusFilter" class="form-select" style="max-width:170px">
                    <option value="">Todos los estados</option>
                    <option value="ok">Disponible</option>
                    <option value="low">Stock bajo</option>
                    <option value="off">Inactivo</option>
                </select>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>PRODUCTO</th>
                            <th>CATEGORÍA</th>
                            <th>PRECIO</th>
                            <th>EXISTENCIAS</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="productsTable"></tbody>
                </table>
            </div>

            <div class="panel-footer">
                <span id="resultsCount">Mostrando 6 productos</span>
                <span><i class="bi bi-info-circle me-1"></i> Vista previa con datos de ejemplo</span>
            </div>
        </section>
    </main>

    <!-- Modal de producto -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" id="modalTitle">Nuevo producto</h5>
                        <div class="text-secondary small mt-1">Completa la información del artículo.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form id="productForm">
                    <div class="modal-body">
                        <input type="hidden" id="productId">

                        <div class="mb-3">
                            <label for="productCode" class="form-label">Código del producto *</label>
                            <input id="productCode" class="form-control" maxlength="30" placeholder="Ej. PROD-001"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="productName" class="form-label">Nombre del producto *</label>
                            <input id="productName" class="form-control" maxlength="150"
                                placeholder="Nombre del artículo" required>
                        </div>

                        <div class="mb-3">
                            <label for="productCategory" class="form-label">Categoría</label>
                            <select id="productCategory" class="form-select">
                                <option value="">Sin categoría</option>
                                <option>Electrónica</option>
                                <option>Accesorios</option>
                                <option>Papelería</option>
                                <option>Oficina</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="productPrice" class="form-label">Precio ($) *</label>
                                <input id="productPrice" type="number" class="form-control" min="0" step="0.01"
                                    placeholder="0.00" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="productStock" class="form-label">Existencias *</label>
                                <input id="productStock" type="number" class="form-control" min="0" step="1"
                                    placeholder="0" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="productMinStock" class="form-label">Stock mínimo</label>
                            <input id="productMinStock" type="number" class="form-control" min="0" step="1" value="5"
                                required>
                            <div class="form-text">Se marcará como stock bajo cuando llegue a este nivel o menos.</div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="productActive" checked>
                            <label class="form-check-label small" for="productActive">Producto activo</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2 me-1"></i> Guardar producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const API_PRODUCTOS = "../api/productos.php";

        let products = [];
        let categories = [];

        const modal = new bootstrap.Modal(
            document.getElementById("productModal")
        );

        const form = document.getElementById("productForm");

        async function apiRequest(url, options = {}) {
            const response = await fetch(url, {
                credentials: "same-origin",
                ...options,
                headers: {
                    "Accept": "application/json",
                    ...(options.body ? {
                        "Content-Type": "application/json"
                    } : {}),
                    ...(options.headers || {})
                }
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error("Tu sesión expiró. Inicia sesión nuevamente.");
                }

                throw new Error(data.error || "No se pudo completar la solicitud.");
            }

            return data;
        }

        async function loadProducts() {
            try {
                const data = await apiRequest(API_PRODUCTOS);

                products = data.productos;
                categories = data.categorias;

                renderCategoryOptions();
                renderProducts();
            } catch (error) {
                console.error(error);
                alert(error.message);
            }
        }

        function renderCategoryOptions() {
            const filter = document.getElementById("categoryFilter");
            const select = document.getElementById("productCategory");

            const previousFilter = filter.value;
            const previousCategory = select.value;

            filter.innerHTML = '<option value="">Todas las categorías</option>';
            select.innerHTML = '<option value="">Sin categoría</option>';

            categories.forEach(category => {
                const optionFilter = document.createElement("option");
                optionFilter.value = category.nombre;
                optionFilter.textContent = category.nombre;
                filter.appendChild(optionFilter);

                const optionSelect = document.createElement("option");
                optionSelect.value = category.id;
                optionSelect.textContent = category.nombre;
                select.appendChild(optionSelect);
            });

            if ([...filter.options].some(o => o.value === previousFilter)) {
                filter.value = previousFilter;
            }

            if ([...select.options].some(o => o.value === previousCategory)) {
                select.value = previousCategory;
            }
        }

        function getStatus(product) {
            if (!product.activo) return "off";
            if (product.stock <= product.stock_minimo) return "low";
            return "ok";
        }

        function statusHTML(product) {
            const status = getStatus(product);

            const labels = {
                ok: ["status-ok", "Disponible"],
                low: ["status-low", "Stock bajo"],
                off: ["status-off", "Inactivo"]
            };

            return `<span class="status ${labels[status][0]}">${labels[status][1]}</span>`;
        }

        function formatMoney(value) {
            return Number(value).toLocaleString("es-MX", {
                style: "currency",
                currency: "MXN"
            });
        }

        function escapeHTML(value) {
            return String(value ?? "").replace(/[&<>"']/g, char => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            })[char]);
        }

        function renderProducts() {
            const search = document.getElementById("searchInput")
                .value.trim().toLowerCase();

            const category = document.getElementById("categoryFilter").value;
            const status = document.getElementById("statusFilter").value;

            const filtered = products.filter(product => {
                const matchesSearch =
                    product.nombre.toLowerCase().includes(search) ||
                    product.codigo.toLowerCase().includes(search);

                const matchesCategory = !category || (product.categoria || "") === category;

                const matchesStatus = !status || getStatus(product) === status;

                return matchesSearch && matchesCategory && matchesStatus;
            });

            document.getElementById("totalProducts").textContent = products.length;

            document.getElementById("lowStock").textContent =
                products.filter(p => p.activo && p.stock <= p.stock_minimo).length;

            document.getElementById("inactiveProducts").textContent =
                products.filter(p => !p.activo).length;

            const tbody = document.getElementById("productsTable");

            if (filtered.length === 0) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="bi bi-search fs-3 d-block mb-2"></i>
                        No se encontraron productos.
                    </td>
                </tr>`;
            } else {
                tbody.innerHTML = filtered.map(p => `
                <tr>
                    <td>
                        <div class="product-name">${escapeHTML(p.nombre)}</div>
                        <div class="product-code">${escapeHTML(p.codigo)}</div>
                    </td>
                    <td>
                        <span class="category-tag">
                            ${escapeHTML(p.categoria || "Sin categoría")}
                        </span>
                    </td>
                    <td class="price">${formatMoney(p.precio)}</td>
                    <td>
                        <span class="${p.activo && p.stock <= p.stock_minimo
                        ? 'text-warning fw-bold' : 'fw-semibold'}">
                            ${p.stock}
                        </span>
                        <span class="text-secondary"> uds.</span>
                    </td>
                    <td>${statusHTML(p)}</td>
                    <td>
                        <div class="actions">
                            <button class="action-btn" title="Editar"
                                onclick="editProduct(${p.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="action-btn"
                                title="${p.activo ? 'Desactivar' : 'Activar'}"
                                onclick="toggleProduct(${p.id})">
                                <i class="bi ${p.activo ? 'bi-eye-slash' : 'bi-eye'}"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join("");
            }

            document.getElementById("resultsCount").textContent =
                `Mostrando ${filtered.length} de ${products.length} productos`;
        }

        function openCreateModal() {
            form.reset();

            document.getElementById("productId").value = "";
            document.getElementById("productMinStock").value = 5;
            document.getElementById("productActive").checked = true;
            document.getElementById("modalTitle").textContent = "Nuevo producto";

            modal.show();
        }

        function editProduct(id) {
            const p = products.find(product => product.id === id);
            if (!p) return;

            document.getElementById("productId").value = p.id;
            document.getElementById("productCode").value = p.codigo;
            document.getElementById("productName").value = p.nombre;
            document.getElementById("productCategory").value =
                p.categoria_id ?? "";
            document.getElementById("productPrice").value = p.precio;
            document.getElementById("productStock").value = p.stock;
            document.getElementById("productMinStock").value = p.stock_minimo;
            document.getElementById("productActive").checked = p.activo;

            document.getElementById("modalTitle").textContent = "Editar producto";
            modal.show();
        }

        form.addEventListener("submit", async event => {
            event.preventDefault();

            const id = document.getElementById("productId").value;

            const payload = {
                accion: id ? "editar" : "crear",
                codigo: document.getElementById("productCode").value.trim(),
                nombre: document.getElementById("productName").value.trim(),
                categoria_id: document.getElementById("productCategory").value || null,
                precio: document.getElementById("productPrice").value,
                stock: document.getElementById("productStock").value,
                stock_minimo: document.getElementById("productMinStock").value,
                activo: document.getElementById("productActive").checked
            };

            if (id) payload.id = Number(id);

            try {
                await apiRequest(API_PRODUCTOS, {
                    method: "POST",
                    body: JSON.stringify(payload)
                });

                modal.hide();
                await loadProducts();
                alert(id ?
                    "Producto actualizado correctamente." :
                    "Producto registrado correctamente.");
            } catch (error) {
                alert(error.message);
            }
        });

        async function toggleProduct(id) {
            const product = products.find(p => p.id === id);
            if (!product) return;

            const action = product.activo ? "desactivar" : "activar";

            if (!confirm(`¿Deseas ${action} el producto "${product.nombre}"?`)) {
                return;
            }

            try {
                await apiRequest(API_PRODUCTOS, {
                    method: "POST",
                    body: JSON.stringify({
                        accion: "estado",
                        id: product.id,
                        activo: !product.activo
                    })
                });

                await loadProducts();
            } catch (error) {
                alert(error.message);
            }
        }

        document.getElementById("searchInput")
            .addEventListener("input", renderProducts);

        document.getElementById("categoryFilter")
            .addEventListener("change", renderProducts);

        document.getElementById("statusFilter")
            .addEventListener("change", renderProducts);

        loadProducts();
    </script>
</body>

</html>