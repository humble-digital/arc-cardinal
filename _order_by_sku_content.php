<h2><span>Order</span> by SKU</h2>
<form class="woocommerce-EditAccountForm order-by-sku">
	<h4><strong>Option 1:</strong> Select SKUs Manually</h4>
    Add products one-by-one
    <div class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first ast-animate-input" style="width: 100%; display:block; float:unset;">
        <input type="text" class="woocommerce-Input woocommerce-Input--password input-text" placeholder="SKU" id="sku-input" style="max-width: 200px;"/>
		<?php
		$max = current_user_can( 'sales_representative' ) ? 6 : 1;
		echo '<input type="number" id="qty-input" min="1" value="1" max="' . $max . '" class="woocommerce-Input woocommerce-Input--password input-text" style="max-width: 70px;" />';
		?>
        <button type="button" class="woocommerce-Button button view" onclick="addRow(jQuery('#sku-input').val(), jQuery('#qty-input').val())" style="display: inline-block; margin-top: 5px; margin-left: 0px;">Add
        </button>
    </div>
	<h4><strong>Option 2:</strong> Upload CSV file</h4>
    Import from a CSV file (download template <a href="<?php echo get_theme_file_uri( '/import.csv' ); ?>" target="_blank">here</a>)
    <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide ast-animate-input" style="margin-top: -15px;">
        <input type="file" id="csv-input" class="woocommerce-Input woocommerce-Input--password input-text"/>
        <button type="button" class="woocommerce-Button button view" onclick="uploadCSV()">Upload CSV</button>
    </div>
    <h4>Summary</h4>
    <table id="sku-table" border="1" class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
        <thead>
        <tr>
            <th><span class="nobr">SKU</span></th>
            <th><span class="nobr">Quantity</span></th>
            <th><span class="nobr">Action</span></th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
    <button id="add-to-cart-btn" type="button" class="woocommerce-Button button view" onclick="addToCart()" style="margin-top: 0;">Add to Cart</button>
	<br><br>
</form>
<script>
    function addRow(sku = '', qty = 1) {
        if (sku != '' && qty >= 1) {
            const table = document.getElementById('sku-table').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `<td>${sku}</td><td>${qty}</td><td><button class="button" onclick="removeRow(this)" style="margin-top: 0;">Remove</button></td>`;
        }
    }

    function removeRow(button) {
        button.closest('tr').remove();
    }

    function uploadCSV() {
        const fileInput = document.getElementById('csv-input');
        const file = fileInput.files[0];
        const reader = new FileReader();

        reader.onload = function (e) {
            const lines = e.target.result.split('\n');
            lines.slice(1).forEach(line => { // slice(1) creates a new array that excludes the first row (headings)
                const [sku, qty] = line.split(',');
                if (sku && qty) {
                    addRow(sku, qty);
                }
            });
        };

        reader.readAsText(file);
    }

    function addToCart() {
        const rows = document.getElementById('sku-table').getElementsByTagName('tbody')[0].rows;
        const items = Array.from(rows).map(row => ({
            sku: row.cells[0].innerText,
            qty: row.cells[1].innerText
        }));


        jQuery('#add-to-cart-btn').prop('disabled', true);
        jQuery.ajax({
            url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
            type: 'POST',
            data: {
                action: 'arc_bulk_add_to_cart',
                nonce: '<?=wp_create_nonce( 'arc_bulk_add_to_cart' )?>',
                items: items
            },
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function (data) {
                jQuery('#add-to-cart-btn').prop('disabled', false);
                if (data.success) {
                    alert('Items added to cart!');
                } else {
                    alert('Failed to add items to cart.');
                }
            }
        });
    }
</script>
