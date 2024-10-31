<?php

// Sécurité : Empêche l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Création de la page d'administration
function sv_fb_add_admin_menu() {
    add_menu_page(
        'SV Floating Buttons',
        'SV Floating Buttons',
        'manage_options',
        'sv-floating-buttons',
        'sv_fb_admin_page'
    );
}

