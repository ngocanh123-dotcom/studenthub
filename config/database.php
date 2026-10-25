
<?php

class Database
{
    private string $host = 'localhost';
    private string $db_name = 'studenthub';
    private string $username = 'root';
    private string $password = '';

    public function connect(): PDO
    {
        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            return $pdo;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new RuntimeException(
                'Không thể kết nối cơ sở dữ liệu. Hãy kiểm tra cấu hình MySQL.'
            );
        }
    }
}
