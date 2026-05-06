document.addEventListener("DOMContentLoaded", function () {
    var forms = document.querySelectorAll("form");

    function getFieldLabel(field) {
        if (!field || !field.id) return "";

        var label = field.form
            ? field.form.querySelector('label[for="' + field.id + '"]')
            : null;
        if (!label) return "";

        return label.textContent.replace(/\*/g, "").trim();
    }

    function getFeedback(field) {
        var group = field.closest(".form-group") || field.parentElement;
        if (!group) return null;

        var feedback = group.querySelector(
            '.invalid-feedback[data-live-feedback="1"]',
        );
        if (feedback) return feedback;

        feedback = group.querySelector(".invalid-feedback");
        if (feedback) return feedback;

        feedback = document.createElement("div");
        feedback.className = "invalid-feedback";
        feedback.setAttribute("data-live-feedback", "1");
        field.insertAdjacentElement("afterend", feedback);
        return feedback;
    }

    function setFeedback(field, message) {
        var feedback = getFeedback(field);
        if (!feedback) return;

        feedback.textContent = message || "";
        if (message) {
            feedback.classList.add("d-block");
        } else {
            feedback.classList.remove("d-block");
        }
    }

    function getValidationMessage(field) {
        var label = getFieldLabel(field);
        var prefix = label ? "O campo " + label + " " : "O campo ";
        var validity = field.validity || {};
        var minLength = field.getAttribute("minlength");
        var maxLength = field.getAttribute("maxlength");
        var min = field.getAttribute("min");
        var max = field.getAttribute("max");

        if (validity.valueMissing) {
            if (field.tagName === "SELECT") {
                return label
                    ? "Selecione uma opção para " + label + "."
                    : "Selecione uma opção.";
            }

            return label ? "Preencha " + label + "." : "Preencha este campo.";
        }

        if (validity.tooShort && minLength) {
            return prefix + "deve ter " + minLength + " ou mais caracteres.";
        }

        if (validity.tooLong && maxLength) {
            return prefix + "não pode exceder " + maxLength + " caracteres.";
        }

        if (validity.rangeUnderflow && min) {
            return prefix + "deve ser maior ou igual a " + min + ".";
        }

        if (validity.rangeOverflow && max) {
            return prefix + "deve ser menor ou igual a " + max + ".";
        }

        if (validity.patternMismatch) {
            return prefix + "possui um formato inválido.";
        }

        if (validity.typeMismatch && field.type === "email") {
            return "Informe um e-mail válido.";
        }

        if (validity.stepMismatch) {
            return prefix + "possui um valor inválido.";
        }

        return label ? "Valor inválido para " + label + "." : "Valor inválido.";
    }

    function updateFieldState(field) {
        if (
            !field ||
            field.disabled ||
            field.type === "hidden" ||
            !field.willValidate
        )
            return;

        var isValid = field.checkValidity();

        if (isValid) {
            field.classList.remove("is-invalid");
            field.removeAttribute("aria-invalid");
            setFeedback(field, "");
            return;
        }

        field.classList.add("is-invalid");
        field.setAttribute("aria-invalid", "true");
        setFeedback(field, getValidationMessage(field));
    }

    forms.forEach(function (form) {
        if (form.hasAttribute("data-disable-client-validation")) return;

        form.noValidate = true;

        var fields = Array.prototype.slice
            .call(form.querySelectorAll("input, select, textarea"))
            .filter(function (field) {
                return (
                    field &&
                    field.type !== "hidden" &&
                    field.type !== "submit" &&
                    field.type !== "button" &&
                    field.type !== "reset"
                );
            });

        fields.forEach(function (field) {
            var validate = function () {
                updateFieldState(field);
            };

            field.addEventListener("input", validate);
            field.addEventListener("change", validate);
            field.addEventListener("blur", validate);
        });

        form.addEventListener("submit", function (event) {
            var invalidField = null;

            fields.forEach(function (field) {
                updateFieldState(field);
                if (!invalidField && !field.checkValidity()) {
                    invalidField = field;
                }
            });

            if (invalidField) {
                event.preventDefault();
                event.stopPropagation();
                if (typeof invalidField.focus === "function") {
                    invalidField.focus();
                }
            }
        });
    });
});
