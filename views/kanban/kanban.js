$(function () {
    const proyectoSelect = document.getElementById('kanban_proyecto');
    const board = document.getElementById('kanban_board');
    const empty = document.getElementById('kanban_empty');
    const btnColumna = document.getElementById('btn-nueva-columna');
    const btnTarea = document.getElementById('btn-nueva-tarea');
    const modalColumna = new bootstrap.Modal(document.getElementById('modalColumna'));
    const modalTarea = new bootstrap.Modal(document.getElementById('modalTarea'));
    const formColumna = document.getElementById('formColumna');
    const formTarea = document.getElementById('formTarea');
    const modalEntregas = new bootstrap.Modal(document.getElementById('modalEntregas'));
    const contenidoEntregas = document.getElementById('contenidoEntregas');
    let tablero = { columnas: [], miembros: [] };
    let sortables = [];
    let sortableColumnas = null;

    proyectoSelect.addEventListener('change', cargarTablero);
    btnColumna.addEventListener('click', () => { formColumna.reset(); $('#tituloModalColumna').text('Nueva columna'); modalColumna.show(); });
    btnTarea.addEventListener('click', () => abrirModalTarea());

    function cargarTablero() {
        const id = proyectoSelect.value;
        btnColumna.disabled = !id; btnTarea.disabled = !id;
        if (!id) { board.innerHTML = ''; empty.hidden = false; return; }
        fetch(`?c=gestion_kanban&a=datos&proyecto_id=${encodeURIComponent(id)}`).then(response => response.json()).then(result => {
            if (!result.success) return Swal.fire('Acceso denegado', result.message, 'error');
            tablero = { columnas: result.columnas || [], miembros: result.miembros || [] };
            renderBoard();
        }).catch(() => Swal.fire('Error', 'No se pudo cargar el tablero.', 'error'));
    }

    function renderBoard() {
        empty.hidden = true;
        board.innerHTML = tablero.columnas.length ? tablero.columnas.map((columna, indice) => `<article class="kanban-column" data-column-id="${columna.id}"><header class="kanban-column-header"><h2 class="kanban-column-title">${escapeHtml(columna.nombre)}</h2><span class="kanban-column-count">${indice + 1}</span><div class="kanban-column-actions"><button class="btn btn-sm btn-light btn-editar-columna" data-id="${columna.id}" title="Editar columna"><i class="fas fa-pen"></i></button><button class="btn btn-sm btn-light text-danger btn-eliminar-columna" data-id="${columna.id}" title="Eliminar columna"><i class="fas fa-trash"></i></button></div></header><div class="kanban-tasks" data-column-id="${columna.id}">${columna.tareas.map(renderTask).join('')}</div></article>`).join('') : '<div class="kanban-empty"><i class="fas fa-columns"></i><h2>Este proyecto aún no tiene columnas</h2><p>Crea la primera columna para comenzar.</p></div>';
        sortables.forEach(sortable => sortable.destroy()); sortables = [];
        if (sortableColumnas) sortableColumnas.destroy();
        sortableColumnas = new Sortable(board, { animation: 180, handle: '.kanban-column-header', draggable: '.kanban-column', onEnd: () => { actualizarNumerosColumnas(); guardarOrdenColumnas(); } });
        document.querySelectorAll('.kanban-tasks').forEach(container => sortables.push(new Sortable(container, { group: 'kanban-tareas', animation: 180, ghostClass: 'kanban-task-ghost', onEnd: guardarMovimiento })));
    }

    function actualizarNumerosColumnas() {
        board.querySelectorAll('.kanban-column').forEach((columna, indice) => {
            const contador = columna.querySelector('.kanban-column-count');
            if (contador) contador.textContent = indice + 1;
        });
    }

    function renderTask(tarea) {
        const estados = { A_TIEMPO: ['Entregada - a tiempo', 'bg-success'], PENDIENTE: ['No entregada', 'bg-warning'], VENCIDA: ['Sin entregas - vencida', 'bg-danger'], ATRASADA: ['Entregada - atrasada', 'bg-danger'] };
        const estado = estados[tarea.estado] || ['Pendiente', 'bg-secondary'];
        const asignados = (tarea.asignados || []).map(username => `<span class="kanban-assignee">${escapeHtml(username)}</span>`).join('');
        const entregas = (tarea.entregas_detalle || []).map(entrega => `<li><strong>${escapeHtml(entrega.usuario_nombre)}</strong>: ${escapeHtml(entrega.mensaje_entrega)} <small>${escapeHtml(entrega.fecha_entregada)}</small></li>`).join('');
        return `<div class="kanban-task" data-task-id="${tarea.id}"><div class="d-flex justify-content-between gap-2"><div class="kanban-task-title">${escapeHtml(tarea.titulo)}</div><div class="dropdown"><button class="btn btn-sm btn-link text-muted p-0" data-bs-toggle="dropdown" title="Acciones"><i class="fas fa-ellipsis-v"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><button class="dropdown-item btn-editar-tarea" data-id="${tarea.id}"><i class="fas fa-pen me-2"></i>Editar</button></li><li><button class="dropdown-item text-danger btn-eliminar-tarea" data-id="${tarea.id}"><i class="fas fa-trash me-2"></i>Eliminar</button></li></ul></div></div>${tarea.descripcion ? `<div class="kanban-task-description">${escapeHtml(tarea.descripcion)}</div>` : ''}<div class="mb-2"><span class="badge ${estado[1]}">${estado[0]}</span></div><div class="kanban-task-meta"><span>${tarea.fecha_vencimiento ? `<i class="far fa-calendar me-1"></i>${formatDate(tarea.fecha_vencimiento)}` : 'Sin fecha'}</span><span>${tarea.entregas > 0 ? `<button type="button" class="btn btn-sm btn-link p-0 btn-ver-entregas" data-id="${tarea.id}"><i class="fas fa-paperclip me-1"></i>${tarea.entregas} entrega(s)</button>` : 'Sin entregas'}</span></div><div>${asignados}</div>${entregas ? `<button type="button" class="btn btn-sm btn-outline-secondary mt-2 btn-ver-entregas" data-id="${tarea.id}"><i class="fas fa-eye me-1"></i>Ver entregas</button>` : ''}</div>`;
    }

    function abrirModalTarea(tarea = null) {
        if (!tablero.columnas.length) return Swal.fire('Primero crea una columna', 'Necesitas al menos una columna para crear tareas.', 'info');
        formTarea.reset();
        formTarea.elements.id.value = tarea?.id || '';
        const selectColumna = document.getElementById('tarea_columna'); selectColumna.innerHTML = tablero.columnas.map(columna => `<option value="${columna.id}" ${tarea?.columna_id == columna.id ? 'selected' : ''}>${escapeHtml(columna.nombre)}</option>`).join('');
        const selectMiembros = document.getElementById('tarea_asignados'); selectMiembros.innerHTML = tablero.miembros.map(miembro => `<option value="${escapeHtml(miembro.username)}" ${(tarea?.asignados || []).includes(miembro.username) ? 'selected' : ''}>${escapeHtml(miembro.nombre)}</option>`).join('');
        if (tarea) { formTarea.elements.titulo.value = tarea.titulo; formTarea.elements.descripcion.value = tarea.descripcion || ''; formTarea.elements.fecha_vencimiento.value = tarea.fecha_vencimiento ? tarea.fecha_vencimiento.replace(' ', 'T').slice(0, 16) : ''; }
        $('#tituloModalTarea').text(tarea ? 'Editar tarea' : 'Nueva tarea'); modalTarea.show();
    }

    function guardarMovimiento(event) {
        const orden = Array.from(event.to.querySelectorAll('.kanban-task')).map(task => task.dataset.taskId);
        const body = new FormData(); body.append('proyecto_id', proyectoSelect.value); body.append('tarea_id', event.item.dataset.taskId); body.append('columna_id', event.to.dataset.columnId); orden.forEach(id => body.append('orden[]', id));
        enviar('?c=gestion_kanban&a=moverTarea', body, null, false);
    }

    function guardarOrdenColumnas() {
        const body = new FormData(); body.append('proyecto_id', proyectoSelect.value); Array.from(board.querySelectorAll('.kanban-column')).forEach(column => body.append('orden[]', column.dataset.columnId));
        enviar('?c=gestion_kanban&a=ordenarColumnas', body, null, false);
    }

    board.addEventListener('click', event => {
        const editarColumna = event.target.closest('.btn-editar-columna');
        if (editarColumna) { const columna = tablero.columnas.find(item => item.id == editarColumna.dataset.id); formColumna.reset(); formColumna.elements.id.value = columna.id; formColumna.elements.nombre.value = columna.nombre; $('#tituloModalColumna').text('Editar columna'); modalColumna.show(); return; }
        const eliminarColumna = event.target.closest('.btn-eliminar-columna');
        if (eliminarColumna) return confirmar('¿Eliminar esta columna?', () => accion('?c=gestion_kanban&a=eliminarColumna', { id: eliminarColumna.dataset.id }));
        const editarTarea = event.target.closest('.btn-editar-tarea');
        if (editarTarea) return fetch(`?c=gestion_kanban&a=obtenerTarea&proyecto_id=${proyectoSelect.value}&id=${editarTarea.dataset.id}`).then(response => response.json()).then(result => result.success ? abrirModalTarea(result.data) : Swal.fire('Error', result.message, 'error'));
        const eliminarTarea = event.target.closest('.btn-eliminar-tarea');
        if (eliminarTarea) return confirmar('¿Eliminar esta tarea?', () => accion('?c=gestion_kanban&a=eliminarTarea', { id: eliminarTarea.dataset.id }));
        const verEntregas = event.target.closest('.btn-ver-entregas');
        if (verEntregas) {
            const tarea = tablero.columnas.flatMap(columna => columna.tareas).find(item => item.id == verEntregas.dataset.id);
            if (!tarea) return;
            document.getElementById('tituloModalEntregas').textContent = `Entregas: ${tarea.titulo}`;
            contenidoEntregas.innerHTML = tarea.entregas_detalle?.length
                ? `<div class="list-group">${tarea.entregas_detalle.map(entrega => `<div class="list-group-item"><div class="d-flex justify-content-between"><strong>${escapeHtml(entrega.usuario_nombre)}</strong><small>${escapeHtml(entrega.fecha_entregada)}</small></div><p class="mb-0 mt-2">${escapeHtml(entrega.mensaje_entrega)}</p></div>`).join('')}</div>`
                : '<p class="text-muted mb-0">Esta tarea no tiene entregas registradas.</p>';
            modalEntregas.show();
        }
    });

    formColumna.addEventListener('submit', event => { event.preventDefault(); const body = new FormData(formColumna); body.append('proyecto_id', proyectoSelect.value); enviar('?c=gestion_kanban&a=guardarColumna', body, modalColumna); });
    formTarea.addEventListener('submit', event => { event.preventDefault(); const body = new FormData(formTarea); body.append('proyecto_id', proyectoSelect.value); enviar('?c=gestion_kanban&a=guardarTarea', body, modalTarea); });
    function accion(url, valores) { const body = new FormData(); body.append('proyecto_id', proyectoSelect.value); Object.entries(valores).forEach(([key, value]) => body.append(key, value)); enviar(url, body, null); }
    function confirmar(titulo, callback) { Swal.fire({ title: titulo, icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' }).then(result => { if (result.isConfirmed) callback(); }); }
    function enviar(url, body, modal, avisar = true) { fetch(url, { method: 'POST', body }).then(response => response.json()).then(result => { if (!result.success) { if (avisar) Swal.fire('No se pudo guardar', result.message, 'warning'); return; } if (modal) modal.hide(); if (avisar) Swal.fire('Guardado', result.message, 'success'); cargarTablero(); }).catch(() => Swal.fire('Error', 'No se pudo contactar con el servidor.', 'error')); }
    function escapeHtml(value) { return $('<div>').text(value ?? '').html(); }
    function formatDate(value) { return new Date(value.replace(' ', 'T')).toLocaleDateString('es-PE'); }
});
