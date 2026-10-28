
document.addEventListener("DOMContentLoaded", function () {
    // Tự đánh dấu mục menu tương ứng với trang đang mở.
    const menuLinks = document.querySelectorAll(".menu-link");
    const currentPage = window.location.pathname.split("/").pop();

    menuLinks.forEach(function (link) {
        const href = link.getAttribute("href");

        if (!href || href.includes("logout.php")) {
            return;
        }

        const linkPage = href.split("/").pop();

        if (linkPage === currentPage) {
            link.classList.add("active");
        } else {
            link.classList.remove("active");
        }
    });

    // Xác nhận trước khi đăng xuất.
    const logoutLink = document.querySelector(".menu-link.logout");

    if (logoutLink) {
        logoutLink.addEventListener("click", function (event) {
            const confirmed = window.confirm(
                "Bạn có chắc chắn muốn đăng xuất khỏi StudentHub?"
            );

            if (!confirmed) {
                event.preventDefault();
            }
        });
    }
});
