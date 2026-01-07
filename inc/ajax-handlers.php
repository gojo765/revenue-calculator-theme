<?php
/**
 * AJAX Handlers for Revenue Calculator - COMPLETE FIXED VERSION
 */

// Save calculation
function save_calculation() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'calculator_ajax_nonce')) {
        wp_send_json_error(array('error' => 'Security check failed. Please refresh the page.'));
        wp_die();
    }
    
    // Get and validate data
    $products = array();
    if (isset($_POST['products'])) {
        $products_data = stripslashes($_POST['products']);
        $products = json_decode($products_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $products = array();
        }
    }
    
    $totals = array(
        'totalCost' => isset($_POST['totals']['totalCost']) ? floatval($_POST['totals']['totalCost']) : 0,
        'totalRevenue' => isset($_POST['totals']['totalRevenue']) ? floatval($_POST['totals']['totalRevenue']) : 0,
        'totalProfit' => isset($_POST['totals']['totalProfit']) ? floatval($_POST['totals']['totalProfit']) : 0
    );
    
    // Prepare response
    $data = array(
        'success' => true,
        'message' => 'Calculation saved successfully',
        'timestamp' => current_time('mysql'),
        'summary' => array(
            'total_products' => count($products),
            'total_cost' => $totals['totalCost'],
            'total_revenue' => $totals['totalRevenue'],
            'total_profit' => $totals['totalProfit']
        )
    );
    
    wp_send_json_success($data);
    wp_die();
}

// Export spreadsheet
function export_spreadsheet() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'calculator_ajax_nonce')) {
        wp_send_json_error(array('error' => 'Security check failed. Please refresh the page.'));
        wp_die();
    }
    
    $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
    
    // Get products data
    $products = array();
    if (isset($_POST['products'])) {
        $products_data = stripslashes($_POST['products']);
        $products = json_decode($products_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $products = array();
        }
    }
    
    // Get totals
    $totals = array(
        'totalCost' => isset($_POST['totals']['totalCost']) ? floatval($_POST['totals']['totalCost']) : 0,
        'totalRevenue' => isset($_POST['totals']['totalRevenue']) ? floatval($_POST['totals']['totalRevenue']) : 0,
        'totalProfit' => isset($_POST['totals']['totalProfit']) ? floatval($_POST['totals']['totalProfit']) : 0
    );
    
    // Check if we have products
    if (empty($products)) {
        wp_send_json_error(array('error' => 'No products to export. Please add some products first.'));
        wp_die();
    }
    
    // Handle different formats
    if ($format === 'csv' || $format === 'excel') {
        export_csv($products, $totals, $format);
    } elseif ($format === 'pdf') {
        export_pdf_html($products, $totals);
    }
    
    wp_die();
}

// Export CSV/Excel
function export_csv($products, $totals, $format) {
    // Set headers for download
    $filename = 'revenue-calculator-' . date('Y-m-d-H-i-s') . '.csv';
    
    if ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        $filename = str_replace('.csv', '.xls', $filename);
    } else {
        header('Content-Type: text/csv');
    }
    
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output BOM for UTF-8
    echo "\xEF\xBB\xBF";
    
    // Output CSV header
    echo "Item Name,Cost (\$),Selling Price (\$),Quantity,Total Cost,Total Revenue,Profit,Profit Margin (%)\n";
    
    // Output products
    foreach ($products as $product) {
        $total_cost = floatval($product['cost']) * intval($product['quantity']);
        $total_revenue = floatval($product['price']) * intval($product['quantity']);
        $profit = $total_revenue - $total_cost;
        $profit_margin = $product['price'] > 0 ? (($product['price'] - $product['cost']) / $product['price'] * 100) : 0;
        
        echo sprintf(
            '"%s",%.2f,%.2f,%d,%.2f,%.2f,%.2f,%.2f%%',
            str_replace('"', '""', $product['name']),
            floatval($product['cost']),
            floatval($product['price']),
            intval($product['quantity']),
            $total_cost,
            $total_revenue,
            $profit,
            $profit_margin
        ) . "\n";
    }
    
    // Output summary
    echo "\n";
    echo "SUMMARY\n";
    echo sprintf('Total Cost,$%.2f', $totals['totalCost']) . "\n";
    echo sprintf('Total Revenue,$%.2f', $totals['totalRevenue']) . "\n";
    echo sprintf('Total Profit,$%.2f', $totals['totalProfit']) . "\n";
    
    $profit_margin_total = $totals['totalRevenue'] > 0 ? ($totals['totalProfit'] / $totals['totalRevenue'] * 100) : 0;
    echo sprintf('Profit Margin,%.2f%%', $profit_margin_total) . "\n";
}

// Export PDF (HTML version for printing)
function export_pdf_html($products, $totals) {
    // Generate HTML content
    $html = generate_pdf_html_content($products, $totals);
    
    // Return HTML as JSON response
    wp_send_json_success(array(
        'content' => $html,
        'filename' => 'revenue-calculator-' . date('Y-m-d-H-i-s') . '.html'
    ));
}

