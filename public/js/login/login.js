document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(".bubbles");
    const bubbleCount = 12;

    for (let i = 0; i < bubbleCount; i++) {
        const bubble = document.createElement("div");
        bubble.classList.add("bubble");

        const size = Math.random() * 60 + 20;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;

        bubble.style.left = `${Math.random() * 100}%`;
        bubble.style.top = `${Math.random() * 100}%`;
        bubble.style.animationDuration = `${15 + Math.random() * 20}s`;

        container.appendChild(bubble);
    }

    document.addEventListener("mousemove", (e) => {
        document.querySelectorAll(".bubble").forEach(bubble => {
            const rect = bubble.getBoundingClientRect();
            const dx = e.clientX - (rect.left + rect.width / 2);
            const dy = e.clientY - (rect.top + rect.height / 2);
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < 120) {
                bubble.style.transform = `translate(${-dx * 0.15}px, ${-dy * 0.15}px)`;
            } else {
                bubble.style.transform = "";
            }
        });
    });
});
