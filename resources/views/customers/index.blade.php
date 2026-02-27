@extends('layout.app')

@section('content')
<div class="row mb-3">
    <div class="col-md-8">
        <h2>Customers</h2>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-success" onclick="openModal()">
            <i class="bi bi-plus-circle"></i> Add Customer
        </button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search by name or email..." oninput="debounceSearch()">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="customersTableBody">
                <!-- Data populated by JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="customerForm" onsubmit="saveCustomer(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalLabel">Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="customerId">
                    
                    <div class="mb-3">
                        <label for="fullName" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="fullName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isActive" checked>
                        <label class="form-check-label" for="isActive">Is Active</label>
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
    const apiUrl = '/api/customers';
    let customerModal;
    let searchTimeout;

    document.addEventListener('DOMContentLoaded', () => {
        customerModal = new bootstrap.Modal(document.getElementById('customerModal'));
        loadCustomers();
    });

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadCustomers();
        }, 300);
    }

    async function loadCustomers() {
        const search = document.getElementById('searchInput').value;
        const url = search ? `${apiUrl}?search=${encodeURIComponent(search)}` : apiUrl;
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            const tbody = document.getElementById('customersTableBody');
            tbody.innerHTML = '';
            
            data.forEach(c => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${c.id}</td>
                    <td>${c.fullName}</td>
                    <td>${c.email}</td>
                    <td>${c.phone || '-'}</td>
                    <td>
                        <span class="badge bg-${c.isActive ? 'success' : 'secondary'}">
                            ${c.isActive ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editCustomer(${c.id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteCustomer(${c.id})"><i class="bi bi-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch (error) {
            showAlert('Failed to load customers.', 'danger');
        }
    }

    function openModal() {
        document.getElementById('customerForm').reset();
        document.getElementById('customerId').value = '';
        document.getElementById('customerModalLabel').innerText = 'Add Customer';
        customerModal.show();
    }

    async function editCustomer(id) {
        try {
            const response = await fetch(`${apiUrl}/${id}`);
            if(!response.ok) throw new Error('Not found');
            const data = await response.json();
            
            document.getElementById('customerId').value = data.id;
            document.getElementById('fullName').value = data.fullName;
            document.getElementById('email').value = data.email;
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('isActive').checked = data.isActive;
            
            document.getElementById('customerModalLabel').innerText = 'Edit Customer';
            customerModal.show();
        } catch (error) {
            showAlert('Failed to fetch customer data.', 'danger');
        }
    }

    async function saveCustomer(e) {
        e.preventDefault();
        const id = document.getElementById('customerId').value;
        
        const payload = {
            fullName: document.getElementById('fullName').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            isActive: document.getElementById('isActive').checked ? 1 : 0
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

            customerModal.hide();
            showAlert('Customer saved successfully!');
            loadCustomers();
        } catch (error) {
            showAlert('Operation failed.', 'danger');
        }
    }

    async function deleteCustomer(id) {
        if(!confirm('Are you sure you want to delete this customer?')) return;
        
        try {
            const response = await fetch(`${apiUrl}/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error('Failed to delete');
            
            showAlert('Customer deleted successfully!');
            loadCustomers();
        } catch (error) {
            showAlert('Cannot delete customer. They might have related orders.', 'danger');
        }
    }
</script>
@endpush
