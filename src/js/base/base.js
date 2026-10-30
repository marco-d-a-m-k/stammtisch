import { isPage } from "..";

/**
 * General Observer
 */

const generalObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (entry.isIntersecting) {
            const parent = entry.target.closest("[observe]");
            const delay = parent ? parent.getAttribute("observe") || 60 : 60;
            const index = Array.from(parent.children).indexOf(entry.target);

            entry.target.classList.add("in-view");
            entry.target.style.setProperty(
                "--anim-delay",
                `${index * delay}ms`,
            );
        } else {
            entry.target.classList.remove("in-view");
        }
    });
});

window.addEventListener("DOMContentLoaded", () => {
    document
        .querySelectorAll("[observe] > *")
        .forEach((el) => generalObserver.observe(el));
});

/**
 * Dialogs: fade out first, close after (iOS Safari would remove them instantly)
 */

function closeDialog(dialog) {
    if (!dialog.open || dialog.classList.contains("is-closing")) return;
    dialog.classList.add("is-closing");

    const done = () => {
        clearTimeout(fallback);
        dialog.classList.remove("is-closing");
        dialog.close();
    };
    const fallback = setTimeout(done, 400); // no transition (e.g. reduced motion)
    dialog.addEventListener("transitionend", (event) => event.target === dialog && done(), { once: true });
}

// Esc and clicks on the backdrop close with the animation too
function closeOnOutside(dialog) {
    dialog.addEventListener("cancel", (event) => {
        event.preventDefault();
        closeDialog(dialog);
    });

    // Keyboard clicks have no position, so check the target first
    dialog.addEventListener("click", (event) => {
        if (event.target !== dialog) return;
        const r = dialog.getBoundingClientRect();
        const inside = event.clientX >= r.left && event.clientX <= r.right && event.clientY >= r.top && event.clientY <= r.bottom;
        if (!inside) closeDialog(dialog);
    });
}

/**
 * Mobile menu: "Menu" opens the nav as a modal
 */

const menuBtn = document.querySelector(".cta--menu");
const menu = document.getElementById("nav");

if (menuBtn && menu) {
    menuBtn.addEventListener("click", () => {
        menu.showModal();
        menuBtn.setAttribute("aria-expanded", "true");
    });

    menu.addEventListener("close", () => menuBtn.setAttribute("aria-expanded", "false"));
    menu.querySelector(".nav-dialog__close")?.addEventListener("click", () => closeDialog(menu));
    closeOnOutside(menu);

    // Rotated / resized to desktop: no modal there
    matchMedia("(min-width: 681px)").addEventListener("change", (event) => {
        if (event.matches && menu.open) menu.close();
    });
}

/**
 * Modals: <button data-dialog="id"> opens <dialog class="modal" id="id">, [data-close] closes
 */

document.querySelectorAll("dialog.modal").forEach((dialog) => {
    closeOnOutside(dialog);
    dialog.querySelectorAll("[data-close]").forEach((btn) => btn.addEventListener("click", () => closeDialog(dialog)));
});

document.querySelectorAll("[data-dialog]").forEach((btn) => {
    const dialog = document.getElementById(btn.dataset.dialog);
    if (dialog) btn.addEventListener("click", () => dialog.showModal());
});

/**
 * Toast notices: slide in, leave by themselves (click closes early)
 */

document.querySelectorAll("[data-toast]").forEach((toast) => {
    const leave = () => {
        if (toast.classList.contains("is-leaving")) return;
        toast.classList.add("is-leaving");
        toast.addEventListener("animationend", () => toast.remove(), {
            once: true,
        });
    };

    setTimeout(leave, toast.classList.contains("notice--error") ? 7000 : 4000);
    toast.addEventListener("click", leave);
});

// Don't show the same message again on reload
const pageUrl = new URL(location.href);
if (pageUrl.searchParams.has("st_msg")) {
    pageUrl.searchParams.delete("st_msg");
    pageUrl.searchParams.delete("st_n");
    history.replaceState(null, "", pageUrl);
}

/**
 * Mobile: "Passwort (mind. 8 Zeichen)" → placeholder "Passwort", hint as text below
 */

const mobileQuery = matchMedia("(max-width: 800px)");

document.querySelectorAll("input[placeholder]").forEach((input, i) => {
    const match = input.placeholder.match(/^(.*?)\s*\((.+)\)$/);
    if (!match) return;

    const [full, short, hint] = match;
    const note = document.createElement("span");
    note.className = "field__hint fs-2 mobile";
    note.id = `field-hint-${i}`;
    note.textContent = hint;
    input.after(note);

    const update = () => {
        input.placeholder = mobileQuery.matches ? short : full;
        if (mobileQuery.matches)
            input.setAttribute("aria-describedby", note.id);
        else input.removeAttribute("aria-describedby");
    };

    update();
    mobileQuery.addEventListener("change", update);
});
