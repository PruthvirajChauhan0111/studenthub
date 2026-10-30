
const API_URL = "events-api.php";

document.addEventListener("DOMContentLoaded", () => {
    const eventsList = document.getElementById("eventsList");
    const eventSearch = document.getElementById("eventSearch");
    const registrationForm = document.getElementById("eventRegistrationForm");
    const addEventForm = document.getElementById("addEventForm");
    const eventSelect = document.getElementById("eventSelect");
    const registrationsBody = document.getElementById("registrationsBody");

    // Display messages to the user.
    function showMessage(message, type = "info") {
        let messageBox = document.getElementById("eventMessage");

        if (!messageBox) {
            messageBox = document.createElement("div");
            messageBox.id = "eventMessage";
            messageBox.setAttribute("role", "status");

            const main = document.querySelector("main") || document.body;
            main.prepend(messageBox);
        }

        messageBox.textContent = message;
        messageBox.className = `event-message ${type}`;
    }

    // Send requests to events-api.php and parse JSON.
    async function apiRequest(url, options = {}) {
        const response = await fetch(url, options);

        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error(
                "Invalid server response. Check events-api.php for PHP errors."
            );
        }

        if (!response.ok || data.success === false) {
            throw new Error(data.message || "Something went wrong.");
        }

        return data;
    }

    // Load all events from the database.
    async function loadEvents() {
        try {
            const data = await apiRequest(`${API_URL}?action=events`);

            if (eventsList) {
                eventsList.replaceChildren();

                if (!data.events || data.events.length === 0) {
                    eventsList.textContent = "No events available.";
                } else {
                    data.events.forEach(event => {
                        const card = document.createElement("article");
                        card.className = "event-card";

                        const title = document.createElement("h3");
                        title.textContent = event.title;

                        const date = document.createElement("p");
                        date.textContent = `Date: ${event.event_date}`;

                        const venue = document.createElement("p");
                        venue.textContent = `Venue: ${event.venue}`;

                        const description = document.createElement("p");
                        description.textContent =
                            event.description || "No description available.";

                        card.append(title, date, venue, description);
                        eventsList.appendChild(card);
                    });
                }
            }

            // Populate the event selection dropdown.
            if (eventSelect) {
                const oldValue = eventSelect.value;
                eventSelect.replaceChildren();

                const placeholder = document.createElement("option");
                placeholder.value = "";
                placeholder.textContent = "Choose an event";
                eventSelect.appendChild(placeholder);

                (data.events || []).forEach(event => {
                    const option = document.createElement("option");
                    option.value = event.id;
                    option.textContent = event.title;
                    eventSelect.appendChild(option);
                });

                if ([...eventSelect.options].some(
                    option => option.value === oldValue
                )) {
                    eventSelect.value = oldValue;
                }
            }

            filterEvents();
        } catch (error) {
            console.error("Could not load events:", error);
            showMessage(error.message, "error");
        }
    }

    // Search events by title, date, venue, or description.
    function filterEvents() {
        if (!eventsList || !eventSearch) return;

        const query = eventSearch.value.trim().toLowerCase();

        eventsList.querySelectorAll(".event-card").forEach(card => {
            card.hidden = !card.textContent.toLowerCase().includes(query);
        });
    }

    // Register a student for an event.
    async function registerStudent(event) {
        event.preventDefault();

        const button = registrationForm.querySelector('[type="submit"]');

        try {
            if (button) button.disabled = true;

            const formData = new FormData(registrationForm);

            const data = await apiRequest(
                `${API_URL}?action=register`,
                {
                    method: "POST",
                    body: formData
                }
            );

            showMessage(
                data.message || "Registration successful!",
                "success"
            );

            registrationForm.reset();
            await loadRegistrations();
        } catch (error) {
            console.error("Registration failed:", error);
            showMessage(error.message, "error");
        } finally {
            if (button) button.disabled = false;
        }
    }

    // Display registrations saved in the database.
    async function loadRegistrations() {
        if (!registrationsBody) return;

        try {
            const data = await apiRequest(
                `${API_URL}?action=registrations`
            );

            registrationsBody.replaceChildren();

            if (!data.registrations || data.registrations.length === 0) {
                const row = document.createElement("tr");
                const cell = document.createElement("td");

                cell.colSpan = 4;
                cell.textContent = "No registrations yet.";

                row.appendChild(cell);
                registrationsBody.appendChild(row);
                return;
            }

            data.registrations.forEach(registration => {
                const row = document.createElement("tr");

                [
                    registration.student_name,
                    registration.student_email,
                    registration.event_title,
                    registration.registered_at
                ].forEach(value => {
                    const cell = document.createElement("td");
                    cell.textContent = value ?? "";
                    row.appendChild(cell);
                });

                registrationsBody.appendChild(row);
            });
        } catch (error) {
            console.error("Could not load registrations:", error);
            showMessage(error.message, "error");
        }
    }

    // Add an event to the database.
    async function saveNewEvent(event) {
        event.preventDefault();

        const button = addEventForm.querySelector('[type="submit"]');

        try {
            if (button) button.disabled = true;

            const formData = new FormData(addEventForm);

            const data = await apiRequest(
                `${API_URL}?action=add`,
                {
                    method: "POST",
                    body: formData
                }
            );

            showMessage(data.message || "Event added successfully!", "success");

            addEventForm.reset();
            await loadEvents();
        } catch (error) {
            console.error("Could not add event:", error);
            showMessage(error.message, "error");
        } finally {
            if (button) button.disabled = false;
        }
    }

    // Connect the HTML forms and search box.
    if (registrationForm) {
        registrationForm.addEventListener("submit", registerStudent);
    }

    if (addEventForm) {
        addEventForm.addEventListener("submit", saveNewEvent);
    }

    if (eventSearch) {
        eventSearch.addEventListener("input", filterEvents);
    }

    // Load data when the page opens.
    loadEvents();
    loadRegistrations();
});
