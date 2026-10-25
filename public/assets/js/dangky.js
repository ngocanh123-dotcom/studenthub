
const searchInput = document.getElementById("searchClass");

if (searchInput) {
    searchInput.addEventListener("input", function () {
        const keyword = this.value
            .trim()
            .toLocaleLowerCase("vi");

        document.querySelectorAll(".class-card").forEach(card => {
            const content = (card.dataset.search || "")
                .toLocaleLowerCase("vi");

            card.hidden = !content.includes(keyword);
        });
    });
}