// Generate PDF HTML content
function generate_pdf_html_content($products, $totals) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Revenue Calculator Report</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Arial', sans-serif; 
                line-height: 1.6; 
                color: #333; 
                padding: 20px;
                background: #fff;
            }
            .report-container { max-width: 1000px; margin: 0 auto; }
            .header { 
                text-align: center; 
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #667eea;
            }
            .header h1 { 
                color: #2d3748; 
                font-size: 28px;
                margin-bottom: 10px;
            }
            .header .date { 
                color: #718096; 
                font-size: 14px;
            }
            .section { margin-bottom: 30px; }
            .section h2 { 
                color: #4a5568; 
                margin-bottom: 15px;
                padding-bottom: 8px;
                border-bottom: 1px solid #e2e8f0;
                font-size: 20px;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 15px 0;
                font-size: 14px;
            }
            table th { 
                background: #f7fafc; 
                padding: 12px 10px; 
                text-align: left; 
                border: 1px solid #e2e8f0;
                font-weight: 600;
                color: #4a5568;
            }
            table td { 
                padding: 10px; 
                border: 1px solid #e2e8f0;
                text-align: right;
            }
            table td:first-child { text-align: left; }
            .profit-positive { color: #48bb78; font-weight: bold; }
            .profit-negative { color: #f56565; font-weight: bold; }
            .summary-cards { 
                display: grid; 
                grid-template-columns: repeat(2, 1fr); 
                gap: 15px; 
                margin: 20px 0;
            }
            @media (min-width: 768px) {
                .summary-cards { grid-template-columns: repeat(4, 1fr); }
            }
            .summary-card { 
                background: #f8f9fa; 
                padding: 20px; 
                border-radius: 8px; 
                text-align: center;
                border: 1px solid #e2e8f0;
            }
            .summary-card h3 { 
                font-size: 14px; 
                color: #718096; 
                margin-bottom: 10px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .summary-card .value { 
                font-size: 24px; 
                font-weight: 700; 
                color: #2d3748;
            }
            .footer { 
                text-align: center; 
                margin-top: 40px; 
                padding-top: 20px;
                border-top: 1px solid #e2e8f0;
                color: #718096;
                font-size: 12px;
            }
            .print-actions { 
                text-align: center; 
                margin-top: 30px;
                padding: 20px;
                background: #f7fafc;
                border-radius: 8px;
            }
            .print-btn { 
                padding: 12px 24px; 
                background: #667eea; 
                color: white; 
                border: none; 
                border-radius: 5px; 
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                margin: 0 10px;
                transition: background 0.3s;
            }
            .print-btn:hover { background: #5a67d8; }
            .close-btn { 
                padding: 12px 24px; 
                background: #f56565; 
                color: white; 
                border: none; 
                border-radius: 5px; 
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                margin: 0 10px;
                transition: background 0.3s;
            }
            .close-btn:hover { background: #e53e3e; }
            
            @media print {
                .print-actions { display: none; }
                body { padding: 0; }
                .header { margin-bottom: 20px; }
                .section { margin-bottom: 20px; }
            }
        </style>
    </head>
    <body>
        <div class="report-container">
            <div class="header">
                <h1>Revenue Calculator Report</h1>
                <div class="date">Generated: <?php echo date('F j, Y, g:i a'); ?></div>
            </div>
            
            <div class="section">
                <h2>Product Details</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Cost ($)</th>
                            <th>Price ($)</th>
                            <th>Quantity</th>
                            <th>Total Cost</th>
                            <th>Total Revenue</th>
                            <th>Profit</th>
                            <th>Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): 
                            $total_cost = floatval($product['cost']) * intval($product['quantity']);
                            $total_revenue = floatval($product['price']) * intval($product['quantity']);
                            $profit = $total_revenue - $total_cost;
                            $margin = $product['price'] > 0 ? (($product['price'] - $product['cost']) / $product['price'] * 100) : 0;
                            $profit_class = $profit >= 0 ? 'profit-positive' : 'profit-negative';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>$<?php echo number_format($product['cost'], 2); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td>$<?php echo number_format($total_cost, 2); ?></td>
                            <td>$<?php echo number_format($total_revenue, 2); ?></td>
                            <td class="<?php echo $profit_class; ?>">$<?php echo number_format($profit, 2); ?></td>
                            <td><?php echo number_format($margin, 2); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h2>Summary</h2>
                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Total Cost</h3>
                        <div class="value">$<?php echo number_format($totals['totalCost'], 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Total Revenue</h3>
                        <div class="value">$<?php echo number_format($totals['totalRevenue'], 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Total Profit</h3>
                        <div class="value <?php echo $totals['totalProfit'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                            $<?php echo number_format($totals['totalProfit'], 2); ?>
                        </div>
                    </div>
                    <div class="summary-card">
                        <h3>Profit Margin</h3>
                        <?php 
                            $profit_margin = $totals['totalRevenue'] > 0 ? ($totals['totalProfit'] / $totals['totalRevenue'] * 100) : 0;
                            $margin_class = $profit_margin >= 20 ? 'profit-positive' : ($profit_margin > 0 ? '' : 'profit-negative');
                        ?>
                        <div class="value <?php echo $margin_class; ?>">
                            <?php echo number_format($profit_margin, 2); ?>%
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="print-actions">
                <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
                <button class="close-btn" onclick="window.close()">❌ Close Window</button>
            </div>
            
            <div class="footer">
                <p>Generated by Revenue Calculator WordPress Theme</p>
                <p>Report ID: RC-<?php echo date('Ymd-His'); ?></p>
            </div>
        </div>
        
        <script>
            // Auto-focus print button
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.print-btn').focus();
            });
            
            // Close window with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    window.close();
                }
            });
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// AI Assistant Handler
function ai_assistant_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_assistant_ajax_nonce')) {
        wp_send_json_error(array('error' => 'Security check failed. Please refresh the page.'));
        wp_die();
    }
    
    $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'general';
    
    if (empty($prompt) && $type === 'general') {
        wp_send_json_error(array('error' => 'Please enter a question or select an analysis type.'));
        wp_die();
    }
    
    // Get products data
    $products = array();
    if (isset($_POST['products'])) {
        $products_data = stripslashes($_POST['products']);
        $products = json_decode($products_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $products = array();
        }
    }
    
    // Process the request
    $response = process_ai_request($prompt, $type, $products);
    
    wp_send_json_success(array('response' => $response));
    wp_die();
}

