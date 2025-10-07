<!-- Modal pentru adăugarea companiei -->
<div class="modal fade" id="addCompanyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-building-add me-2"></i>Adaugă Companie Nouă
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_company">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="company_name" class="form-label">Nume companie *</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email companie *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subscription_status" class="form-label">Status abonament</label>
                        <select class="form-select" id="subscription_status" name="subscription_status">
                            <option value="trial">Trial</option>
                            <option value="active">Activ</option>
                            <option value="suspended">Suspendat</option>
                            <option value="expired">Expirat</option>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="create_admin" name="create_admin" onchange="toggleAdminFields()">
                        <label class="form-check-label" for="create_admin">
                            <strong>Creează și un cont de administrator pentru această companie</strong>
                        </label>
                    </div>
                    
                    <div id="adminFields" style="display: none;">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Completează datele pentru contul de administrator:
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="admin_name" class="form-label">Nume complet admin</label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="admin_username" class="form-label">Username admin</label>
                                <input type="text" class="form-control" id="admin_username" name="admin_username">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_email" class="form-label">Email admin</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email">
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Parolă inițială (opțional)</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generateAdminPassword()">
                                    <i class="bi bi-magic me-1"></i>Generează
                                </button>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="admin_password" name="admin_password" placeholder="Lasă gol pentru generare automată">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('admin_password', this)" title="Arată/ascunde">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('admin_password', this)" title="Copiază">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <div class="form-text" id="admin_password_help">Minim 8 caractere, include litere mari, mici, cifre și simbol.</div>
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
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirmare Ștergere
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_company">
                    <input type="hidden" name="company_id" id="delete_company_id">
                    
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Atenție!</strong> Această acțiune nu poate fi anulată.
                    </div>
                    
                    <p>Ești sigur că vrei să ștergi compania <strong id="delete_company_name"></strong>?</p>
                    <p class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Doar companiile fără utilizatori pot fi șterse.
                    </p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Șterge Compania
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pentru schimbarea statusului -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-gear me-2"></i>Schimbă Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="company_id" id="status_company_id">
                    <input type="hidden" name="new_status" id="status_new_status">
                    
                    <p>Schimbi statusul la: <strong id="status_text"></strong>?</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">Confirmă</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pentru crearea adminului -->
<div class="modal fade" id="createAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus me-2"></i>Creează Admin pentru <span id="admin_company_name"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_admin">
                    <input type="hidden" name="company_id" id="admin_company_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="admin_full_name" class="form-label">Nume complet *</label>
                            <input type="text" class="form-control" id="admin_full_name" name="admin_full_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="admin_username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Informație:</strong> Se va genera automat o parolă temporară care va fi afișată după creare.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>Creează Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pentru asocierea unui admin existent -->
<div class="modal fade" id="assignAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-badge me-2"></i>
                        Asociază Administrator pentru <span id="assign_admin_company_name"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_admin">
                    <input type="hidden" name="company_id" id="assign_admin_company_id">

                    <div class="mb-3">
                        <label for="assign_admin_user_id" class="form-label">Selectează utilizatorul *</label>
                        <select class="form-select" id="assign_admin_user_id" name="user_id" required>
                            <option value="">Selectează un utilizator...</option>
                        </select>
                        <small class="text-muted" id="assign_admin_helper">
                            Selectează un utilizator existent și promovează-l ca administrator principal al companiei.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary" id="assign_admin_submit">
                        <i class="bi bi-check-lg me-2"></i>Salvează Adminul
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAdminFields() {
    const checkbox = document.getElementById('create_admin');
    const fields = document.getElementById('adminFields');
    
    if (checkbox.checked) {
        fields.style.display = 'block';
        // Face câmpurile obligatorii
        document.getElementById('admin_name').required = true;
        document.getElementById('admin_username').required = true;
        document.getElementById('admin_email').required = true;
    } else {
        fields.style.display = 'none';
        // Elimină obligativitatea
        document.getElementById('admin_name').required = false;
        document.getElementById('admin_username').required = false;
        document.getElementById('admin_email').required = false;
    }
}
</script>