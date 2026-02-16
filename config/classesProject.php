<?php
class Project {

    private $conn;
    private $table = "projects";

    public $id;
    public $name;
    public $description;
    public $visibility;
    public $owner_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Получить все проекты
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Получить один проект по ID
    public function getById() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->visibility = $row['visibility'];
            $this->owner_id = $row['owner_id'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // Создать проект
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  SET name = :name,
                      description = :description,
                      visibility = :visibility,
                      owner_id = :owner_id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":visibility", $this->visibility);
        $stmt->bindParam(":owner_id", $this->owner_id);

        return $stmt->execute();
    }

    // Обновить проект
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET name = :name,
                      description = :description,
                      visibility = :visibility
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":visibility", $this->visibility);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Удалить проект
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
}