// Process AI request
function process_ai_request($prompt, $type, $products = array()) {
    // Convert prompt to lowercase for easier matching
    $lower_prompt = strtolower($prompt);
    
    // If no specific type but we have products, analyze them
    if ($type === 'general' && !empty($products)) {
        $type = 'analysis';
    }
    
    switch ($type) {
        case 'analysis':
            return analyze_pricing($products, $prompt);
        case 'optimization':
            return provide_optimization_suggestions($products);
        case 'market_analysis':
            return provide_market_analysis($products);
        case 'break_even':  // Add this line
            return provide_break_even_analysis($products); 
        default:
            return provide_general_response($prompt, $products);
    }
}

// Analyze pricing
function analyze_pricing($products, $prompt) {
    if (empty($products)) {
        return "❌ **No Products Found**\n\nI don't see any products to analyze. Please add some products using the calculator above, then try again.";
    }
    
    $analysis = "📊 **PRICING ANALYSIS REPORT**\n\n";
    
    // Calculate statistics
    $total_cost = 0;
    $total_revenue = 0;
    $total_profit = 0;
    $product_count = count($products);
    
    $margins = array();
    $profit_per_product = array();
    
    foreach ($products as $product) {
        $cost = floatval($product['cost']) * intval($product['quantity']);
        $revenue = floatval($product['price']) * intval($product['quantity']);
        $profit = $revenue - $cost;
        $margin = $product['price'] > 0 ? (($product['price'] - $product['cost']) / $product['price'] * 100) : 0;
        
        $total_cost += $cost;
        $total_revenue += $revenue;
        $total_profit += $profit;
        $margins[] = $margin;
        $profit_per_product[] = array(
            'name' => $product['name'],
            'profit' => $profit,
            'margin' => $margin
        );
    }
    
    $avg_margin = count($margins) > 0 ? array_sum($margins) / count($margins) : 0;
    $overall_margin = $total_revenue > 0 ? ($total_profit / $total_revenue * 100) : 0;
    
    // Format numbers
    $formatted_cost = number_format($total_cost, 2);
    $formatted_revenue = number_format($total_revenue, 2);
    $formatted_profit = number_format($total_profit, 2);
    $formatted_avg_margin = number_format($avg_margin, 2);
    $formatted_overall_margin = number_format($overall_margin, 2);
    
    $analysis .= "📈 **KEY METRICS**\n";
    $analysis .= "```\n";
    $analysis .= "Total Products: " . $product_count . "\n";
    $analysis .= "Total Cost: $" . $formatted_cost . "\n";
    $analysis .= "Total Revenue: $" . $formatted_revenue . "\n";
    $analysis .= "Total Profit: $" . $formatted_profit . "\n";
    $analysis .= "Average Margin: " . $formatted_avg_margin . "%\n";
    $analysis .= "Overall Margin: " . $formatted_overall_margin . "%\n";
    $analysis .= "```\n\n";
    
    // Health assessment
    $analysis .= "💡 **HEALTH ASSESSMENT**\n";
    
    if ($overall_margin >= 40) {
        $analysis .= "✅ **Excellent!** Your profit margin is above 40%, which is outstanding.\n";
        $analysis .= "   - Consider premium positioning\n";
        $analysis .= "   - Expand product line with similar margins\n";
    } elseif ($overall_margin >= 30) {
        $analysis .= "👍 **Very Good!** Your margin is above 30%, which is healthy.\n";
        $analysis .= "   - Maintain current strategy\n";
        $analysis .= "   - Consider small price increases\n";
    } elseif ($overall_margin >= 20) {
        $analysis .= "📊 **Good.** Your margin is above 20%, which is acceptable.\n";
        $analysis .= "   - Room for optimization\n";
        $analysis .= "   - Review supplier costs\n";
    } elseif ($overall_margin >= 10) {
        $analysis .= "⚠️ **Fair.** Your margin is between 10-20%, which is low.\n";
        $analysis .= "   - Immediate optimization needed\n";
        $analysis .= "   - Consider price increases\n";
    } elseif ($overall_margin >= 0) {
        $analysis .= "❌ **Poor.** Your margin is below 10%, which is unsustainable.\n";
        $analysis .= "   - Urgent action required\n";
        $analysis .= "   - Review entire pricing strategy\n";
    } else {
        $analysis .= "🔥 **Critical!** You're operating at a loss.\n";
        $analysis .= "   - Immediate intervention needed\n";
        $analysis .= "   - Consider discontinuing unprofitable products\n";
    }
    
    $analysis .= "\n";
    
    // Product performance
    if ($product_count > 1) {
        // Sort by profit margin
        usort($profit_per_product, function($a, $b) {
            return $b['margin'] <=> $a['margin'];
        });
        
        $analysis .= "🏆 **TOP PERFORMERS** (by margin)\n";
        for ($i = 0; $i < min(3, $product_count); $i++) {
            $profit = $profit_per_product[$i];
            $analysis .= ($i + 1) . ". **" . $profit['name'] . "**: " . number_format($profit['margin'], 2) . "% margin\n";
        }
        
        $analysis .= "\n";
        
        // Bottom performers
        if ($product_count > 3) {
            $analysis .= "📉 **NEEDS ATTENTION**\n";
            $bottom_count = min(3, $product_count - 3);
            for ($i = $product_count - $bottom_count; $i < $product_count; $i++) {
                $profit = $profit_per_product[$i];
                if ($profit['margin'] < 20) {
                    $analysis .= "• **" . $profit['name'] . "**: " . number_format($profit['margin'], 2) . "% margin\n";
                }
            }
            $analysis .= "\n";
        }
    }
    
    // Recommendations
    $analysis .= "🚀 **RECOMMENDATIONS**\n";
    
    if ($overall_margin < 20) {
        $analysis .= "1. **Increase Prices**: Test 10-15% price increases on low-margin products\n";
        $analysis .= "2. **Cost Reduction**: Negotiate with suppliers or find alternatives\n";
        $analysis .= "3. **Product Mix**: Focus on higher-margin products\n";
    } elseif ($overall_margin < 30) {
        $analysis .= "1. **Optimize Pricing**: Fine-tune prices based on demand\n";
        $analysis .= "2. **Volume Discounts**: Offer bulk pricing to increase sales\n";
        $analysis .= "3. **Value Add**: Bundle products or add services\n";
    } else {
        $analysis .= "1. **Maintain Strategy**: Current pricing is working well\n";
        $analysis .= "2. **Expand Offerings**: Introduce complementary products\n";
        $analysis .= "3. **Premium Options**: Consider higher-priced variants\n";
    }
    
    $analysis .= "\n💭 **Quick Tip**: " . get_random_pricing_tip();
    
    return $analysis;
}

