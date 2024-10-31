<?php
/**
 * Plugin Name: SV Floating Buttons
 * Description: Plugin pour créer des boutons flottants personnalisables avec options de couleur, d'icônes.
 * Version: 1
 * Author: Svetlana Sultanyan
 */

if (!defined('ABSPATH')) exit;

class SV_Floating_Buttons {
    public function __construct() {

        add_action('admin_menu', array($this, 'create_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_footer', array($this, 'display_floating_buttons'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles_scripts'));
        add_action('wp_ajax_sv_add_button', array($this, 'ajax_add_button'));
        add_action('wp_ajax_sv_edit_button', array($this, 'ajax_edit_button'));
        add_action('wp_ajax_sv_delete_button', array($this, 'ajax_delete_button'));
        add_shortcode('sv_floating_button', array($this, 'render_shortcode'));
    }
    public function create_admin_menu() {
        add_menu_page(
            'SV Floating Buttons',
            'SV Floating Buttons',
            'manage_options',
            'sv-floating-buttons',
            array($this, 'settings_page_content'),
            'dashicons-admin-generic',
            80
        );
    }



    // Display buttons in a table with edit option in admin
    public function display_buttons_admin_table($buttons_data) {
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Texte du Bouton</th>
                <th scope="col">Couleur de Fond</th>
                <th scope="col">Couleur du Texte</th>
                <th scope="col">Icône</th>
                <th scope="col">Position</th>
                <th scope="col">Aperçu</th>
                <th scope="col">Shortcode</th>
                <th scope="col">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($buttons_data as $index => $button): ?>
                <tr data-index="<?php echo esc_attr($index); ?>">
                    <td><?php echo esc_html($index); ?></td>
                    <td><input type="text" class="button-text" value="<?php echo esc_attr($button['text']); ?>"></td>
                    <td><input type="color" class="button-bg-color" value="<?php echo esc_attr($button['bg_color']); ?>"></td>
                    <td><input type="color" class="button-text-color" value="<?php echo esc_attr($button['text_color']); ?>"></td>
                    <td>
                    <span class="button-icon" style="display: inline-block; margin: 0 10px;">
                        <i class="<?php echo esc_attr($button['icon']); ?>" style="font-size: 24px;"></i>
                    </span>
                        <button type="button" class="choisirIconeButton button button-secondary">Choisir une Icône</button>
                    </td>
                    <td>
                        <select class="button-position">
                            <option value="top-left" <?php selected($button['position'], 'top-left'); ?>>Haut à Gauche</option>
                            <option value="top-right" <?php selected($button['position'], 'top-right'); ?>>Haut à Droite</option>
                            <option value="bottom-left" <?php selected($button['position'], 'bottom-left'); ?>>Bas Gauche</option>
                            <option value="bottom-right" <?php selected($button['position'], 'bottom-right'); ?>>Bas à droite</option>
                        </select>
                    </td>
                    <td>
                        <div style="display: inline-block; padding: 5px; border: 1px solid #ccc; border-radius: 4px; background-color: <?php echo esc_attr($button['bg_color']); ?>;">
                            <i class="<?php echo esc_attr($button['icon']); ?>" style="color: <?php echo esc_attr($button['text_color']); ?>; font-size: 24px;"></i>
                            <span style="color: <?php echo esc_attr($button['text_color']); ?>; margin-left: 5px;"><?php echo esc_html($button['text']); ?></span>
                        </div>
                    </td>
                    <td><span>[sv_floating_button id="<?php echo esc_attr($index); ?>"]</span></td>
                    <td>
                        <button class="sv-edit-button button button-primary" data-index="<?php echo esc_attr($index); ?>">Enregistrer</button>
                        <button class="sv-delete-button button button-secondary" data-index="<?php echo esc_attr($index); ?>">Supprimer</button>
                    </td>
                </tr>
            <?php endforeach; ?>


            </tbody>
        </table>

        <?php
    }
    public function ajax_add_button() {
        check_ajax_referer('sv_buttons_nonce', 'nonce');

        $buttons_data = get_option('sv_buttons_data', array());
        $button_id = count($buttons_data); // Or generate a unique ID as previously discussed
        $buttons_data[$button_id] = array(
            'text' => sanitize_text_field($_POST['text']),
            'bg_color' => sanitize_hex_color($_POST['bg_color']),
            'text_color' => sanitize_hex_color($_POST['text_color']),
            'icon' => sanitize_text_field($_POST['icon']),
            'position' => sanitize_text_field($_POST['position']),
            'link' => esc_url($_POST['link']),
        );
        update_option('sv_buttons_data', $buttons_data);

        wp_send_json_success($buttons_data);
    }




    public function ajax_edit_button() {
        check_ajax_referer('sv_buttons_nonce', 'nonce');

        $index = intval($_POST['index']);
        $buttons_data = get_option('sv_buttons_data', array());

        if (isset($buttons_data[$index])) {
            // Sanitize and update button fields
            $buttons_data[$index]['text'] = sanitize_text_field($_POST['text']);
            $buttons_data[$index]['bg_color'] = sanitize_hex_color($_POST['bg_color']);
            $buttons_data[$index]['text_color'] = sanitize_hex_color($_POST['text_color']);
            $buttons_data[$index]['icon'] = sanitize_text_field($_POST['icon']);
            $buttons_data[$index]['position'] = sanitize_text_field($_POST['position']);

            // Save the updated data to the database
            update_option('sv_buttons_data', $buttons_data);

            // Send back the updated button data to JavaScript
            wp_send_json_success($buttons_data);
        } else {
            wp_send_json_error(['message' => 'Button not found.']);
        }
    }





    // Function to delete a button
    public function ajax_delete_button() {
        check_ajax_referer('sv_buttons_nonce', 'nonce');

        $index = intval($_POST['index']);
        $buttons_data = get_option('sv_buttons_data', array());
        if (isset($buttons_data[$index])) {
            unset($buttons_data[$index]);
            $buttons_data = array_values($buttons_data); // Ré-indexer les éléments
            update_option('sv_buttons_data', $buttons_data);
            wp_send_json_success($buttons_data);
        }
        wp_send_json_error();
    }
    // Affichage de la page d'administration
    public function settings_page_content() {
        $buttons_data = get_option('sv_buttons_data', array());
        ?>
        <div class="wrap">
            <h1>Gestion des Boutons Flottants</h1>
            <!-- Notification for button added -->
            <div id="sv-notification" class="notice notice-success is-dismissible" style="display:none;">
                <p>Bouton ajouté avec succès !</p>
            </div>
            <div id="icon-selection-modal" class="icon-modal" style="display:none;">
                <div class="icon-modal-content">
                    <h2>Sélectionnez une icône</h2>
                    <!-- Input that activates the icon picker -->
                    <input type="text" id="icon-picker" class="form-control" placeholder="Cliquez pour choisir une icône">
                    <button type="button" id="close-icon-modal" class="button button-secondary">Fermer</button>
                </div>
            </div>
            <div id="sv-buttons-list">
                <h2>Liste des Boutons</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Texte du Bouton</th>
                        <th scope="col">Couleur de Fond</th>
                        <th scope="col">Couleur du Texte</th>
                        <th scope="col">Icône</th>
                        <th scope="col">Position</th>
                        <th scope="col">Aperçu</th>
                        <th scope="col">Shortcode</th>
                        <th scope="col">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="sv-buttons-table-body">
                    <?php foreach ($buttons_data as $index => $button): ?>
                        <tr>
                            <td><?php echo esc_html($index); ?></td>
                            <td><input type="text" class="button-text" value="<?php echo esc_attr($button['text']); ?>"></td>
                            <td><input type="color" class="button-bg-color" value="<?php echo esc_attr($button['bg_color']); ?>"></td>
                            <td><input type="color" class="button-text-color" value="<?php echo esc_attr($button['text_color']); ?>"></td>
                            <td>
                                <span class="button-icon"><i class="<?php echo esc_attr($button['icon']); ?>" style="font-size: 24px;"></i></span>
                                <button type="button" class="choisirIconeButton button button-secondary">Choisir une Icône</button>
                                <input type="hidden" class="icon-value" value="<?php echo esc_attr($button['icon']); ?>" />
                            </td>
                            <td>
                                <select class="button-position">
                                    <option value="top-left" <?php selected($button['position'], 'top-left'); ?>>Haut Gauche</option>
                                    <option value="top-right" <?php selected($button['position'], 'top-right'); ?>>Haut Droit</option>
                                    <option value="bottom-left" <?php selected($button['position'], 'bottom-left'); ?>>Bas Gauche</option>
                                    <option value="bottom-right" <?php selected($button['position'], 'bottom-right'); ?>>Bas Droit</option>
                                </select>
                            </td>
                            <td>
                                <button style="display: inline-block; padding: 5px; border: 1px solid #ccc; border-radius: 4px; background-color: <?php echo esc_attr($button['bg_color']); ?>;">
                                    <i class="<?php echo esc_attr($button['icon']); ?>" style="color: <?php echo esc_attr($button['text_color']); ?>; font-size: 24px;"></i>
                                    <span style="color: <?php echo esc_attr($button['text_color']); ?>; margin-left: 5px;"><?php echo esc_html($button['text']); ?></span>
                                </button>
                            </td>
                            <td>
                                <span>[sv_floating_button id="<?php echo esc_attr($index); ?>"]</span>
                            </td>
                            <td>
                                <button class="sv-edit-button button button-primary" data-index="<?php echo esc_attr($index); ?>">Enregistrer</button>
                                <button class="sv-delete-button button button-secondary" data-index="<?php echo esc_attr($index); ?>">Supprimer</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h2>Ajouter un Nouveau Bouton</h2>
            <form id="sv-add-button-form">
                <label for="sv-button-text">Texte du Bouton</label>
                <input type="text" id="sv-button-text" required>

                <label for="sv-button-bg-color">Couleur de Fond</label>
                <input type="color" id="sv-button-bg-color" required>

                <label for="sv-button-text-color">Couleur du Texte</label>
                <input type="color" id="sv-button-text-color" required>

                <label for="sv-button-icon">Icône</label>
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <input type="text" id="sv-button-icon" style="width: 60%;" readonly placeholder="Sélectionnez une icône">
                    <button type="button" id="sv-upload-icon" class="button">Choisir une Icône</button>
                </div>
                <div id="icon-preview" style="font-size: 24px; margin-bottom: 10px;"></div>

                <label for="sv-button-position">Position</label>
                <select id="sv-button-position" required>
                    <option value="top-left">Haut Gauche</option>
                    <option value="top-right">Haut Droit</option>
                    <option value="bottom-left">Bas Gauche</option>
                    <option value="bottom-right">Bas Droit</option>
                </select>

                <button type="button" id="sv-add-button" class="button button-primary">Ajouter le Bouton</button>
            </form>


        </div>
        <?php
    }


    // Enqueue des scripts d'admin pour AJAX
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_sv-floating-buttons') {
            return;
        }
        wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css' );
        wp_enqueue_style( 'iconpicker-css', 'https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/css/fontawesome-iconpicker.min.css' );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'iconpicker-js', 'https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/js/fontawesome-iconpicker.min.js', [ 'jquery' ], null, true );
        // Inline script to initialize icon picker
        wp_add_inline_script( 'iconpicker-js', 'jQuery(function($) { $(".icon-picker").iconpicker(); });' );
        // Bootstrap JavaScript
        wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), null, true);
        wp_enqueue_script('font-awesome-picker-js', 'https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/js/fontawesome-iconpicker.min.js', array('jquery'), '3.2.0', true);
        wp_enqueue_script('sv-floating-buttons-js', plugins_url('/assets//js/sv-floating-buttons.js', __FILE__), array('jquery'), '1.0', true);
        // Localize script with AJAX URL and nonce for security
        wp_localize_script('sv-floating-buttons-js', 'svFloatingButtons', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sv_buttons_nonce')
        ));
    }

    // Enqueue des styles et scripts frontend
    public function enqueue_styles_scripts() {
        wp_enqueue_style('sv-floating-buttons-style', plugins_url('/assets/css/sv-floating-buttons.css', __FILE__));
        wp_enqueue_style('font-awesome-picker-css', 'https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/css/fontawesome-iconpicker.min.css');

        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
    }

    // Fonction pour afficher les boutons en frontend
    public function display_floating_buttons() {
        $buttons_data = get_option('sv_buttons_data', array());
        foreach ($buttons_data as $button) {
            $this->render_button($button);
        }
    }
    // Fonction pour afficher un bouton
    private function render_button($button) {
        $position_class = isset($button['position']) ? $button['position'] : 'bottom-right';
        $link = !empty($button['link']) ? 'onclick="'.$button['link'].'"' : ''; // Action JavaScript ou lien
        ?>
        <div class="sv-floating-button <?php echo esc_attr($position_class); ?>" style="background-color: <?php echo esc_attr($button['bg_color']); ?>; color: <?php echo esc_attr($button['text_color']); ?>;" <?php echo $link; ?>>
            <i class="fas <?php echo esc_attr($button['icon']); ?>"></i>
            <span><?php echo esc_html($button['text']); ?></span>
        </div>
        <?php
    }
    // Affichage de la liste des boutons en administration
    private function display_buttons_admin_list() {
        $buttons_data = get_option('sv_buttons_data', array());
        if (empty($buttons_data)) {
            echo '<p>Aucun bouton ajouté pour le moment.</p>';
            return;
        }
        echo '<ul>';
        foreach ($buttons_data as $index => $button) {
            echo '<li>';
            echo esc_html($button['text']) . ' - ';
            echo '<button class="sv-delete-button" data-index="' . esc_attr($index) . '">Supprimer</button>';
            echo ' - Shortcode: <code>[sv_floating_button id="' . esc_attr($index) . '"]</code>';
            echo '</li>';
        }
        echo '</ul>';
    }








    // Function pour rendre le bouton en shortcode
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts, 'sv_floating_button');
        $buttons_data = get_option('sv_buttons_data', array());
        if (isset($buttons_data[$atts['id']])) {
            $button = $buttons_data[$atts['id']];
            $style = sprintf('background-color: %s; color: %s;', esc_attr($button['bg_color']), esc_attr($button['text_color']));
            $link_attr = !empty($button['link']) ? 'href="'.esc_url($button['link']).'"' : '';
            return sprintf(
                '<a class="sv-floating-button %s" %s style="%s">
                <i class="%s"></i> %s
            </a>',
                esc_attr($button['position']),
                $link_attr,
                $style,
                esc_attr($button['icon']), // Display the icon class correctly
                esc_html($button['text'])
            );
        }

        return '';
    }


}

new SV_Floating_Buttons();
