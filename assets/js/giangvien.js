
const classTable = document.getElementById("classTable");
const studentTable = document.getElementById("studentTable");
const searchClass = document.getElementById("searchClass");
const searchStudent = document.getElementById("searchStudent");

let lopDangChon = null;

// Hiển thị ngày hiện tại
const today = document.getElementById("today");
if (today) {
    today.textContent = new Date().toLocaleDateString("vi-VN");
}

// Tìm kiếm lớp: lọc những dòng PHP đã hiển thị từ MySQL
if (searchClass && classTable) {
    searchClass.addEventListener("input", function () {
        const tuKhoa = this.value.toLocaleLowerCase("vi").trim();

        classTable.querySelectorAll("tr").forEach(row => {
            row.hidden = !row.textContent
                .toLocaleLowerCase("vi")
                .includes(tuKhoa);
        });
    });
}

// Tìm kiếm sinh viên trong lớp đang chọn
if (searchStudent && studentTable) {
    searchStudent.addEventListener("input", function () {
        const tuKhoa = this.value.toLocaleLowerCase("vi").trim();

        studentTable.querySelectorAll("tr").forEach(row => {
            row.hidden = !row.textContent
                .toLocaleLowerCase("vi")
                .includes(tuKhoa);
        });
    });
}

// Nút xem sinh viên được xử lý bằng API trong index.php.
// Không tạo dữ liệu lớp hoặc sinh viên mẫu tại file này.
document.querySelectorAll(".view-students").forEach(button => {
    button.addEventListener("click", () => {
        lopDangChon = button.dataset.class;
    });
});