// Provide optimization suggestions
function provide_optimization_suggestions($products) {
    if (empty($products)) {
        return "❌ **No Products Found**\n\nI don't see any products to optimize. Please add some products using the calculator above, then try again.";
    }
    
    $suggestions = "🚀 **OPTIMIZATION SUGGESTIONS**\n\n";
    
    foreach ($products as $index => $product) {
        $margin = $product['price'] > 0 ? (($product['price'] - $product['cost']) / $product['price'] * 100) : 0;
        $current_profit = ($product['price'] - $product['cost']) * $product['quantity'];
        
        $suggestions .= "**" . ($index + 1) . ". " . $product['name'] . "**\n";
        $suggestions .= "   Current Margin: " . number_format($margin, 2) . "%\n";
        $suggestions .= "   Current Profit: $" . number_format($current_profit, 2) . "\n";
        
        if ($margin < 15) {
            $suggestions .= "   ⚠️ **CRITICAL OPTIMIZATION NEEDED**\n";
            $suggestions .= "   • **Target Price**: $" . number_format($product['cost'] * 1.35, 2) . " (35% margin)\n";
            $suggestions .= "   • **Action**: Increase price immediately or find new supplier\n";
            $suggestions .= "   • **Potential Profit**: $" . number_format(($product['cost'] * 1.35 - $product['cost']) * $product['quantity'], 2) . "\n";
        } elseif ($margin < 25) {
            $suggestions .= "   📈 **OPTIMIZATION OPPORTUNITY**\n";
            $suggestions .= "   • **Test Price**: $" . number_format($product['cost'] * 1.4, 2) . " (40% margin)\n";
            $suggestions .= "   • **Action**: A/B test price increase\n";
            $suggestions .= "   • **Volume Strategy**: Offer 10% discount on 5+ units\n";
        } elseif ($margin < 40) {
            $suggestions .= "   👍 **HEALTHY MARGIN**\n";
            $suggestions .= "   • **Maintain**: Current pricing is good\n";
            $suggestions .= "   • **Upsell**: Create premium bundle\n";
            $suggestions .= "   • **Cross-sell**: Pair with complementary products\n";
        } else {
            $suggestions .= "   ✅ **EXCELLENT MARGIN**\n";
            $suggestions .= "   • **Premium Position**: Consider luxury branding\n";
            $suggestions .= "   • **Expand**: Introduce related products\n";
            $suggestions .= "   • **Loyalty**: Create subscription/repeat purchase program\n";
        }
        
        $suggestions .= "\n";
    }
    
    // General strategies
    $suggestions .= "🔧 **GENERAL STRATEGIES**\n";
    $suggestions .= "1. **Tiered Pricing**:\n";
    $suggestions .= "   - 1 unit: Full price\n";
    $suggestions .= "   - 3-5 units: 10% discount\n";
    $suggestions .= "   - 6+ units: 15% discount\n\n";
    
    $suggestions .= "2. **Seasonal Adjustments**:\n";
    $suggestions .= "   - High season: +10-15%\n";
    $suggestions .= "   - Low season: -5-10%\n";
    $suggestions .= "   - Clearance: -20-30%\n\n";
    
    $suggestions .= "3. **Competitive Analysis**:\n";
    $suggestions .= "   - Monitor 3 competitors monthly\n";
    $suggestions .= "   - Price 5-10% below premium competitors\n";
    $suggestions .= "   - Match budget competitors\n\n";
    
    $suggestions .= "4. **Cost Management**:\n";
    $suggestions .= "   - Quarterly supplier review\n";
    $suggestions .= "   - Bulk purchasing discounts\n";
    $suggestions .= "   - Alternative sourcing options\n";
    
    return $suggestions;
}

