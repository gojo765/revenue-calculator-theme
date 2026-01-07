jQuery(document).ready(function($) {
    // Initialize products array from localStorage
    let products = [];
    try {
        const storedProducts = localStorage.getItem('revenue_calculator_products');
        if (storedProducts) {
            products = JSON.parse(storedProducts);
        }
    } catch (e) {
        console.error('Error loading products from localStorage:', e);
        products = [];
    }
    
    // DOM Elements
    const $itemName = $('#item-name');
    const $itemCost = $('#item-cost');
    const $itemPrice = $('#item-price');
    const $itemQuantity = $('#item-quantity');
    const $addButton = $('#add-item');
    const $clearButton = $('#clear-all');
    const $productsList = $('#products-list');
    const $totalCost = $('#total-cost');
    const $totalRevenue = $('#total-revenue');
    const $totalProfit = $('#total-profit');
    const $profitMargin = $('#profit-margin');
    const $saveButton = $('#save-calculation');
    const $exportCsv = $('#export-csv');
    const $exportExcel = $('#export-excel');
    const $exportPdf = $('#export-pdf');
    
    // Initialize calculator on page load
    function initCalculator() {
        console.log('Initializing calculator with', products.length, 'products');
        updateProductsTable();
        updateSummary();
        setupEventListeners();
        
        // Log initial state for debugging
        console.log('Initial products:', products);
    }
    
    // Setup all event listeners
    function setupEventListeners() {
        console.log('Setting up event listeners');
        
        // Add product button
        $addButton.off('click').on('click', function(e) {
            e.preventDefault();
            addProduct();
        });
        
        // Clear all button
        $clearButton.off('click').on('click', function(e) {
            e.preventDefault();
            clearAllProducts();
        });
        
        // Save calculation button
        $saveButton.off('click').on('click', function(e) {
            e.preventDefault();
            saveCalculation();
        });
        
        // Export buttons
        $exportCsv.off('click').on('click', function(e) {
            e.preventDefault();
            exportSpreadsheet('csv');
        });
        
        $exportExcel.off('click').on('click', function(e) {
            e.preventDefault();
            exportSpreadsheet('excel');
        });
        
        $exportPdf.off('click').on('click', function(e) {
            e.preventDefault();
            exportSpreadsheet('pdf');
        });
        
        // Enter key support in form fields
        $itemName.on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                addProduct();
            }
        });
        
        $itemCost.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                addProduct();
            }
        });
        
        $itemPrice.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                addProduct();
            }
        });
        
        $itemQuantity.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                addProduct();
            }
        });
        
        // Event delegation for dynamically created remove buttons
        $(document).on('click', '.remove-product-btn', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            if (productId) {
                removeProduct(productId);
            }
        });
    }
    
    // Add a new product - FIXED CALCULATIONS
    function addProduct() {
        console.log('Adding product...');
        
        // Get values from form
        const name = $itemName.val().trim();
        const cost = parseFloat($itemCost.val()) || 0;
        const price = parseFloat($itemPrice.val()) || 0;
        const quantity = parseInt($itemQuantity.val()) || 1;
        
        console.log('Form values:', { name, cost, price, quantity });
        
        // Validate inputs
        if (!name) {
            showAlert('Please enter a product name', 'error');
            $itemName.focus();
            return;
        }
        
        if (isNaN(cost) || cost < 0) {
            showAlert('Please enter a valid cost (0 or greater)', 'error');
            $itemCost.focus();
            return;
        }
        
        if (isNaN(price) || price < 0) {
            showAlert('Please enter a valid selling price (0 or greater)', 'error');
            $itemPrice.focus();
            return;
        }
        
        if (isNaN(quantity) || quantity < 1) {
            showAlert('Please enter a valid quantity (1 or greater)', 'error');
            $itemQuantity.focus();
            return;
        }
        
        // Calculate derived values CORRECTLY
        const totalCost = parseFloat((cost * quantity).toFixed(2));
        const totalRevenue = parseFloat((price * quantity).toFixed(2));
        const profit = parseFloat((totalRevenue - totalCost).toFixed(2));
        
        console.log('Calculated values:', { totalCost, totalRevenue, profit });
        
        // Create product object WITH ALL PROPERTIES
        const product = {
            id: 'prod_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            name: name,
            cost: parseFloat(cost.toFixed(2)),
            price: parseFloat(price.toFixed(2)),
            quantity: quantity,
            totalCost: totalCost,
            totalRevenue: totalRevenue,
            profit: profit,
            added: new Date().toISOString()
        };
        
        console.log('Created product object:', product);
        
        // Add to products array
        products.push(product);
        
        // Update UI
        saveProducts();
        updateProductsTable();
        updateSummary();
        
        // Clear form and show success message
        clearForm();
        showAlert(`"${name}" added successfully!`, 'success');
        
        // Focus back to name field
        $itemName.focus();
    }
    
    // Remove a product
    function removeProduct(productId) {
        console.log('Removing product:', productId);
        
        if (!confirm('Are you sure you want to remove this product?')) {
            return;
        }
        
        // Filter out the product to remove
        const initialLength = products.length;
        products = products.filter(p => p.id !== productId);
        
        if (products.length < initialLength) {
            // Update UI
            saveProducts();
            updateProductsTable();
            updateSummary();
            showAlert('Product removed successfully', 'success');
        } else {
            showAlert('Product not found', 'error');
        }
    }
    
    // Clear all products
    function clearAllProducts() {
        if (products.length === 0) {
            showAlert('No products to clear', 'info');
            return;
        }
        
        if (confirm(`Are you sure you want to remove ALL ${products.length} products? This cannot be undone.`)) {
            products = [];
            saveProducts();
            updateProductsTable();
            updateSummary();
            showAlert('All products cleared', 'success');
        }
    }
    
    // Update the products table - FIXED TO SHOW ALL DATA
    function updateProductsTable() {
        console.log('Updating table with', products.length, 'products');
        
        // Clear the table body
        $productsList.empty();
        
        // If no products, show empty state
        if (products.length === 0) {
            $productsList.html(`
                <tr class="empty-state">
                    <td colspan="8">
                        <div style="text-align: center; padding: 40px 20px; color: #718096;">
                            <div style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;">📊</div>
                            <div style="font-size: 18px; font-weight: 500; margin-bottom: 10px;">No products yet</div>
                            <div style="font-size: 14px; opacity: 0.7;">Add your first product using the form above</div>
                            <div style="margin-top: 20px; font-size: 13px; color: #a0aec0;">
                                Try: <strong>Laptop</strong> - Cost: $800, Price: $999, Quantity: 2
                            </div>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }
        
        // Add each product as a table row
        products.forEach((product, index) => {
            const profitClass = product.profit >= 0 ? 'profit-positive' : 'profit-negative';
            const profitDisplay = product.profit >= 0 ? 
                `+$${Math.abs(product.profit).toFixed(2)}` : 
                `-$${Math.abs(product.profit).toFixed(2)}`;
            
            // DEBUG: Log each product's data
            console.log(`Product ${index}:`, {
                name: product.name,
                cost: product.cost,
                price: product.price,
                quantity: product.quantity,
                totalCost: product.totalCost,
                totalRevenue: product.totalRevenue,
                profit: product.profit
            });
            
            // Create the table row HTML with ALL DATA
            const rowHtml = `
                <tr class="product-row" data-product-id="${product.id}">
                    <td class="product-name">
                        <strong>${escapeHtml(product.name)}</strong>
                    </td>
                    <td class="product-cost">$${product.cost?.toFixed(2) || '0.00'}</td>
                    <td class="product-price">$${product.price?.toFixed(2) || '0.00'}</td>
                    <td class="product-quantity">${product.quantity || 1}</td>
                    <td class="product-total-cost">$${product.totalCost?.toFixed(2) || '0.00'}</td>
                    <td class="product-total-revenue">$${product.totalRevenue?.toFixed(2) || '0.00'}</td>
                    <td class="product-profit ${profitClass}">
                        <strong>${profitDisplay}</strong>
                    </td>
                    <td class="product-actions">
                        <button type="button" 
                                class="btn btn-danger remove-product-btn"
                                data-product-id="${product.id}"
                                title="Remove this product"
                                style="padding: 6px 12px; font-size: 13px;">
                            🗑️ Remove
                        </button>
                    </td>
                </tr>
            `;
            
            // Append the row to the table
            $productsList.append(rowHtml);
        });
        
        console.log('Table updated with', products.length, 'rows');
    }
    
    // Update the summary section
    function updateSummary() {
        console.log('Updating summary');
        
        // Calculate totals
        const totals = calculateTotals();
        
        console.log('Summary totals:', totals);
        
        // Update summary cards
        $totalCost.text('$' + totals.totalCost.toFixed(2));
        $totalRevenue.text('$' + totals.totalRevenue.toFixed(2));
        $totalProfit.text('$' + totals.totalProfit.toFixed(2));
        
        // Update profit color
        if (totals.totalProfit >= 0) {
            $totalProfit.removeClass('profit-negative').addClass('profit-positive');
        } else {
            $totalProfit.removeClass('profit-positive').addClass('profit-negative');
        }
        
        // Calculate and update profit margin
        const profitMargin = totals.totalRevenue > 0 ? 
            (totals.totalProfit / totals.totalRevenue * 100) : 0;
        $profitMargin.text(profitMargin.toFixed(2) + '%');
        
        // Update profit margin color
        if (profitMargin >= 30) {
            $profitMargin.removeClass('profit-negative').addClass('profit-positive');
        } else if (profitMargin >= 10) {
            $profitMargin.css('color', '#d69e2e'); // Yellow/amber for medium
        } else if (profitMargin > 0) {
            $profitMargin.css('color', '#ed8936'); // Orange for low
        } else {
            $profitMargin.removeClass('profit-positive').addClass('profit-negative');
        }
        
        // Update export button states
        updateExportButtons();
    }
    
    // Calculate totals from all products
    function calculateTotals() {
        let totalCost = 0;
        let totalRevenue = 0;
        let totalProfit = 0;
        
        products.forEach(product => {
            totalCost += product.totalCost || 0;
            totalRevenue += product.totalRevenue || 0;
            totalProfit += product.profit || 0;
        });
        
        return {
            totalCost: parseFloat(totalCost.toFixed(2)),
            totalRevenue: parseFloat(totalRevenue.toFixed(2)),
            totalProfit: parseFloat(totalProfit.toFixed(2))
        };
    }
    
    // Save calculation to server via AJAX
    function saveCalculation() {
        if (products.length === 0) {
            showAlert('No products to save. Please add some products first.', 'error');
            return;
        }
        
        const totals = calculateTotals();
        
        // Show loading state
        const originalText = $saveButton.html();
        $saveButton.html('<span class="loading-spinner"></span> Saving...');
        $saveButton.prop('disabled', true);
        
        // Prepare data for AJAX request
        const data = {
            action: 'save_calculation',
            nonce: calculator_ajax.nonce,
            products: JSON.stringify(products),
            totals: totals
        };
        
        console.log('Saving calculation:', data);
        
        // Send AJAX request
        $.ajax({
            url: calculator_ajax.ajax_url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                console.log('Save response:', response);
                if (response.success) {
                    showAlert('✅ Calculation saved successfully!', 'success');
                } else {
                    showAlert('❌ Error: ' + (response.data?.error || 'Failed to save'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error, xhr.responseText);
                showAlert('❌ Connection error. Please try again.', 'error');
            },
            complete: function() {
                $saveButton.html(originalText);
                $saveButton.prop('disabled', false);
            }
        });
    }
    
    // Export spreadsheet in different formats
    function exportSpreadsheet(format) {
        if (products.length === 0) {
            showAlert('No products to export. Please add some products first.', 'error');
            return;
        }
        
        const totals = calculateTotals();
        const $exportButton = $(`#export-${format}`);
        const originalText = $exportButton.html();
        
        // Show loading state
        $exportButton.html('<span class="loading-spinner"></span> Generating...');
        $exportButton.prop('disabled', true);
        
        // Prepare data
        const data = {
            action: 'export_spreadsheet',
            nonce: calculator_ajax.nonce,
            format: format,
            products: JSON.stringify(products),
            totals: totals
        };
        
        console.log('Exporting:', format, 'with', products.length, 'products');
        
        // For CSV/Excel: Direct form submission
        if (format === 'csv' || format === 'excel') {
            // Create a temporary form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = calculator_ajax.ajax_url;
            form.style.display = 'none';
            form.target = '_blank';
            
            // Add all data fields
            Object.keys(data).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = data[key];
                form.appendChild(input);
            });
            
            // Submit the form
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            // Show success message
            setTimeout(() => {
                showAlert(`✅ ${format.toUpperCase()} file downloaded!`, 'success');
                $exportButton.html(originalText);
                $exportButton.prop('disabled', false);
            }, 1000);
            
            return;
        }
        
        // For PDF: Use AJAX to get HTML content
        if (format === 'pdf') {
            $.ajax({
                url: calculator_ajax.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Open HTML in new window for printing
                        const newWindow = window.open('', '_blank');
                        if (newWindow) {
                            newWindow.document.write(response.data.content);
                            newWindow.document.close();
                            showAlert('✅ PDF report opened in new window. Use "Print" to save as PDF.', 'success');
                        } else {
                            showAlert('❌ Please allow popups to view the PDF report.', 'error');
                        }
                    } else {
                        showAlert('❌ Error: ' + (response.data?.error || 'Failed to generate PDF'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('PDF export error:', error);
                    
                    // Fallback: Generate simple HTML report
                    const htmlContent = generateSimpleHTMLReport(products, totals);
                    const newWindow = window.open('', '_blank');
                    if (newWindow) {
                        newWindow.document.write(htmlContent);
                        newWindow.document.close();
                        showAlert('✅ Basic report opened. Use "Print" to save as PDF.', 'success');
                    } else {
                        showAlert('❌ Please allow popups to view the report.', 'error');
                    }
                },
                complete: function() {
                    $exportButton.html(originalText);
                    $exportButton.prop('disabled', false);
                }
            });
        }
    }
    
    // Generate simple HTML report (fallback for PDF)
    function generateSimpleHTMLReport(products, totals) {
        let html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Revenue Calculator Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
                h2 { color: #555; margin-top: 30px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background: #f5f5f5; padding: 12px; text-align: left; border: 1px solid #ddd; font-weight: bold; }
                td { padding: 10px; border: 1px solid #ddd; }
                .profit { color: #4CAF50; font-weight: bold; }
                .loss { color: #f44336; font-weight: bold; }
                .summary { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .summary-item { margin: 10px 0; }
                .print-btn { padding: 12px 24px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 20px 10px; }
                .close-btn { background: #f44336; }
                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body>
            <h1>💰 Revenue Calculator Report</h1>
            <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
            <p><strong>Total Products:</strong> ${products.length}</p>
            
            <h2>Product Details</h2>
            <table>
                <tr>
                    <th>Product Name</th>
                    <th>Cost</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total Cost</th>
                    <th>Total Revenue</th>
                    <th>Profit</th>
                </tr>`;
        
        // Add product rows
        products.forEach(product => {
            const profitClass = product.profit >= 0 ? 'profit' : 'loss';
            html += `
                <tr>
                    <td>${escapeHtml(product.name)}</td>
                    <td>$${product.cost?.toFixed(2) || '0.00'}</td>
                    <td>$${product.price?.toFixed(2) || '0.00'}</td>
                    <td>${product.quantity || 1}</td>
                    <td>$${product.totalCost?.toFixed(2) || '0.00'}</td>
                    <td>$${product.totalRevenue?.toFixed(2) || '0.00'}</td>
                    <td class="${profitClass}">$${product.profit?.toFixed(2) || '0.00'}</td>
                </tr>`;
        });
        
        // Add summary
        const profitMargin = totals.totalRevenue > 0 ? (totals.totalProfit / totals.totalRevenue * 100) : 0;
        html += `
            </table>
            
            <div class="summary">
                <h2>📊 Summary</h2>
                <div class="summary-item"><strong>Total Cost:</strong> $${totals.totalCost.toFixed(2)}</div>
                <div class="summary-item"><strong>Total Revenue:</strong> $${totals.totalRevenue.toFixed(2)}</div>
                <div class="summary-item"><strong>Total Profit:</strong> $${totals.totalProfit.toFixed(2)}</div>
                <div class="summary-item"><strong>Profit Margin:</strong> ${profitMargin.toFixed(2)}%</div>
            </div>
            
            <div class="no-print" style="text-align: center; margin-top: 40px;">
                <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
                <button class="print-btn close-btn" onclick="window.close()">✕ Close</button>
                <p style="margin-top: 20px; color: #666; font-size: 14px;">
                    Tip: In the print dialog, select "Save as PDF" to create a PDF file.
                </p>
            </div>
            
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; color: #888; font-size: 12px; text-align: center;">
                Generated by Revenue Calculator WordPress Theme
            </div>
        </body>
        </html>`;
        
        return html;
    }
    
    // Update export button states based on product count
    function updateExportButtons() {
        const hasProducts = products.length > 0;
        const buttons = [$exportCsv, $exportExcel, $exportPdf, $saveButton];
        
        buttons.forEach(button => {
            button.prop('disabled', !hasProducts);
            if (!hasProducts) {
                button.css('opacity', '0.6');
            } else {
                button.css('opacity', '1');
            }
        });
    }
    
    // Save products to localStorage
    function saveProducts() {
        try {
            localStorage.setItem('revenue_calculator_products', JSON.stringify(products));
            console.log('Products saved to localStorage:', products);
        } catch (e) {
            console.error('Error saving to localStorage:', e);
            showAlert('Warning: Could not save products locally.', 'warning');
        }
    }
    
    // Clear the form
    function clearForm() {
        $itemName.val('');
        $itemCost.val('');
        $itemPrice.val('');
        $itemQuantity.val(1);
    }
    
    // Show alert/notification message
    function showAlert(message, type = 'info') {
        // Remove any existing alerts
        $('.alert-notification').remove();
        
        const alertClass = type === 'error' ? 'alert-error' : 
                          type === 'warning' ? 'alert-warning' : 
                          type === 'success' ? 'alert-success' : 'alert-info';
        
        const icon = type === 'error' ? '❌' : 
                    type === 'warning' ? '⚠️' : 
                    type === 'success' ? '✅' : 'ℹ️';
        
        const alert = $(`
            <div class="alert-notification ${alertClass}" style="
                padding: 12px 16px;
                margin: 10px 0;
                border-radius: 6px;
                animation: fadeIn 0.3s;
                display: flex;
                align-items: center;
                gap: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            ">
                <span style="font-size: 18px;">${icon}</span>
                <span>${escapeHtml(message)}</span>
            </div>
        `);
        
        // Insert at the top of the calculator section
        $('.calculator-section').prepend(alert);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Helper function to escape HTML special characters
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Quick test function
    function addTestProduct() {
        const testProduct = {
            id: 'test_' + Date.now(),
            name: 'Test Laptop',
            cost: 800,
            price: 999,
            quantity: 2,
            totalCost: 1600,
            totalRevenue: 1998,
            profit: 398,
            added: new Date().toISOString()
        };
        
        products.push(testProduct);
        saveProducts();
        updateProductsTable();
        updateSummary();
        showAlert('Test product added!', 'success');
    }
    
    // Initialize the calculator
    initCalculator();
    
    // Add CSS for styling
    $('head').append(`
        <style>
            .loading-spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-right: 8px;
                vertical-align: middle;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .alert-error {
                background: #fee;
                border-left: 4px solid #f00;
                color: #c00;
            }
            
            .alert-success {
                background: #efe;
                border-left: 4px solid #0a0;
                color: #080;
            }
            
            .alert-warning {
                background: #ffe;
                border-left: 4px solid #fa0;
                color: #850;
            }
            
            .alert-info {
                background: #eef;
                border-left: 4px solid #08c;
                color: #058;
            }
            
            .product-row:hover {
                background-color: #f8f9fa;
            }
            
            .profit-positive {
                color: #48bb78;
                font-weight: bold;
            }
            
            .profit-negative {
                color: #f56565;
                font-weight: bold;
            }
            
            .btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            /* Make table data more visible */
            #products-table td {
                padding: 12px 8px;
                border-bottom: 1px solid #e2e8f0;
                font-size: 14px;
            }
            
            #products-table td.product-cost,
            #products-table td.product-price,
            #products-table td.product-total-cost,
            #products-table td.product-total-revenue,
            #products-table td.product-profit {
                font-family: 'Courier New', monospace;
                font-weight: 600;
            }
            
            #products-table th {
                background: #4a5568;
                color: white;
                padding: 14px 8px;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 12px;
                letter-spacing: 0.5px;
            }
        </style>
    `);
    
    // Add debug button
    setTimeout(() => {
        if ($('.calculator-section').length) {
            
            
            $('#test-add-btn').on('click', addTestProduct);
            
            $('#view-data-btn').on('click', function() {
                console.log('=== CURRENT PRODUCTS ===');
                console.log(products);
                console.log('=== LOCALSTORAGE ===');
                console.log(JSON.parse(localStorage.getItem('revenue_calculator_products') || '[]'));
                console.log('=== TABLE ROWS ===');
                console.log($('#products-list tr').length, 'rows');
                alert('Check browser console (F12) for data');
            });
            
            $('#clear-debug-btn').on('click', function() {
                if (confirm('Clear all products?')) {
                    products = [];
                    saveProducts();
                    updateProductsTable();
                    updateSummary();
                    showAlert('All products cleared', 'success');
                }
            });
        }
    }, 1000);
});