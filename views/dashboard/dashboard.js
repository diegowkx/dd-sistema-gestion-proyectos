$(function () {
    const modalElement = document.getElementById('modalIpDetalle');
    const modalIp = modalElement ? new bootstrap.Modal(modalElement) : null;

    if ($('#datatable_ips').length) {
        $('#datatable_ips').DataTable({
            responsive: true,
            ajax: { url: '?c=dashboard&a=listarIps', dataSrc: json => json.data || [] },
            columns: [
                { data: 'usuario_username' }, { data: 'usuario_nombre_completo' }, { data: 'rol_name' }, { data: 'ip_address' }, { data: 'fecha_acceso' },
                { data: null, orderable: false, className: 'text-center', render: row => `<button class="btn btn-sm btn-outline-primary btn-ver-ip" data-id="${row.id}" title="Ver detalle"><i class="fas fa-eye"></i></button>` }
            ],
            language: { search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros', zeroRecords: 'No se encontraron resultados', emptyTable: 'Ningún dato disponible', loadingRecords: 'Cargando...' }
        });
    }

    $(document).on('click', '.btn-ver-ip', function () {
        fetch(`?c=dashboard&a=getIpById&id=${$(this).data('id')}`).then(response => response.json()).then(result => {
            if (!result.success) return;
            const data = result.data;
            Object.entries({ det_ip_id: `#${data.id}`, det_ip_usuario: data.usuario_username, det_ip_nombre: data.usuario_nombre_completo, det_ip_correo: data.correo || '-', det_ip_rol: data.rol_name, det_ip_address: data.ip_address, det_ip_fecha: data.fecha_acceso }).forEach(([id, value]) => { const element = document.getElementById(id); if (element) element.innerText = value; });
            if (modalIp) modalIp.show();
        });
    });

    const pintarLista = (id, values, labels, colors) => {
        const container = document.getElementById(id);
        if (!container) return;
        const total = Object.values(values).reduce((sum, value) => sum + Number(value), 0);
        container.innerHTML = Object.entries(labels).map(([key, label]) => { const amount = Number(values[key] || 0); const percent = total ? Math.round(amount * 100 / total) : 0; return `<div class="mb-3"><div class="d-flex justify-content-between extra-small"><span>${label}</span><strong>${amount}</strong></div><div class="progress" style="height:8px"><div class="progress-bar ${colors[key]}" style="width:${percent}%"></div></div></div>`; }).join('');
    };

    function establecerRangoAnual() {
        const year = new Date().getFullYear();
        $('#fecha_desde').val(`${year}-01-01`);
        $('#fecha_hasta').val(`${year}-12-31`);
    }

    function cargarResumen() {
        const params = new URLSearchParams({ c: 'dashboard', a: 'resumen' });
        ['fecha_desde', 'fecha_hasta'].forEach(id => { const value = document.getElementById(id)?.value; if (value) params.set(id, value); });
        fetch(`?${params}`).then(response => response.json()).then(result => {
            if (!result.success) return;
            const summary = result.data.resumen;
            [['kpi_proyectos_totales', summary.proyectos_totales], ['kpi_proyectos_vigentes', summary.proyectos_vigentes], ['kpi_tareas_totales', summary.tareas_totales], ['kpi_tareas_completadas', summary.tareas_completadas]].forEach(([id, value]) => { const element = document.getElementById(id); if (element) element.innerText = value; });
            pintarLista('lista-publicacion', result.data.publicacion, { VIGENTES: 'Vigentes', VENCIDOS: 'Vencidos' }, { VIGENTES: 'bg-success', VENCIDOS: 'bg-danger' });
            pintarLista('lista-asistencia', { COMPLETADAS: summary.tareas_completadas, PENDIENTES: summary.tareas_totales - summary.tareas_completadas }, { COMPLETADAS: 'Completadas', PENDIENTES: 'Pendientes' }, { COMPLETADAS: 'bg-success', PENDIENTES: 'bg-warning' });
            pintarLista('lista-certificaciones', { ACTIVOS: summary.usuarios_activos }, { ACTIVOS: 'Usuarios activos' }, { ACTIVOS: 'bg-primary' });
        });
    }

    $('#filtro-dashboard').on('submit', event => { event.preventDefault(); cargarResumen(); });
    $('#limpiar-filtro-dashboard').on('click', () => { establecerRangoAnual(); cargarResumen(); });
    establecerRangoAnual();
    cargarResumen();
});
