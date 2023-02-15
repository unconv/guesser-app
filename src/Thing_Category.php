<?php
class Thing_Category {
    public function __construct(
        public PDO $db,
        public ?int $id,
        public int $category_id,
        public int $thing_id,
    ) {}
  
    public function save() {
        $stmt = $this->db->prepare(
            "INSERT INTO thing_categories (
                category_id,
                thing_id
            ) VALUES (
                :category_id,
                :thing_id
            )"
        );

        $stmt->execute( [
            ":category_id" => $this->category_id,
            ":thing_id" => $this->thing_id,
        ] );
      
        $this->id = $this->db->lastInsertId();
    }

    public static function load( int $id, PDO $db ) {
        $stmt = $db->prepare(
            "SELECT category_id,
                    thing_id,
            FROM    thing_categories
            WHERE   thing_id = :id"
        );

        $stmt->execute( [
            ":id" => $id
        ] );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );
      
        $thing_category = new Thing_Category(
            id: $id,
            db: $db,
            category_id: $row['category_id'],
            thing_id: $row['thing_id'],
        );

        return $thing_category;
    }
}
