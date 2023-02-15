<?php
class Thing {

    public function __construct(
        protected PDO $db,
        public ?int $id,
        public string $name,
    ) {}

    public function save() {
        $stmt = $this->db->prepare(
            "INSERT INTO things (
                thing_name
            ) VALUES (
                :thing_name
            )"
        );
        $stmt->execute( [
            ":thing_name" => $this->name
        ] );

        $this->id = $this->db->lastInsertId();
    }

    public static function load( int $id, PDO $db ): static|null {
        $stmt = $db->prepare(
            "SELECT * FROM things WHERE thing_id = :thing_id"
        );

        $stmt->execute( [
            ":thing_id" => $id
        ] );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );

        if( ! $row ) {
            return null;
        }

        $thing = new Thing(
            db: $db,
            id: $row['thing_id'],
            name: $row['thing_name'],
        );

        return $thing;
    }

    public static function by_name( string $name, PDO $db ): static|null {
        $stmt = $db->prepare(
            "SELECT * FROM things WHERE thing_name = :thing_name"
        );

        $stmt->execute( [
            ":thing_name" => $name
        ] );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );

        if( ! $row ) {
            return null;
        }

        $thing = new Thing(
            db: $db,
            id: $row['thing_id'],
            name: $row['thing_name'],
        );

        return $thing;
    }

    /**
     * @return int[] an array of question ids
     */
    public function question_ids(): array {
        $stmt = $this->db->prepare(
            "SELECT question_id
            FROM    answers
            WHERE   thing_id = :thing_id"
        );

        $stmt->execute( [
            ":thing_id" => $this->id,
        ] );

        return $stmt->fetchAll( PDO::FETCH_COLUMN );
    }
}
