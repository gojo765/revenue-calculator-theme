<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <div class="container">
        <header class="header">

        </header>

        <main class="main-content">
            <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <section class="section calculator-section">
                    <h4>Enter Your Products</h4>

                    
                    <div id="calculator-form">
                        <div class="input-group">
                            <label for="item-name">Item Name *</label>
                            <input type="text" id="item-name" placeholder="Enter product name" required>
                            <small class="form-help">Required field</small>
                        </div>
                        
                        <div class="grid-inputs">
                            <div class="input-group">
                                <label for="item-cost">Cost ($) *</label>
                                <input type="number" id="item-cost" step="0.01" min="0" placeholder="0.00" required>
                                <small class="form-help">Per unit cost</small>
                            </div>
                            
                            <div class="input-group">
                                <label for="item-price">Selling Price ($) *</label>
                                <input type="number" id="item-price" step="0.01" min="0" placeholder="0.00" required>
                                <small class="form-help">Per unit price</small>
                            </div>
                            
                            <div class="input-group">
                                <label for="item-quantity">Quantity *</label>
                                <input type="number" id="item-quantity" min="1" value="1" required>
                                <small class="form-help">Minimum: 1</small>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="add-item" class="btn btn-primary">
                                <span class="btn-icon">➕</span> Add Product
                            </button>
                            <button type="button" id="clear-all" class="btn btn-danger">
                                <span class="btn-icon">🗑️</span> Clear All
                            </button>
                            <button type="button" id="add-sample" class="btn btn-secondary" style="display: none;">
                                <span class="btn-icon">🔧</span> Add Sample
                            </button>
                        </div>
                        
                        <div class="form-tips">
                            <p><strong>💡 Tip:</strong> Press Enter in any field to quickly add product</p>
                            <p><strong>⌨️ Shortcuts:</strong> Ctrl+Enter = Add Product | Ctrl+S = Save | Esc = Clear Form</p>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="products-table" id="products-table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Cost ($)</th>
                                    <th>Price ($)</th>
                                    <th>Qty</th>
                                    <th>Total Cost</th>
                                    <th>Total Revenue</th>
                                    <th>Profit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="products-list">
                                <!-- Products will be added here dynamically -->
                                <tr id="no-products-row">
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <div class="empty-icon">📊</div>
                                            <h3>No products added yet</h3>
                                            <p>Add your first product using the form above</p>
                                            <div class="empty-hint">
                                                Try: <strong>Laptop</strong> - Cost: $800, Price: $999, Quantity: 2
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot id="products-footer" style="display: none;">
                                <tr>
                                    <td colspan="4" class="text-right"><strong>Totals:</strong></td>
                                    <td id="footer-total-cost"><strong>$0.00</strong></td>
                                    <td id="footer-total-revenue"><strong>$0.00</strong></td>
                                    <td id="footer-total-profit"><strong>$0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="table-stats" id="table-stats" style="display: none;">
                        <div class="stats-item">
                            <span class="stats-label">Products:</span>
                            <span class="stats-value" id="stats-product-count">0</span>
                        </div>
                        <div class="stats-item">
                            <span class="stats-label">Total Items:</span>
                            <span class="stats-value" id="stats-total-quantity">0</span>
                        </div>
                        <div class="stats-item">
                            <span class="stats-label">Avg Margin:</span>
                            <span class="stats-value" id="stats-avg-margin">0%</span>
                        </div>
                    </div>
                </section>

                <section class="section summary-section">
                    <h4>Summary & Preview</h4>
                    
                    <div class="summary-cards">
                        <div class="summary-card">
                            <div class="summary-icon">💰</div>
                            <h3>Total Cost</h3>
                            <div class="value" id="total-cost">$0.00</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon">📈</div>
                            <h3>Total Revenue</h3>
                            <div class="value" id="total-revenue">$0.00</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon">💵</div>
                            <h3>Total Profit</h3>
                            <div class="value" id="total-profit">$0.00</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-icon">📊</div>
                            <h3>Profit Margin</h3>
                            <div class="value" id="profit-margin">0%</div>
                            <div class="summary-subtext">
                                <span id="margin-status" class="status-neutral">Neutral</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profit-breakdown" id="profit-breakdown" style="display: none;">
                        <h3>Profit Breakdown</h3>
                        <div class="breakdown-chart">
                            <div class="chart-bar">
                                <div class="bar-segment cost-segment" style="width: 0%;" title="Cost"></div>
                                <div class="bar-segment profit-segment" style="width: 0%;" title="Profit"></div>
                            </div>
                            <div class="chart-labels">
                                <span class="chart-label">Cost: <span id="chart-cost">$0</span></span>
                                <span class="chart-label">Profit: <span id="chart-profit">$0</span></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="actions">
                        <div class="action-group">
                            <h4>Export Reports</h4>
                            <div class="export-buttons">
                                <button id="export-csv" class="btn btn-secondary export-btn" disabled>
                                    <span class="btn-icon">📊</span> CSV
                                </button>
                                <button id="export-excel" class="btn btn-secondary export-btn" disabled>
                                    <span class="btn-icon">📈</span> Excel
                                </button>
                                <button id="export-pdf" class="btn btn-secondary export-btn" disabled>
                                    <span class="btn-icon">📄</span> PDF
                                </button>
                            </div>
                        </div>
                        

                    </div>
                    
                    <div class="quick-actions">
                        <button id="quick-clear" class="btn btn-outline" style="display: none;">
                            Clear All Data
                        </button>
                        <button id="quick-export" class="btn btn-outline" style="display: none;">
                            Quick Export
                        </button>
                    </div>
                </section>

                <section class="section ai-section">
                    <h4>AI Pricing Assistant</h4>
                    
                    <div class="ai-header">
                        <div class="ai-status">
                            <span class="status-indicator online"></span>
                            <span class="status-text">AI Assistant Online</span>
                        </div>
                        <button id="clear-chat1" class="btn btn-small">
                            <span class="btn-icon">🗑️</span> Clear Chat
                        </button>
                    </div>
                    
                    <div class="ai-messages-container">
                        <div class="ai-messages" id="ai-messages">
                            <div class="ai-message system">
                                <div class="message-header">
                                    <span class="message-sender">🤖 AI Assistant</span>
                                    <span class="message-time">Just now</span>
                                </div>
                                <div class="message-content">
                                    <p><strong>Hello! I'm here to help you optimize your pricing strategy.</strong></p>
                                    <p>I can help you with:</p>
                                    <ul>
                                        <li>📊 Analyzing your current pricing and profit margins</li>
                                        <li>🚀 Providing optimization suggestions</li>
                                        <li>🌐 Market analysis and competitive insights</li>
                                        <li>💡 Answering pricing-related questions</li>
                                    </ul>
                                    <p>Add your products above and click "Analyze Current Pricing" to get started!</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ai-typing" id="ai-typing" style="display: none;">
                            <div class="typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span>AI is thinking...</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ai-input-container">
                        <div class="quick-questions">
                            <p class="quick-questions-title">💡 Quick Questions:</p>
                            <div class="quick-question-buttons">
                                <button class="quick-question-btn" data-question="What's a good profit margin?">
                                    Profit Margins
                                </button>
                                <button class="quick-question-btn" data-question="How do I price my products?">
                                    Pricing Strategy
                                </button>
                                <button class="quick-question-btn" data-question="Should I lower my prices?">
                                    Price Optimization
                                </button>
                                <button class="quick-question-btn" data-question="How to increase profit?">
                                    Increase Profits
                                </button>
                            </div>
                        </div>
                        
                        <div class="ai-input-area">
                            <textarea id="ai-question" placeholder="Ask about pricing, margins, or get optimization suggestions..." rows="2"></textarea>
                            <button id="ask-ai" class="btn btn-primary">
                                <span class="btn-icon">✨</span> Ask AI
                            </button>
                        </div>
                        
                        <div class="ai-actions">
                            <button id="analyze-pricing" class="btn btn-secondary">
                                <span class="btn-icon">📊</span> Analyze Current Pricing
                            </button>
                            <button id="suggest-optimization" class="btn btn-secondary">
                                <span class="btn-icon">🚀</span> Get Optimization Suggestions
                            </button>
                            <button id="market-analysis" class="btn btn-secondary">
                                <span class="btn-icon">🌐</span> Market Analysis
                            </button>
                            <button id="break-even-analysis" class="btn btn-secondary">
                                <span class="btn-icon">⚖️</span> Break-Even Analysis
                            </button>
                        </div>
                    </div>
                    
                    <div class="ai-tips">
                        <p><strong>Pro Tips:</strong></p>
                        <ul>
                            <li>Add at least 2 products for comprehensive analysis</li>
                            <li>Use "Analyze Current Pricing" after adding products</li>
                            <li>Click quick questions for instant answers</li>
                            <li>Export reports to share with your team</li>
                        </ul>
                    </div>
                </section>
                
                <?php the_content(); ?>
            <?php endwhile; endif; ?>
        </main>
        
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-info">
                    <h3><?php bloginfo('name'); ?></h3>
                    <p><?php bloginfo('description'); ?></p>
                </div>
                <div class="footer-links">
                    <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
                    <p>Revenue Calculator Theme v1.0</p>
                </div>
            </div>
        </footer>
    </div>

    <?php wp_footer(); ?>
    

</body>
</html>