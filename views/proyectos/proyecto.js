$(document).ready(function () {
    let estadoFiltro = 'ACTIVO';
    let lideresAgregados = [];
    let miembrosAgregados = [];

    const modalElement = document.getElementById('modalProyecto');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;
    const form = document.getElementById('formProyecto');
    const fechaVencimiento = document.getElementById('proyecto_vencimiento');

    const lideresDisponibles = new Map(
        Array.from(document.querySelectorAll('#proyecto_lideres_disponibles option')).map(option => [option.value, option.textContent])
    );
    const miembrosDisponibles = new Map(
        Array.from(document.querySelectorAll('#proyecto_miembros_disponibles option')).map(option => [option.value, option.textContent])
    );

    // ==========================================
    // DATATABLE CONFIGURACIÓN
    // ==========================================
    const tabla = $('#datatable_proyectos').DataTable({
        autoWidth: false,
        serverSide: false,
        responsive: true,
        bDestroy: true,
        ajax: {
            url: '?c=proyectos&a=listar',
            data: function (d) { d.estado = estadoFiltro; },
            dataSrc: function (json) {
                if (!json || !json.success || !Array.isArray(json.data)) return [];
                return json.data;
            }
        },
        columns: [
            {
                data: null,
                render: row => `<strong>${escapeHtml(row.titulo)}</strong><br><small class="text-muted">${escapeHtml(row.descripcion || 'Sin descripción')}</small>`
            },
            {
                data: 'lider_nombre',
                render: (data, type, row) => `${escapeHtml(data)}<br><small class="text-muted">${escapeHtml(row.lider_name)}</small>`
            },
            {
                data: null,
                render: row => `${formatDate(row.fecha_inicio_global)}<br><small class="text-muted">hasta ${formatDate(row.fecha_vencimiento_global)}</small>`
            },
            {
                data: 'miembros',
                render: data => `<span class="badge bg-light text-dark">${data} miembros</span>`
            },
            {
                data: 'tareas',
                render: (data, type, row) => `${data} tareas <small class="text-muted">/ ${row.columnas} columnas</small>`
            },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: function (row) {
                    // SVGs coherentes con el módulo de usuarios y roles
                    const svgEditar = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>`;
                    const svgDesactivar = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>`;
                    const svgRestaurar = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>`;

                    let botones = '';

                    if (row.activo == 1) {
                        botones = `
                            <button type="button" class="btn btn-warning text-white btn-editar rounded-circle shadow-sm" data-id="${row.id}" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Editar">
                                ${svgEditar}
                            </button>
                            <button type="button" class="btn btn-danger btn-eliminar rounded-circle shadow-sm" data-id="${row.id}" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Desactivar">
                                ${svgDesactivar}
                            </button>
                        `;
                    } else {
                        botones = `
                            <button type="button" class="btn btn-success btn-restaurar rounded-circle shadow-sm" data-id="${row.id}" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Restaurar">
                                ${svgRestaurar}
                            </button>
                        `;
                    }

                    return `<div class="d-flex align-items-center justify-content-center gap-2">${botones}</div>`;
                }
            }
        ],
        language: {
            paginate: {
                next: '<i class="fa fa-angle-double-right"></i>',
                previous: '<i class="fa fa-angle-double-left" aria-hidden="true"></i>'
            },
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron proyectos",
            emptyTable: "No hay proyectos registrados",
            loadingRecords: "Cargando...",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)"
        }
    });

    // Control de pestañas por estado
    $('#pills-tab-proyectos .tab-estado').on('click', function () {
        $('#pills-tab-proyectos .tab-estado').removeClass('active');
        $(this).addClass('active');
        estadoFiltro = $(this).data('estado');
        tabla.ajax.reload();
    });

    // ==========================================
    // FUNCIONES AUXILIARES DE FORMULARIO Y EQUIPO
    // ==========================================
    function limpiarFormulario() {
        if (form) form.reset();
        $('#proyecto_id').val('');
        lideresAgregados = [];
        miembrosAgregados = [];
        renderDisponibles();
        renderEquipo();
    }

    function renderDisponibles() {
        const liderGlobal = $('#proyecto_lider').val();
        const lideres = $('#proyecto_lideres_disponibles').empty();
        const miembros = $('#proyecto_miembros_disponibles').empty();

        lideresDisponibles.forEach((texto, username) => {
            if (username !== liderGlobal && !lideresAgregados.includes(username)) {
                lideres.append(new Option(texto, username));
            }
        });

        miembrosDisponibles.forEach((texto, username) => {
            if (username !== liderGlobal && !miembrosAgregados.includes(username) && !lideresAgregados.includes(username)) {
                miembros.append(new Option(texto, username));
            }
        });
    }

    function renderEquipo() {
        const lideres = $('#proyecto_lideres_agregados').empty();
        const miembros = $('#proyecto_miembros_agregados').empty();
        const svgQuitar = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>`;

        lideresAgregados.forEach(username => {
            lideres.append(`
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <span>${escapeHtml(lideresDisponibles.get(username))}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-quitar-lider rounded-circle p-0 d-inline-flex align-items-center justify-content-center" data-username="${escapeHtml(username)}" style="width: 26px; height: 26px;" title="Quitar líder adicional">
                        ${svgQuitar}
                    </button>
                    <input type="hidden" name="lideres_adicionales[]" value="${escapeHtml(username)}">
                </div>
            `);
        });

        miembrosAgregados.forEach(username => {
            miembros.append(`
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <span>${escapeHtml(miembrosDisponibles.get(username))}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-quitar-miembro rounded-circle p-0 d-inline-flex align-items-center justify-content-center" data-username="${escapeHtml(username)}" style="width: 26px; height: 26px;" title="Quitar miembro">
                        ${svgQuitar}
                    </button>
                    <input type="hidden" name="miembros_usuarios[]" value="${escapeHtml(username)}">
                </div>
            `);
        });

        if (!lideresAgregados.length) lideres.html('<span class="text-muted small">No hay líderes adicionales agregados.</span>');
        if (!miembrosAgregados.length) miembros.html('<span class="text-muted small">No hay miembros agregados.</span>');
    }

    function agregarDesde(selectSelector, lista) {
        const username = $(selectSelector).val();
        if (!username || lista.includes(username)) return;
        lista.push(username);
        renderDisponibles();
        renderEquipo();
    }

    function quitarDeEquipo(username, lista) {
        const index = lista.indexOf(username);
        if (index === -1) return;
        lista.splice(index, 1);
        renderDisponibles();
        renderEquipo();
    }

    function actualizarMinimoVencimiento() {
        if (!fechaVencimiento) return;
        const ahora = new Date();
        ahora.setSeconds(0, 0);
        const pad = valor => String(valor).padStart(2, '0');
        fechaVencimiento.min = `${ahora.getFullYear()}-${pad(ahora.getMonth() + 1)}-${pad(ahora.getDate())}T${pad(ahora.getHours())}:${pad(ahora.getMinutes())}`;
    }

    // ==========================================
    // EVENTOS DE GESTIÓN DE PROYECTOS Y EQUIPO
    // ==========================================
    $('.btn-nuevo-proyecto').on('click', () => {
        limpiarFormulario();
        actualizarMinimoVencimiento();
        $('#tituloModalProyecto').text('Nuevo proyecto');
        if (modal) modal.show();
    });

    $('#btn-agregar-lider').on('click', () => {
        const liderSeleccionado = $('#proyecto_lideres_disponibles').val();
        if (liderSeleccionado === $('#proyecto_lider').val()) {
            Swal.fire('Líder global', 'El líder global no puede agregarse como líder adicional.', 'info');
            return;
        }
        agregarDesde('#proyecto_lideres_disponibles', lideresAgregados);
    });

    $('#btn-agregar-miembro').on('click', () => agregarDesde('#proyecto_miembros_disponibles', miembrosAgregados));

    $('#proyecto_lider').on('change', function () {
        const liderGlobal = $(this).val();
        miembrosAgregados = miembrosAgregados.filter(username => username !== liderGlobal);
        renderDisponibles();
        renderEquipo();
    });

    $('#proyecto_lideres_agregados').on('click', '.btn-quitar-lider', function () {
        quitarDeEquipo($(this).data('username'), lideresAgregados);
    });

    $('#proyecto_miembros_agregados').on('click', '.btn-quitar-miembro', function () {
        quitarDeEquipo($(this).data('username'), miembrosAgregados);
    });

    // ==========================================
    // ACCIONES DE DATATABLE (EDITAR, RESTAURAR, ELIMINAR)
    // ==========================================
    $('#datatable_proyectos').on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        fetch(`?c=proyectos&a=obtener&id=${id}`)
            .then(response => response.json())
            .then(result => {
                if (!result.success) return Swal.fire('Error', result.message, 'error');
                const data = result.data;

                limpiarFormulario();
                $('#proyecto_id').val(data.id);
                $('#proyecto_titulo').val(data.titulo);
                $('#proyecto_descripcion').val(data.descripcion);
                $('#proyecto_inicio').val(data.fecha_inicio_global.replace(' ', 'T').slice(0, 16));
                $('#proyecto_vencimiento').val(data.fecha_vencimiento_global.replace(' ', 'T').slice(0, 16));
                $('#proyecto_lider').val(data.lider_name);

                lideresAgregados = (data.lideres_adicionales || []).filter(username => username !== data.lider_name && lideresDisponibles.has(username));
                miembrosAgregados = (data.miembros_usuarios || []).filter(username => miembrosDisponibles.has(username) && username !== data.lider_name && !lideresAgregados.includes(username));

                renderDisponibles();
                renderEquipo();
                actualizarMinimoVencimiento();

                $('#tituloModalProyecto').text('Editar proyecto');
                if (modal) modal.show();
            })
            .catch(() => Swal.fire('Error', 'No se pudo cargar la información del proyecto.', 'error'));
    });

    $('#datatable_proyectos').on('click', '.btn-restaurar', function () {
        const id = $(this).data('id');
        const body = new FormData();
        body.append('id', id);

        fetch('?c=proyectos&a=restaurar', { method: 'POST', body })
            .then(response => response.json())
            .then(data => {
                Swal.fire(data.success ? 'Listo' : 'Error', data.message, data.success ? 'success' : 'error');
                if (data.success) tabla.ajax.reload(null, false);
            })
            .catch(() => Swal.fire('Error', 'Error en la comunicación con el servidor.', 'error'));
    });

    $('#datatable_proyectos').on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Desactivar proyecto?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Desactivar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (!result.isConfirmed) return;
            const body = new FormData();
            body.append('id', id);

            fetch('?c=proyectos&a=eliminar', { method: 'POST', body })
                .then(response => response.json())
                .then(data => {
                    Swal.fire(data.success ? 'Listo' : 'Error', data.message, data.success ? 'success' : 'error');
                    if (data.success) tabla.ajax.reload(null, false);
                })
                .catch(() => Swal.fire('Error', 'Error en la comunicación con el servidor.', 'error'));
        });
    });

    // ==========================================
    // ENVÍO DEL FORMULARIO
    // ==========================================
    if (form) {
        form.addEventListener('submit', event => {
            event.preventDefault();

            fetch('?c=proyectos&a=guardar', { method: 'POST', body: new FormData(form) })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        return Swal.fire('No se pudo guardar', data.message || 'Revisa los datos del proyecto.', 'warning');
                    }
                    if (modal) modal.hide();
                    tabla.ajax.reload(null, false);
                    Swal.fire('Guardado', data.message, 'success');
                })
                .catch(() => Swal.fire('Error', 'No fue posible comunicarse con el servidor.', 'error'));
        });
    }

    // ==========================================
    // UTILIDADES
    // ==========================================
    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function formatDate(value) {
        if (!value) return '-';
        return new Date(value.replace(' ', 'T')).toLocaleDateString('es-PE');
    }
});