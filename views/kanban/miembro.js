$(function () {
    const proyectoSelect = document.getElementById('kanban_proyecto');
    const board = document.getElementById('kanban_board');
    const empty = document.getElementById('kanban_empty');
    const miembrosPanel = document.getElementById('kanban_miembros');
    const modalEntregas = new bootstrap.Modal(document.getElementById('modalEntregas'));
    const modalEntrega = new bootstrap.Modal(document.getElementById('modalEntrega'));
    const listaEntregas = document.getElementById('listaEntregas');
    const btnNuevaEntrega = document.getElementById('btnNuevaEntrega');
    const form = document.getElementById('formEntrega');
    const modalEntregasElement = document.getElementById('modalEntregas');
    const modalEntregaElement = document.getElementById('modalEntrega');
    let tablero = [];
    let miembros = [];
    let tareaActual = null;

    [modalEntregasElement, modalEntregaElement].forEach(modal => modal.addEventListener('hide.bs.modal', () => {
        if (modal.contains(document.activeElement)) document.activeElement.blur();
    }));

    proyectoSelect.addEventListener('change', () => {
        if (proyectoSelect.value) localStorage.setItem('kanban_proyecto_id', proyectoSelect.value);
        else localStorage.removeItem('kanban_proyecto_id');
        cargarTablero();
    });
    restaurarProyecto();

    function cargarTablero() {
        const id = proyectoSelect.value;
        if (!id) { board.innerHTML = ''; empty.hidden = false; miembrosPanel.classList.add('d-none'); return; }
        return fetch(`?c=kanban&a=datosMiembro&proyecto_id=${encodeURIComponent(id)}`).then(response => response.json()).then(result => {
            if (!result.success) return Swal.fire('Acceso denegado', result.message, 'error');
            tablero = result.columnas || [];
            miembros = result.miembros || [];
            miembrosPanel.innerHTML = `<strong><i class="fas fa-users me-1"></i>Equipo:</strong> ${miembros.length ? miembros.map(miembro => `<span class="kanban-assignee">${escapeHtml(miembro.nombre)} <small>(${escapeHtml(miembro.tipo)})</small></span>`).join('') : '<span class="text-muted">Sin miembros registrados.</span>'}`;
            miembrosPanel.classList.remove('d-none');
            renderBoard();
        }).catch(() => Swal.fire('Error', 'No se pudo cargar tu tablero.', 'error'));
    }

    function restaurarProyecto() {
        const proyectoGuardado = localStorage.getItem('kanban_proyecto_id');
        if (proyectoGuardado && Array.from(proyectoSelect.options).some(option => option.value === proyectoGuardado)) {
            proyectoSelect.value = proyectoGuardado;
            cargarTablero();
        }
    }

    function renderBoard() {
        empty.hidden = true;
        board.innerHTML = tablero.length ? tablero.map((columna, indice) => `<article class="kanban-column"><header class="kanban-column-header"><h2 class="kanban-column-title">${escapeHtml(columna.nombre)}</h2><span class="kanban-column-count">${indice + 1}</span></header><div class="kanban-tasks">${columna.tareas.map(renderTask).join('')}</div></article>`).join('') : '<div class="kanban-empty"><i class="fas fa-tasks"></i><h2>No tienes tareas asignadas</h2><p>Cuando te asignen una tarea aparecerá aquí.</p></div>';
    }

    function renderTask(tarea) {
        const estados = { A_TIEMPO: ['Entregada - a tiempo', 'bg-success'], PENDIENTE: ['No entregada', 'bg-warning'], VENCIDA: ['Sin entregas - vencida', 'bg-danger'], ATRASADA: ['Entregada - atrasada', 'bg-danger'] };
        const estado = estados[tarea.estado] || ['Pendiente', 'bg-secondary'];
        const entregas = tarea.entregas_detalle || [];
        const propia = tarea.mi_entrega || null;
        return `<div class="kanban-task"><div class="kanban-task-title">${escapeHtml(tarea.titulo)}</div>${tarea.descripcion ? `<div class="kanban-task-description">${escapeHtml(tarea.descripcion)}</div>` : ''}<span class="badge ${estado[1]} mb-2">${estado[0]}</span><div class="kanban-task-meta"><span>${tarea.fecha_vencimiento ? `<i class="far fa-calendar me-1"></i>${formatDate(tarea.fecha_vencimiento)}` : 'Sin fecha'}</span><span>${entregas.length} entrega(s)</span></div><button class="btn btn-sm btn-luxury-primary mt-2 btn-ver-entregas" data-id="${tarea.id}"><i class="fas fa-paper-plane me-1"></i>Entregas</button></div>`;
    }

    board.addEventListener('click', event => {
        const ver = event.target.closest('.btn-ver-entregas');
        if (ver) {
            tareaActual = tablero.flatMap(columna => columna.tareas).find(item => item.id == ver.dataset.id);
            abrirEntregas(tareaActual);
        }
    });

    btnNuevaEntrega.addEventListener('click', () => abrirFormularioEntrega());

    function abrirEntregas(tarea) {
        if (!tarea) return;
        tareaActual = tarea;
        document.getElementById('tituloModalEntregas').textContent = `Entregas: ${tarea.titulo}`;
        renderEntregas(tarea);
        modalEntregas.show();
    }

    function renderEntregas(tarea) {
        listaEntregas.innerHTML = tarea.entregas_detalle?.length ? tarea.entregas_detalle.map(entrega => `<div class="col-12"><article class="card h-100"><div class="card-body"><div class="d-flex justify-content-between"><strong>${escapeHtml(entrega.usuario_nombre)}</strong><small>${escapeHtml(entrega.fecha_entregada)}</small></div><p class="mb-2 mt-2">${escapeHtml(entrega.mensaje_entrega)}</p>${tarea.mi_entrega?.id == entrega.id ? `<div class="text-end"><button class="btn btn-sm btn-outline-primary btn-editar-entrega" data-id="${entrega.id}"><i class="fas fa-pen me-1"></i>Editar</button><button class="btn btn-sm btn-outline-danger ms-1 btn-eliminar-entrega" data-id="${entrega.id}"><i class="fas fa-trash me-1"></i>Eliminar</button></div>` : ''}</div></article></div>`).join('') : '<div class="col-12"><p class="text-muted mb-0">No hay entregas registradas.</p></div>';
    }

    function actualizarEntregas() {
        return cargarTablero().then(() => {
            tareaActual = tablero.flatMap(columna => columna.tareas).find(tarea => tarea.id == tareaActual?.id);
            if (tareaActual) renderEntregas(tareaActual);
        });
    }

    function abrirFormularioEntrega(entrega = null) {
        form.reset(); form.elements.id.value = entrega?.id || ''; form.elements.tarea_id.value = tareaActual.id; form.elements.mensaje_entrega.value = entrega?.mensaje_entrega || '';
        document.getElementById('tituloModalEntrega').textContent = entrega ? 'Editar mi entrega' : 'Registrar entrega'; modalEntrega.show();
    }

    listaEntregas.addEventListener('click', event => {
        const editar = event.target.closest('.btn-editar-entrega');
        if (editar) { const entrega = tareaActual.entregas_detalle.find(item => item.id == editar.dataset.id); if (entrega) abrirFormularioEntrega(entrega); }
        const eliminar = event.target.closest('.btn-eliminar-entrega');
        if (eliminar) Swal.fire({ title: '¿Eliminar tu entrega?', text: 'Esta acción es permanente y quitará el avance registrado.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' }).then(result => { if (!result.isConfirmed) return; const body = new FormData(); body.append('id', eliminar.dataset.id); body.append('proyecto_id', proyectoSelect.value); fetch('?c=kanban&a=eliminarEntrega', { method: 'POST', body }).then(response => response.json()).then(data => { if (!data.success) return Swal.fire('Error', data.message, 'error'); return actualizarEntregas().then(() => Swal.fire('Eliminada', data.message, 'success')); }); });
    });

    form.addEventListener('submit', event => { event.preventDefault(); const body = new FormData(form); body.append('proyecto_id', proyectoSelect.value); fetch('?c=kanban&a=guardarEntrega', { method: 'POST', body }).then(response => response.json()).then(result => { if (!result.success) return Swal.fire('No se pudo guardar', result.message, 'warning'); modalEntrega.hide(); return actualizarEntregas().then(() => Swal.fire('Guardado', result.message, 'success')); }); });
    function escapeHtml(value) { return $('<div>').text(value ?? '').html(); }
    function formatDate(value) { return new Date(value.replace(' ', 'T')).toLocaleDateString('es-PE'); }
});
