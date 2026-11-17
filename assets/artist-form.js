const artistForm = document.querySelector('form[data-chronology-error]');
const birthYear = artistForm.elements.namedItem('birth');
const deathYear = artistForm.elements.namedItem('death');

function validateChronology() {
    deathYear.setCustomValidity('');
    if (birthYear.value && deathYear.value && birthYear.validity.valid && deathYear.validity.valid
        && Number(deathYear.value) <= Number(birthYear.value)) {
        deathYear.setCustomValidity(artistForm.dataset.chronologyError);
    }
}

artistForm.addEventListener('input', validateChronology);
artistForm.addEventListener('change', validateChronology);
artistForm.addEventListener('submit', (event) => {
    validateChronology();
    if (!deathYear.reportValidity()) event.preventDefault();
});
