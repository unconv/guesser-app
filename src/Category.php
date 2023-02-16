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

    public function fetchThings( int $min = 0 ) {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT thing_id
            FROM    thing_categories
            WHERE   category_id IN (
                SELECT    thing_categories.category_id
                FROM      thing_categories
                LEFT JOIN categories ON thing_categories.category_id = categories.category_id
                WHERE     categories.category_id = :id OR
                          categories.parent_category_id = :id
                GROUP BY  categories.category_id
                HAVING    COUNT(*) >= :min
            )"
        );

        $stmt->execute( [
            ":id" => $this->id,
            ":min" => $min
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
                    category_id != :id AND
                    category_id != :parent_id
                    ".$exclude_query
        );

        $stmt->execute( [
            ":id" => $this->id,
            ":parent_id" => $this->parent,
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

        $exec[":best_category"] = $guess->best_category();

        $best_guesses = $guess->best_guesses();
        $thing_ids = array_map( fn( $thing ) => $thing->id, $best_guesses );
        $thing_ids = implode( ", ", $thing_ids );

        if( ! $thing_ids ) {
            $thing_ids = "0";
        }

        $stmt = $db->prepare(
            "SELECT categories.category_id AS category_id,
                    categories.category_name AS category_name,
                    categories.parent_category_id AS parent_category_id,
                    COUNT(*) AS thing_count,
                    categories.parent_category_id = 0 AS is_top_category,
                    categories.category_id = :best_category AS is_best_category,
                    categories.category_id IN (
                        SELECT thing_categories.category_id
                        FROM   thing_categories
                        WHERE  thing_id IN (
                            ".$thing_ids."
                        )
                    ) AS is_thing_category
            FROM    categories
            LEFT JOIN thing_categories ON thing_categories.category_id = categories.category_id
            ".$exclude_query."
            GROUP BY categories.category_id
            HAVING (thing_count > 3 OR categories.parent_category_id = 0)
            ORDER BY is_top_category DESC,
                     is_best_category DESC,
                     is_thing_category DESC,
                     RAND()
            LIMIT 1"
        );

        $stmt->execute( $exec );

        $row = $stmt->fetch( PDO::FETCH_ASSOC );

        if( ! $row ) {
            return null;
        }

        if( ! $row['is_top_category'] && ! $row['is_best_category'] && ! $row['is_thing_category'] ) {
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
