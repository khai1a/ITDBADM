document.addEventListener("DOMContentLoaded", function () {
    const roleSelect = document.getElementById("role");
    const branchSelect = document.getElementById("branch");
    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirmpassword");
    const form = document.querySelector("form");

    const allowedBranchRoles = ["Branch Manager", "Branch Employee"];

    roleSelect.addEventListener("change", function () {
        const selectedRole = roleSelect.value;

        if (allowedBranchRoles.includes(selectedRole)) {
            branchSelect.disabled = false;
        } else {
            branchSelect.disabled = true;
            branchSelect.value = ""; 
        }
    });

    function validatePasswords() {
        if (passwordField.value !== confirmPasswordField.value) {
            confirmPasswordField.setCustomValidity("Passwords do not match!");
        } else {
            confirmPasswordField.setCustomValidity("");
        }
    }

    passwordField.addEventListener("input", validatePasswords);
    confirmPasswordField.addEventListener("input", validatePasswords);

    form.addEventListener("submit", function (e) {
        if (passwordField.value !== confirmPasswordField.value) {
            e.preventDefault();
            alert("Passwords do not match!");
        }

        if (allowedBranchRoles.includes(roleSelect.value) && branchSelect.value === "") {
            e.preventDefault();
            alert("Please select a branch for this role.");
        }
    });
});
