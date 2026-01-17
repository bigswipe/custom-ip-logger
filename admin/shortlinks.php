<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_submenu_page(
        'ip-logger-dashboard',
        'Shortlinks',
        'Shortlinks',
        'manage_options',
        'ip-logger-shortlinks',
        'iplogger_render_shortlinks_page'
    );
});

function iplogger_render_shortlinks_page() {
    ob_start(); // Start buffering

    $shortlinks = get_option('iplogger_shortlinks', []);

    if (isset($_POST['bulk_delete']) && !empty($_POST['selected_slugs'])) {
        $slugs_to_delete = array_map('sanitize_title', $_POST['selected_slugs']);
        foreach ($slugs_to_delete as $slug) {
            unset($shortlinks[$slug]);
        }
        update_option('iplogger_shortlinks', $shortlinks);
        echo '<div class="updated"><p>Selected shortlinks deleted.</p></div>';
    }

    if (isset($_POST['add_shortlink'])) {
        $slug = sanitize_title($_POST['slug']);
        $url = esc_url_raw($_POST['url']);
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_text_field($_POST['description']);
        $image = esc_url_raw($_POST['image']);
        $shortlinks[$slug] = compact('url', 'title', 'description', 'image');
        update_option('iplogger_shortlinks', $shortlinks);
        echo '<div class="updated"><p>Shortlink saved.</p></div>';
    }

    if (isset($_GET['delete'])) {
        $slug = sanitize_title($_GET['delete']);
        unset($shortlinks[$slug]);
        update_option('iplogger_shortlinks', $shortlinks);
        echo '<div class="updated"><p>Shortlink deleted.</p></div>';
    }

    $edit_slug = '';
    $edit_link = ['url' => '', 'title' => '', 'description' => '', 'image' => ''];
    if (isset($_GET['edit']) && isset($shortlinks[$_GET['edit']])) {
        $edit_slug = sanitize_title($_GET['edit']);
        $edit_link = $shortlinks[$edit_slug];
    }
    ?>

    <div class="wrap iplogger-admin">
        <h1>Manage Shortlinks</h1>
        <div class="iplogger-layout-grid">

            <!-- Form Panel -->
            <div class="iplogger-form">
                <h2 style="display: flex; justify-content: space-between; align-items: center;">
                    <?php echo $edit_slug
                        ? "<span>Edit Shortlink</span><a href='?page=ip-logger-shortlinks' class='button'>➕ Add New Link</a>"
                        : "<span>Add New / Update Shortlink</span>"; ?>
                </h2>

                <form method="post" id="shortlink-form">
                    <table class="form-table"><tr>
                        <th scope="row">Slug (go/slug)</th>
                        <td><input type="text" name="slug" id="input-slug" value="<?php echo esc_attr($edit_slug); ?>" required></td>
                    </tr><tr>
                        <th scope="row">Destination URL</th>
                        <td><input type="url" name="url" id="input-url" value="<?php echo esc_attr($edit_link['url']); ?>" required></td>
                    </tr><tr>
                        <th scope="row">Link Title</th>
                        <td><input type="text" name="title" id="input-title" value="<?php echo esc_attr($edit_link['title']); ?>"></td>
                    </tr><tr>
                        <th scope="row">Link Description</th>
                        <td>
                            <input type="text" name="description" id="input-description" maxlength="100" value="<?php echo esc_attr($edit_link['description']); ?>">
                            <div id="desc-count">0 / 100 characters</div>
                        </td>
                    </tr><tr>
                        <th scope="row">Preview Image URL</th>
                        <td><input type="url" name="image" id="input-image" value="<?php echo esc_attr($edit_link['image']); ?>"></td>
                    </tr></table>
                    <p><input type="submit" name="add_shortlink" class="button-primary" value="<?php echo $edit_slug ? 'Update Shortlink' : 'Save Shortlink'; ?>"></p>
                </form>
            </div>

            <!-- WhatsApp Preview -->
            <div class="iplogger-preview">
                <h2>WhatsApp Preview</h2>
                <div class="mobile-frame">
                    <div class="wa-header">
                        <div class="wa-profile"></div>
                        <div class="wa-title">
                            <strong>John Doe</strong><br>
                            <small>last seen today at 9:05 PM</small>
                        </div>
                    </div>
                    <div class="wa-chat">
                        <div class="wa-msg wa-in">Hi</div>
                        <div class="wa-msg wa-in">Hello!</div>
                        <div class="wa-msg wa-in">Here's the offer 👇</div>
                        <div class="wa-link-preview">
                            <div class="preview-image" id="preview-image"></div>
                            <div class="preview-title" id="preview-title">Title will appear here</div>
                            <div class="preview-desc" id="preview-desc">Description will appear here</div>
                            <div class="preview-url" id="preview-url">yourdomain.com/go/slug</div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- layout grid -->

        <?php if ($shortlinks) : ?>
            <h2>Existing Shortlinks</h2>
            <form method="post" id="delete-form">
                <table class="widefat striped">
                    <thead><tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>Slug</th><th>Destination URL</th><th>Short URL</th>
                        <th>Title</th><th>Description</th><th>Image</th><th>Actions</th>
                    </tr></thead><tbody>
                    <?php foreach ($shortlinks as $slug => $link) :
                        $short_url = esc_url(home_url("/go/{$slug}")); ?>
                        <tr>
                            <td><input type="checkbox" name="selected_slugs[]" value="<?php echo esc_attr($slug); ?>"></td>
                            <td><code><?php echo esc_html($slug); ?></code></td>
                            <td><a href="<?php echo esc_url($link['url']); ?>" target="_blank"><?php echo esc_html($link['url']); ?></a></td>
                            <td><a href="<?php echo $short_url; ?>" target="_blank"><?php echo $short_url; ?></a></td>
                            <td><?php echo esc_html($link['title']); ?></td>
                            <td><?php echo esc_html($link['description']); ?></td>
                            <td><img src="<?php echo esc_url($link['image']); ?>" style="max-height:40px;"></td>
                            <td>
                                <a href="?page=ip-logger-shortlinks&edit=<?php echo esc_attr($slug); ?>" class="button">Edit</a>
                                <a href="?page=ip-logger-shortlinks&delete=<?php echo esc_attr($slug); ?>" class="button">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p><input type="submit" name="bulk_delete" class="button button-secondary" value="Delete Selected"></p>
            </form>
        <?php else : ?>
            <p>No shortlinks created yet.</p>
        <?php endif; ?>
    </div>

    <style>
    .iplogger-admin * { box-sizing: border-box; }
    .iplogger-layout-grid { display: flex; flex-wrap: wrap; gap: 30px; }
    .iplogger-form { flex: 1; min-width: 340px; max-width: 600px; }
    .iplogger-preview { max-width: 380px; min-width: 320px; flex-shrink: 0; }

    .mobile-frame {
        background: #111; border-radius: 40px; box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        color: white; font-family: sans-serif; max-width: 360px;
        margin: 0 auto; position: relative; padding-bottom: 40px; overflow: hidden;
    }
    .wa-header {
        display: flex; align-items: center; background: #202c33; padding: 10px 12px;
    }
    .wa-profile {
        width: 38px; height: 38px; border-radius: 50%;
        background: url('https://i.pravatar.cc/301') center/cover no-repeat;
        margin-right: 10px;
    }
    .wa-title small { color: #a9b3b9; }
    .wa-chat {
        background: #121b22 url('https://static.whatsapp.net/rsrc.php/v3/yd/r/wJz6rLATb5l.png') repeat;
        padding: 15px;
    }
    .wa-msg {
        background: #2a3942; padding: 8px 12px; border-radius: 8px;
        margin-bottom: 6px; font-size: 14px; color: #fff;
    }
    .wa-link-preview {
        background: #1e2c33; border-radius: 8px; padding: 8px;
        border-left: 4px solid #25d366;
    }
    .preview-image {
        height: 160px; background-color: #444;
        background-size: cover; background-position: center;
        border-radius: 6px; margin-bottom: 8px;
    }
    .preview-title { font-weight: bold; color: #fff; }
    .preview-desc { color: #ccc; font-size: 13px; }
    .preview-url { font-size: 12px; color: #25d366; }
    #desc-count { font-size: 11px; margin-top: 4px; color: #666; }
    </style>

    <script>
    const titleInput = document.getElementById('input-title');
    const descInput = document.getElementById('input-description');
    const descCount = document.getElementById('desc-count');
    const imageInput = document.getElementById('input-image');
    const slugInput  = document.getElementById('input-slug');

    const titleEl = document.getElementById('preview-title');
    const descEl = document.getElementById('preview-desc');
    const urlEl = document.getElementById('preview-url');
    const imageEl = document.getElementById('preview-image');

    function updatePreview() {
        const title = titleInput.value.trim() || 'Title will appear here';
        const desc  = descInput.value.trim() || 'Description will appear here';
        const slug  = slugInput.value.trim() || 'slug';
        const url   = window.location.origin + '/go/' + slug;
        const img   = imageInput.value.trim();

        titleEl.textContent = title;
        descEl.textContent = desc;
        urlEl.textContent = url;
        descCount.textContent = desc.length + ' / 100 characters';

        const testImg = new Image();
        testImg.onload = () => imageEl.style.backgroundImage = `url('${img}')`;
        testImg.onerror = () => imageEl.style.backgroundImage = "url('https://via.placeholder.com/400x200?text=No+Preview')";
        if (img) testImg.src = img;
        else imageEl.style.backgroundImage = "url('https://via.placeholder.com/400x200?text=No+Preview')";
    }

    [titleInput, descInput, imageInput, slugInput].forEach(el => el.addEventListener('input', updatePreview));
    updatePreview();

    const selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('input[name="selected_slugs[]"]').forEach(cb => cb.checked = this.checked);
        });
    }
    </script>

    <?php
    echo ob_get_clean(); // Final flush
}
