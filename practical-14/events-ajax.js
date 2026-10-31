(() => {
    const apiUrl = 'api/events.php';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const form = document.getElementById('eventForm');
    const eventId = document.getElementById('eventId');
    const formHeading = document.getElementById('formHeading');
    const saveButton = document.getElementById('saveButton');
    const cancelEdit = document.getElementById('cancelEdit');
    const eventList = document.getElementById('eventList');
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const messageBox = document.getElementById('apiMessage');
    const recordSummary = document.getElementById('recordSummary');
    const searchInput = document.getElementById('searchEvents');
    const categoryFilter = document.getElementById('filterCategory');
    const statusFilter = document.getElementById('filterStatus');
    let searchDelay;

    function showMessage(message, type = 'success') {
        messageBox.textContent = message;
        messageBox.className = `p13-message ${type}`;
        messageBox.hidden = false;
    }
    function hideMessage() { messageBox.hidden = true; messageBox.textContent = ''; }
    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin', ...options,
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(options.method && options.method !== 'GET' ? { 'X-CSRF-Token': csrfToken } : {}),
                ...(options.headers || {})
            }
        });
        let result;
        try { result = await response.json(); }
        catch (error) { throw new Error('The server returned an invalid response. Check the PHP API.'); }
        if (!response.ok || !result.success) throw new Error(result.message || 'The request failed.');
        return result;
    }
    function escapeHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
    }
    function formatDate(value) {
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return escapeHTML(value);
        return date.toLocaleString('en-IN', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
    }
    function updateCategories(categories) {
        const selected = categoryFilter.value;
        categoryFilter.innerHTML = '<option value="">All categories</option>';
        categories.forEach(category => {
            const option = document.createElement('option'); option.value = category; option.textContent = category;
            categoryFilter.appendChild(option);
        });
        categoryFilter.value = categories.includes(selected) ? selected : '';
    }
    function renderEvents(events) {
        eventList.innerHTML = '';
        emptyState.hidden = events.length !== 0;
        events.forEach(item => {
            const card = document.createElement('article'); card.className = 'p13-event-card';
            const poster = item.poster_url
                ? `<img class="p13-event-poster" src="${escapeHTML(item.poster_url)}" alt="Poster for ${escapeHTML(item.title)}">`
                : '<div class="p13-poster-placeholder" aria-hidden="true">✦</div>';
            card.innerHTML = `${poster}<div class="p13-event-content">
                <div class="p13-event-meta"><span class="p13-category">${escapeHTML(item.category)}</span><span class="p13-status ${item.status === 'open' ? 'is-open' : 'is-closed'}">${escapeHTML(item.status)}</span></div>
                <h4>${escapeHTML(item.title)}</h4><p class="p13-description">${escapeHTML(item.description)}</p>
                <p class="p13-event-detail">▦ ${formatDate(item.event_date)}</p><p class="p13-event-detail">⌖ ${escapeHTML(item.location)}</p>
                <div class="p13-card-actions"><button type="button" class="p13-edit" data-action="edit" data-id="${item.event_id}">Edit</button><button type="button" class="p13-delete" data-action="delete" data-id="${item.event_id}">Delete</button></div></div>`;
            eventList.appendChild(card);
        });
    }
    async function loadEvents() {
        loadingState.hidden = false; loadingState.textContent = 'Loading events from the database...';
        eventList.innerHTML = ''; emptyState.hidden = true;
        const params = new URLSearchParams();
        if (searchInput.value.trim()) params.set('search', searchInput.value.trim());
        if (categoryFilter.value) params.set('category', categoryFilter.value);
        if (statusFilter.value) params.set('status', statusFilter.value);
        try {
            const result = await request(`${apiUrl}?${params.toString()}`);
            updateCategories(result.categories || []); renderEvents(result.events || []);
            recordSummary.textContent = `${result.count} event(s) found`;
        } catch (error) { loadingState.textContent = error.message; showMessage(error.message, 'error'); }
        finally { loadingState.hidden = true; }
    }
    function resetForm() {
        form.reset(); eventId.value = ''; formHeading.textContent = 'Add a new event';
        saveButton.textContent = 'Add event'; cancelEdit.hidden = true;
    }
    function fillForm(item) {
        eventId.value = item.event_id; form.elements.title.value = item.title;
        form.elements.description.value = item.description; form.elements.category.value = item.category;
        form.elements.event_date.value = item.event_date.replace(' ', 'T').slice(0, 16);
        form.elements.location.value = item.location; form.elements.status.value = item.status;
        formHeading.textContent = 'Update event'; saveButton.textContent = 'Save changes'; cancelEdit.hidden = false;
        document.getElementById('title').focus();
    }
    form.addEventListener('submit', async event => {
        event.preventDefault(); hideMessage(); if (!form.reportValidity()) return;
        const editing = eventId.value !== '';
        const payload = {
            title: form.elements.title.value.trim(), description: form.elements.description.value.trim(),
            category: form.elements.category.value.trim(), event_date: form.elements.event_date.value,
            location: form.elements.location.value.trim(), status: form.elements.status.value
        };
        if (editing) payload.event_id = Number(eventId.value);
        saveButton.disabled = true; saveButton.textContent = editing ? 'Saving...' : 'Adding...';
        try {
            const result = await request(apiUrl, {method: editing ? 'PUT' : 'POST', body: JSON.stringify(payload)});
            resetForm(); showMessage(result.message); await loadEvents();
        } catch (error) { showMessage(error.message, 'error'); saveButton.textContent = editing ? 'Save changes' : 'Add event'; }
        finally { saveButton.disabled = false; }
    });
    eventList.addEventListener('click', async event => {
        const button = event.target.closest('button[data-action]'); if (!button) return;
        const id = Number(button.dataset.id); const action = button.dataset.action;
        if (action === 'edit') {
            try { const result = await request(apiUrl); const selected = result.events.find(item => item.event_id === id);
                if (!selected) { showMessage('This event no longer exists.', 'error'); await loadEvents(); return; }
                fillForm(selected);
            } catch (error) { showMessage(error.message, 'error'); }
            return;
        }
        if (action === 'delete' && window.confirm('Are you sure you want to delete this event?')) {
            button.disabled = true; button.textContent = 'Deleting...';
            try { const result = await request(apiUrl, {method:'DELETE', body:JSON.stringify({event_id:id})});
                if (eventId.value === String(id)) resetForm(); showMessage(result.message); await loadEvents();
            } catch (error) { showMessage(error.message, 'error'); button.disabled = false; button.textContent = 'Delete'; }
        }
    });
    cancelEdit.addEventListener('click', resetForm);
    document.getElementById('refreshButton').addEventListener('click', loadEvents);
    categoryFilter.addEventListener('change', loadEvents); statusFilter.addEventListener('change', loadEvents);
    searchInput.addEventListener('input', () => { window.clearTimeout(searchDelay); searchDelay = window.setTimeout(loadEvents, 300); });
    loadEvents();
})();
