/* this must be on the embedding website */
function ruigehond015_snuggle(event) {
    const iframes = document.querySelectorAll('iframe'),
        len = iframes.length;
    for (let i = 0; i < len; i++) {
        if (iframes[i].contentWindow === event.source) {
            iframes[i].style.height = `${event.data.height}px`;

            return;
        }
    }
}

if (document.readyState !== 'loading') {
    window.addEventListener('message', ruigehond015_snuggle, false)
} else {
    document.addEventListener('DOMContentLoaded', function () {
        window.addEventListener('message', ruigehond015_snuggle, false)
    });
}
