<?php
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar" class="d-flex flex-column p-3 text-white">
    <div class="text-center mb-4 mt-2">
        <h3 class="fw-bold m-0 text-light">SISTEMA POS </h3>
        <small style="color: var(--verde-claro);">GESTION COMERCIAL</small>
    </div>
    <hr style="border: none; background-color: var(--verde-medio); height: 2px;">

    <ul class="nav nav-pills flex-column mb-auto mt-2">
        <li class="nav-item mb-2">
            <a href="/dashboard.php"
                class="nav-link text-white fw-semibold menu-item <?php echo $pagina_actual === 'dashboard.php' ? 'active-menu' : ''; ?>">
                INICIO
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="/catalogo.php"
                class="nav-link text-white fw-semibold menu-item <?php echo $pagina_actual === 'catalogo.php' ? 'active-menu' : ''; ?>">
                CATALOGO
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="/pos.php"
                class="nav-link text-white fw-semibold menu-item <?php echo $pagina_actual === 'pos.php' ? 'active-menu' : ''; ?>">
                PUNTO DE VENTA
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="/historial.php"
                class="nav-link text-white fw-semibold menu-item <?php echo $pagina_actual === 'historial.php' ? 'active-menu' : ''; ?>">
                REPORTES
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="/clientes.php"
                class="nav-link text-white fw-semibold menu-item <?php echo $pagina_actual === 'clientes.php' ? 'active-menu' : ''; ?>">
                CLIENTES
            </a>
        </li>
    </ul>

    <hr style="border: none; background-color: var(--verde-medio); height: 2px;">
    <div class="text-center pb-2">
        <small class="text-white-50">version 1.0.0 2026</small>
    </div>
</nav>
