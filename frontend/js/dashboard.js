document.addEventListener('DOMContentLoaded', function() {
    // auth.js handles redirection, so we can assume if we are here,
    // the user is authenticated.

    const usernameSpan = document.getElementById('username');
    const user = JSON.parse(localStorage.getItem('user'));

    if (user && user.username) {
        usernameSpan.textContent = user.username;
    }

    document.getElementById('logout-btn').addEventListener('click', window.handleLogout);

    const trackButtons = document.querySelectorAll('.track-button');

    trackButtons.forEach(button => {
        button.addEventListener('click', function() {
            handleButtonClick(this);
        });
    });

    async function handleButtonClick(button) {
        const token = localStorage.getItem('access_token');
        const buttonId = button.id;
        
        // Disable button and show spinner
        button.disabled = true;
        button.classList.add('loading');

        try {
            const response = await fetch(API_BASE_URL + 'clicks/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ button_id: buttonId })
            });

            if (response.ok) {
                console.log(`Click on ${buttonId} tracked successfully.`);
            } else {
                const errorData = await response.json();
                console.error(`Error tracking click on ${buttonId}:`, errorData.message);
                // Optionally show an error message to the user
            }

        } catch (error) {
            console.error('Network or server error:', error);
            // Optionally show an error message to the user
        } finally {
            // Re-enable button and hide spinner
            button.disabled = false;
            button.classList.remove('loading');
        }
    }

    // AI Insight Generation
    const getInsightBtn = document.getElementById('get-ai-insight-btn');
    getInsightBtn.addEventListener('click', async function() {
        this.disabled = true;
        this.textContent = 'Analyzing...';

        try {
            const response = await fetch(API_BASE_URL + 'ai/analyze_clicks', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const insightContainer = document.getElementById('ai-insight-container');
            const insightDiv = document.getElementById('ai-insight');

            if (response.ok) {
                const data = await response.json();
                insightDiv.textContent = data.analysis;
                insightContainer.style.display = 'block';
            } else {
                const errorData = await response.json();
                insightDiv.textContent = 'Error: ' + (errorData.error || 'Failed to get insight.');
                insightContainer.style.display = 'block';
            }
        } catch (error) {
            console.error('AI insight error:', error);
            const insightContainer = document.getElementById('ai-insight-container');
            const insightDiv = document.getElementById('ai-insight');
            insightDiv.textContent = 'An unexpected error occurred.';
            insightContainer.style.display = 'block';
        } finally {
            this.disabled = false;
            this.textContent = 'Get AI Insight';
        }
    });
});
