@extends('layout.app')

@section('content')
<div class="row mb-3">
    <div class="col-md-8">
        <h2>Orders</h2>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-success" id="addOrderBtn">
            <i class="bi bi-plus-circle"></i> Add Order
        </button>
    </div>
</div>

{{-- Alerta flotante para mensajes --}}
<div id="liveAlert" class="alert alert-dismissible fade position-fixed top-0 end-0 m-3" style="z-index: 9999; display: none;" role="alert">
    <span id="alertMessage"></span>
    <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'"></button>
</div>

<div class="card mb-4">
    <div class="card-body">
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search by order number...">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order #</th>
                        <th>Customer Name</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <tr id="loadingRow">
                        <td colspan="6" class="text-center">Loading orders...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="orderForm">
                @csrf {{-- Aunque no se usa directamente en JS, lo dejamos por claridad --}}
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel">Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="orderId" name="id">
                    
                    <div class="mb-3">
                        <label for="customerId" class="form-label">Customer *</label>
                        <select class="form-select" id="customerId" name="customerId" required>
                            <option value="">Select a customer...</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="orderNumber" class="form-label">Order Number *</label>
                        <input type="text" class="form-control" id="orderNumber" name="orderNumber" required>
                        <div class="form-text">Will be auto-generated if left empty</div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="CREATED">CREATED</option>
                            <option value="PAID">PAID</option>
                            <option value="CANCELLED">CANCELLED</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="totalAmount" class="form-label">Total Amount *</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="totalAmount" name="totalAmount" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function() {
        // Configuración
        const apiUrl = '/api/orders';
        const customersApiUrl = '/api/customers';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Elementos del DOM
        const ordersTableBody = document.getElementById('ordersTableBody');
        const searchInput = document.getElementById('searchInput');
        const addOrderBtn = document.getElementById('addOrderBtn');
        const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
        const orderForm = document.getElementById('orderForm');
        const customerSelect = document.getElementById('customerId');
        const alertBox = document.getElementById('liveAlert');
        const alertMessage = document.getElementById('alertMessage');

        let searchTimeout;
        const currencyFormatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

        // Inicialización
        document.addEventListener('DOMContentLoaded', () => {
            loadCustomersDropdown();
            loadOrders();
            setupEventListeners();
        });

        function setupEventListeners() {
            searchInput.addEventListener('input', debounceSearch);
            addOrderBtn.addEventListener('click', openModal);
            orderForm.addEventListener('submit', saveOrder);
            document.getElementById('orderModal').addEventListener('hidden.bs.modal', () => {
                orderForm.reset();
                document.getElementById('orderId').value = '';
            });
        }

        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadOrders();
            }, 300);
        }

        async function loadCustomersDropdown() {
            try {
                const response = await fetch(customersApiUrl, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Error al cargar clientes');
                const data = await response.json();
                
                // Limpiar opciones previas
                customerSelect.innerHTML = '<option value="">Select a customer...</option>';
                
                data.forEach(c => {
                    if (c.isActive) {
                        const option = document.createElement('option');
                        option.value = c.id;
                        option.textContent = `${c.fullName} (${c.email})`;
                        customerSelect.appendChild(option);
                    }
                });
            } catch (error) {
                showAlert('Failed to load customers for dropdown.', 'warning');
            }
        }

        async function loadOrders() {
            showLoading(true);
            const search = searchInput.value.trim();
            const url = search ? `${apiUrl}?search=${encodeURIComponent(search)}` : apiUrl;
            
            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Error al cargar pedidos');
                const data = await response.json();
                
                renderOrders(data);
            } catch (error) {
                showAlert('Failed to load orders.', 'danger');
                ordersTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading orders</td></tr>';
            } finally {
                showLoading(false);
            }
        }

        function renderOrders(orders) {
            if (!orders.length) {
                ordersTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No orders found</td></tr>';
                return;
            }

            const statusBadge = {
                'CREATED': 'info',
                'PAID': 'success',
                'CANCELLED': 'danger'
            };

            let html = '';
            orders.forEach(o => {
                html += `<tr>
                    <td>${o.id}</td>
                    <td><strong>${o.orderNumber || 'N/A'}</strong></td>
                    <td>${o.customer ? escapeHtml(o.customer.fullName) : 'N/A'}</td>
                    <td>${currencyFormatter.format(o.totalAmount || 0)}</td>
                    <td>
                        <span class="badge bg-${statusBadge[o.status] || 'secondary'}">
                            ${o.status}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="window.editOrder(${o.id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="window.deleteOrder(${o.id})"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
            });
            ordersTableBody.innerHTML = html;
        }

        // Helper para escapar HTML (seguridad básica)
        function escapeHtml(unsafe) {
            return unsafe.replace(/[&<>"]/g, function(m) {
                if(m === '&') return '&amp;'; if(m === '<') return '&lt;'; if(m === '>') return '&gt;'; if(m === '"') return '&quot;';
                return m;
            });
        }

        function showLoading(show) {
            if (show) {
                ordersTableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading orders...</td></tr>';
            }
        }

        function openModal() {
            document.getElementById('orderModalLabel').innerText = 'Add Order';
            document.getElementById('orderId').value = '';
            // No generamos número aquí, dejamos que el backend lo sugiera o lo autocomplete
            orderModal.show();
        }

        window.editOrder = async function(id) {
            try {
                const response = await fetch(`${apiUrl}/${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Not found');
                const data = await response.json();
                
                document.getElementById('orderId').value = data.id;
                document.getElementById('customerId').value = data.customerId;
                document.getElementById('orderNumber').value = data.orderNumber || '';
                document.getElementById('status').value = data.status;
                document.getElementById('totalAmount').value = data.totalAmount;
                document.getElementById('notes').value = data.notes || '';
                
                document.getElementById('orderModalLabel').innerText = 'Edit Order';
                orderModal.show();
            } catch (error) {
                showAlert('Failed to fetch order data.', 'danger');
            }
        };

        async function saveOrder(e) {
            e.preventDefault();
            const id = document.getElementById('orderId').value;
            
            const payload = {
                customerId: document.getElementById('customerId').value,
                orderNumber: document.getElementById('orderNumber').value || null, // permitir que backend genere
                status: document.getElementById('status').value,
                totalAmount: parseFloat(document.getElementById('totalAmount').value),
                notes: document.getElementById('notes').value || ''
            };

            const method = id ? 'PUT' : 'POST';
            const url = id ? `${apiUrl}/${id}` : apiUrl;

            // Deshabilitar botón para evitar múltiples envíos
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    let errors = errorData.message || 'Validation error';
                    if (errorData.errors) {
                        errors = Object.values(errorData.errors).flat().join('<br>');
                    }
                    showAlert(errors, 'danger');
                    return;
                }

                orderModal.hide();
                showAlert('Order saved successfully!', 'success');
                loadOrders();
            } catch (error) {
                showAlert('Operation failed. Check your connection.', 'danger');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Save';
            }
        }

        window.deleteOrder = async function(id) {
            if (!confirm('Are you sure you want to delete this order?')) return;
            
            try {
                const response = await fetch(`${apiUrl}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                if (!response.ok) throw new Error('Failed to delete');
                
                showAlert('Order deleted successfully!', 'success');
                loadOrders();
            } catch (error) {
                showAlert('Cannot delete order.', 'danger');
            }
        };

        function showAlert(message, type = 'success') {
            alertMessage.innerText = message;
            alertBox.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alertBox.style.display = 'block';
            // Auto ocultar después de 3 segundos
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 3000);
        }
    })();
</script>
@endpush