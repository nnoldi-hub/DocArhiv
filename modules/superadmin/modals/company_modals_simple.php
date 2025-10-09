<!-- Modal simplu pentru adăugarea companiei -->
<div id="addCompanyModal" class="modal" style="display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="position: relative; margin: 5% auto; width: 90%; max-width: 600px; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div style="padding: 20px; border-bottom: 1px solid #dee2e6;">
            <h5 style="margin: 0; display: flex; align-items: center;">
                <i class="bi bi-building-add me-2"></i>Adaugă Companie Nouă
            </h5>
            <button type="button" onclick="closeAddModal()" style="position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST" style="padding: 20px;">
            <input type="hidden" name="action" value="add_company">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nume companie *</label>
                <input type="text" name="company_name" required style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email companie *</label>
                <input type="email" name="email" required style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Status abonament</label>
                <select name="subscription_status" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;">
                    <option value="trial">Trial</option>
                    <option value="active">Activ</option>
                    <option value="suspended">Suspendat</option>
                    <option value="expired">Expirat</option>
                </select>
            </div>
            
            <hr style="margin: 20px 0;">
            
            <div style="margin-bottom: 15px;">
                <label style="display: flex; align-items: center; font-weight: 600;">
                    <input type="checkbox" id="createAdminCheck" name="create_admin" onchange="toggleAdminSection()" style="margin-right: 8px;">
                    Creează și un cont de administrator pentru această companie
                </label>
            </div>
            
            <div id="adminSection" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                <p style="color: #0c5460; margin-bottom: 15px;">
                    <i class="bi bi-info-circle me-2"></i>Completează datele pentru contul de administrator:
                </p>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nume complet admin</label>
                    <input type="text" name="admin_name" id="adminName" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Username admin</label>
                    <input type="text" name="admin_username" id="adminUsername" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email admin</label>
                    <input type="email" name="admin_email" id="adminEmail" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                        Parolă inițială (opțional)
                        <button type="button" onclick="generatePassword()" style="margin-left: 10px; padding: 4px 8px; background: #6c757d; color: white; border: none; border-radius: 3px; font-size: 12px; cursor: pointer;">Generează</button>
                    </label>
                    <input type="text" name="admin_password" id="adminPassword" placeholder="Lasă gol pentru generare automată" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;">
                    <small style="color: #6c757d;">Minim 8 caractere, include litere mari, mici, cifre și simbol.</small>
                </div>
            </div>
            
            <div style="text-align: right; border-top: 1px solid #dee2e6; padding-top: 15px; margin-top: 20px;">
                <button type="button" onclick="closeAddModal()" style="padding: 8px 16px; margin-right: 10px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Anulează</button>
                <button type="submit" style="padding: 8px 16px; background: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="bi bi-check-lg me-1"></i>Adaugă Compania
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addCompanyModal').style.display = 'block';
    document.body.style.overflow = 'hidden'; // Previne scroll-ul în fundal
}

function closeAddModal() {
    document.getElementById('addCompanyModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // Restabilește scroll-ul
    
    // Reset form
    const form = document.querySelector('#addCompanyModal form');
    if (form) form.reset();
    
    // Ascunde secțiunea admin
    document.getElementById('adminSection').style.display = 'none';
    document.getElementById('createAdminCheck').checked = false;
}

function toggleAdminSection() {
    const checkbox = document.getElementById('createAdminCheck');
    const section = document.getElementById('adminSection');
    const fields = ['adminName', 'adminUsername', 'adminEmail'];
    
    if (checkbox.checked) {
        section.style.display = 'block';
        // Face câmpurile obligatorii
        fields.forEach(id => {
            const field = document.getElementById(id);
            if (field) field.required = true;
        });
    } else {
        section.style.display = 'none';
        // Elimină obligativitatea
        fields.forEach(id => {
            const field = document.getElementById(id);
            if (field) field.required = false;
        });
    }
}

function generatePassword() {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    document.getElementById('adminPassword').value = password;
}

// Închide modal-ul când se face click în afara lui
document.addEventListener('click', function(e) {
    const modal = document.getElementById('addCompanyModal');
    if (e.target === modal) {
        closeAddModal();
    }
});

// Închide modal-ul cu tasta Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddModal();
    }
});
</script>