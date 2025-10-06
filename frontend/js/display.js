document.addEventListener('DOMContentLoaded', function() {
    const cardsContainer = document.getElementById('cards-container');
    const statusLight = document.getElementById('status-light');
    const statusText = document.getElementById('status-text');
    const totalClicksCountEl = document.getElementById('total-clicks-count');

    const eventSourceUrl = API_BASE_URL + `clicks/stats`;
    const eventSource = new EventSource(eventSourceUrl);

    let clickDataStore = {};
    let totalClicks = 0;

    eventSource.onopen = function() {
        console.log("Connection to SSE stream opened.");
        statusLight.classList.add('connected');
        statusText.textContent = 'Live';
    };

    eventSource.addEventListener('stats_update', function(event) {
        const data = JSON.parse(event.data);
        
        const newDataStore = {};
        let newTotalClicks = 0;
        const incomingKeys = new Set();

        data.forEach(item => {
            const key = `${item.user_id}-${item.button_id}`;
            newDataStore[key] = item;
            newTotalClicks += parseInt(item.click_count, 10);
            incomingKeys.add(key);
        });
        
        clickDataStore = newDataStore;
        totalClicks = newTotalClicks;

        renderOrUpdateCards(incomingKeys);
    });

    eventSource.onerror = function(err) {
        console.error("EventSource failed:", err);
        statusLight.classList.remove('connected');
        statusText.textContent = 'Disconnected';
        eventSource.close();
    };

    function renderOrUpdateCards(incomingKeys) {
        // Update total clicks
        totalClicksCountEl.textContent = totalClicks;

        // Remove cards that are no longer in the data
        const existingCardElements = document.querySelectorAll('.col[id^="card-"]');
        existingCardElements.forEach(cardEl => {
            const cardKey = cardEl.id.replace('card-', '');
            if (!incomingKeys.has(cardKey)) {
                cardEl.remove();
            }
        });

        // Sort and update/create cards
        const sortedData = Object.values(clickDataStore).sort((a, b) => {
            return new Date(b.updated_at) - new Date(a.updated_at);
        });

        sortedData.forEach(item => {
            const key = `${item.user_id}-${item.button_id}`;
            const existingCard = document.getElementById(`card-${key}`);

            if (existingCard) {
                // Card exists, just update its content
                updateCardContent(existingCard, item);
            } else {
                // Card doesn't exist, create and append it
                const newCard = createCard(item);
                cardsContainer.appendChild(newCard);
            }
        });
    }

    function updateCardContent(cardElement, item) {
        const countEl = cardElement.querySelector('.card-count');
        const userEl = cardElement.querySelector('.username');
        const timestampEl = cardElement.querySelector('.timestamp');

        // Update only if content has changed
        if (countEl.textContent !== String(item.click_count)) {
            countEl.textContent = item.click_count;
        }
        if (userEl.textContent !== item.name) {
            userEl.textContent = item.name;
        }
        
        const newDate = formatDate(new Date(item.updated_at));
        if (timestampEl.textContent !== newDate) {
            timestampEl.textContent = newDate;
        }
    }

    function createCard(item) {
        const col = document.createElement('div');
        col.className = 'col'; 
        col.id = `card-${item.user_id}-${item.button_id}`;

        const formattedDate = formatDate(new Date(item.updated_at));

        col.innerHTML = `
            <div class="card data-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h2 class="card-title h5">${item.button_id}</h2>
                        <p class="card-count display-4">${item.click_count}</p>
                    </div>
                    <div class="card-user-info mt-auto pt-3 border-top">
                        Last click by <span class="username">${item.name}</span>
                        <div class="timestamp text-muted">${formattedDate}</div>
                    </div>
                </div>
            </div>
        `;
        return col;
    }

    function formatDate(date) {
        const d = new Date(date);
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');
        return `${month}/${day}/${year} ${hours}:${minutes}:${seconds}`;
    }

});
