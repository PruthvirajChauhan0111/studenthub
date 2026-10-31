
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registrationForm");

    const username = document.getElementById("username");
    const email = document.getElementById("email");
    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("confirm_password");
    const terms = document.getElementById("terms");

    form.addEventListener("submit", function (event) {
        let valid = true;

        document.querySelectorAll("form small").forEach(function (item) {
            item.textContent = "";
        });

        const usernamePattern = /^[A-Za-z0-9_]{3,50}$/;

        if (!usernamePattern.test(username.value.trim())) {
            document.getElementById("usernameError").textContent =
                "Use 3–50 letters, numbers or underscores.";
            valid = false;
        }

        if (!email.validity.valid || email.value.trim() === "") {
            document.getElementById("emailError").textContent =
                "Enter a valid email address.";
            valid = false;
        }

        if (password.value.length < 8) {
            document.getElementById("passwordError").textContent =
                "Password must contain at least 8 characters.";
            valid = false;
        }

        if (password.value !== confirmPassword.value) {
            document.getElementById("confirmError").textContent =
                "Passwords do not match.";
            valid = false;
        }

        if (!terms.checked) {
            document.getElementById("termsError").textContent =
                "Please accept the terms and conditions.";
            valid = false;
        }

        if (!valid) {
            event.preventDefault();
        }
    });
});