// Provide market analysis
function provide_market_analysis($products) {
    if (empty($products)) {
        return "❌ **No Products Found**\n\nI don't see any products to analyze. Please add some products using the calculator above, then try again.";
    }
    
    $product_names = array();
    $categories = array();
    
    foreach ($products as $product) {
        $product_names[] = $product['name'];
        
        // Simple category detection based on product name
        $name_lower = strtolower($product['name']);
        if (strpos($name_lower, 'phone') !== false || strpos($name_lower, 'laptop') !== false || 
            strpos($name_lower, 'tablet') !== false || strpos($name_lower, 'computer') !== false) {
            $categories['electronics'] = true;
        } elseif (strpos($name_lower, 'shirt') !== false || strpos($name_lower, 'pant') !== false || 
                  strpos($name_lower, 'dress') !== false || strpos($name_lower, 'shoe') !== false) {
            $categories['apparel'] = true;
        } elseif (strpos($name_lower, 'book') !== false || strpos($name_lower, 'course') !== false || 
                  strpos($name_lower, 'ebook') !== false) {
            $categories['education'] = true;
        } else {
            $categories['other'] = true;
        }
    }
    
    $category_list = array_keys($categories);
    
    $analysis = "🌐 **MARKET ANALYSIS REPORT**\n\n";
    
    $analysis .= "📦 **PRODUCTS ANALYZED**\n";
    $analysis .= "```\n";
    foreach ($product_names as $name) {
        $analysis .= "• " . $name . "\n";
    }
    $analysis .= "```\n\n";
    
    $analysis .= "🏷️ **CATEGORIES DETECTED**\n";
    $analysis .= implode(", ", $category_list) . "\n\n";
    
    $analysis .= "📊 **INDUSTRY BENCHMARKS**\n";
    
    if (isset($categories['electronics'])) {
        $analysis .= "**Electronics**:\n";
        $analysis .= "• Typical Margin: 15-30%\n";
        $analysis .= "• Competitive: Very High\n";
        $analysis .= "• Price Sensitivity: High\n";
        $analysis .= "• Seasonality: Q4 peak\n\n";
    }
    
    if (isset($categories['apparel'])) {
        $analysis .= "**Apparel**:\n";
        $analysis .= "• Typical Margin: 40-60%\n";
        $analysis .= "• Competitive: High\n";
        $analysis .= "• Price Sensitivity: Medium\n";
        $analysis .= "• Seasonality: Strong (fashion cycles)\n\n";
    }
    
    if (isset($categories['education'])) {
        $analysis .= "**Education/Books**:\n";
        $analysis .= "• Typical Margin: 30-50%\n";
        $analysis .= "• Competitive: Medium\n";
        $analysis .= "• Price Sensitivity: Low-Medium\n";
        $analysis .= "• Seasonality: Back-to-school (Aug-Sep)\n\n";
    }
    
    if (isset($categories['other'])) {
        $analysis .= "**General Retail**:\n";
        $analysis .= "• Typical Margin: 25-45%\n";
        $analysis .= "• Competitive: Varies\n";
        $analysis .= "• Price Sensitivity: Medium\n";
        $analysis .= "• Seasonality: Moderate\n\n";
    }
    
    $analysis .= "🎯 **COMPETITIVE POSITIONING**\n";
    $analysis .= "1. **Price Leadership**: Compete on lowest price (requires volume)\n";
    $analysis .= "2. **Value Premium**: Higher price for better quality/service\n";
    $analysis .= "3. **Niche Focus**: Specialized products with loyal customers\n";
    $analysis .= "4. **Convenience**: Easy access, fast delivery\n\n";
    
    $analysis .= "📅 **SEASONAL CONSIDERATIONS**\n";
    $analysis .= "**Q1 (Jan-Mar)**: Post-holiday sales, consider 10-15% discounts\n";
    $analysis .= "**Q2 (Apr-Jun)**: Steady demand, test new price points\n";
    $analysis .= "**Q3 (Jul-Sep)**: Back-to-school/fall prep, increase marketing\n";
    $analysis .= "**Q4 (Oct-Dec)**: Peak season, premium pricing possible\n\n";
    
    $analysis .= "🔍 **RECOMMENDED ACTIONS**\n";
    $analysis .= "1. **Weekly**: Check competitor prices (3 main competitors)\n";
    $analysis .= "2. **Monthly**: Review sales data and adjust prices\n";
    $analysis .= "3. **Quarterly**: Full competitive analysis\n";
    $analysis .= "4. **Annually**: Complete market positioning review\n\n";
    
    $analysis .= "📈 **MARKET TRENDS**\n";
    $analysis .= "• **E-commerce Growth**: Online sales increasing 10-15% annually\n";
    $analysis .= "• **Mobile Shopping**: 60-70% of traffic from mobile devices\n";
    $analysis .= "• **Price Transparency**: Customers compare prices easily\n";
    $analysis .= "• **Value Focus**: Quality and service matter more than ever\n";
    
    return $analysis;
}

