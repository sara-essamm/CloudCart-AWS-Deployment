/* =====================================================================
   CloudCart - small JavaScript helpers (no frameworks needed)
   ===================================================================== */

document.addEventListener("DOMContentLoaded", function () {

    // 1. Ask "Are you sure?" before submitting forms marked with data-confirm="..."
    //    (used for removing cart items and deleting products)
    document.querySelectorAll("form[data-confirm]").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // 2. Checkout: disable the button after the first click so an order
    //    cannot be submitted twice by accident.
    document.querySelectorAll("form[data-single-submit]").forEach(function (form) {
        form.addEventListener("submit", function () {
            var button = form.querySelector("button[type='submit']");
            if (button) {
                button.disabled = true;
                button.textContent = "Placing order...";
            }
        });
    });

    // 3. Register page: warn right away if the two passwords are different.
    var registerForm = document.getElementById("register-form");
    if (registerForm) {
        var password = document.getElementById("password");
        var confirmPassword = document.getElementById("confirm_password");

        function checkPasswordsMatch() {
            if (confirmPassword.value && password.value !== confirmPassword.value) {
                confirmPassword.classList.add("is-invalid");
            } else {
                confirmPassword.classList.remove("is-invalid");
            }
        }

        password.addEventListener("input", checkPasswordsMatch);
        confirmPassword.addEventListener("input", checkPasswordsMatch);

        registerForm.addEventListener("submit", function (event) {
            if (password.value !== confirmPassword.value) {
                event.preventDefault();
                confirmPassword.classList.add("is-invalid");
                confirmPassword.focus();
            }
        });
    }

    // 4. Success / info messages fade away by themselves after 4 seconds.
    //    (Errors and warnings stay until the user closes them.)
    document.querySelectorAll(".alert-success, .alert-info").forEach(function (alertBox) {
        setTimeout(function () {
            if (window.bootstrap) {
                bootstrap.Alert.getOrCreateInstance(alertBox).close();
            } else {
                alertBox.remove();
            }
        }, 4000);
    });
});
