<?php
// app/Controllers/HocPhanController.php

require_once __DIR__ . '/../Models/HocPhan.php';

class HocPhanController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new HocPhan($conn);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Kiểm tra quyền quản trị viên
        $vaiTro = strtolower(trim((string) ($_SESSION['role'] ?? '')));
        if (empty($_SESSION['username']) || !in_array($vaiTro, ['admin', 'quantri', 'quan_tri', 'quan_tri_vien'], true)) {
            header('Location: /studenthub/public/index.php');
            exit;
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public function index(): void
    {
        $thongBao = '';
        $loaiThongBao = 'success';
        $loi = '';

        $tuKhoa = trim((string) ($_GET['q'] ?? ''));
        $soDongMoiTrang = 8;
        $trangHienTai = max(1, (int) ($_GET['page'] ?? 1));

        $duLieuSua = [
            'id' => '',
            'ma_hoc_phan' => '',
            'ten_hoc_phan' => '',
            'so_tin_chi' => '',
            'loai_hoc_phan' => 'dai_cuong',
            'mo_ta' => ''
        ];

        // Lấy dữ liệu học phần cần sửa (GET ?sua=ID)
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sua'])) {
            $idSua = (int) $_GET['sua'];
            if ($idSua > 0) {
                $hocPhanSua = $this->model->getById($idSua);
                if ($hocPhanSua) {
                    $duLieuSua = $hocPhanSua;
                } else {
                    $thongBao = 'Không tìm thấy học phần cần sửa.';
                    $loaiThongBao = 'error';
                }
            }
        }

        // Xử lý POST (Thêm, Sửa, Xóa)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfNhan = $_POST['csrf_token'] ?? '';
            if (!is_string($csrfNhan) || !hash_equals($_SESSION['csrf_token'], $csrfNhan)) {
                $thongBao = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
                $loaiThongBao = 'error';
            } else {
                $hanhDong = trim((string) ($_POST['hanh_dong'] ?? ''));

                try {
                    if ($hanhDong === 'them' || $hanhDong === 'sua') {
                        $id = (int) ($_POST['id'] ?? 0);
                        $maHocPhan = strtoupper(trim((string) ($_POST['ma_hoc_phan'] ?? '')));
                        $tenHocPhan = trim((string) ($_POST['ten_hoc_phan'] ?? ''));
                        $soTinChiNhap = trim((string) ($_POST['so_tin_chi'] ?? ''));
                        $loaiHocPhanNhap = trim((string) ($_POST['loai_hoc_phan'] ?? 'dai_cuong'));

                        $loaiHocPhanMap = [
                            'Đại cương' => 'dai_cuong',
                            'Cơ sở ngành' => 'co_so_nganh',
                            'Chuyên ngành' => 'chuyen_nganh',
                            'dai_cuong' => 'dai_cuong',
                            'co_so_nganh' => 'co_so_nganh',
                            'chuyen_nganh' => 'chuyen_nganh'
                        ];

                        if (!isset($loaiHocPhanMap[$loaiHocPhanNhap])) {
                            throw new RuntimeException('Loại học phần không hợp lệ.');
                        }
                        $loaiHocPhan = $loaiHocPhanMap[$loaiHocPhanNhap];
                        $moTa = trim((string) ($_POST['mo_ta'] ?? ''));

                        $duLieuSua = [
                            'id' => $id,
                            'ma_hoc_phan' => $maHocPhan,
                            'ten_hoc_phan' => $tenHocPhan,
                            'so_tin_chi' => $soTinChiNhap,
                            'loai_hoc_phan' => $loaiHocPhan,
                            'mo_ta' => $moTa
                        ];

                        if (!preg_match('/^[A-Z0-9_-]{2,30}$/', $maHocPhan)) {
                            throw new RuntimeException('Mã học phần phải từ 2-30 ký tự, chỉ gồm chữ cái, số, gạch nối hoặc gạch dưới.');
                        }

                        if ($tenHocPhan === '' || mb_strlen($tenHocPhan, 'UTF-8') < 2 || mb_strlen($tenHocPhan, 'UTF-8') > 150) {
                            throw new RuntimeException('Tên học phần phải từ 2 đến 150 ký tự.');
                        }

                        if (!ctype_digit($soTinChiNhap)) {
                            throw new RuntimeException('Số tín chỉ phải là số nguyên dương.');
                        }

                        $soTinChi = (int) $soTinChiNhap;
                        if ($soTinChi < 1 || $soTinChi > 10) {
                            throw new RuntimeException('Số tín chỉ phải từ 1 đến 10.');
                        }

                        if ($moTa !== '' && mb_strlen($moTa, 'UTF-8') > 1000) {
                            throw new RuntimeException('Mô tả học phần không được vượt quá 1000 ký tự.');
                        }

                        $excludeId = ($hanhDong === 'sua') ? $id : 0;
                        if ($this->model->checkCodeExists($maHocPhan, $excludeId)) {
                            throw new RuntimeException("Mã học phần \"{$maHocPhan}\" đã tồn tại trong hệ thống.");
                        }

                        if ($hanhDong === 'them') {
                            $this->model->insert($maHocPhan, $tenHocPhan, $soTinChi, $loaiHocPhan, $moTa);
                            $_SESSION['course_notice'] = 'Thêm học phần thành công.';
                        } else {
                            if ($id <= 0) {
                                throw new RuntimeException('Học phần cần cập nhật không hợp lệ.');
                            }
                            $this->model->update($id, $maHocPhan, $tenHocPhan, $soTinChi, $loaiHocPhan, $moTa);
                            $_SESSION['course_notice'] = 'Cập nhật học phần thành công.';
                        }

                        $_SESSION['course_notice_type'] = 'success';
                        header('Location: /studenthub/public/index.php?route=admin_hoc_phan');
                        exit;
                    }

                    if ($hanhDong === 'xoa') {
                        $idXoa = (int) ($_POST['id'] ?? 0);
                        if ($idXoa <= 0) {
                            throw new RuntimeException('Mã học phần không hợp lệ.');
                        }

                        if ($this->model->countClassesUsing($idXoa) > 0) {
                            throw new RuntimeException('Không thể xóa học phần này vì đã có lớp học phần sử dụng. Hãy xử lý các lớp liên quan trước.');
                        }

                        $this->model->delete($idXoa);
                        $_SESSION['course_notice'] = 'Xóa học phần thành công.';
                        $_SESSION['course_notice_type'] = 'success';
                        header('Location: /studenthub/public/index.php?route=admin_hoc_phan');
                        exit;
                    }
                } catch (Exception $e) {
                    $thongBao = $e->getMessage();
                    $loaiThongBao = 'error';
                }
            }
        }

        // Nhận thông báo flash từ session sau khi redirect
        if (!empty($_SESSION['course_notice'])) {
            $thongBao = $_SESSION['course_notice'];
            $loaiThongBao = $_SESSION['course_notice_type'] ?? 'success';
            unset($_SESSION['course_notice'], $_SESSION['course_notice_type']);
        }

        // 1. Đếm tổng số môn học: SELECT COUNT(*) FROM hoc_phan
        $tongHocPhan = $this->model->countAll();

        // 2. Tính toán phân trang và lấy danh sách môn học
        $tongKetQua = $this->model->countFiltered($tuKhoa);
        $tongTrang = max(1, (int) ceil($tongKetQua / $soDongMoiTrang));
        $trangHienTai = min($trangHienTai, $tongTrang);
        $viTriBatDau = ($trangHienTai - 1) * $soDongMoiTrang;

        $danhSach = $this->model->getPaginated($tuKhoa, $soDongMoiTrang, $viTriBatDau);

        // Nạp view app/Views/admin/hoc_phan.php
        require __DIR__ . '/../Views/admin/hoc_phan.php';
    }
}
