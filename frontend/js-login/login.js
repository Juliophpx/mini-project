import { countries } from './config/countries.js';
import { getCountryFromIP } from './services/ipInfoService.js';
import { sendLoginIdentifier, verifyCode } from './services/authService.js';

(function() {
    // --- STATE ---
    let isVerificationStep = false;
    let lastKnownIdentifierType = 'unknown';

    // --- DOM ELEMENTS ---
    const form = document.getElementById('loginForm');
    const loginIdentifierInput = document.getElementById('loginIdentifier');
    const loginIdentifierFeedback = document.getElementById('loginIdentifierFeedback');
    const loginIdentifierLabel = document.querySelector('label[for="loginIdentifier"]');
    const verificationSection = document.getElementById('verificationSection');
    const verificationInput = document.getElementById('verificationCode');
    const verificationCodeFeedback = document.getElementById('verificationCodeFeedback');
    const verificationCodeLabel = document.querySelector('label[for="verificationCode"]');
    const identifierDisplayWrapper = document.getElementById('identifierDisplayWrapper');
    const identifierDisplay = document.getElementById('identifierDisplay');
    const editIdentifierBtn = document.getElementById('editIdentifierBtn');
    const submitButton = document.getElementById('submitButton');
    const buttonText = submitButton.querySelector('.button-text');
    const spinner = submitButton.querySelector('.spinner');
    const responseMessage = document.getElementById('responseMessage');

    // --- Custom Select Elements ---
    const selectContainer = document.getElementById('country-select-container');
    const selectedDisplay = selectContainer.querySelector('.custom-select-selected');
    const optionsContainer = selectContainer.querySelector('.custom-select-options');
    const hiddenInput = document.getElementById('country-select');

    // --- HELPER FUNCTIONS ---
    function showError(input, feedback, label, message) {
        input.classList.add('is-invalid');
        feedback.textContent = ''; // Clear previous feedback if any
        if (label) {
            label.textContent = message;
            label.classList.add('label-error');
        }
    }

    function clearError(input, feedback, label, defaultLabel) {
        input.classList.remove('is-invalid');
        feedback.textContent = '';
        if (label) {
            label.textContent = defaultLabel;
            label.classList.remove('label-error');
        }
    }

    function getIdentifierType(identifier) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        // A simplified regex for phone numbers starting with '+'
        const phoneRegex = /^\+[1-9][0-9]{7,14}$/;
        if (emailRegex.test(identifier)) return 'email';
        // Remove spaces before testing phone number
        if (phoneRegex.test(identifier.replace(/\s/g, ''))) return 'phone';
        return 'unknown';
    }

    function setUIVisibility(isVerify) {
        const elementsToHide = [selectContainer.parentElement, loginIdentifierInput.parentElement];
        if (isVerify) {
            elementsToHide.forEach(el => el.classList.add('hidden'));
            identifierDisplayWrapper.classList.add('visible');
            verificationSection.classList.add('visible');
            submitButton.classList.add('hidden');
            buttonText.textContent = "VERIFY";
        } else {
            elementsToHide.forEach(el => el.classList.remove('hidden'));
            identifierDisplayWrapper.classList.remove('visible');
            verificationSection.classList.remove('visible');
            submitButton.classList.remove('hidden');
            buttonText.textContent = "CONTINUE";
        }
    }
    
    function selectCountry(country) {
        if (!country) return;
        
        selectedDisplay.innerHTML = `
            <span>
                <img src="img/flags/${country.isoCode.toLowerCase()}.png" alt="${country.name}">
                ${country.name}
            </span>
            <div class="custom-select-arrow"></div>
        `;
        selectedDisplay.style.color = '#111';
        hiddenInput.value = country.dialCode;
        selectContainer.classList.add('has-value');
        selectContainer.classList.remove('open');
        
        // Manually trigger change for other listeners
        hiddenInput.dispatchEvent(new Event('change'));
    }

    function toggleSpinner(show) {
        if (show) {
            submitButton.disabled = true;
            spinner.style.display = 'block';
        } else {
            submitButton.disabled = false;
            spinner.style.display = 'none';
        }
    }

    function showResponseMessage(message, type) {
        responseMessage.textContent = message;
        responseMessage.className = `response-message visible ${type}`;
    }

    function hideResponseMessage() {
        responseMessage.className = 'response-message';
    }

    // --- ASYNC HANDLERS ---
    async function handleVerification() {
        const identifier = loginIdentifierInput.value;
        const code = verificationInput.value;
        
        toggleSpinner(true);
        hideResponseMessage();
        const data = await verifyCode(lastKnownIdentifierType, identifier, code);
        toggleSpinner(false);

        if (data.status === 'success') {
            clearError(verificationInput, verificationCodeFeedback, verificationCodeLabel, 'Verification Code');
            localStorage.setItem('access_token', data.access_token);
            localStorage.setItem('user', JSON.stringify(data.user));
            document.cookie = `refresh_token=${data.refresh_token}; path=/; samesite=strict; max-age=2592000`;

            console.log('Login successful:', 'Access Token:', data.access_token, 'User ID:', data.user.id,  'User email:', data.user.email, 'User phone:', data.user.phone);

            window.location.href = 'dashboard.html';
        } else {
            showError(verificationInput, verificationCodeFeedback, verificationCodeLabel, data.error || 'Verification failed.');
            showResponseMessage(data.error || 'Verification failed.', 'error');
        }
    }

    async function handleFormSubmit(e) {
        e.preventDefault();
        if (isVerificationStep) return; // Prevent re-submission

        const identifier = loginIdentifierInput.value;
        lastKnownIdentifierType = getIdentifierType(identifier);

        if (lastKnownIdentifierType === 'unknown') {
            showError(loginIdentifierInput, loginIdentifierFeedback, loginIdentifierLabel, 'Please enter a valid email or phone number.');
            return;
        } else {
            clearError(loginIdentifierInput, loginIdentifierFeedback, loginIdentifierLabel, 'Email or Phone Number');
        }

        toggleSpinner(true);
        hideResponseMessage();

        const data = await sendLoginIdentifier(lastKnownIdentifierType, identifier);
        
        toggleSpinner(false);

        if (data.status === 'success') {
            isVerificationStep = true;
            loginIdentifierInput.disabled = true;
            identifierDisplay.textContent = identifier;
            setUIVisibility(true);
            verificationInput.focus();
        } else {
            showError(loginIdentifierInput, loginIdentifierFeedback, loginIdentifierLabel, data.error || 'Failed to send code.');
            showResponseMessage(data.error || 'Failed to send code.', 'error');
        }
    }

    // --- EVENT HANDLERS ---
    function handleEditIdentifier() {
        isVerificationStep = false;
        loginIdentifierInput.disabled = false;
        setUIVisibility(false);
        verificationInput.value = '';
        hideResponseMessage();
        clearError(loginIdentifierInput, loginIdentifierFeedback, loginIdentifierLabel, 'Email or Phone Number');
        clearError(verificationInput, verificationCodeFeedback, verificationCodeLabel, 'Verification Code');
        loginIdentifierInput.focus();
    }

    function handleIdentifierInput() {
        const value = loginIdentifierInput.value;
        const isEmailLike = /[a-zA-Z@._-]/.test(value);
        const countryCode = hiddenInput.value;

        if (isEmailLike) {
            // If user starts typing an email after selecting a country, remove the dial code
            if (value.startsWith(countryCode)) {
                loginIdentifierInput.value = value.substring(countryCode.length);
            }
        } else {
            // It's likely a phone number
            let numbersOnly = value.replace(/[^0-9]/g, '');
            // If the dial code is already there, don't re-add it
            if (value.startsWith(countryCode)) {
                 numbersOnly = value.substring(countryCode.length).replace(/[^0-9]/g, '');
            }
            // Limit phone number length (e.g., 15 digits total)
            if (numbersOnly.length > 15) {
                numbersOnly = numbersOnly.slice(0, 15);
            }
            loginIdentifierInput.value = numbersOnly.length > 0 ? countryCode + numbersOnly : '';
        }
        clearError(loginIdentifierInput, loginIdentifierFeedback, loginIdentifierLabel, 'Email or Phone Number');
        hideResponseMessage();
    }
    
    function handleCountryChange() {
        const value = loginIdentifierInput.value;
        // Only update if it looks like a phone number (or is empty)
        if (!/[a-zA-Z@._-]/.test(value)) { 
            const numbers = value.replace(/^\+\d+/, ''); // remove old dial code
            loginIdentifierInput.value = numbers.length > 0 ? hiddenInput.value + numbers : '';
            loginIdentifierInput.focus();
        }
    }

    // --- INITIALIZATION ---
    function initialize() {
        // Populate custom select
        countries.forEach(country => {
            const optionDiv = document.createElement('div');
            optionDiv.classList.add('custom-select-option');
            optionDiv.dataset.value = country.dialCode;
            optionDiv.dataset.iso = country.isoCode;
            optionDiv.innerHTML = `
                <img src="img/flags/${country.isoCode.toLowerCase()}.png" alt="${country.name}">
                <span>${country.name} (${country.dialCode})</span>
            `;
            optionsContainer.appendChild(optionDiv);
        });

        // Custom select logic
        selectedDisplay.addEventListener('click', () => {
            selectContainer.classList.toggle('open');
        });

        optionsContainer.addEventListener('click', (e) => {
            const option = e.target.closest('.custom-select-option');
            if (option) {
                const country = countries.find(c => c.isoCode === option.dataset.iso);
                selectCountry(country);
            }
        });

        window.addEventListener('click', (e) => {
            if (!selectContainer.contains(e.target)) {
                selectContainer.classList.remove('open');
            }
        });

        // IP Info lookup to pre-select country
        getCountryFromIP().then(data => {
            if (data && data.country) {
                const isoCode = data.country.trim().toUpperCase();
                const country = countries.find(c => c.isoCode === isoCode);
                if (country) {
                    selectCountry(country);
                }
            }
        }).catch(error => {
            console.error('Error fetching IP information, defaulting to US:', error);
            const defaultCountry = countries.find(c => c.isoCode === 'US');
            if (defaultCountry) {
                selectCountry(defaultCountry);
            }
        });

        // Form event listeners
        form.addEventListener('submit', handleFormSubmit);
        editIdentifierBtn.addEventListener('click', handleEditIdentifier);
        loginIdentifierInput.addEventListener('input', handleIdentifierInput);
        hiddenInput.addEventListener('change', handleCountryChange);
        
        verificationInput.addEventListener('input', () => {
            // Automatically submit when 6 digits are entered
            verificationInput.value = verificationInput.value.replace(/[^0-9]/g, '').slice(0, 6);
            clearError(verificationInput, verificationCodeFeedback, verificationCodeLabel, 'Verification Code');
            hideResponseMessage();
            if (verificationInput.value.length === 6) {
                handleVerification();
            }
        });
    }

    // Run initialization on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', initialize);

})();
