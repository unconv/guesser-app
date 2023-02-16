<?php
use ChatWTF\CategorizerChatWTF;
use ChatWTF\QuestionerChatWTF;
use ChatWTF\TopCategorizerChatWTF;

class Trainer {
    public function __construct(
        protected PDO $db,
        protected QuestionerChatWTF $questioner,
        protected CategorizerChatWTF $categorizer,
        protected TopCategorizerChatWTF $topcategorizer,
    ) {

    }

    public function train( $thing_name ) {
        $thing = Thing::by_name( $thing_name, $this->db );

        if( ! $thing ) {
            $thing = new Thing(
                id: null,
                db: $this->db,
                name: $thing_name,
            );
    
            $thing->save();
        }

        $questions = $this->create_questions( $thing_name );

        foreach( $questions as $question_text ) {
            echo " - Question: ".$question_text."\n";

            $question = Question::by_text( $question_text, $this->db );

            if( ! $question ) {
                $question = new Question(
                    id: null,
                    db: $this->db,
                    text: $question_text,
                );
    
                $question->save();
            }

            $answer = new Answer(
                id: null,
                db: $this->db,
                question_id: $question->id,
                thing_id: $thing->id,
            );

            $answer->save();
        }

        $categories = $this->create_categories( $thing_name );
        $parent = $this->select_top_category( $thing_name );

        foreach( $categories as $category_name ) {
            $category_parent = $this->select_top_category( $category_name );
            if( $category_parent !== $parent ) {
                continue;
            }

            echo " - Category: ".$category_name."\n";

            $category = Category::by_name( $category_name, $this->db );

            if( ! $category ) {
                $category = new Category(
                    id: null,
                    db: $this->db,
                    name: $category_name,
                    parent: $parent,
                );
    
                $category->save();
            }

            $thing_category = new Thing_Category(
                id: null,
                db: $this->db,
                category_id: $category->id,
                thing_id: $thing->id,
            );

            $thing_category->save();
        }
    }

    /**
     * @return string[] Questions
     */
    public function create_questions( $word ): array {
        $response = $this->questioner->ask( $word );
        $response = rtrim( trim( $response->answer ) );

        if( stripos( $response, "had an error" ) !== false ) {
            echo "Server error, trying again...\n";
            sleep( 5 );
            return $this->create_questions( $word );
        }
        
        $questions = $this->filter_list( $response );

        // don't allow thing name in question
        $questions = array_filter( $questions,
            fn( $question ) => stripos( $question, $word ) === false
        );

        return $questions;
    }

    /**
     * @return string[] Categories
     */
    public function create_categories( $word ): array {
        $response = $this->categorizer->ask( $word );
        $response = rtrim( trim( $response->answer ) );

        if( stripos( $response, "had an error" ) !== false ) {
            echo "Server error, trying again...\n";
            sleep( 5 );
            return $this->create_categories( $word );
        }
        
        $categories = $this->filter_list( $response );

        return $categories;
    }

    /**
     * Picks a top level category for given sub-category.
     * Creates top level category if it doesn't exist
     * 
     * @return int ID of top level category
     */
    public function select_top_category( string $category ): int {
        $response = $this->topcategorizer->ask( $category );
        $response = rtrim( trim( $response->answer ) );

        if( stripos( $response, "had an error" ) !== false ) {
            echo "Server error, trying again...\n";
            sleep( 5 );
            return $this->select_top_category( $category );
        }
        
        $categories = $this->filter_list( $response );

        $category_name = $categories[0];

        $category = Category::by_name( $category_name, $this->db );

        if( ! $category ) {
            $category = new Category(
                id: null,
                db: $this->db,
                name: $category_name,
            );

            $category->save();
        }

        echo " - TopCategory: ".$category->name."\n";

        return $category->id;
    }

    protected function filter_list( string $list ): array {
        $list = preg_split( "/(\n|;)/", $list );

        // trim
        $list = array_map(
            fn( $item ) => trim( trim( trim( rtrim( $item ) ), "-." ) ),
            $list
        );

        // remove empty items
        $list = array_filter(
            $list,
            fn( $item ) => ! empty( $item )
        );

        // remove numberings
        $list = array_map(
            fn( $item ) => trim( preg_replace( '/^[0-9]+/', '', $item ) ),
            $list
        );

        return $list;
    }
}
