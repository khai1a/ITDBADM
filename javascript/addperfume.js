document.addEventListener("DOMContentLoaded", function () {
    const exclusiveSelect = document.getElementById("isExclusive");
    const countrySelect = document.getElementById("country");

    function toggleCountry() {
        if (exclusiveSelect.value === "0") {
            countrySelect.disabled = true;
            countrySelect.value = "";
        } else {
            countrySelect.disabled = false;
        }
    }

    toggleCountry();

    exclusiveSelect.addEventListener("change", toggleCountry);
});
