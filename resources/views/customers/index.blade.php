@extends('layout.app')

@section('content')
<div class="row mb-3">
    <div class="col-md-8">
        <h2>Clientes</h2>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-success" onclick="abrirModal()">
            <i class="bi bi-plus-circle"></i> Nuevo Cliente 
        </button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="input-group mb-3">
            <input type="text" id="buscarInput" class="form-control" placeholder="Buscar por nombre o email...">
            <button class="btn btn-primary" onclick="cargarClientes()">Buscar</button>
        </div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaClientes">
                <!-- Se llena con JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCliente" onsubmit="guardarCliente(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="clienteModalLabel">Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="clienteId">
                    
                    <div class="mb-3">
                        <label for="fullName" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="fullName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="phone">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isActive" checked>
                        <label class="form-check-label" for="isActive">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Configuración básica
    const apiUrl = '/api/customers';
    let modal;

    // Inicializar cuando la página carga
    document.addEventListener('DOMContentLoaded', function() {
        modal = new bootstrap.Modal(document.getElementById('clienteModal'));
        cargarClientes();
    });

    // Cargar lista de clientes
    async function cargarClientes() {
        const search = document.getElementById('buscarInput').value;
        let url = apiUrl;
        if (search) {
            url += '?search=' + encodeURIComponent(search);
        }

        try {
            const respuesta = await fetch(url);
            const clientes = await respuesta.json();
            
            const tbody = document.getElementById('tablaClientes');
            tbody.innerHTML = '';

            clientes.forEach(c => {
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td>${c.id}</td>
                    <td>${c.fullName}</td>
                    <td>${c.email}</td>
                    <td>${c.phone || '-'}</td>
                    <td>
                        <span class="badge bg-${c.isActive ? 'success' : 'secondary'}">
                            ${c.isActive ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editarCliente(${c.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarCliente(${c.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(fila);
            });
        } catch (error) {
            alert('Error al cargar los clientes');
            console.error(error);
        }
    }

    // Abrir modal para nuevo cliente
    function abrirModal() {
        document.getElementById('formCliente').reset();
        document.getElementById('clienteId').value = '';
        document.getElementById('clienteModalLabel').innerText = 'Nuevo Cliente';
        modal.show();
    }

    // Editar cliente existente
    async function editarCliente(id) {
        try {
            const respuesta = await fetch(`${apiUrl}/${id}`);
            if (!respuesta.ok) throw new Error('No encontrado');
            const data = await respuesta.json();

            document.getElementById('clienteId').value = data.id;
            document.getElementById('fullName').value = data.fullName;
            document.getElementById('email').value = data.email;
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('isActive').checked = data.isActive;

            document.getElementById('clienteModalLabel').innerText = 'Editar Cliente';
            modal.show();
        } catch (error) {
            alert('Error al obtener los datos del cliente');
        }
    }

    // Guardar (crear o actualizar)
    async function guardarCliente(e) {
        e.preventDefault();
        
        const id = document.getElementById('clienteId').value;
        const datos = {
            fullName: document.getElementById('fullName').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            isActive: document.getElementById('isActive').checked ? 1 : 0
        };

        const metodo = id ? 'PUT' : 'POST';
        const url = id ? `${apiUrl}/${id}` : apiUrl;

        try {
            const respuesta = await fetch(url, {
                method: metodo,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });

            if (!respuesta.ok) {
                const errorData = await respuesta.json();
                let mensaje = errorData.message || 'Error de validación';
                if (errorData.errors) {
                    mensaje = Object.values(errorData.errors).flat().join('\n');
                }
                alert(mensaje);
                return;
            }

            modal.hide();
            alert('Cliente guardado correctamente');
            cargarClientes();
        } catch (error) {
            alert('Error al guardar el cliente');
        }
    }

    // Eliminar cliente
    async function eliminarCliente(id) {
        if (!confirm('¿Estás seguro de eliminar este cliente?')) return;

        try {
            const respuesta = await fetch(`${apiUrl}/${id}`, { method: 'DELETE' });
            if (!respuesta.ok) throw new Error('Error al eliminar');
            
            alert('Cliente eliminado');
            cargarClientes();
        } catch (error) {
            alert('No se pudo eliminar (quizás tiene órdenes asociadas)');
        }
    }
</script>
@endpush