const revealTargets = document.querySelectorAll(".cb-reveal");

if ("IntersectionObserver" in window) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.style.setProperty("--delay", "80ms");
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.15 });

  revealTargets.forEach((target) => observer.observe(target));
}

const menuButtons = document.querySelectorAll(".collection-menu button");
const pieces = document.querySelectorAll(".collection-piece");

menuButtons.forEach((button) => {
  button.addEventListener("click", () => {
    menuButtons.forEach((item) => item.classList.remove("is-current"));
    pieces.forEach((piece) => piece.classList.remove("is-current"));
    button.classList.add("is-current");
    document.getElementById(button.dataset.target)?.classList.add("is-current");
  });
});
