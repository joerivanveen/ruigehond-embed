/**
 * send information to the embedding website to resize the iframe as to disappear
 */
function ruigehond015_unframe() {
    let dimensions_new, dimensions_old = {width: 0, height: 0};

    if (parent === self) return;

    function getHigh() {
        //https://stackoverflow.com/a/11864824
        return Math.max(document.body.scrollHeight, document.documentElement.scrollHeight)
    }

    function sendToParent() {
        dimensions_new = {
            'width': window.innerWidth,
            'height': getHigh()
        };

        if ((dimensions_new.width !== dimensions_old.width)
            || (dimensions_new.height !== dimensions_old.height)
        ) {
            window.parent.postMessage(dimensions_new, "*");
            dimensions_old = dimensions_new;
        }
    }

    if (window.MutationObserver) {
        //https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver
        const observer = new MutationObserver(sendToParent);
        const config = {
            attributes: true,
            attributeOldValue: false,
            characterData: true,
            characterDataOldValue: false,
            childList: true,
            subtree: true
        };

        observer.observe(document.body, config);
    } else { // if no mutation observer check for changes on a timed interval
        window.setInterval(sendToParent, 300);
    }

    sendToParent();
}

if (document.readyState !== 'loading') {
    ruigehond015_unframe();
} else {
    document.addEventListener('DOMContentLoaded', function () {
        ruigehond015_unframe();
    });
}