// SIMPLE BREAK-EVEN ANALYSIS
function provide_break_even_analysis($products) {
    if (empty($products)) {
        return "Please add products first to calculate break-even.";
    }
    
    $analysis = "**Break-Even Analysis**\n\n";
    
    // Calculate basic numbers
    $total_cost = 0;
    $total_revenue = 0;
    $total_units = 0;
    
    foreach ($products as $product) {
        $cost = floatval($product['cost']);
        $price = floatval($product['price']);
        $quantity = intval($product['quantity']);
        
        $total_cost += $cost * $quantity;
        $total_revenue += $price * $quantity;
        $total_units += $quantity;
    }
    
    // Simple calculations
    $profit = $total_revenue - $total_cost;
    $avg_price = $total_units > 0 ? $total_revenue / $total_units : 0;
    $avg_cost = $total_units > 0 ? $total_cost / $total_units : 0;
    
    // Simple break-even (without fixed costs)
    $break_even_units = 0;
    if ($avg_price > $avg_cost) {
        $break_even_units = ceil($total_cost / ($avg_price - $avg_cost));
    }
    
    // Current status
    $analysis .= "**Current Situation:**\n";
    $analysis .= "• Total Products: " . count($products) . "\n";
    $analysis .= "• Total Units: " . $total_units . "\n";
    $analysis .= "• Total Revenue: $" . number_format($total_revenue, 2) . "\n";
    $analysis .= "• Total Cost: $" . number_format($total_cost, 2) . "\n";
    $analysis .= "• Current Profit: $" . number_format($profit, 2) . "\n\n";
    
    // Break-even info
    $analysis .= "**Break-Even Point:**\n";
    
    if ($avg_price > $avg_cost) {
        $analysis .= "• Average Price per unit: $" . number_format($avg_price, 2) . "\n";
        $analysis .= "• Average Cost per unit: $" . number_format($avg_cost, 2) . "\n";
        $analysis .= "• Profit per unit: $" . number_format($avg_price - $avg_cost, 2) . "\n";
        $analysis .= "• Need to sell: **" . $break_even_units . " units** to break even\n";
        $analysis .= "• Break-even revenue: **$" . number_format($break_even_units * $avg_price, 2) . "**\n\n";
        
        // Are we above or below break-even?
        if ($total_units >= $break_even_units) {
            $units_above = $total_units - $break_even_units;
            $analysis .= "✅ **Good news!** You're already above break-even by " . $units_above . " units.\n";
        } else {
            $units_needed = $break_even_units - $total_units;
            $analysis .= "⚠️ **You need to sell " . $units_needed . " more units** to break even.\n";
        }
    } else {
        $analysis .= "❌ **Cannot break even** - Your average cost ($" . number_format($avg_cost, 2) . ") is higher than your average price ($" . number_format($avg_price, 2) . ")\n";
        $analysis .= "You're losing money on each sale. Consider:\n";
        $analysis .= "1. Increase prices\n";
        $analysis .= "2. Reduce costs\n";
        $analysis .= "3. Stop selling unprofitable products\n";
    }
    
    $analysis .= "\n**Simple Tips:**\n";
    
    if ($profit > 0) {
        $analysis .= "1. You're making a profit! Keep it up.\n";
        $analysis .= "2. Try to sell " . ceil($break_even_units * 0.2) . " more units for a safety buffer.\n";
        $analysis .= "3. Focus on your best-selling products.\n";
    } else if ($profit == 0) {
        $analysis .= "1. You're at break-even point.\n";
        $analysis .= "2. Try to increase prices by 5-10%.\n";
        $analysis .= "3. Look for ways to reduce costs.\n";
    } else {
        $analysis .= "1. You're losing money.\n";
        $analysis .= "2. Urgent: Increase prices or reduce costs.\n";
        $analysis .= "3. Focus only on profitable products.\n";
    }
    
    return $analysis;
}

