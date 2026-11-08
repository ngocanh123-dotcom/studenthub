<?php
// app/Models/HocPhan.php

class HocPhan
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Lấy toàn bộ danh sách môn học
     * SELECT * FROM hoc_phan ORDER BY id ASC
     */
    public function getAll(string $keyword = ''): array
    {
        if ($keyword !== '') {
            $sql = "SELECT * FROM hoc_phan 
                    WHERE ma_hoc_phan LIKE :q1 OR ten_hoc_phan LIKE :q2 
                    ORDER BY id ASC";
            $stmt = $this->conn->prepare($sql);
            $tim = '%' . $keyword . '%';
            $stmt->execute([':q1' => $tim, ':q2' => $tim]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->conn->query("SELECT * FROM hoc_phan ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm tổng số môn học
     * SELECT COUNT(*) FROM hoc_phan
     */
    public function countAll(): int
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM hoc_phan");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Đếm số môn học theo từ khóa tìm kiếm
     */
    public function countFiltered(string $keyword = ''): int
    {
        if ($keyword === '') {
            return $this->countAll();
        }

        $sql = "SELECT COUNT(*) FROM hoc_phan 
                WHERE ma_hoc_phan LIKE :q1 OR ten_hoc_phan LIKE :q2";
        $stmt = $this->conn->prepare($sql);
        $tim = '%' . $keyword . '%';
        $stmt->execute([':q1' => $tim, ':q2' => $tim]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Lấy danh sách học phần phân trang
     */
    public function getPaginated(string $keyword = '', int $limit = 8, int $offset = 0): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        if ($keyword !== '') {
            $sql = "SELECT * FROM hoc_phan 
                    WHERE ma_hoc_phan LIKE :q1 OR ten_hoc_phan LIKE :q2 
                    ORDER BY id ASC 
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $tim = '%' . $keyword . '%';
            $stmt->bindValue(':q1', $tim, PDO::PARAM_STR);
            $stmt->bindValue(':q2', $tim, PDO::PARAM_STR);
        } else {
            $sql = "SELECT * FROM hoc_phan 
                    ORDER BY id ASC 
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết học phần theo ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM hoc_phan WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Kiểm tra mã học phần đã tồn tại chưa
     */
    public function checkCodeExists(string $maHocPhan, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM hoc_phan WHERE ma_hoc_phan = :ma";
        $params = [':ma' => $maHocPhan];

        if ($excludeId > 0) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    /**
     * Thêm học phần mới
     */
    public function insert(string $maHocPhan, string $tenHocPhan, int $soTinChi, string $loaiHocPhan, string $moTa = ''): bool
    {
        $sql = "INSERT INTO hoc_phan (ma_hoc_phan, ten_hoc_phan, so_tin_chi, loai_hoc_phan, mo_ta) 
                VALUES (:ma, :ten, :tin_chi, :loai, :mo_ta)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':ma' => $maHocPhan,
            ':ten' => $tenHocPhan,
            ':tin_chi' => $soTinChi,
            ':loai' => $loaiHocPhan,
            ':mo_ta' => $moTa !== '' ? $moTa : null,
        ]);
    }

    /**
     * Cập nhật học phần
     */
    public function update(int $id, string $maHocPhan, string $tenHocPhan, int $soTinChi, string $loaiHocPhan, string $moTa = ''): bool
    {
        $sql = "UPDATE hoc_phan 
                SET ma_hoc_phan = :ma, ten_hoc_phan = :ten, so_tin_chi = :tin_chi, 
                    loai_hoc_phan = :loai, mo_ta = :mo_ta 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':ma' => $maHocPhan,
            ':ten' => $tenHocPhan,
            ':tin_chi' => $soTinChi,
            ':loai' => $loaiHocPhan,
            ':mo_ta' => $moTa !== '' ? $moTa : null,
        ]);
    }

    /**
     * Đếm số lớp học phần đang sử dụng môn học này
     */
    public function countClassesUsing(int $id): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM lop_hoc_phan WHERE hoc_phan_id = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Xóa học phần
     */
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM hoc_phan WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

if (!class_exists('HocPhanModel')) {
    class_alias('HocPhan', 'HocPhanModel');
}
