/* this must be on the embedding website */
function ruigehond015_snuggle(event) {
    const iframes = document.querySelectorAll('iframe'),
        len = iframes.length;
    for (let i = 0; i < len; i++) {
        if (iframes[i].contentWindow === event.source) {
            const iframe = iframes[i];
            iframe.style.height = `${event.data.height}px`;
            iframe.contentWindow.postMessage({hostname: window.location.hostname}, '*');

            return;
        }
    }
}

window.addEventListener('message', ruigehond015_snuggle, false)
