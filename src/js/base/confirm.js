/**
 * Confirm destructive buttons: <button data-confirm="Wirklich?">
 */

document.addEventListener("click", (event) => {
    const btn = event.target.closest("[data-confirm]");
    if (btn && !window.confirm(btn.dataset.confirm)) {
        event.preventDefault();
    }
});
