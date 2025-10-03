<!-- Modal pentru vizualizarea detaliilor utilizatorului -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person me-2"></i>Detalii Utilizator
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="userDetails">
                    <!-- Detaliile utilizatorului vor fi încărcate aici -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pentru confirmarea acțiunilor -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-question-circle me-2"></i>Confirmare Acțiune
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Ești sigur că vrei să continui?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirmă</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pentru ștergerea utilizatorului -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
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
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Atenție!</strong> Această acțiune nu poate fi anulată.
                    </div>
                    
                    <p>Ești sigur că vrei să ștergi utilizatorul <strong id="delete_user_name"></strong>?</p>
                    <p class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Utilizatorii cu documente asociate nu pot fi șterși.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Șterge Utilizatorul
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pentru resetarea parolei -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-key me-2"></i>Resetare Parolă
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">

                    <p>Schimbi parola pentru utilizatorul <strong id="reset_user_name"></strong>.</p>
                    <div class="alert alert-info py-2">
                        <small><i class="bi bi-shield-lock me-1"></i>Introduceți o parolă puternică (minim 8 caractere, recomandat litere mari/mici, cifre și simboluri).</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parolă nouă</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmă parola</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div id="password_feedback" class="small text-muted"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key me-2"></i>Schimbă Parola
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePassword(id, btn){
    const input = document.getElementById(id);
    if(!input) return;
    const icon = btn.querySelector('i');
    if(input.type === 'password') { input.type = 'text'; icon.classList.replace('bi-eye','bi-eye-slash'); }
    else { input.type = 'password'; icon.classList.replace('bi-eye-slash','bi-eye'); }
}

// Feedback live simplu (dacă scriptul e încărcat după elemente)
['new_password','confirm_password'].forEach(id => {
    const el = document.getElementById(id);
    if(el){
        el.addEventListener('input', () => {
            const pw = document.getElementById('new_password').value;
            const cf = document.getElementById('confirm_password').value;
            let msg = '';
            if(pw.length < 8) msg += 'Minim 8 caractere. ';
            if(!/[A-Z]/.test(pw)) msg += 'Adaugă literă mare. ';
            if(!/[a-z]/.test(pw)) msg += 'Adaugă literă mică. ';
            if(!/[0-9]/.test(pw)) msg += 'Adaugă cifră. ';
            if(!/[^A-Za-z0-9]/.test(pw)) msg += 'Adaugă simbol. ';
            if(cf && pw !== cf) msg += 'Parolele nu coincid.';
            document.getElementById('password_feedback').textContent = msg.trim();
        });
    }
});
</script>