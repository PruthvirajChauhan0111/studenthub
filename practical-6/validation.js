
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registrationForm");

    if (!form) return;

    const fields = {
        fullName: document.getElementById("fullName"),
        email: document.getElementById("email"),
        phone: document.getElementById("phone"),
        course: document.getElementById("course"),
        password: document.getElementById("password"),
        confirmPassword: document.getElementById("confirmPassword"),
        terms: document.getElementById("terms")
    };

    const message = document.getElementById("formMessage");

    function showError(fieldName, text) {
        const field = fields[fieldName];
        const error = document.getElementById(fieldName + "Error");

        error.textContent = text;
        field.setAttribute("aria-invalid", text ? "true" : "false");
        field.classList.toggle("input-error", Boolean(text));
    }

    function validateName() {
        const value = fields.fullName.value.trim();
        const valid = /^[\p{L}][\p{L}\s.'-]{1,59}$/u.test(value);

        showError(
            "fullName",
            !value
                ? "Full name is required."
                : !valid
                    ? "Enter a valid name with at least 2 characters."
                    : ""
        );

        return valid;
    }

    function validateEmail() {
        const value = fields.email.value.trim();
        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

        showError(
            "email",
            !value
                ? "Email address is required."
                : !valid
                    ? "Enter a valid email address."
                    : ""
        );

        return valid;
    }

    function validatePhone() {
        const value = fields.phone.value.trim();
        const valid = /^[6-9]\d{9}$/.test(value);

        showError(
            "phone",
            !value
                ? "Mobile number is required."
                : !valid
                    ? "Enter a valid 10-digit Indian mobile number."
                    : ""
        );

        return valid;
    }

    function validateCourse() {
        const valid = fields.course.value !== "";

        showError("course", valid ? "" : "Please select your course.");

        return valid;
    }

    function validatePassword() {
        const value = fields.password.value;

        const valid =
            value.length >= 8 &&
            /[a-z]/.test(value) &&
            /[A-Z]/.test(value) &&
            /\d/.test(value) &&
            /[^A-Za-z0-9]/.test(value);

        showError(
            "password",
            !value
                ? "Password is required."
                : !valid
                    ? "Use 8+ characters with uppercase, lowercase, a number and a special character."
                    : ""
        );

        if (fields.confirmPassword.value) {
            validateConfirmPassword();
        }

        return valid;
    }

    function validateConfirmPassword() {
        const value = fields.confirmPassword.value;
        const valid = value !== "" && value === fields.password.value;

        showError(
            "confirmPassword",
            !value
                ? "Please confirm your password."
                : !valid
                    ? "Passwords do not match."
                    : ""
        );

        return valid;
    }

    function validateTerms() {
        const valid = fields.terms.checked;

        showError(
            "terms",
            valid ? "" : "You must agree to the terms and conditions."
        );

        return valid;
    }

    const validators = {
        fullName: validateName,
        email: validateEmail,
        phone: validatePhone,
        course: validateCourse,
        password: validatePassword,
        confirmPassword: validateConfirmPassword,
        terms: validateTerms
    };

    Object.keys(validators).forEach(function (name) {
        const field = fields[name];
        const eventName =
            field.type === "checkbox" || field.tagName === "SELECT"
                ? "change"
                : "input";

        field.addEventListener(eventName, function () {
            validators[name]();

            if (message.textContent) {
                message.textContent = "";
                message.className = "form-message";
            }
        });

        field.addEventListener("blur", validators[name]);
    });

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        const results = Object.values(validators).map(function (validate) {
            return validate();
        });

        const isValid = results.every(Boolean);

        if (!isValid) {
            message.textContent = "Please correct the errors before continuing.";
            message.className = "form-message error-message";

            const firstInvalid = form.querySelector('[aria-invalid="true"]');

            if (firstInvalid) {
                firstInvalid.focus();
            }

            return;
        }

        message.textContent =
            "Validation successful! Your form is ready for backend integration.";
        message.className = "form-message success-message";
    });
});
