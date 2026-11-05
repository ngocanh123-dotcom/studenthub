<?php

class AdminTaiKhoanModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function usernameExists(string $username, int $excludeId = 0): bool
    {
        $sql = 'SELECT COUNT(*) FROM nguoi_dung WHERE ten_dang_nhap = :username';
        $parameters = [':username' => $username];

        if ($excludeId > 0) {
            $sql .= ' AND id <> :id';
            $parameters[':id'] = $excludeId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $account, string $passwordHash): void
    {
        $statement = $this->pdo->prepare("
            INSERT INTO nguoi_dung
                (ten_dang_nhap, mat_khau_hash, ho_ten, email, so_dien_thoai, vai_tro)
            VALUES
                (:username, :password, :fullname, :email, :phone, :role)
        ");
        $statement->execute([
            ':username' => $account['ten_dang_nhap'],
            ':password' => $passwordHash,
            ':fullname' => $account['ho_ten'],
            ':email' => $account['email'] !== '' ? $account['email'] : null,
            ':phone' => $account['so_dien_thoai'] !== '' ? $account['so_dien_thoai'] : null,
            ':role' => $account['vai_tro'],
        ]);
    }

    public function update(int $id, array $account): void
    {
        $statement = $this->pdo->prepare("
            UPDATE nguoi_dung
            SET ten_dang_nhap = :username,
                ho_ten = :fullname,
                email = :email,
                so_dien_thoai = :phone,
                vai_tro = :role
            WHERE id = :id
        ");
        $statement->execute([
            ':username' => $account['ten_dang_nhap'],
            ':fullname' => $account['ho_ten'],
            ':email' => $account['email'] !== '' ? $account['email'] : null,
            ':phone' => $account['so_dien_thoai'] !== '' ? $account['so_dien_thoai'] : null,
            ':role' => $account['vai_tro'],
            ':id' => $id,
        ]);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare("
            SELECT id, ten_dang_nhap, ho_ten, email, so_dien_thoai, vai_tro
            FROM nguoi_dung
            WHERE id = :id
            LIMIT 1
        ");
        $statement->execute([':id' => $id]);
        $account = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($account) ? $account : null;
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM nguoi_dung WHERE id = :id');
        $statement->execute([':id' => $id]);
    }

    public function countAdmins(): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'admin'")
            ->fetchColumn();
    }

    public function getAccounts(string $keyword, string $role): array
    {
        $sql = "
            SELECT id, ten_dang_nhap, ho_ten, email, so_dien_thoai, vai_tro
            FROM nguoi_dung
            WHERE 1 = 1
        ";
        $parameters = [];

        if ($keyword !== '') {
            $sql .= ' AND (ten_dang_nhap LIKE :q1 OR ho_ten LIKE :q2 OR email LIKE :q3)';
            $search = '%' . $keyword . '%';
            $parameters[':q1'] = $search;
            $parameters[':q2'] = $search;
            $parameters[':q3'] = $search;
        }

        if (in_array($role, ['sinh_vien', 'giang_vien', 'admin'], true)) {
            $sql .= ' AND vai_tro = :role';
            $parameters[':role'] = $role;
        }

        $sql .= ' ORDER BY id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAccounts(): int
    {
        return (int) $this->pdo
            ->query('SELECT COUNT(*) FROM nguoi_dung')
            ->fetchColumn();
    }
}
