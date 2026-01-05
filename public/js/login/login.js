document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.querySelector('.login-wrapper');
    if (!wrapper) return;

    const bubbleCount = 18;

    for (let i = 0; i < bubbleCount; i++) {
        const bubble = document.createElement('span');
        bubble.classList.add('login-bubble');

        const size = Math.random() * 80 + 20;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;

        bubble.style.left = `${Math.random() * 100}%`;
        bubble.style.animationDuration = `${20 + Math.random() * 20}s`;
        bubble.style.animationDelay = `${Math.random() * 10}s`;

        wrapper.appendChild(bubble);
    }
});
