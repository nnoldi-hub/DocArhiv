<!-- Modal pentru adăugarea companiei -->
<div class="modal fade" id="addCompanyModal" tabindex="-1" aria-labelledby="addCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="addCompanyForm" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCompanyModalLabel">
                        <i class="bi bi-building-add me-2"></i>Adaugă Companie Nouă
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_company">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_company_name" class="form-label">Nume companie *</label>
                            <input type="text" class="form-control" id="add_company_name" name="company_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_company_email" class="form-label">Email companie *</label>
                            <input type="email" class="form-control" id="add_company_email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_subscription_status" class="form-label">Status abonament</label>
                        <select class="form-select" id="add_subscription_status" name="subscription_status">
                            <option value="trial">Trial</option>
                            <option value="active">Activ</option>
                            <option value="suspended">Suspendat</option>
                            <option value="expired">Expirat</option>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="add_create_admin" name="create_admin" onchange="toggleAddAdminFields()">
                        <label class="form-check-label" for="add_create_admin">
                            <strong>Creează și un cont de administrator pentru această companie</strong>
                        </label>
                    </div>
                    
                    <div id="addAdminFields" style="display: none;">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Completează datele pentru contul de administrator:
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_admin_name" class="form-label">Nume complet admin</label>
                                <input type="text" class="form-control" id="add_admin_name" name="admin_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_admin_username" class="form-label">Username admin</label>
                                <input type="text" class="form-control" id="add_admin_username" name="admin_username">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="add_admin_email" class="form-label">Email admin</label>
                            <input type="email" class="form-control" id="add_admin_email" name="admin_email">
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Parolă inițială (opțional)</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generateAddAdminPassword()">
                                    <i class="bi bi-magic me-1"></i>Generează
                                </button>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="add_admin_password" name="admin_password" placeholder="Lasă gol pentru generare automată">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('add_admin_password', this)" title="Arată/ascunde">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('add_admin_password', this)" title="Copiază">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <div class="form-text">Minim 8 caractere, include litere mari, mici, cifre și simbol.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-2"></i>Adaugă Compania
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pentru ștergerea companiei -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="deleteCompanyForm">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirmare Ștergere
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_company">
                    <input type="hidden" name="company_id" id="delete_company_id">
                    
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Atenție!</strong> Această acțiune nu poate fi anulată.
                    </div>
                    
                    <p>Ești sigur că vrei să ștergi compania <strong id="delete_company_name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Șterge Compania
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAddAdminFields() {
    const checkbox = document.getElementById('add_create_admin');
    const fields = document.getElementById('addAdminFields');
    
    if (checkbox && fields) {
        if (checkbox.checked) {
            fields.style.display = 'block';
            // Face câmpurile obligatorii
            const requiredFields = ['add_admin_name', 'add_admin_username', 'add_admin_email'];
            requiredFields.forEach(id => {
                const field = document.getElementById(id);
                if (field) field.required = true;
            });
        } else {
            fields.style.display = 'none';
            // Elimină obligativitatea
            const requiredFields = ['add_admin_name', 'add_admin_username', 'add_admin_email'];
            requiredFields.forEach(id => {
                const field = document.getElementById(id);
                if (field) field.required = false;
            });
        }
    }
}

function generateAddAdminPassword() {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    const field = document.getElementById('add_admin_password');
    if (field) {
        field.value = password;
        field.type = 'text'; // Arată parola generată
    }
}

function togglePasswordVisibility(fieldId, button) {
    const field = document.getElementById(fieldId);
    const icon = button.querySelector('i');
    
    if (field && icon) {
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            field.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
}

function copyToClipboard(fieldId, button) {
    const field = document.getElementById(fieldId);
    if (field && field.value) {
        navigator.clipboard.writeText(field.value).then(() => {
            const originalIcon = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check"></i>';
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-secondary');
            
            setTimeout(() => {
                button.innerHTML = originalIcon;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 1500);
        });
    }
}

// Asigură submit-ul formului
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addCompanyForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Permite submit-ul normal - nu preveni default
            console.log('Form submitted');
        });
    }
});
</script>