<?php
class Category {
    public function __construct(
        protected PDO $db,
        public ?int $id,
        public string $name,
        public int $parent = 0,
    ) {}

    public static function load( int $id, PDO $db ): static|null {
        $stmt = $db->prepare(
            "SELECT category_id,
                    category_name,
                    parent_category_id
            FROM    categories
            WHERE   category_id = :id
            LIMIT   1"
        );

        $stmt->execute( [
            ":id" => $id,
        ] );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );

        if( ! $row ) {
            return null;
        }

        $category = new Category(
            db: $db,
            id: $row['category_id'],
            name: $row['category_name'],
            parent: $row['parent_category_id'],
        );

        return $category;
    }

    public static function by_name( string $name, PDO $db ): static|null {
        $stmt = $db->prepare(
            "SELECT category_id,
                    category_name,
                    parent_category_id
            FROM    categories
            WHERE   category_name = :name
            LIMIT   1"
        );

        $stmt->execute( [
            ":name" => $name,
        ] );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );

        if( ! $row ) {
            return null;
        }

        $category = new Category(
            db: $db,
            id: $row['category_id'],
            name: $row['category_name'],
            parent: $row['parent_category_id'],
        );

        return $category;
    }

    public function fetchThings() {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT thing_id
            FROM    thing_categories
            WHERE   category_id IN (
                SELECT category_id
                FROM   categories
                WHERE  category_id = :id OR
                       parent_category_id = :id
            )"
        );

        $stmt->execute( [
            ":id" => $this->id
        ] );

        $rows = $stmt->fetchAll( PDO::FETCH_ASSOC );

        $things = [];

        foreach( $rows as $row ) {
            $things[] = $row['thing_id'];
        }

        return $things;
    }

    /**
     * Get other top level categories
     * 
     * @return Category[]
     */
    public function get_other_categories( Guess $guess ): array {
        $exclude = $guess->guessed_categories;

        $exclude_query = "";

        if( ! empty( $exclude ) ) {
            $exclude_query = " AND category_id NOT IN (".implode( ", ", $exclude ).")";
        }

        $stmt = $this->db->prepare(
            "SELECT *
            FROM    categories
            WHERE   parent_category_id = 0 AND
                    category_id != :id
                    ".$exclude_query
        );

        $stmt->execute( [
            ":id" => $this->id
        ] );

        $categories = [];

        while( $cat = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
            $categories[] = new Category(
                db: $this->db,
                id: $cat['category_id'],
                name: $cat['category_name'],
                parent: $cat['parent_category_id'],
            );
        }

        return $categories;
    }

    public static function fetchRandom( Guess $guess, PDO $db ): static|null {
        $exclude = $guess->guessed_categories;

        $exclude_query = "";
        $exec = [];

        if( ! empty( $exclude ) ) {
            $exclude_query = " WHERE categories.category_id NOT IN (".implode( ", ", $exclude ).")";
        }

        $parents = implode( ", ", $guess->categories );
        if( ! $parents ) {
            $parents = "0";
        }

        $stmt = $db->prepare(
            "SELECT categories.category_id AS category_id,
                    categories.category_name AS category_name,
                    categories.parent_category_id AS parent_category_id,
                    COUNT(*) AS thing_count
            FROM    categories
            LEFT JOIN thing_categories ON thing_categories.category_id = categories.category_id
            ".$exclude_query."
            GROUP BY categories.category_id
            HAVING (thing_count > 3 OR categories.parent_category_id = 0)
            ORDER BY categories.parent_category_id = 0 DESC,
                     categories.category_id IN (
                        SELECT category_id
                        FROM   categories
                        WHERE  parent_category_id IN (
                            ".$parents."
                        )
                     ) DESC,
                     RAND()
            LIMIT 1"
        );

        $stmt->execute( $exec );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );

        if( ! $row ) {
            return null;
        }

        $category = new Category(
            db: $db,
            id: $row['category_id'],
            name: $row['category_name'],
            parent: $row['parent_category_id'],
        );

        return $category;
    }

    public function save() {
        if( ! isset( $this->id ) ) {
            $stmt = $this->db->prepare(
                "INSERT INTO categories (
                    category_name,
                    parent_category_id
                ) VALUES (
                    :name,
                    :parent
                )"
            );
    
            $stmt->execute( [
                ":name" => $this->name,
                ":parent" => $this->parent,
            ] );
    
            $this->id = $this->db->lastInsertId();
        } else {
            $stmt = $this->db->prepare(
                "UPDATE categories
                SET     category_name = :name,
                        parent_category_id = :parent
                WHERE   category_id = :id
                LIMIT   1"
            );
    
            $stmt->execute( [
                ":name" => $this->name,
                ":parent" => $this->parent,
                ":id" => $this->id
            ] );
        }
    }
}
