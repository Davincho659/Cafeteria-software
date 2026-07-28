// =====================================================
// GESTIÓN DE USUARIOS (solo admin)
// =====================================================
// El token CSRF lo agrega automáticamente el wrapper global de fetch
// (auth-helper.js), por eso aquí no se maneja a mano.

(function () {
    let editandoId = null;      // null = creando, id = editando
    let usuarioActualId = null; // para no dejar que el admin se borre a sí mismo

    const $ = (id) => document.getElementById(id);

    // ------------------------------------------------------------- utilidades
    function notificar(icono, titulo, texto) {
        if (window.Swal) {
            return Swal.fire({ icon: icono, title: titulo, text: texto, confirmButtonColor: '#5B3411' });
        }
        alert(titulo + (texto ? '\n' + texto : ''));
        return Promise.resolve();
    }

    function escapar(txt) {
        const d = document.createElement('div');
        d.textContent = txt == null ? '' : txt;
        return d.innerHTML;
    }

    // ------------------------------------------------------------- cargar lista
    async function cargarUsuarios() {
        const cuerpo = $('usersTableBody');
        cuerpo.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Cargando…</td></tr>';

        try {
            const r = await fetch('?pg=users&action=getUsers');
            const j = await r.json();

            if (!j.success) throw new Error(j.error || 'No se pudieron cargar los usuarios');

            usuarioActualId = j.currentUserId;
            const usuarios = j.data || [];

            if (usuarios.length === 0) {
                cuerpo.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">No hay usuarios.</td></tr>';
                return;
            }

            cuerpo.innerHTML = usuarios.map((u) => {
                const esAdmin = u.rol === 'admin';
                const esYo = Number(u.idUsuario) === Number(usuarioActualId);
                const insignia = esAdmin
                    ? '<span class="badge bg-warning text-dark">Administrador</span>'
                    : '<span class="badge bg-secondary">Empleado</span>';
                const marcaYo = esYo ? ' <small class="text-muted">(tú)</small>' : '';

                return `
                    <tr>
                        <td class="fw-semibold">
                            <i class="fa-solid fa-user text-muted me-1"></i>
                            ${escapar(u.nombre)}${marcaYo}
                        </td>
                        <td>${insignia}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary btn-editar"
                                    data-id="${u.idUsuario}"
                                    data-nombre="${escapar(u.nombre)}"
                                    data-rol="${u.rol}">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-eliminar"
                                    data-id="${u.idUsuario}"
                                    data-nombre="${escapar(u.nombre)}"
                                    ${esYo ? 'disabled title="No puedes eliminar tu propio usuario"' : ''}>
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
            }).join('');
        } catch (e) {
            cuerpo.innerHTML = `<tr><td colspan="3" class="text-center text-danger py-4">${escapar(e.message)}</td></tr>`;
        }
    }

    // ------------------------------------------------------------- formulario
    function modoCrear() {
        editandoId = null;
        $('formTitle').textContent = 'Nuevo Usuario';
        $('userId').value = '';
        $('userName').value = '';
        $('userPin').value = '';
        $('userRole').value = 'empleado';
        $('pinHelp').textContent = 'Mínimo 4 caracteres.';
        $('pinRequiredMark').style.display = '';
        $('btnCancelEdit').style.display = 'none';
    }

    function modoEditar(id, nombre, rol) {
        editandoId = id;
        $('formTitle').textContent = 'Editar Usuario';
        $('userId').value = id;
        $('userName').value = nombre;
        $('userPin').value = '';
        $('userRole').value = rol;
        // Al editar el PIN es opcional: vacío = se conserva el actual.
        $('pinHelp').textContent = 'Déjalo vacío para conservar el PIN actual.';
        $('pinRequiredMark').style.display = 'none';
        $('btnCancelEdit').style.display = '';
        $('userName').focus();
    }

    async function guardar(evento) {
        evento.preventDefault();

        const nombre = $('userName').value.trim();
        const pin = $('userPin').value;
        const rol = $('userRole').value;

        if (!nombre) {
            return notificar('warning', 'Falta el nombre', 'Escribe el nombre del usuario.');
        }
        // Al crear, el PIN es obligatorio; al editar puede ir vacío.
        if (!editandoId && pin.length < 4) {
            return notificar('warning', 'PIN muy corto', 'El PIN debe tener al menos 4 caracteres.');
        }
        if (editandoId && pin !== '' && pin.length < 4) {
            return notificar('warning', 'PIN muy corto', 'El PIN debe tener al menos 4 caracteres.');
        }

        const accion = editandoId ? 'updateUser' : 'createUser';
        const cuerpo = { nombre, pin, rol };
        if (editandoId) cuerpo.idUsuario = editandoId;

        const boton = $('btnSaveUser');
        boton.disabled = true;

        try {
            const r = await fetch('?pg=users&action=' + accion, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(cuerpo),
            });
            const j = await r.json();

            if (!j.success) throw new Error(j.error || 'No se pudo guardar');

            await notificar('success', j.message || 'Guardado', '');
            modoCrear();
            cargarUsuarios();
        } catch (e) {
            notificar('error', 'No se pudo guardar', e.message);
        } finally {
            boton.disabled = false;
        }
    }

    async function eliminar(id, nombre) {
        let confirmado = true;

        if (window.Swal) {
            const res = await Swal.fire({
                icon: 'warning',
                title: '¿Eliminar usuario?',
                html: `Se eliminará <strong>${escapar(nombre)}</strong>. Esta acción no se puede deshacer.`,
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            });
            confirmado = res.isConfirmed;
        } else {
            confirmado = confirm('¿Eliminar el usuario ' + nombre + '?');
        }

        if (!confirmado) return;

        try {
            const r = await fetch('?pg=users&action=deleteUser', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idUsuario: id }),
            });
            const j = await r.json();

            if (!j.success) throw new Error(j.error || 'No se pudo eliminar');

            await notificar('success', 'Usuario eliminado', '');
            cargarUsuarios();
        } catch (e) {
            notificar('error', 'No se pudo eliminar', e.message);
        }
    }

    // ------------------------------------------------------------- eventos
    document.addEventListener('DOMContentLoaded', function () {
        $('userForm').addEventListener('submit', guardar);
        $('btnCancelEdit').addEventListener('click', modoCrear);
        $('btnRefreshUsers').addEventListener('click', cargarUsuarios);

        // Delegación: los botones se crean dinámicamente con la tabla.
        $('usersTableBody').addEventListener('click', function (e) {
            const editar = e.target.closest('.btn-editar');
            if (editar) {
                modoEditar(editar.dataset.id, editar.dataset.nombre, editar.dataset.rol);
                return;
            }
            const borrar = e.target.closest('.btn-eliminar');
            if (borrar && !borrar.disabled) {
                eliminar(borrar.dataset.id, borrar.dataset.nombre);
            }
        });

        cargarUsuarios();
    });
})();
