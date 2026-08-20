$(document).ready(function () {
    const modalElement = document.getElementById('modalIpDetalle');
    const modalIp = modalElement ? new bootstrap.Modal(modalElement) : null;

    // ==========================================
    // DATATABLE AUDITORÍA DE IPS
    // ==========================================
    if ($('#datatable_ips').length) {
        $('#datatable_ips').DataTable({
            autoWidth: false,
            serverSide: false,
            responsive: true,
            bDestroy: true,
            ajax: {
                url: '?c=dashboard&a=listarIps',
                dataSrc: function (json) {
                    if (!json || !json.data || !Array.isArray(json.data)) return [];
                    return json.data;
                }
            },
            columns: [
                { data: 'usuario_username' },
                { data: 'usuario_nombre_completo' },
                { data: 'rol_name' },
                { data: 'ip_address' },
                { data: 'fecha_acceso' },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (row) {
                        // SVG idéntico al patrón del módulo de usuarios
                        const svgVerDetalle = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>`;

                        const botonDetalle = `
                           <button type="button" class="btn btn-dark text-white btn-ver-ip rounded-circle shadow-sm" data-id="${row.id}" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Ver detalle">
                                ${svgVerDetalle}
                            </button>
                        `;

                        return `<div class="d-flex align-items-center justify-content-center gap-2">${botonDetalle}</div>`;
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
                zeroRecords: "No se encontraron resultados",
                emptyTable: "Ningún dato disponible",
                loadingRecords: "Cargando...",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)"
            }
        });
    }

    // ==========================================
    // EVENTO VER DETALLE IP (DELEGADO)
    // ==========================================
    $(document).on('click', '.btn-ver-ip', function () {
        const id = $(this).data('id');

        fetch(`?c=dashboard&a=getIpById&id=${id}`)
            .then(res => res.json())
            .then(result => {
                if (!result.success) return;
                const data = result.data;

                const mapping = {
                    det_ip_id: `#${data.id}`,
                    det_ip_usuario: data.usuario_username,
                    det_ip_nombre: data.usuario_nombre_completo,
                    det_ip_correo: data.correo || '-',
                    det_ip_rol: data.rol_name,
                    det_ip_address: data.ip_address,
                    det_ip_fecha: data.fecha_acceso
                };

                Object.entries(mapping).forEach(([elementId, value]) => {
                    const element = document.getElementById(elementId);
                    if (element) element.innerText = value;
                });

                if (modalIp) modalIp.show();
            })
            .catch(() => Swal.fire('Error', 'Error al cargar el detalle de la IP', 'error'));
    });

    // ==========================================
    // RENDERIZADO DE BARRAS DE PROGRESO
    // ==========================================
    const pintarLista = (id, values, labels, colors) => {
        const container = document.getElementById(id);
        if (!container) return;

        const total = Object.values(values).reduce((sum, value) => sum + Number(value), 0);

        container.innerHTML = Object.entries(labels).map(([key, label]) => {
            const amount = Number(values[key] || 0);
            const percent = total ? Math.round((amount * 100) / total) : 0;

            return `
                <div class="mb-3">
                    <div class="d-flex justify-content-between extra-small">
                        <span>${label}</span>
                        <strong>${amount}</strong>
                    </div>
                    <div class="progress" style="height:8px">
                        <div class="progress-bar ${colors[key]}" style="width:${percent}%"></div>
                    </div>
                </div>`;
        }).join('');
    };

    function establecerRangoAnual() {
        const year = new Date().getFullYear();
        $('#fecha_desde').val(`${year}-01-01`);
        $('#fecha_hasta').val(`${year}-12-31`);
    }

    function cargarResumen() {
        const params = new URLSearchParams({ c: 'dashboard', a: 'resumen' });

        ['fecha_desde', 'fecha_hasta'].forEach(id => {
            const value = document.getElementById(id)?.value;
            if (value) params.set(id, value);
        });

        fetch(`?${params}`)
            .then(res => res.json())
            .then(result => {
                if (!result.success) return;

                const summary = result.data.resumen;
                const kpis = [
                    ['kpi_proyectos_totales', summary.proyectos_totales],
                    ['kpi_proyectos_vigentes', summary.proyectos_vigentes],
                    ['kpi_tareas_totales', summary.tareas_totales],
                    ['kpi_tareas_completadas', summary.tareas_completadas]
                ];

                kpis.forEach(([id, value]) => {
                    const element = document.getElementById(id);
                    if (element) element.innerText = value;
                });

                pintarLista(
                    'lista-publicacion',
                    result.data.publicacion,
                    { VIGENTES: 'Vigentes', VENCIDOS: 'Vencidos' },
                    { VIGENTES: 'bg-success', VENCIDOS: 'bg-danger' }
                );

                pintarLista(
                    'lista-asistencia',
                    {
                        COMPLETADAS: summary.tareas_completadas,
                        PENDIENTES: summary.tareas_totales - summary.tareas_completadas
                    },
                    { COMPLETADAS: 'Completadas', PENDIENTES: 'Pendientes' },
                    { COMPLETADAS: 'bg-success', PENDIENTES: 'bg-warning' }
                );

                pintarLista(
                    'lista-certificaciones',
                    { ACTIVOS: summary.usuarios_activos },
                    { ACTIVOS: 'Usuarios activos' },
                    { ACTIVOS: 'bg-primary' }
                );
            });
    }

    // ==========================================
    // EVENTOS DE FILTRADO
    // ==========================================
    $('#filtro-dashboard').on('submit', function (e) {
        e.preventDefault();
        cargarResumen();
    });

    $('#limpiar-filtro-dashboard').on('click', function () {
        establecerRangoAnual();
        cargarResumen();
    });

    // Carga inicial
    establecerRangoAnual();
    cargarResumen();
});