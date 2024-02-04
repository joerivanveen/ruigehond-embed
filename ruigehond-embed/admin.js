/**
 * adds copy to clipboard buttons to the links
 */
function ruigehond015() {
    if (!navigator.clipboard || !window.isSecureContext) {
        return;
    }

    function getLink(parts) {
        let i, part;
        const len = parts.length;
        for (i = 0; i < len; ++i) {
            part = parts[i];
            if ('http://' === part.slice(0, 7)) {
                return part;
            }
            if ('https://' === part.slice(0, 8)) {
                return part;
            }
        }
    }

    const form = document.getElementById('ruigehond015-settings-form');
    if (!form) return;
    const explanations = form.querySelectorAll('.ruigehond015.explanation.title');
    const len = explanations.length - 1; // forget about the last one (new title)
    let i, explanation, parts, link;
    for (i = 0; i < len; ++i) {
        explanation = explanations[i];
        parts = explanation.innerText.split(' ');
        if ((link = getLink(parts))) {
            const copy = document.createElement('span');
            copy.classList.add('copy-to-clipboard');
            copy.innerHTML = '→📋';
            copy.style.whiteSpace = 'nowrap';
            copy.setAttribute('data-link', link);
            copy.onclick = async function (e) {
                e.preventDefault();
                e.stopPropagation();
                const link = this.getAttribute('data-link');
                const butt = this;
                await navigator.clipboard.writeText(link).then(function () {
                    butt.classList.add('success');
                    setTimeout(function () {
                        butt.classList.remove('success');
                    }, 3000);
                }).catch(function () {
                    butt.classList.add('fail');
                });
            }
            explanation.appendChild(copy);
        }
    }
}

if (document.readyState !== 'loading') {
    ruigehond015();
} else {
    document.addEventListener('DOMContentLoaded', function () {
        ruigehond015();
    });
}
