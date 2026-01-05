document.addEventListener('turbo:load', () => {
    const wrapper = document.querySelector('.change-password-wrapper');
    if (!wrapper) return;

    // 🔥 Evitar duplicar burbujas si Turbo recarga la vista
    wrapper.querySelectorAll('.change-password-bubble').forEach(b => b.remove());

    const bubbleCount = 18;

    for (let i = 0; i < bubbleCount; i++) {
        const bubble = document.createElement('span');
        bubble.classList.add('change-password-bubble');

        const size = Math.random() * 80 + 20;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;

        bubble.style.left = `${Math.random() * 100}%`;
        bubble.style.animationDuration = `${20 + Math.random() * 20}s`;
        bubble.style.animationDelay = `${Math.random() * 10}s`;

        wrapper.appendChild(bubble);
    }
});
