<?php
require loadView('Layouts/header');

// Solo administradores
if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    echo '<div class="container mt-5"><div class="alert alert-danger">Acceso restringido a administradores.</div></div>';
    require loadView('Layouts/Footer');
    return;
}

require_once dirname(__DIR__) . '/Models/Settings.php';
$s = (new Settings())->getAll();
?>
<link rel="stylesheet" href="<?= asset('assets/css/settings.css') ?>">

<div class="app-root settings-page">
    <div class="settings-wrap">
        <div class="settings-head">
            <h1><i class="fa-solid fa-gear"></i> Configuración del negocio</h1>
            <p>Personaliza el nombre, el logo y los colores. Los cambios se aplican en todo el sistema.</p>
        </div>

        <form id="settingsForm" enctype="multipart/form-data">
            <div class="settings-grid">

                <!-- Columna: datos -->
                <div class="settings-card">
                    <h3>Identidad</h3>

                    <label class="form-label" for="nombre_negocio">Nombre del negocio</label>
                    <input type="text" class="form-control" id="nombre_negocio" name="nombre_negocio"
                           maxlength="100" value="<?= esc($s['nombre_negocio']) ?>" required>

                    <label class="form-label mt-3" for="logoInput">Logo</label>
                    <div class="logo-uploader">
                        <img id="logoPreview" src="<?= asset('assets/img/' . $s['logo']) ?>" alt="Logo actual">
                        <div>
                            <input type="file" class="form-control" id="logoInput" name="logo" accept="image/png,image/jpeg,image/webp">
                            <small class="text-muted">PNG, JPG o WEBP. Máx 2&nbsp;MB.</small>
                        </div>
                    </div>

                    <label class="form-label mt-3" for="moneda">Símbolo de moneda</label>
                    <input type="text" class="form-control settings-short" id="moneda" name="moneda"
                           maxlength="5" value="<?= esc($s['moneda']) ?>">

                    <label class="form-label mt-3" for="mensaje_factura">Mensaje en la factura</label>
                    <input type="text" class="form-control" id="mensaje_factura" name="mensaje_factura"
                           maxlength="150" value="<?= esc($s['mensaje_factura']) ?>">

                    <hr class="my-3">
                    <h4 class="h6 text-muted">Datos para el tiquete impreso</h4>
                    <small class="text-muted d-block mb-2">
                        Se imprimen en el encabezado de la factura. Déjalos vacíos si no aplican.
                    </small>

                    <label class="form-label mt-2" for="nit">NIT / Documento</label>
                    <input type="text" class="form-control" id="nit" name="nit"
                           maxlength="30" value="<?= esc($s['nit'] ?? '') ?>" placeholder="Ej: 900.123.456-7">

                    <label class="form-label mt-3" for="direccion">Dirección</label>
                    <input type="text" class="form-control" id="direccion" name="direccion"
                           maxlength="120" value="<?= esc($s['direccion'] ?? '') ?>" placeholder="Ej: Calle 10 # 5-23">

                    <label class="form-label mt-3" for="telefono">Teléfono</label>
                    <input type="text" class="form-control" id="telefono" name="telefono"
                           maxlength="40" value="<?= esc($s['telefono'] ?? '') ?>" placeholder="Ej: 300 123 4567">

                    <label class="form-label mt-3" for="mensaje_pie">Mensaje de despedida</label>
                    <input type="text" class="form-control" id="mensaje_pie" name="mensaje_pie"
                           maxlength="150" value="<?= esc($s['mensaje_pie'] ?? '') ?>" placeholder="Ej: Vuelva pronto">
                </div>

                <!-- Columna: colores -->
                <div class="settings-card">
                    <h3>Colores del tema</h3>

                    <div class="color-field">
                        <div>
                            <span class="color-name">Color principal</span>
                            <small class="text-muted d-block">Barra superior y botones</small>
                        </div>
                        <input type="color" id="color_primario" name="color_primario" value="<?= esc($s['color_primario']) ?>">
                    </div>

                    <div class="color-field">
                        <div>
                            <span class="color-name">Color secundario</span>
                            <small class="text-muted d-block">Encabezados y detalles</small>
                        </div>
                        <input type="color" id="color_secundario" name="color_secundario" value="<?= esc($s['color_secundario']) ?>">
                    </div>

                    <div class="color-field">
                        <div>
                            <span class="color-name">Color de acento</span>
                            <small class="text-muted d-block">Resaltados y llamadas a la acción</small>
                        </div>
                        <input type="color" id="color_acento" name="color_acento" value="<?= esc($s['color_acento']) ?>">
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="resetColors">
                        <i class="fa-solid fa-rotate-left"></i> Restaurar colores por defecto
                    </button>

                    <!-- Vista previa -->
                    <div class="theme-preview" id="themePreview">
                        <div class="tp-bar" id="tpBar">
                            <img src="<?= asset('assets/img/' . $s['logo']) ?>" id="tpLogo" alt="">
                            <span id="tpName"><?= esc($s['nombre_negocio']) ?></span>
                        </div>
                        <div class="tp-body">
                            <button class="tp-btn-primary" id="tpBtnPrimary">Procesar venta</button>
                            <span class="tp-accent" id="tpAccent">Acento</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" class="btn btn-success btn-lg" id="saveBtn">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const DEFAULTS = { color_primario: '#5B3411', color_secundario: '#6B3E1A', color_acento: '#E07A2F' };

    const $ = (id) => document.getElementById(id);

    // --- Vista previa en vivo ---
    function applyPreview() {
        $('tpBar').style.background = $('color_primario').value;
        $('tpBtnPrimary').style.background = $('color_primario').value;
        $('tpAccent').style.color = $('color_acento').value;
        $('tpName').textContent = $('nombre_negocio').value || 'Mi negocio';
    }

    ['color_primario', 'color_secundario', 'color_acento'].forEach((id) =>
        $(id).addEventListener('input', applyPreview));
    $('nombre_negocio').addEventListener('input', applyPreview);

    $('resetColors').addEventListener('click', () => {
        $('color_primario').value = DEFAULTS.color_primario;
        $('color_secundario').value = DEFAULTS.color_secundario;
        $('color_acento').value = DEFAULTS.color_acento;
        applyPreview();
    });

    // Previsualizar logo elegido antes de subir
    $('logoInput').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire('Archivo muy grande', 'El logo debe pesar menos de 2 MB', 'warning');
            e.target.value = '';
            return;
        }
        const url = URL.createObjectURL(file);
        $('logoPreview').src = url;
        $('tpLogo').src = url;
    });

    applyPreview();

    // --- Guardar ---
    $('settingsForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = $('saveBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

        try {
            const resp = await fetch('index.php?pg=settings&action=save', {
                method: 'POST',
                body: new FormData($('settingsForm'))
            });
            const data = await resp.json();

            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: 'La configuración se aplicó correctamente',
                    timer: 1400,
                    showConfirmButton: false
                });
                // Recargar para que el nuevo tema/logo se vea en toda la app
                window.location.reload();
            } else {
                Swal.fire('Error', data.error || 'No se pudo guardar', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Fallo de conexión al guardar', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
        }
    });
})();
</script>

<?php require loadView('Layouts/Footer'); ?>
