@extends('layout.app')

@section('content')
<div class="row mb-3">
    <div class="col-md-8">
        <h2>Orders</h2>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-success" onclick="openModal()">
            <i class="bi bi-plus-circle"></i> Add Order
        </button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search by order number..." oninput="debounceSearch()">
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
                <!-- Data populated by JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="orderForm" onsubmit="saveOrder(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel">Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="orderId">
                    
                    <div class="mb-3">
                        <label for="customerId" class="form-label">Customer *</label>
                        <select class="form-select" id="customerId" required>
                            <option value="">Select a customer...</option>
                            <!-- Populated via API -->
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="orderNumber" class="form-label">Order Number *</label>
                        <input type="text" class="form-control" id="orderNumber" required>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" required>
                            <option value="CREATED">CREATED</option>
                            <option value="PAID">PAID</option>
                            <option value="CANCELLED">CANCELLED</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="totalAmount" class="form-label">Total Amount *</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="totalAmount" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" rows="3"></textarea>
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
    const apiUrl = '/api/orders';
    const customersApiUrl = '/api/customers';
    let orderModal;
    let searchTimeout;

    document.addEventListener('DOMContentLoaded', () => {
        orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
        loadOrders();
        loadCustomersDropdown();
    });

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadOrders();
        }, 300);
    }

    async function loadCustomersDropdown() {
        try {
            const response = await fetch(customersApiUrl);
            const data = await response.json();
            
            const select = document.getElementById('customerId');
            
            data.forEach(c => {
                if(c.isActive) {
                    const option = document.createElement('option');
                    option.value = c.id;
                    option.text = `${c.fullName} (${c.email})`;
                    select.appendChild(option);
                }
            });
        } catch (error) {
            showAlert('Failed to load customers for dropdown.', 'warning');
        }
    }

    async function loadOrders() {
        const search = document.getElementById('searchInput').value;
        const url = search ? `${apiUrl}?search=${encodeURIComponent(search)}` : apiUrl;
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            const tbody = document.getElementById('ordersTableBody');
            tbody.innerHTML = '';
            
            let statusBadge = {
                'CREATED': 'info',
                'PAID': 'success',
                'CANCELLED': 'danger'
            };

            data.forEach(o => {
                // Formatting currency
                const formatter = new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                });

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${o.id}</td>
                    <td><strong>${o.orderNumber}</strong></td>
                    <td>${o.customer ? o.customer.fullName : 'N/A'}</td>
                    <td>${formatter.format(o.totalAmount)}</td>
                    <td>
                        <span class="badge bg-${statusBadge[o.status] || 'secondary'}">
                            ${o.status}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editOrder(${o.id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteOrder(${o.id})"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch (error) {
            showAlert('Failed to load orders.', 'danger');
        }
    }

    function openModal() {
        document.getElementById('orderForm').reset();
        document.getElementById('orderId').value = '';
        
        // Auto generate order number on new entry
        document.getElementById('orderNumber').value = 'ORD-' + Math.floor(Math.random() * 1000000);
        
        document.getElementById('orderModalLabel').innerText = 'Add Order';
        orderModal.show();
    }

    async function editOrder(id) {
        try {
            const response = await fetch(`${apiUrl}/${id}`);
            if(!response.ok) throw new Error('Not found');
            const data = await response.json();
            
            document.getElementById('orderId').value = data.id;
            document.getElementById('customerId').value = data.customerId;
            document.getElementById('orderNumber').value = data.orderNumber;
            document.getElementById('status').value = data.status;
            document.getElementById('totalAmount').value = data.totalAmount;
            document.getElementById('notes').value = data.notes || '';
            
            document.getElementById('orderModalLabel').innerText = 'Edit Order';
            orderModal.show();
        } catch (error) {
            showAlert('Failed to fetch order data.', 'danger');
        }
    }

    async function saveOrder(e) {
        e.preventDefault();
        const id = document.getElementById('orderId').value;
        
        const payload = {
            customerId: document.getElementById('customerId').value,
            orderNumber: document.getElementById('orderNumber').value,
            status: document.getElementById('status').value,
            totalAmount: parseFloat(document.getElementById('totalAmount').value),
            notes: document.getElementById('notes').value
        };

        const method = id ? 'PUT' : 'POST';
        const url = id ? `${apiUrl}/${id}` : apiUrl;

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const errorData = await response.json();
                let errors = errorData.message || 'Validation error';
                if (errorData.errors) {
                    errors = Object.values(errorData.errors).flat().join('<br>');
                }
                showAlert(errors, 'danger');
                return;
            }

            orderModal.hide();
            showAlert('Order saved successfully!');
            loadOrders();
        } catch (error) {
            showAlert('Operation failed.', 'danger');
        }
    }

    async function deleteOrder(id) {
        if(!confirm('Are you sure you want to delete this order?')) return;
        
        try {
            const response = await fetch(`${apiUrl}/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Failed to delete');
            
            showAlert('Order deleted successfully!');
            loadOrders();
        } catch (error) {
            showAlert('Cannot delete order.', 'danger');
        }
    }
</script>
@endpush
