const BASE_URL = API_BASE_URL + 'auth';
const CSRF_URL = API_BASE_URL + 'csrf/token';

async function apiRequest(url, method = 'POST', body = null, headers = {}) {
    try {
        const defaultHeaders = {
            'Content-Type': 'application/json'
        };
        const response = await fetch(url, {
            method,
            headers: { ...defaultHeaders, ...headers },
            credentials: 'include',
            body: body ? JSON.stringify(body) : null
        });

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
            return await response.json();
        }

        return {
            status: response.ok ? 'success' : 'error',
            text: await response.text()
        };
    } catch (error) {
        console.error(`API request to ${url} failed:`, error);
        return { status: 'error', error: 'A network error occurred.' };
    }
}

async function getCsrfToken() {
    try {
        const response = await fetch(CSRF_URL, { credentials: 'include' });
        if (!response.ok) {
            throw new Error('Failed to fetch CSRF token');
        }
        const data = await response.json();
        return data.csrf_token;
    } catch (error) {
        console.error('Error getting CSRF token:', error);
        return null;
    }
}

export function sendLoginIdentifier(type, identifier) {
    const endpoint = type === 'phone' ? 'login_sms' : 'login_email';
    const url = `${BASE_URL}/${endpoint}`;
    const body = { [type]: identifier };
    return apiRequest(url, 'POST', body);
}

export function verifyCode(type, identifier, code) {
    const endpoint = type === 'phone' ? 'verify_sms' : 'verify_email';
    const url = `${BASE_URL}/${endpoint}`;
    const body = { [type]: identifier, code };
    return apiRequest(url, 'POST', body);
}
