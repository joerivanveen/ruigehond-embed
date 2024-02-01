/**
 * adds copy to clipboard buttons to the links
 */
function ruigehond015() {
    if (!navigator.clipboard || !window.isSecureContext) {
        return;
    }

    async function copyToClipboard(str) {
        if (navigator.clipboard) {
            console.warn('navigator ok');
            await navigator.clipboard.writeText(str).then(function () {
                return true;
            }).catch(function () {
                return false;
            });
        } else {
            return false;
        }
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
    const len = explanations.length;
    let i, explanation, parts, link;
    for (i = 0; i < len; ++i) {
        explanation = explanations[i];
        parts = explanation.innerText.split(' ');
        if ((link = getLink(parts))) {
            const copy = document.createElement('span');
            copy.classList.add('copy-to-clipboard');
            copy.innerHTML = 'copy';
            copy.onclick = function (e) {
                e.preventDefault();
                e.stopPropagation();
                copyToClipboard(link).then(function (success) {
                    if (success) {
                        copy.classList.add('success');
                        setTimeout(function () {
                            copy.classList.remove('success');
                        }, 3000);
                    } else {
                        copy.classList.add('fail');
                    }
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
