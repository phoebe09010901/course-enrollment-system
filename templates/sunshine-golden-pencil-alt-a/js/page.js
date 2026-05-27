const revealItems = document.querySelectorAll(".cb-reveal");

if ("IntersectionObserver" in window) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.style.setProperty("--delay", `${Math.min(entry.target.dataset.index || 0, 6) * 70}ms`);
      entry.target.classList.add("is-visible");
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.16 });

  revealItems.forEach((item, index) => {
    item.dataset.index = index;
    observer.observe(item);
  });
}

const previewImage = document.querySelector("#atelierPreviewImage");
const previewCaption = document.querySelector("#atelierPreviewCaption");
const previewButtons = document.querySelectorAll(".preview-thumbs button");

previewButtons.forEach((button) => {
  button.addEventListener("click", () => {
    previewButtons.forEach((item) => item.classList.remove("is-active"));
    button.classList.add("is-active");
    previewImage.src = button.dataset.image;
    previewCaption.textContent = button.dataset.caption;
  });
});
