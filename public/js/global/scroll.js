function findScrollableParent(element) {
    let current = element?.parentElement;

    while (current) {
        const style = window.getComputedStyle(current);
        const overflowY = style.overflowY;

        const isScrollable =
            (overflowY === "auto" || overflowY === "scroll") &&
            current.scrollHeight > current.clientHeight;

        if (isScrollable) {
            return current;
        }

        current = current.parentElement;
    }

    return null;
}

function resetContentFrameScroll() {
    const frame = document.getElementById("content");
    if (!frame) return;

    requestAnimationFrame(() => {
        const scrollParent = findScrollableParent(frame);

        if (scrollParent) {
            scrollParent.scrollTop = 0;
        }

        frame.scrollTop = 0;
        window.scrollTo(0, 0);
    });
}

document.addEventListener("turbo:frame-load", (event) => {
    const frame = event.target;
    if (!frame || frame.id !== "content") return;

    resetContentFrameScroll();
});

document.addEventListener("turbo:load", () => {
    resetContentFrameScroll();
});
