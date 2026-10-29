
document.addEventListener("DOMContentLoaded", function () {
    // Hiển thị ngày hiện tại
    const today = document.getElementById("today");

    if (today) {
        today.textContent = new Date().toLocaleDateString("vi-VN");
    }

    // Tìm kiếm lớp học phần
    const searchClass = document.getElementById("searchClass");
    const classTable = document.getElementById("classTable");

    if (searchClass && classTable) {
        searchClass.addEventListener("input", function () {
            const tuKhoa = this.value
                .toLocaleLowerCase("vi")
                .trim();

            classTable.querySelectorAll("tr").forEach(function (row) {
                row.hidden = !row.textContent
                    .toLocaleLowerCase("vi")
                    .includes(tuKhoa);
            });
        });
    }

    // Tìm kiếm sinh viên
    const searchStudent = document.getElementById("searchStudent");
    const studentTable = document.getElementById("studentTable");

    if (searchStudent && studentTable) {
        searchStudent.addEventListener("input", function () {
            const tuKhoa = this.value
                .toLocaleLowerCase("vi")
                .trim();

            studentTable.querySelectorAll("tr").forEach(function (row) {
                row.hidden = !row.textContent
                    .toLocaleLowerCase("vi")
                    .includes(tuKhoa);
            });
        });
    }

    // Xuất danh sách sinh viên ra PDF
    const btnExportPDF = document.getElementById("btnExportPDF");

    if (btnExportPDF) {
        btnExportPDF.addEventListener("click", function () {
            window.print();
        });
    }
});
