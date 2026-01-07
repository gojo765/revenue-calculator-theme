jQuery(document).ready(function($) {
    let conversationHistory = [];
    
    // Ask AI question - FIXED
    $('#ask-ai').on('click', function() {
        const question = $('#ai-question').val().trim();
        if (!question) {
            addAIMessage('Please enter a question first.', 'error');
            return;
        }
        
        sendAIRequest(question, 'general');
        $('#ai-question').val('');
    });
    
    // Analyze pricing - FIXED
    $('#analyze-pricing').on('click', function() {
        const products = JSON.parse(localStorage.getItem('revenue_calculator_products')) || [];
        if (products.length === 0) {
            addAIMessage('❌ Please add some products first to analyze pricing.', 'error');
            return;
        }
        
        const prompt = "Analyze my current pricing strategy and provide insights";
        sendAIRequest(prompt, 'analysis');
    });
    
    // Get optimization suggestions - FIXED
    $('#suggest-optimization').on('click', function() {
        const products = JSON.parse(localStorage.getItem('revenue_calculator_products')) || [];
        if (products.length === 0) {
            addAIMessage('❌ Please add some products first to get optimization suggestions.', 'error');
            return;
        }
        
        const prompt = "Provide optimization suggestions for my current product lineup";
        sendAIRequest(prompt, 'optimization');
    });
    
    // Market analysis - FIXED
    $('#market-analysis').on('click', function() {
        const products = JSON.parse(localStorage.getItem('revenue_calculator_products')) || [];
        if (products.length === 0) {
            addAIMessage('❌ Please add some products first for market analysis.', 'error');
            return;
        }
        
        const prompt = "Provide market analysis for my product types";
        sendAIRequest(prompt, 'market_analysis');
    });

    // Simple Break-Even Analysis
$('#break-even-analysis').on('click', function() {
    const products = JSON.parse(localStorage.getItem('revenue_calculator_products')) || [];
    
    if (products.length === 0) {
        addAIMessage('Please add some products first.', 'error');
        return;
    }
    
    const prompt = "Calculate simple break-even analysis";
    sendAIRequest(prompt, 'break_even');
});
    
    // Send AI request - FIXED
    function sendAIRequest(prompt, type) {
        const button = $(`#${type === 'general' ? 'ask-ai' : type}`);
        const originalText = button.html();
        button.html('<span class="loading"></span> Processing...');
        button.prop('disabled', true);
        
        // Get current products from localStorage
        const products = JSON.parse(localStorage.getItem('revenue_calculator_products')) || [];
        
        addAIMessage(`**You**: ${prompt}`, 'user');
        
        $.ajax({
            url: ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_assistant',
                nonce: ai_ajax.nonce,
                prompt: prompt,
                type: type,
                products: JSON.stringify(products),
                history: JSON.stringify(conversationHistory.slice(-5))
            },
            success: function(response) {
                if (response.success) {
                    const aiResponse = response.data.response;
                    addAIMessage(aiResponse, 'system');
                    
                    // Store in conversation history
                    conversationHistory.push({ role: 'user', content: prompt });
                    conversationHistory.push({ role: 'assistant', content: aiResponse });
                    
                    // Keep history manageable
                    if (conversationHistory.length > 10) {
                        conversationHistory = conversationHistory.slice(-10);
                    }
                } else {
                    addAIMessage('❌ Error: ' + (response.data?.error || 'Unable to process request. Please try again.'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                addAIMessage('❌ Connection error. Please check your internet connection and try again.', 'error');
            },
            complete: function() {
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    }
    
    // Add message to AI chat - FIXED
    function addAIMessage(message, type = 'system') {
        const messageClass = type === 'user' ? 'user' : 
                           type === 'error' ? 'error' : 'system';
        
        // Format message with line breaks
        const formattedMessage = message.replace(/\n/g, '<br>');
        
        const messageDiv = `
            <div class="ai-message ${messageClass}">
                ${formattedMessage}
            </div>
        `;
        
        $('#ai-messages').append(messageDiv);
        $('#ai-messages').scrollTop($('#ai-messages')[0].scrollHeight);
    }
    
    // Enter key to send message
    $('#ai-question').on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('#ask-ai').click();
        }
    });
    
    // Clear chat button
    $('#ai-messages').after('<button id="clear-chat1" class="btn btn-danger" style="margin-top: 10px; padding: 5px 10px; font-size: 14px;">Clear Chat</button>');
    
    $('#clear-chat1').on('click', function() {
        if (confirm('Clear all chat messages?')) {
            $('#ai-messages').empty();
            conversationHistory = [];
            addAIMessage('🤖 **AI Assistant**: Chat cleared. How can I help you with your pricing strategy today?', 'system');
        }
    });
    
    // Initial AI greeting
    addAIMessage('🤖 **AI Assistant**: Hello! I\'m here to help you optimize your pricing strategy. I can:\n\n1. 📊 Analyze your current pricing\n2. 🚀 Provide optimization suggestions\n3. 🌐 Give market analysis\n4. 💡 Answer pricing-related questions\n\nAdd your products above and click "Analyze Current Pricing" to get started!', 'system');
    
    // Quick question buttons
    const quickQuestions = [
        "What's a good profit margin?",
        "How do I price my products?",
        "Should I lower my prices?",
        "How to increase profit?"
    ];
    
    const quickQuestionsHTML = quickQuestions.map(q => 
        `<button class="quick-question-btn" style="margin: 5px; padding: 5px 10px; background: #e2e8f0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">${q}</button>`
    ).join('');
    
    $('#ai-question').before(`<div class="quick-questions" style="margin-bottom: 10px;">${quickQuestionsHTML}</div>`);
    
    $('.quick-question-btn').on('click', function() {
        const question = $(this).text();
        $('#ai-question').val(question);
        $('#ask-ai').click();
    });
});