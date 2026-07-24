document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-file-upload-form]").forEach(function (form) {
        var input = form.querySelector("[data-file-upload-input]");
        var submit = form.querySelector("[data-file-upload-submit]");
        var feedback = form.querySelector("[data-file-upload-feedback]");
        var fileName = form.querySelector("[data-file-upload-name]");
        var clear = form.querySelector("[data-file-upload-clear]");

        if (!input || !submit || !feedback || !fileName || !clear) return;

        function updateUploadState() {
            var file = input.files && input.files.length ? input.files[0] : null;

            submit.disabled = !file;
            submit.setAttribute("aria-disabled", String(!file));
            feedback.classList.toggle("d-none", !file);
            fileName.textContent = file ? file.name : "";
        }

        input.addEventListener("change", updateUploadState);
        clear.addEventListener("click", function () {
            input.value = "";
            updateUploadState();
        });
        form.addEventListener("reset", function () {
            window.setTimeout(updateUploadState, 0);
        });
        updateUploadState();
    });

    document.querySelectorAll("[data-file-rename-toggle]").forEach(function (toggle) {
        var form = document.getElementById(toggle.getAttribute("aria-controls"));
        if (!form) return;

        var input = form.querySelector("[data-file-rename-input]");

        function setRenameVisibility(isVisible) {
            form.hidden = !isVisible;
            toggle.hidden = isVisible;
            toggle.setAttribute("aria-expanded", String(isVisible));

            if (isVisible && input) {
                input.focus();
                input.select();
            }
        }

        toggle.addEventListener("click", function () {
            setRenameVisibility(true);
        });

        form.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                event.preventDefault();
                setRenameVisibility(false);
                toggle.focus();
            }
        });
    });
});
