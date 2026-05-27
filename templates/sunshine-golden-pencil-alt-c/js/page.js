const revealNodes = document.querySelectorAll(".cb-reveal");

if ("IntersectionObserver" in window) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.style.setProperty("--delay", "90ms");
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.16 });

  revealNodes.forEach((node) => observer.observe(node));
}