// Provide general response
function provide_general_response($prompt, $products = array()) {
    $lower_prompt = strtolower($prompt);
    
    // Common questions and answers
    if (strpos($lower_prompt, 'profit margin') !== false || strpos($lower_prompt, 'margin') !== false) {
        return "💡 **PROFIT MARGIN EXPLAINED**\n\n" .
               "**Formula**: (Selling Price - Cost) ÷ Selling Price × 100%\n\n" .
               "**Industry Standards**:\n" .
               "• **<20%**: Low - Needs optimization\n" .
               "• **20-30%**: Standard - Healthy for most businesses\n" .
               "• **30-40%**: Good - Sustainable growth\n" .
               "• **40-50%**: Excellent - Premium positioning\n" .
               "• **>50%**: Outstanding - Luxury/niche\n\n" .
               "**Tips**:\n" .
               "1. Calculate ALL costs (shipping, packaging, fees)\n" .
               "2. Aim for minimum 25% margin after expenses\n" .
               "3. Higher-margin products subsidize lower ones\n\n" .
               "Want me to analyze your specific profit margins? Add your products above and click 'Analyze Current Pricing'.";
    }
    
    if (strpos($lower_prompt, 'price') !== false || strpos($lower_prompt, 'cost') !== false || 
        strpos($lower_prompt, 'pricing') !== false) {
        return "💰 **PRICING STRATEGY GUIDE**\n\n" .
               "**4 Main Methods**:\n\n" .
               "1. **Cost-Plus**: Cost + Desired Margin\n" .
               "   *Example*: $10 cost + 40% margin = $16.67 price\n\n" .
               "2. **Value-Based**: What customers will pay\n" .
               "   *Example*: Software saves 10 hours × $50/hour = $500 value\n\n" .
               "3. **Competitor-Based**: Match/beat competitors\n" .
               "   *Example*: Competitor charges $99, you charge $89\n\n" .
               "4. **Psychological**: $9.99 vs $10.00\n" .
               "   *Example*: $197 feels less than $200\n\n" .
               "**For your question**: '{$prompt}'\n\n" .
               "I recommend:\n" .
               "1. Calculate your break-even point\n" .
               "2. Research 3-5 competitor prices\n" .
               "3. Test 2-3 price points\n" .
               "4. Monitor sales for 2-4 weeks\n" .
               "5. Adjust based on data\n\n" .
               "Need specific help with your products? Add them above!";
    }
    
    if (strpos($lower_prompt, 'profit') !== false && strpos($lower_prompt, 'increase') !== false) {
        return "📈 **INCREASE PROFITS**\n\n" .
               "**5 Ways to Boost Profits**:\n\n" .
               "1. **Increase Prices** (5-10% test)\n" .
               "   *Impact*: Direct margin improvement\n" .
               "   *Risk*: May lose price-sensitive customers\n\n" .
               "2. **Reduce Costs**\n" .
               "   *Options*: Bulk buying, supplier negotiation\n" .
               "   *Impact*: Immediate margin increase\n" .
               "   *Risk*: Quality may suffer\n\n" .
               "3. **Increase Volume**\n" .
               "   *Options*: Marketing, promotions, SEO\n" .
               "   *Impact*: Spreads fixed costs\n" .
               "   *Risk*: Requires investment\n\n" .
               "4. **Upsell/Cross-sell**\n" .
               "   *Options*: Bundles, add-ons, premium versions\n" .
               "   *Impact*: Higher average order value\n" .
               "   *Risk*: Can complicate buying process\n\n" .
               "5. **Improve Efficiency**\n" .
               "   *Options*: Automate, streamline processes\n" .
               "   *Impact*: Reduces overhead\n" .
               "   *Risk*: Implementation costs\n\n" .
               "**Quick Wins**:\n" .
               "• Add $1 to your lowest-priced item\n" .
               "• Offer 3-item bundle at 10% discount\n" .
               "• Negotiate 5% better terms with top supplier";
    }
    
    if (strpos($lower_prompt, 'break even') !== false) {
        return "⚖️ **BREAK-EVEN ANALYSIS**\n\n" .
               "**Formula**: Fixed Costs ÷ (Price - Variable Cost per Unit)\n\n" .
               "**Example**:\n" .
               "• Fixed Costs: $1,000/month (rent, utilities, etc.)\n" .
               "• Price: $50\n" .
               "• Variable Cost: $20/unit\n" .
               "• Break-even: $1,000 ÷ ($50 - $20) = 34 units\n\n" .
               "**Tips**:\n" .
               "1. Include ALL fixed costs\n" .
               "2. Track variable costs accurately\n" .
               "3. Recalculate when costs change\n" .
               "4. Aim for 2-3× break-even for safety\n\n" .
               "Need help calculating your break-even point? Add your products above!";
    }
    
    // Default responses
    $responses = array(
        "🤖 **AI ASSISTANT**\n\n" .
        "I understand you're asking about: **'{$prompt}'**\n\n" .
        "Based on pricing strategy best practices:\n\n" .
        "1. **Know Your Costs**: Include everything (COGS, shipping, fees, overhead)\n" .
        "2. **Understand Your Market**: Research competitors and customer willingness to pay\n" .
        "3. **Test and Adjust**: Start with a price, monitor sales, adjust as needed\n" .
        "4. **Communicate Value**: Explain why your price is justified\n" .
        "5. **Review Regularly**: Markets change, so should your prices\n\n" .
        "Would you like me to analyze your specific products? Add them above and click 'Analyze Current Pricing'.",

        "💭 **PRICING INSIGHTS**\n\n" .
        "Regarding your question: **'{$prompt}'**\n\n" .
        "**Key Principles**:\n\n" .
        "• **Perceived Value > Actual Cost**: Customers pay for benefits, not costs\n" .
        "• **Price Anchoring**: Show a higher price first to make actual price seem better\n" .
        "• **Decoy Effect**: Offer 3 options where the middle one looks best\n" .
        "• **Price Sensitivity**: Some markets care more about price than others\n\n" .
        "**Action Steps**:\n" .
        "1. Document all your costs\n" .
        "2. Research 5 competitor prices\n" .
        "3. Survey customers on value perception\n" .
        "4. Test 2-3 price points\n" .
        "5. Analyze results after 30 days\n\n" .
        "Ready for a specific analysis? Add your products above!",

        "🔍 **PRICING STRATEGY**\n\n" .
        "For your query: **'{$prompt}'**\n\n" .
        "**Successful pricing considers**:\n\n" .
        "📊 **Costs**: Fixed + variable + overhead\n" .
        "🏢 **Competition**: Market price ranges and positioning\n" .
        "👥 **Customers**: Willingness to pay and price sensitivity\n" .
        "💎 **Value**: Unique benefits and differentiators\n" .
        "🎯 **Goals**: Profit targets and market share objectives\n\n" .
        "**Common Mistakes to Avoid**:\n" .
        "❌ Pricing based only on costs\n" .
        "❌ Copying competitor prices without understanding their costs\n" .
        "❌ Not testing different price points\n" .
        "❌ Forgetting to account for all expenses\n" .
        "❌ Not adjusting prices over time\n\n" .
        "Want personalized advice? Add your products to the calculator!"
    );
    
    return $responses[array_rand($responses)];
}

// Helper function for random tips
function get_random_pricing_tip() {
    $tips = array(
        "Try 'charm pricing' - prices ending in .99 or .97 often perform better",
        "Test a 5% price increase on your best-selling product next month",
        "Create a premium bundle at 15-20% higher perceived value",
        "Monitor competitor prices every Tuesday when many stores update",
        "Offer seasonal discounts 2-3 weeks before inventory refresh",
        "Implement tiered pricing (Basic/Pro/Premium) for different segments",
        "Use A/B testing to find the optimal price point for each product",
        "Consider psychological pricing ($19.99 vs $20 creates a big perception difference)",
        "Review and renegotiate supplier contracts every 6 months",
        "Create urgency with limited-time pricing (48-hour flash sale)",
        "Offer payment plans to make higher-priced items more accessible",
        "Bundle slow-moving items with popular ones to clear inventory",
        "Test free shipping with minimum order vs built-in shipping costs",
        "Use customer surveys to understand price sensitivity",
        "Implement loyalty pricing for repeat customers"
    );
    
    return $tips[array_rand($tips)];
}