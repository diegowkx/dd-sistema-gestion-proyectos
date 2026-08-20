$(function () {
    let estadoFiltro = 'ACTIVO';
    let lideresAgregados = [];
    let miembrosAgregados = [];
    const modal = new bootstrap.Modal(document.getElementById('modalProyecto'));
    const form = document.getElementById('formProyecto');
    const lideresDisponibles = new Map(Array.from(document.querySelectorAll('#proyecto_lideres_disponibles option')).map(option => [option.value, option.textContent]));
    const miembrosDisponibles = new Map(Array.from(document.querySelectorAll('#proyecto_miembros_disponibles option')).map(option => [option.value, option.textContent]));
    const fechaVencimiento = document.getElementById('proyecto_vencimiento');
    const tabla = $('#datatable_proyectos').DataTable({
        responsive: true,
        ajax: { url: '?c=proyectos&a=listar', data: data => { data.estado = estadoFiltro; }, dataSrc: json => json.success ? json.data : [] },
        columns: [
            { data: null, render: row => `<strong>${escapeHtml(row.titulo)}</strong><br><small class="text-muted">${escapeHtml(row.descripcion || 'Sin descripción')}</small>` },
            { data: 'lider_nombre', render: (data, type, row) => `${escapeHtml(data)}<br><small class="text-muted">${escapeHtml(row.lider_name)}</small>` },
            { data: null, render: row => `${formatDate(row.fecha_inicio_global)}<br><small class="text-muted">hasta ${formatDate(row.fecha_vencimiento_global)}</small>` },
            { data: 'miembros', render: data => `<span class="badge bg-light text-dark">${data} miembros</span>` },
            { data: 'tareas', render: (data, type, row) => `${data} tareas <small class="text-muted">/ ${row.columnas} columnas</small>` },
            { data: null, orderable: false, className: 'text-center', render: row => row.activo == 1
                ? `<button class="btn btn-sm btn-outline-primary btn-editar" data-id="${row.id}" title="Editar"><i class="fas fa-pen"></i></button> <button class="btn btn-sm btn-outline-danger btn-eliminar" data-id="${row.id}" title="Desactivar"><i class="fas fa-trash"></i></button>`
                : `<button class="btn btn-sm btn-outline-success btn-restaurar" data-id="${row.id}" title="Restaurar"><i class="fas fa-rotate-left"></i></button>` }
        ],
        language: { search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros', zeroRecords: 'No se encontraron proyectos', emptyTable: 'No hay proyectos registrados', loadingRecords: 'Cargando...', info: 'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty: 'Mostrando 0 a 0 de 0 registros' }
    });

    $('#pills-tab-proyectos .tab-estado').on('click', function () {
        $('#pills-tab-proyectos .tab-estado').removeClass('active');
        $(this).addClass('active');
        estadoFiltro = $(this).data('estado');
        tabla.ajax.reload();
    });

    function limpiarFormulario() {
        form.reset();
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
        lideresAgregados.forEach(username => {
            lideres.append(`<div class="d-flex justify-content-between align-items-center border-bottom py-2"><span>${escapeHtml(lideresDisponibles.get(username))}</span><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-lider" data-username="${escapeHtml(username)}" title="Quitar líder adicional"><i class="fas fa-times"></i></button><input type="hidden" name="lideres_adicionales[]" value="${escapeHtml(username)}"></div>`);
        });
        miembrosAgregados.forEach(username => {
            miembros.append(`<div class="d-flex justify-content-between align-items-center border-bottom py-2"><span>${escapeHtml(miembrosDisponibles.get(username))}</span><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-miembro" data-username="${escapeHtml(username)}" title="Quitar miembro"><i class="fas fa-times"></i></button><input type="hidden" name="miembros_usuarios[]" value="${escapeHtml(username)}"></div>`);
        });
        if (!lideresAgregados.length) lideres.html('<span class="text-muted small">No hay líderes adicionales agregados.</span>');
        if (!miembrosAgregados.length) miembros.html('<span class="text-muted small">No hay miembros agregados.</span>');
    }

    function agregarDesde(selectSelector, lista, opciones) {
        const username = $(selectSelector).val();
        if (!username || lista.includes(username)) return;
        lista.push(username);
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

    function quitarDeEquipo(username, lista, selector, opciones) {
        const index = lista.indexOf(username);
        if (index === -1) return;
        lista.splice(index, 1);
        renderDisponibles();
        renderEquipo();
    }

    $('.btn-nuevo-proyecto').on('click', () => { limpiarFormulario(); actualizarMinimoVencimiento(); $('#tituloModalProyecto').text('Nuevo proyecto'); modal.show(); });
    $('#btn-agregar-lider').on('click', () => {
        const liderSeleccionado = $('#proyecto_lideres_disponibles').val();
        if (liderSeleccionado === $('#proyecto_lider').val()) {
            Swal.fire('Líder global', 'El líder global no puede agregarse como líder adicional.', 'info');
            return;
        }
        agregarDesde('#proyecto_lideres_disponibles', lideresAgregados, lideresDisponibles);
    });
    $('#btn-agregar-miembro').on('click', () => agregarDesde('#proyecto_miembros_disponibles', miembrosAgregados, miembrosDisponibles));
    $('#proyecto_lider').on('change', function () {
        const liderGlobal = $(this).val();
        miembrosAgregados = miembrosAgregados.filter(username => username !== liderGlobal);
        renderDisponibles();
        renderEquipo();
    });
    $('#proyecto_lideres_agregados').on('click', '.btn-quitar-lider', function () { quitarDeEquipo($(this).data('username'), lideresAgregados, '#proyecto_lideres_disponibles', lideresDisponibles); });
    $('#proyecto_miembros_agregados').on('click', '.btn-quitar-miembro', function () { quitarDeEquipo($(this).data('username'), miembrosAgregados, '#proyecto_miembros_disponibles', miembrosDisponibles); });
    $('#datatable_proyectos').on('click', '.btn-editar', function () {
        fetch(`?c=proyectos&a=obtener&id=${$(this).data('id')}`).then(response => response.json()).then(result => {
            if (!result.success) return Swal.fire('Error', result.message, 'error');
            const data = result.data;
            limpiarFormulario();
            $('#proyecto_id').val(data.id); $('#proyecto_titulo').val(data.titulo); $('#proyecto_descripcion').val(data.descripcion);
            $('#proyecto_inicio').val(data.fecha_inicio_global.replace(' ', 'T').slice(0, 16)); $('#proyecto_vencimiento').val(data.fecha_vencimiento_global.replace(' ', 'T').slice(0, 16)); $('#proyecto_lider').val(data.lider_name);
            lideresAgregados = (data.lideres_adicionales || []).filter(username => username !== data.lider_name && lideresDisponibles.has(username));
            miembrosAgregados = (data.miembros_usuarios || []).filter(username => miembrosDisponibles.has(username) && username !== data.lider_name && !lideresAgregados.includes(username));
            renderDisponibles();
            renderEquipo();
            actualizarMinimoVencimiento();
            $('#tituloModalProyecto').text('Editar proyecto'); modal.show();
        });
    });
    $('#datatable_proyectos').on('click', '.btn-restaurar', function () {
        const body = new FormData(); body.append('id', $(this).data('id'));
        fetch('?c=proyectos&a=restaurar', { method: 'POST', body }).then(response => response.json()).then(data => { Swal.fire(data.success ? 'Listo' : 'Error', data.message, data.success ? 'success' : 'error'); if (data.success) tabla.ajax.reload(null, false); });
    });
    $('#datatable_proyectos').on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        Swal.fire({ title: '¿Desactivar proyecto?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Desactivar', cancelButtonText: 'Cancelar' }).then(result => {
            if (!result.isConfirmed) return;
            const body = new FormData(); body.append('id', id);
            fetch('?c=proyectos&a=eliminar', { method: 'POST', body }).then(response => response.json()).then(data => { Swal.fire(data.success ? 'Listo' : 'Error', data.message, data.success ? 'success' : 'error'); if (data.success) tabla.ajax.reload(null, false); });
        });
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        fetch('?c=proyectos&a=guardar', { method: 'POST', body: new FormData(form) })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    return Swal.fire('No se pudo guardar', data.message || 'Revisa los datos del proyecto.', 'warning');
                }
                modal.hide();
                tabla.ajax.reload(null, false);
                Swal.fire('Guardado', data.message, 'success');
            })
            .catch(() => Swal.fire('Error', 'No fue posible comunicarse con el servidor.', 'error'));
    });

    function escapeHtml(value) { return $('<div>').text(value ?? '').html(); }
    function formatDate(value) { return new Date(value.replace(' ', 'T')).toLocaleDateString('es-PE'); }
});
