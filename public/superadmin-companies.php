<?php
/**
 * SuperAdmin Companies Entry Point
 * public/superadmin-companies.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol SuperAdmin
if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    redirect('/login.php');
}

// Configurează variabilele pentru layout
$page_title = 'Gestiune Companii - SuperAdmin';
$page_description = 'Administrare companii, abonamente și administratori';
$header_actions = <<<HTML
<div class="d-flex flex-wrap gap-2">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
        <i class="bi bi-plus-lg me-2"></i>Adaugă Companie
    </button>
    <div class="btn-group">
        <select class="form-select" id="statusFilter" onchange="filterCompanies()">
            <option value="">Toate statusurile</option>
            <option value="active">Active</option>
            <option value="trial">Trial</option>
            <option value="suspended">Suspendate</option>
            <option value="expired">Expirate</option>
        </select>
    </div>
    <div class="input-group" style="width: 300px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" id="searchInput" placeholder="Caută companie..." onkeyup="filterCompanies()">
    </div>
</div>
HTML;
$current_page = 'companies';
$content_file = '../modules/superadmin/companies_content.php';

// Include layout-ul unificat
require_once '../modules/superadmin/layout.php';