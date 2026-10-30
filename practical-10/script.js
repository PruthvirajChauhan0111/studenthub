
/*
  StudentHub - Practical 4
  DOM Manipulation, Event Handling and UI Interactivity
*/

document.addEventListener("DOMContentLoaded", function () {
    const body = document.body;
    const header = document.querySelector("header");
    const nav = document.querySelector("nav");
    const main = document.querySelector("main");

    if (!header || !main) return;

    // ---------------------------------------
    // 1. LIGHT / DARK THEME SWITCHER
    // ---------------------------------------

    const themeButton = document.createElement("button");
    themeButton.type = "button";
    themeButton.id = "themeToggle";
    themeButton.className = "theme-toggle";
    themeButton.setAttribute("aria-pressed", "false");
    header.appendChild(themeButton);

    function applyTheme(theme) {
        const dark = theme === "dark";

        body.classList.toggle("dark-theme", dark);
        themeButton.textContent = dark
            ? "☀️ Light Mode"
            : "🌙 Dark Mode";

        themeButton.setAttribute("aria-pressed", String(dark));
        themeButton.setAttribute(
            "aria-label",
            dark ? "Switch to light theme" : "Switch to dark theme"
        );
    }

    let savedTheme = "light";

    try {
        savedTheme = localStorage.getItem("studenthub-theme") || "light";
    } catch (error) {
        console.warn("Theme preference could not be loaded.");
    }

    applyTheme(savedTheme);

    themeButton.addEventListener("click", function () {
        const newTheme = body.classList.contains("dark-theme")
            ? "light"
            : "dark";

        applyTheme(newTheme);

        try {
            localStorage.setItem("studenthub-theme", newTheme);
        } catch (error) {
            console.warn("Theme preference could not be saved.");
        }
    });

    // ---------------------------------------
    // 2. RESPONSIVE HAMBURGER MENU
    // ---------------------------------------

    if (nav) {
        const navList = nav.querySelector("ul");

        if (navList) {
            const menuButton = document.createElement("button");
            menuButton.type = "button";
            menuButton.className = "menu-toggle";
            menuButton.textContent = "☰ Menu";
            menuButton.setAttribute("aria-expanded", "false");
            menuButton.setAttribute("aria-controls", "mainMenu");

            navList.id = "mainMenu";
            nav.insertBefore(menuButton, navList);

            menuButton.addEventListener("click", function () {
                const isOpen = nav.classList.toggle("menu-open");

                menuButton.setAttribute(
                    "aria-expanded",
                    String(isOpen)
                );

                menuButton.textContent = isOpen
                    ? "✕ Close Menu"
                    : "☰ Menu";
            });

            navList.addEventListener("click", function (event) {
                if (event.target.closest("a")) {
                    nav.classList.remove("menu-open");
                    menuButton.setAttribute("aria-expanded", "false");
                    menuButton.textContent = "☰ Menu";
                }
            });
        }
    }

    // ---------------------------------------
    // 3. DISMISSIBLE NOTIFICATION BANNER
    // ---------------------------------------

    const notification = document.createElement("div");
    notification.className = "notification-banner";
    notification.setAttribute("role", "status");

    const notificationText = document.createElement("p");
    notificationText.textContent =
        "🎓 Welcome to StudentHub! Explore your academic resources.";

    const dismissButton = document.createElement("button");
    dismissButton.type = "button";
    dismissButton.textContent = "Dismiss";
    dismissButton.setAttribute("aria-label", "Dismiss notification");

    notification.append(notificationText, dismissButton);
    header.insertAdjacentElement("afterend", notification);

    dismissButton.addEventListener("click", function () {
        notification.remove();
    });

    // ---------------------------------------
    // 4. ACCESSIBLE MODAL POPUP
    // ---------------------------------------

    const modalButton = document.createElement("button");
    modalButton.type = "button";
    modalButton.className = "btn modal-open-button";
    modalButton.textContent = "Quick Help";

    header.appendChild(modalButton);

    const modal = document.createElement("dialog");
    modal.className = "help-modal";
    modal.setAttribute("aria-labelledby", "modalTitle");

    const modalTitle = document.createElement("h2");
    modalTitle.id = "modalTitle";
    modalTitle.textContent = "Welcome to StudentHub";

    const modalMessage = document.createElement("p");
    modalMessage.textContent =
        "Use the navigation menu to explore courses, attendance, results, " +
        "timetable, college events and student support.";

    const modalClose = document.createElement("button");
    modalClose.type = "button";
    modalClose.className = "btn";
    modalClose.textContent = "Close";

    modal.append(modalTitle, modalMessage, modalClose);
    body.appendChild(modal);

    modalButton.addEventListener("click", function () {
        if (typeof modal.showModal === "function") {
            modal.showModal();
        } else {
            modal.setAttribute("open", "");
        }
    });

    modalClose.addEventListener("click", function () {
        if (typeof modal.close === "function") {
            modal.close();
        } else {
            modal.removeAttribute("open");
        }
    });

    modal.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.close();
        }
    });

    // ---------------------------------------
    // 5. COLLAPSIBLE FAQ ON ABOUT PAGE
    // ---------------------------------------

    if (
        location.pathname.toLowerCase().endsWith("/about.html") ||
        location.pathname.toLowerCase().endsWith("about.html")
    ) {
        const faqSection = document.createElement("section");
        faqSection.className = "faq-section";

        const faqHeading = document.createElement("h2");
        faqHeading.textContent = "Frequently Asked Questions";

        faqSection.appendChild(faqHeading);

        const questions = [
            {
                question: "What is StudentHub?",
                answer:
                    "StudentHub is a student portal concept for accessing " +
                    "courses, attendance, results and college events."
            },
            {
                question: "How can I view my courses?",
                answer:
                    "Open the Courses page from the main navigation menu."
            },
            {
                question: "Where can I find college events?",
                answer:
                    "Open the Events page to explore sample college activities."
            },
            {
                question: "How can I contact student support?",
                answer:
                    "Visit the Contact page to find support information " +
                    "and the contact form."
            }
        ];

        questions.forEach(function (item) {
            const details = document.createElement("details");
            details.className = "faq-item";

            const summary = document.createElement("summary");
            summary.textContent = item.question;

            const answer = document.createElement("p");
            answer.textContent = item.answer;

            details.append(summary, answer);
            faqSection.appendChild(details);
        });

        main.appendChild(faqSection);
    }

    // ---------------------------------------
    // 6. CONTENT SLIDER ON EVENTS PAGE
    // ---------------------------------------

    const eventSection = main.querySelector(
        'section[aria-label="College events"]'
    );

    if (eventSection) {
        const slides = Array.from(
            eventSection.querySelectorAll("article")
        );

        if (slides.length > 1) {
            eventSection.classList.add("event-slider");

            const controls = document.createElement("div");
            controls.className = "slider-controls";

            const previousButton = document.createElement("button");
            previousButton.type = "button";
            previousButton.textContent = "← Previous";
            previousButton.setAttribute("aria-label", "Previous event");

            const slideStatus = document.createElement("span");
            slideStatus.className = "slider-status";
            slideStatus.setAttribute("aria-live", "polite");

            const nextButton = document.createElement("button");
            nextButton.type = "button";
            nextButton.textContent = "Next →";
            nextButton.setAttribute("aria-label", "Next event");

            controls.append(
                previousButton,
                slideStatus,
                nextButton
            );

            eventSection.insertAdjacentElement("afterend", controls);

            let currentSlide = 0;

            function showSlide(index) {
                currentSlide =
                    (index + slides.length) % slides.length;

                slides.forEach(function (slide, slideIndex) {
                    const active = slideIndex === currentSlide;

                    slide.hidden = !active;
                    slide.setAttribute(
                        "aria-hidden",
                        String(!active)
                    );
                });

                slideStatus.textContent =
                    "Event " + (currentSlide + 1) +
                    " of " + slides.length;
            }

            previousButton.addEventListener("click", function () {
                showSlide(currentSlide - 1);
            });

            nextButton.addEventListener("click", function () {
                showSlide(currentSlide + 1);
            });

            showSlide(0);
        }
    }
});
