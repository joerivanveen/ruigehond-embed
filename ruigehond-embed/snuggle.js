/* this must be on the embedding website */
function ruigehond015_snuggle(event) {
    const iframes = document.querySelectorAll('iframe'),
        len = iframes.length;
    for (let i = 0; i < len; i++) {
        if (iframes[i].contentWindow === event.source) {
            const iframe = iframes[i],
                data = event.data;
            if (data.hasOwnProperty('height')) {
                iframe.style.height = `${data.height}px`;
                iframe.contentWindow.postMessage({hostname: window.location.hostname}, '*');
            }
            if (data.hasOwnProperty('scrollTo')) {
                let y = (data.scrollTo.y || 0);
                let x = (data.scrollTo.x || 0);
                // todo: implement x / horizontal scroll
                y += iframe.getBoundingClientRect().top + window.scrollY;
                if ('scrollBehavior' in document.documentElement.style) {
                    window.scrollTo({
                        left: x,
                        top: y,
                        behavior: 'smooth'
                    });
                } else {
                    window.scrollTo(x, y);
                }
            }
            return;
        }
    }
}

window.addEventListener('message', ruigehond015_snuggle, false)
