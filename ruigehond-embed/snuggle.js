/* this must be on the embedding website */
function ruigehond015_snuggle(event) {
    const iframes = document.querySelectorAll('iframe'),
        len = iframes.length;
    function fromTop(iframe) {
        // add element to beginning of body
        const center = window.innerWidth / 2,
            div = document.createElement('div'),
            style = div.style;
        let ontop;
        style.position = 'fixed';
        style.overflow = 'hidden';
        style.height = '3px';
        style.width = '100%';
        iframe.insertAdjacentElement('beforebegin',div);
        // gradually move it down until nothing is blocking it anymore
        for (let y = 1, max = window.innerHeight; y < max; y+=3) {
            style.top = `${y}px`;
            try { // elementFromPoint can error on mobile browsers
                ontop = document.elementFromPoint(center, y + 1);
                if (iframe === ontop || 'html' === ontop.tagName || div.contains(ontop)) {
                    div.remove();
                    return y;
                }
            } catch(e) {
                break;
            }
        }
        div.remove();
        return 0; // it’s broken
    }
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
                y += iframe.getBoundingClientRect().top + window.scrollY - fromTop(iframe);
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
