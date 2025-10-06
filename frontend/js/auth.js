// Function to get a cookie by name
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

const CSRF_URL = API_BASE_URL + 'csrf/token';

async function fetchCsrfToken() {
    const response = await fetch(CSRF_URL, { credentials: 'include' });
    if (!response.ok) {
        throw new Error(`Unable to fetch CSRF token (status ${response.status})`);
    }
    const data = await response.json();
    if (!data?.csrf_token) {
        throw new Error('CSRF token missing in response');
    }
    return data.csrf_token;
}

// Validate token with backend
const token = localStorage.getItem('access_token');
if (!token) {
    window.location.href = 'login.html';
} else {
    fetch(API_BASE_URL + 'auth/validate_token', {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`
        }
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        }

        // Token is invalid, try to refresh it
        const refreshToken = getCookie('refresh_token');
        if (!refreshToken) {
            // No refresh token, clear access token and redirect to login
            localStorage.removeItem('access_token');
            window.location.href = 'login.html';
            // Return a rejected promise to stop the chain
            return Promise.reject('No refresh token available');
        }

        return fetch(API_BASE_URL + 'auth/refresh', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ refresh_token: refreshToken })
        })
        .then(refreshResponse => {
            if (!refreshResponse.ok) {
                // Refresh failed, clear tokens and redirect to login
                localStorage.removeItem('access_token');
                document.cookie = 'refresh_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
                window.location.href = 'login.html';
                // Return a rejected promise to stop the chain
                return Promise.reject('Token refresh failed');
            }
            return refreshResponse.json();
        })
        .then(refreshData => {
            // Refresh successful, save new tokens
            localStorage.setItem('access_token', refreshData.access_token);
            document.cookie = `refresh_token=${refreshData.refresh_token}; path=/; samesite=strict; max-age=604800`; // 7 days
            // Reload the page to re-validate with the new token
            window.location.reload();
            // Return a new promise that never resolves to prevent the rest of the chain from executing
            return new Promise(() => {});
        });
    })
    .then(data => {
        if (data && data.user) {
            console.log('Token is valid for user:', data.user);
        }
    })
    .catch(error => {
        if (error !== 'No refresh token available' && error !== 'Token refresh failed') {
            console.error('Token validation error:', error);
            // On unexpected error, assume token is invalid
            localStorage.removeItem('access_token');
            document.cookie = 'refresh_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
            window.location.href = 'login.html';
        }
        // For rejected promises from refresh logic, the redirection is already handled.
    });
}

const LOGOUT_URL = API_BASE_URL + 'auth/logout';

function clearSession() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('access_token'); // For good measure
    document.cookie = 'refresh_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; samesite=strict';
}

async function postLogout({ accessToken, refreshToken, csrfToken }) {
    const response = await fetch(LOGOUT_URL, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${accessToken}`,
            'X-Csrf-Token': csrfToken
        },
        body: JSON.stringify({
            refresh_token: refreshToken,
            invalidate_all: true
        })
    });

    if (!response.ok) {
        let message = `Logout request failed (status ${response.status})`;
        try {
            const errorBody = await response.json();
            if (errorBody?.error) {
                message = errorBody.error;
            }
        } catch (e) {
            // Ignore JSON parse errors
        }
        throw new Error(message);
    }
}

window.handleLogout = async (event) => {
    event?.preventDefault?.();

    const accessToken = localStorage.getItem('token') || localStorage.getItem('access_token');
    const refreshToken = getCookie('refresh_token');

    if (!accessToken || !refreshToken) {
        clearSession();
        window.location.href = 'login.html';
        return;
    }

    try {
        const csrfToken = await fetchCsrfToken();
        await postLogout({ accessToken, refreshToken, csrfToken });
        // On successful logout, clear session and redirect
        clearSession();
        window.location.href = 'login.html';
    } catch (error) {
        console.error('Logout failed:', error.message || error);
        // Even if logout fails, clear local session and redirect
        clearSession();
        window.location.href = 'login.html';
    }
};
