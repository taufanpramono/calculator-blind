<?php 

function custom_gorden_blind_form_shortcode() {
    ob_start();
    ?>

    <!-- Main Container -->
    <div id="custom-gorden-blind-app">
        <!-- Header Section -->
        <div class="header-section">
           <div class="header-info">
               <div class="title-judul"> 
                    <h3>Kategori Produk</h3>
               </div>
               <div class="isi-konten">
                    <img id="selected-category-image" src="" alt="Gambar Kategori" style="display:none;">
                    <div class="text-content">
                        <p id="selected-category-name">Belum Dipilih</p>
                        <p id="selected-category-description" style="display:none;"></p>
                        
                    </div>
                </div>
               <div class="bottom-bawah">
                   <button id="select-category-btn" class="button">Pilih Kategori</button>
               </div>
            </div>
            <div class="header-total">
                <h3>Total Biaya</h3>
                <p id="total-cost-top">Rp. 0</p>
            </div>
        </div>

        <!-- Dynamic Section -->
        <div id="dynamic-sections">
            <button id="add-window-btn" class="button">Tambah Jendela</button>
            <button id="add-door-btn" class="button">Tambah Pintu</button>
        </div>
        
        <!-- Dynamic Section 2 -->
        <div id="dynamic-sections-2">
            <div id="sections-list"></div>
        </div>

        <!-- Footer Section -->
        <div class="footer-section">
            <h3>Total Biaya</h3>
            <p id="total-cost-bottom">Rp. 0</p>
            <a id="whatsapp-link" href="#" class="button" target="_blank">Kirim Produk</a>
        </div>
    </div>

    <!-- Modal Pilih Kategori -->
    <div id="category-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <button class="close-modal-btn">x</button>
            <h2>Kategori Produk</h2>
            <table id="category-table">
                <thead>
                    <tr>
                        <th>Nama Kategori</th>
                        <th class="hide-on-mobile">Gambar</th>
                        <th>Deskripsi</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // PHP: Load Kategori Produk
                    $parent_category_slug = 'gorden-blind';
                    $parent_category = get_term_by('slug', $parent_category_slug, 'product_cat');

                    if ($parent_category) {
                        $child_categories = get_terms([
                            'taxonomy' => 'product_cat',
                            'parent' => $parent_category->term_id,
                            'hide_empty' => true,
                        ]);

                        foreach ($child_categories as $child) {
                            $image_url = wp_get_attachment_url(get_term_meta($child->term_id, 'thumbnail_id', true));
                            echo '<tr>';
                            echo '<td>' . esc_html($child->name) . '</td>';
                            echo '<td class="hide-on-mobile"><img src="' . esc_url($image_url) . '" width="50" /></td>';
                            echo '<td>' . esc_html($child->description) . '</td>';
                            echo '<td><button class="select-category-btn" data-category-id="' . esc_attr($child->term_id) . '">Pilih</button></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal Tambahkan Ukuran -->
    <div id="add-dimension-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <button class="close-modal-btn">x</button>
            <h2 id="dimension-modal-title">Tambahkan Ukuran</h2>
            <form id="dimension-form">
                <label for="width">Lebar (cm):</label>
                <input type="number" id="width" name="width" required>
                <label for="height">Tinggi (cm):</label>
                <input type="number" id="height" name="height" required>
                <button type="submit" class="button">Simpan</button>
            </form>
        </div>
    </div>

    <!-- Modal Pilih Produk -->
    <div id="product-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <button class="close-modal-btn">x</button>
            <h2>Pilih Produk</h2>
            <table id="product-table">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Gambar</th>
                        <th>Deskripsi</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            let selectedCategoryId = null;
            let dimensions = [];
            let totalCost = 0;

            // Event: Open Category Modal
            $('#select-category-btn').on('click', function() {
                $('#category-modal').show();
            });
            
             // Close Modal Function
            function closeModal() {
                $('.modal').hide();
            }
            
             // Event: Close Modal
            $(document).on('click', '.close-modal-btn', closeModal);

            // Event: Select Category
            $('.select-category-btn').on('click', function() {
                selectedCategoryId = $(this).data('category-id');
                const categoryName = $(this).closest('tr').find('td:first').text();
                const categoryImage = $(this).closest('tr').find('td:nth-child(2) img').attr('src');
                const categoryDescription = $(this).closest('tr').find('td:nth-child(3)').text();

                // Update selected category name, image, and description
                $('#selected-category-name').text(categoryName);
                $('#selected-category-image').attr('src', categoryImage).show();
                $('#selected-category-description').text(categoryDescription).show();

                $('#category-modal').hide();

                // Load Products using AJAX
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    method: 'POST',
                    data: {
                        action: 'load_products_by_category',
                        category_id: selectedCategoryId,
                    },
                    success: function(response) {
                        $('#product-table tbody').html(response);
                    },
                });
            });


            // Event: Add Dimension
            $('#add-window-btn, #add-door-btn').on('click', function() {
                 if (!selectedCategoryId) {
                    alert('Silakan pilih kategori terlebih dahulu.');
                    return;
                }
                
                const isWindow = $(this).attr('id') === 'add-window-btn';
                $('#dimension-modal-title').text(isWindow ? 'Tambahkan Ukuran Jendela' : 'Tambahkan Ukuran Pintu');
                $('#add-dimension-modal').show();

                // Save Dimension
                $('#dimension-form').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    const width = parseFloat($('#width').val());
                    const height = parseFloat($('#height').val());
                    const type = isWindow ? 'Jendela' : 'Pintu';

                    dimensions.push({ type, width, height, product: null, cost: 0, quantity: 1 });
                    renderSections();
                    $('#add-dimension-modal').hide();
                });
            });

            // Render Sections
            function renderSections() {
            const list = $('#sections-list').empty();
            dimensions.forEach((item, index) => {
                const section = $(`
                    <div class="section">
                        <div class="section-header">
                            <h4>${index + 1}. ${item.type} - Ukuran ${item.width}cm x ${item.height}cm</h4>
                            <button class="delete-section-btn" data-index="${index}">x</button>
                        </div>
                        <button class="select-product-btn button" data-index="${index}">Pilih Produk</button>
                        ${item.product ? `
                        <div class="product-details">
                            <span>${item.product}</span>
                            <span>Rp. ${item.cost.toLocaleString('id-ID')}</span>
                            <input type="number" class="quantity-input" data-index="${index}" value="${item.quantity}" min="1">
                            <span>Rp. ${(item.cost * item.quantity).toLocaleString('id-ID')}</span>
                        </div>` : ''}
                    </div>
                `);
                list.append(section);
            });
            calculateTotal();
        }


           // Event: Select Product
           $(document).on('click', '.select-product-btn', function() {
               const index = $(this).data('index');
               $('#product-modal').show();

               // Select Product
               $('#product-table tbody').off('click').on('click', '.choose-product-btn', function() {
                   const productName = $(this).data('name');
                   const productPrice = parseFloat($(this).data('price')); // Harga asli produk
                   const widthInMeters = dimensions[index].width / 100; // Konversi ke meter
                   const heightInMeters = dimensions[index].height / 100; // Konversi ke meter
                   const area = widthInMeters * heightInMeters; // Hitung luas area

                   // Kalkulasi harga
                   if (area < 1) {
                       dimensions[index].cost = productPrice; // Gunakan harga asli produk
                   } else {
                       dimensions[index].cost = area * productPrice; // Gunakan formula
                   }

                   dimensions[index].product = productName;
                   renderSections();
                   $('#product-modal').hide();
               });
           });


            // Event: Change Quantity
            $(document).on('change', '.quantity-input', function() {
                const index = $(this).data('index');
                const quantity = parseInt($(this).val());
                dimensions[index].quantity = quantity > 0 ? quantity : 1;
                renderSections();
            });

            // Event: Delete Section
            $(document).on('click', '.delete-section-btn', function() {
                const index = $(this).data('index');
                dimensions.splice(index, 1);
                renderSections();
            });

            // Calculate Total
            function calculateTotal() {
            let totalCost = 0;
            let whatsappMessage = "Detail Pesanan:%0A"; // Awal pesan WhatsApp

            dimensions.forEach((item, index) => {
                if (item.product) {
                    const productCost = item.cost * item.quantity;
                    totalCost += productCost;
                    whatsappMessage += `${index + 1}. ${item.product} (${item.type} - ${item.width}cm x ${item.height}cm) - Rp. ${item.cost.toLocaleString('id-ID')} x ${item.quantity} pcs = Rp. ${productCost.toLocaleString('id-ID')}%0A`;
                }
            });

            whatsappMessage += `%0ATotal Biaya: Rp. ${totalCost.toLocaleString('id-ID')}`; // Tambahkan total di akhir
            $('#total-cost-top, #total-cost-bottom').text('Rp. ' + totalCost.toLocaleString('id-ID'));
            $('#whatsapp-link').attr('href', `https://wa.me/6289516567728?text=${whatsappMessage}`);
        }


      });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_gorden_blind_form', 'custom_gorden_blind_form_shortcode');

// AJAX Handler for Products
function load_products_by_category() {
    $category_id = intval($_POST['category_id']);
    $args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category_id,
            ],
        ],
    ];
    $products = new WP_Query($args);
    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            $price = get_post_meta(get_the_ID(), '_price', true);
            echo '<tr>';
            echo '<td>' . get_the_title() . '</td>';
            echo '<td>' . wc_price($price) . '</td>';
            echo '<td><img src="' . get_the_post_thumbnail_url() . '" width="50" /></td>';
            echo '<td>' . wp_trim_words(get_the_excerpt(), 10) . '</td>';
            echo '<td><button class="choose-product-btn" data-name="' . get_the_title() . '" data-price="' . esc_attr($price) . '">Pilih</button></td>';
            echo '</tr>';
        }
    }
    wp_die();
}
add_action('wp_ajax_load_products_by_category', 'load_products_by_category');
add_action('wp_ajax_nopriv_load_products_by_category', 'load_products_by_category');



 ?>